<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

class Register extends BaseController
{
    private const ROLE_SLUGS = [
        UserModel::ROLE_PACIENTE,
        UserModel::ROLE_MEDICO,
    ];

    private UserModel $userModel;

    /**
     * @var array<string, array{nombre: string, id: int}>
     */
    private array $rolesCache = [];

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        if ($redirect = $this->redirigirSiAutenticado()) {
            return $redirect;
        }

        $roles = $this->rolesDisponibles();
        if ($roles === []) {
            throw new PageNotFoundException('No hay roles habilitados para registrarse.');
        }

        return view('auth/register', [
            'title'       => 'Crear cuenta',
            'roleOptions' => $roles,
            'errors'      => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function store()
    {
        if ($redirect = $this->redirigirSiAutenticado()) {
            return $redirect;
        }

        $roles = $this->rolesDisponibles();
        if ($roles === []) {
            throw new PageNotFoundException('No hay roles habilitados para registrarse.');
        }

        $roleList = implode(',', array_keys($roles));

        $rules = [
            'nombre'    => 'required|min_length[2]|max_length[120]',
            'apellido'  => 'required|min_length[2]|max_length[120]',
            'dni'       => 'required|min_length[6]|max_length[20]|is_unique[users.dni]',
            'email'     => 'required|valid_email|max_length[180]|is_unique[users.email]',
            'telefono'  => 'permit_empty|max_length[50]',
            'fecha_nac' => 'permit_empty|valid_date[Y-m-d]|before_today',
            'rol'       => 'required|in_list[' . $roleList . ']',
            'password'  => 'required|min_length[8]|max_length[64]|regex_match[/^(?=.*[A-Za-z])(?=.*\\d)(?=.*[^A-Za-z0-9]).+$/]',
        ];

        $messages = [
            'dni' => [
                'is_unique' => 'El DNI ya está registrado por otro usuario.',
            ],
            'email' => [
                'is_unique' => 'El email ya está registrado por otro usuario.',
            ],
            'rol' => [
                'in_list' => 'El rol seleccionado no es válido.',
            ],
            'fecha_nac' => [
                'valid_date' => 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.',
                'before_today' => 'La fecha de nacimiento debe ser anterior a hoy.',
            ],
            'password' => [
                'regex_match' => 'La contraseña debe tener al menos 8 caracteres, una letra, un número y un símbolo.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $rol = strtolower((string) $this->request->getPost('rol'));
        if (! isset($roles[$rol])) {
            return redirect()->back()->withInput()->with('errors', [
                'rol' => 'El rol seleccionado no está disponible.',
            ]);
        }

        $payload = [
            'nombre'        => trim((string) $this->request->getPost('nombre')),
            'apellido'      => trim((string) $this->request->getPost('apellido')),
            'dni'           => trim((string) $this->request->getPost('dni')),
            'email'         => trim((string) $this->request->getPost('email')),
            'telefono'      => $this->normalizarTelefono($this->request->getPost('telefono')),
            'fecha_nac'     => $this->normalizarFecha($this->request->getPost('fecha_nac')),
            'password_hash' => password_hash((string) $this->request->getPost('password'), PASSWORD_BCRYPT),
            'role_id'       => $roles[$rol]['id'],
            'activo'        => 1,
        ];

        try {
            $this->userModel->insert($payload);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al registrar usuario: {exception}', ['exception' => $exception]);

            return redirect()->back()->withInput()->with('errors', [
                'general' => 'No se pudo crear tu cuenta. Intentalo nuevamente.',
            ]);
        }

        session()->setFlashdata('register_success', 'Tu cuenta fue creada correctamente. Ahora podés iniciar sesión.');

        return redirect()->route('auth_login');
    }

    private function redirigirSiAutenticado(): ?RedirectResponse
    {
        $session = session();
        if ($session->has('user_id') && $session->has('rol')) {
            return redirect()->to($this->rutaPorRol((string) $session->get('rol')));
        }

        return null;
    }

    private function rutaPorRol(string $rol): string
    {
        return match ($rol) {
            UserModel::ROLE_ADMIN    => site_url('admin/home'),
            UserModel::ROLE_MEDICO   => site_url('medico/home'),
            UserModel::ROLE_PACIENTE => site_url('paciente/home'),
            default                  => base_url('/'),
        };
    }

    /**
     * @return array<string, array{nombre: string, id: int}>
     */
    private function rolesDisponibles(): array
    {
        if ($this->rolesCache !== []) {
            return $this->rolesCache;
        }

        $builder = db_connect()->table('roles');
        $resultado = $builder
            ->select(['id', 'nombre', 'slug'])
            ->whereIn('slug', self::ROLE_SLUGS)
            ->orderBy('nombre', 'ASC')
            ->get()
            ->getResultArray();

        $roles = [];
        foreach ($resultado as $rol) {
            $slug = strtolower((string) ($rol['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $roles[$slug] = [
                'id'     => (int) ($rol['id'] ?? 0),
                'nombre' => (string) ($rol['nombre'] ?? ucfirst($slug)),
            ];
        }

        $this->rolesCache = $roles;

        return $roles;
    }

    private function normalizarTelefono(mixed $valor): ?string
    {
        $telefono = trim((string) ($valor ?? ''));

        return $telefono === '' ? null : $telefono;
    }

    private function normalizarFecha(mixed $valor): ?string
    {
        $fecha = trim((string) ($valor ?? ''));

        return $fecha === '' ? null : $fecha;
    }
}
