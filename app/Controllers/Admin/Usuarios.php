<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Usuarios extends BaseController
{
    private const PAGINATION_GROUP = 'admin_usuarios';
    private const PER_PAGE = 10;
    private const ROLE_FILTER_DEFAULT = 'todos';
    private const ROLE_FILTERS = [
        'todos'    => 'Todos',
        'paciente' => 'Pacientes',
        'medico'   => 'Médicos',
        'admin'    => 'Administradores',
    ];
    private const ROLE_FORM_SLUGS = [
        UserModel::ROLE_PACIENTE,
        UserModel::ROLE_MEDICO,
        UserModel::ROLE_ADMIN,
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
        $roleFiltro = $this->normalizarRole($this->request->getGet('role'));
        $busqueda = trim((string) $this->request->getGet('q'));
        $mostrarInactivos = $this->debeMostrarInactivos($this->request->getGet('incluir_inactivos'));

        $usuarios = $this->userModel->paginateUsuarios(
            $busqueda,
            $roleFiltro === self::ROLE_FILTER_DEFAULT ? null : $roleFiltro,
            ! $mostrarInactivos,
            self::PER_PAGE,
            self::PAGINATION_GROUP
        );

        $viewData = $this->layoutData() + [
            'title'            => 'Usuarios del sistema',
            'usuarios'         => $usuarios,
            'pager'            => $this->userModel->pager,
            'pagerGroup'       => self::PAGINATION_GROUP,
            'busqueda'         => $busqueda,
            'roleFiltro'       => $roleFiltro,
            'roleOptions'      => self::ROLE_FILTERS,
            'mostrarInactivos' => $mostrarInactivos,
        ];

        return view('admin/usuarios/index', $viewData);
    }

    public function create()
    {
        $roleOptions = $this->roleSelectOptions();

        if ($roleOptions === []) {
            throw new PageNotFoundException('No hay roles configurados para crear usuarios.');
        }

        $viewData = $this->layoutData() + [
            'title'       => 'Nuevo usuario',
            'roleOptions' => $roleOptions,
            'errors'      => session()->getFlashdata('errors') ?? [],
        ];

        return view('admin/usuarios/create', $viewData);
    }

    public function store()
    {
        $rolesDisponibles = $this->rolesPorSlug();

        if ($rolesDisponibles === []) {
            throw new PageNotFoundException('No hay roles configurados para crear usuarios.');
        }

        $roleList = implode(',', array_keys($rolesDisponibles));

        $rules = [
            'nombre'    => 'required|min_length[2]|max_length[120]',
            'apellido'  => 'required|min_length[2]|max_length[120]',
            'dni'       => 'required|min_length[6]|max_length[20]|is_unique[users.dni]',
            'email'     => 'required|valid_email|max_length[180]|is_unique[users.email]',
            'telefono'  => 'permit_empty|max_length[50]',
            'fecha_nac' => 'permit_empty|valid_date[Y-m-d]',
            'rol'       => 'required|in_list[' . $roleList . ']',
            'password'  => 'required|min_length[8]|max_length[64]',
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
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $rolSlug = strtolower((string) $this->request->getPost('rol'));

        if (! isset($rolesDisponibles[$rolSlug])) {
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
            'role_id'       => $rolesDisponibles[$rolSlug]['id'],
            'activo'        => 1,
        ];

        try {
            $this->userModel->insert($payload);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al crear usuario: {exception}', ['exception' => $exception]);

            return redirect()->back()->withInput()->with('errors', [
                'general' => 'No se pudo crear el usuario. Inténtalo nuevamente.',
            ]);
        }

        session()->setFlashdata('success', 'Usuario creado correctamente.');

        return redirect()->route('admin_usuarios_index');
    }

    private function normalizarRole(mixed $valor): string
    {
        $valorNormalizado = strtolower(trim((string) $valor));

        if ($valorNormalizado === '') {
            return self::ROLE_FILTER_DEFAULT;
        }

        return array_key_exists($valorNormalizado, self::ROLE_FILTERS)
            ? $valorNormalizado
            : self::ROLE_FILTER_DEFAULT;
    }

    private function debeMostrarInactivos(mixed $valor): bool
    {
        if ($valor === null) {
            return false;
        }

        $valor = strtolower((string) $valor);

        return in_array($valor, ['1', 'true', 'on', 'si'], true);
    }

    /**
     * @return array<string, string>
     */
    private function roleSelectOptions(): array
    {
        $roles = $this->rolesPorSlug();

        $options = [];
        foreach (self::ROLE_FORM_SLUGS as $slug) {
            if (isset($roles[$slug])) {
                $options[$slug] = $roles[$slug]['nombre'];
            }
        }

        foreach ($roles as $slug => $rol) {
            if (! isset($options[$slug])) {
                $options[$slug] = $rol['nombre'];
            }
        }

        return $options;
    }

    /**
     * @return array<string, array{nombre: string, id: int}>
     */
    private function rolesPorSlug(): array
    {
        if ($this->rolesCache !== []) {
            return $this->rolesCache;
        }

        $builder = db_connect()->table('roles');
        $resultado = $builder
            ->select(['id', 'nombre', 'slug'])
            ->whereIn('slug', self::ROLE_FORM_SLUGS)
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

    private function normalizarFecha(mixed $valor): ?string
    {
        $fecha = trim((string) ($valor ?? ''));

        return $fecha === '' ? null : $fecha;
    }

    private function normalizarTelefono(mixed $valor): ?string
    {
        $telefono = trim((string) ($valor ?? ''));

        return $telefono === '' ? null : $telefono;
    }
}
