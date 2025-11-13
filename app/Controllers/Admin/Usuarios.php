<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\UserModel;
use App\Services\PerfilUsuarioService;
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

    private PerfilUsuarioService $perfilService;

    /**
     * @var array<string, array{nombre: string, id: int}>
     */
    private array $rolesCache = [];

    public function __construct()
    {
        $this->userModel     = new UserModel();
        $this->perfilService = new PerfilUsuarioService($this->userModel);
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

    public function edit(int $usuarioId)
    {
        $usuario = $this->obtenerUsuarioConRol($usuarioId);

        $nombreCompleto = trim((string) ($usuario->nombre ?? '') . ' ' . ((string) ($usuario->apellido ?? '')));
        $tituloPerfil   = $nombreCompleto !== '' ? 'Perfil de ' . $nombreCompleto : 'Perfil del usuario';
        $rolNombre      = (string) ($usuario->rol_nombre ?? ucfirst((string) ($usuario->rol_slug ?? 'Usuario')));
        $estaActivo     = (bool) ($usuario->activo ?? false);

        $viewData = $this->layoutData() + [
            'title'               => 'Gestión de usuario',
            'usuario'             => $usuario,
            'rolLabel'            => $rolNombre . ' · ' . ($estaActivo ? 'Activo' : 'Inactivo'),
            'perfilTitulo'        => $tituloPerfil,
            'perfilDescripcion'   => 'Actualizá los datos personales o gestioná el acceso de este usuario.',
            'volverUrl'           => route_to('admin_usuarios_index'),
            'formRoutes'          => [
                'datos'    => route_to('admin_usuarios_update', $usuarioId),
                'password' => '#',
            ],
            'mostrarPasswordForm' => false,
            'adminActions'        => [
                'enabled'             => true,
                'estadoActual'        => $estaActivo,
                'estadoRoute'         => route_to('admin_usuarios_toggle_estado', $usuarioId),
                'resetPasswordRoute'  => route_to('admin_usuarios_reset_password', $usuarioId),
                'resetPasswordConfirm'=> '¿Deseás generar una nueva contraseña temporal para este usuario?',
            ],
            'errorsDatos'         => session()->getFlashdata('errors_datos') ?? [],
            'errorsPassword'      => [],
        ];

        return view('paciente/perfil/index', $viewData);
    }

    public function update(int $usuarioId)
    {
        $usuario = $this->obtenerUsuarioConRol($usuarioId);
        $usuarioId = (int) $usuario->id;

        $rules = [
            'nombre'   => 'required|min_length[2]|max_length[120]',
            'apellido' => 'required|min_length[2]|max_length[120]',
            'dni'      => 'required|min_length[6]|max_length[20]|is_unique[users.dni,id,' . $usuarioId . ']',
            'email'    => 'required|valid_email|max_length[180]|is_unique[users.email,id,' . $usuarioId . ']',
            'telefono' => 'permit_empty|max_length[50]',
            'fecha_nac'=> 'permit_empty|valid_date[Y-m-d]',
        ];

        $messages = [
            'dni' => [
                'is_unique' => 'El DNI ya está registrado por otro usuario.',
            ],
            'email' => [
                'is_unique' => 'El email ya está registrado por otro usuario.',
            ],
            'fecha_nac' => [
                'valid_date' => 'La fecha de nacimiento debe tener el formato YYYY-MM-DD.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors_datos', $this->validator->getErrors());
        }

        $payload = [
            'nombre'    => trim((string) $this->request->getPost('nombre')),
            'apellido'  => trim((string) $this->request->getPost('apellido')),
            'dni'       => trim((string) $this->request->getPost('dni')),
            'email'     => trim((string) $this->request->getPost('email')),
            'telefono'  => $this->normalizarTelefono($this->request->getPost('telefono')),
            'fecha_nac' => $this->normalizarFecha($this->request->getPost('fecha_nac')),
        ];

        try {
            $this->perfilService->actualizarDatos($usuarioId, $payload);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al actualizar usuario desde admin: {exception}', ['exception' => $exception]);

            return redirect()->back()->withInput()->with('errors_datos', [
                'general' => 'No se pudieron guardar los cambios. Inténtalo nuevamente.',
            ]);
        }

        session()->setFlashdata('success', 'Datos del usuario actualizados correctamente.');

        return redirect()->route('admin_usuarios_edit', [$usuarioId]);
    }

    public function resetPassword(int $usuarioId)
    {
        $usuario = $this->obtenerUsuarioConRol($usuarioId);
        $usuarioId = (int) $usuario->id;

        $passwordTemporal = $this->generarPasswordTemporal();

        try {
            $this->perfilService->actualizarPassword($usuarioId, $passwordTemporal);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al resetear contraseña de usuario {id}: {exception}', [
                'id'         => $usuarioId,
                'exception'  => $exception,
            ]);

            session()->setFlashdata('error', 'No se pudo resetear la contraseña. Inténtalo nuevamente.');

            return redirect()->route('admin_usuarios_edit', [$usuarioId]);
        }

        session()->setFlashdata('success', 'Contraseña reseteada correctamente.');
        session()->setFlashdata('info', 'Nueva contraseña temporal: ' . $passwordTemporal);

        return redirect()->route('admin_usuarios_edit', [$usuarioId]);
    }

    public function cambiarEstado(int $usuarioId)
    {
        $usuario = $this->obtenerUsuarioConRol($usuarioId);

        $accion = strtolower(trim((string) $this->request->getPost('accion')));
        if (! in_array($accion, ['desactivar', 'reactivar'], true)) {
            session()->setFlashdata('error', 'Acción no válida.');

            return redirect()->route('admin_usuarios_edit', [$usuarioId]);
        }

        $estaActivo = (bool) ($usuario->activo ?? false);

        if ($accion === 'desactivar' && ! $estaActivo) {
            session()->setFlashdata('info', 'El usuario ya se encuentra inactivo.');

            return redirect()->route('admin_usuarios_edit', [$usuarioId]);
        }

        if ($accion === 'reactivar' && $estaActivo) {
            session()->setFlashdata('info', 'El usuario ya está activo.');

            return redirect()->route('admin_usuarios_edit', [$usuarioId]);
        }

        $nuevoEstado = $accion === 'reactivar' ? 1 : 0;
        $mensaje     = $accion === 'reactivar'
            ? 'Usuario reactivado correctamente.'
            : 'Usuario desactivado correctamente.';

        try {
            $this->userModel->update((int) $usuario->id, ['activo' => $nuevoEstado]);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al cambiar estado del usuario {id}: {exception}', [
                'id'        => (int) $usuario->id,
                'exception' => $exception,
            ]);

            session()->setFlashdata('error', 'No se pudo actualizar el estado del usuario.');

            return redirect()->route('admin_usuarios_edit', [$usuarioId]);
        }

        session()->setFlashdata('success', $mensaje);

        return redirect()->route('admin_usuarios_edit', [$usuarioId]);
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

    private function obtenerUsuarioConRol(int $usuarioId): User
    {
        $usuario = $this->userModel
            ->select('users.*, roles.slug AS rol_slug, roles.nombre AS rol_nombre')
            ->join('roles', 'roles.id = users.role_id', 'inner')
            ->where('users.id', $usuarioId)
            ->first();

        if (! $usuario instanceof User) {
            throw new PageNotFoundException('El usuario solicitado no existe.');
        }

        return $usuario;
    }

    private function generarPasswordTemporal(int $length = 12): string
    {
        $raw = bin2hex(random_bytes(max(4, $length)));

        return substr($raw, 0, $length);
    }
}
