<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TipoDiagnosticoModel;
use Throwable;

class TiposDiagnostico extends BaseController
{
    private const PER_PAGE = 10;
    private const PAGINATION_GROUP = 'admin_tipos_diagnostico';
    private const SLUG_MAX_LENGTH = 120;

    private TipoDiagnosticoModel $tipoModel;

    public function __construct()
    {
        $this->tipoModel = new TipoDiagnosticoModel();
    }

    public function index()
    {
        $busqueda = trim((string) $this->request->getGet('q'));

        $tipos = $this->tipoModel->paginateConUso(
            $busqueda,
            self::PER_PAGE,
            self::PAGINATION_GROUP
        );

        $session    = session();
        $formErrors = $session->getFlashdata('tipo_errors') ?? [];
        $formMode   = (string) ($session->getFlashdata('tipo_form_mode') ?? '');
        $formEditId = $session->getFlashdata('tipo_edit_id');

        return view('admin/tipos_diagnostico/index', $this->layoutData() + [
            'title'      => 'Tipos de diagnóstico',
            'tipos'      => $tipos,
            'busqueda'   => $busqueda,
            'pager'      => $this->tipoModel->pager,
            'pagerGroup' => self::PAGINATION_GROUP,
            'formErrors' => $formErrors,
            'formMode'   => $formMode,
            'formEditId' => $formEditId,
        ]);
    }

    public function store()
    {
        $rules = [
            'nombre'      => 'required|min_length[2]|max_length[120]|is_unique[tipos_diagnostico.nombre]',
            'descripcion' => 'permit_empty|max_length[500]',
        ];

        $messages = [
            'nombre' => [
                'required'  => 'El nombre es obligatorio.',
                'is_unique' => 'Ya existe un tipo de diagnóstico con ese nombre.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return $this->redirectConErrores('create');
        }

        $nombre      = trim((string) $this->request->getPost('nombre'));
        $descripcion = trim((string) $this->request->getPost('descripcion'));

        $payload = [
            'nombre'      => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'slug'        => $this->generarSlugUnico($nombre),
            'activo'      => 1,
        ];

        try {
            $this->tipoModel->insert($payload);
        } catch (Throwable $exception) {
            log_message('error', 'Error al crear tipo de diagnóstico: {exception}', ['exception' => $exception]);

            return $this->redirectConErrores('create', [
                'general' => 'No se pudo guardar el tipo de diagnóstico. Inténtalo nuevamente.',
            ]);
        }

        session()->setFlashdata('success', 'Tipo de diagnóstico creado correctamente.');

        return redirect()->route('admin_tipos_diagnostico_index');
    }

    public function update(int $tipoId)
    {
        $tipo = $this->tipoModel->find($tipoId);

        if ($tipo === null) {
            session()->setFlashdata('error', 'El tipo de diagnóstico seleccionado no existe.');

            return redirect()->route('admin_tipos_diagnostico_index');
        }

        $rules = [
            'nombre'      => 'required|min_length[2]|max_length[120]|is_unique[tipos_diagnostico.nombre,id,' . $tipoId . ']',
            'descripcion' => 'permit_empty|max_length[500]',
        ];

        $messages = [
            'nombre' => [
                'required'  => 'El nombre es obligatorio.',
                'is_unique' => 'Ya existe un tipo de diagnóstico con ese nombre.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return $this->redirectConErrores('edit', [], $tipoId);
        }

        $nombre      = trim((string) $this->request->getPost('nombre'));
        $descripcion = trim((string) $this->request->getPost('descripcion'));

        $datos = [
            'nombre'      => $nombre,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
        ];

        if ($nombre !== (string) $tipo->nombre) {
            $datos['slug'] = $this->generarSlugUnico($nombre, $tipoId);
        }

        try {
            $this->tipoModel->update($tipoId, $datos);
        } catch (Throwable $exception) {
            log_message('error', 'Error al actualizar tipo de diagnóstico: {exception}', ['exception' => $exception]);

            return $this->redirectConErrores('edit', [
                'general' => 'No se pudo actualizar el tipo de diagnóstico. Inténtalo nuevamente.',
            ], $tipoId);
        }

        session()->setFlashdata('success', 'Tipo de diagnóstico actualizado correctamente.');

        return redirect()->route('admin_tipos_diagnostico_index');
    }

    public function toggle(int $tipoId)
    {
        $tipo = $this->tipoModel->find($tipoId);

        if ($tipo === null) {
            session()->setFlashdata('error', 'El tipo de diagnóstico seleccionado no existe.');

            return redirect()->route('admin_tipos_diagnostico_index');
        }

        $accion = strtolower(trim((string) $this->request->getPost('accion')));

        if (! in_array($accion, ['activar', 'desactivar'], true)) {
            session()->setFlashdata('error', 'La acción seleccionada no es válida.');

            return redirect()->route('admin_tipos_diagnostico_index');
        }

        $nuevoEstado = $accion === 'activar' ? 1 : 0;

        if ((int) $tipo->activo === $nuevoEstado) {
            session()->setFlashdata('info', 'El estado seleccionado ya estaba aplicado.');

            return redirect()->route('admin_tipos_diagnostico_index');
        }

        try {
            $this->tipoModel->update($tipoId, ['activo' => $nuevoEstado]);
        } catch (Throwable $exception) {
            log_message('error', 'Error al cambiar el estado del tipo de diagnóstico: {exception}', ['exception' => $exception]);

            session()->setFlashdata('error', 'No se pudo actualizar el estado. Inténtalo nuevamente.');

            return redirect()->route('admin_tipos_diagnostico_index');
        }

        session()->setFlashdata('success', $nuevoEstado === 1
            ? 'Tipo de diagnóstico reactivado correctamente.'
            : 'Tipo de diagnóstico desactivado correctamente.'
        );

        return redirect()->route('admin_tipos_diagnostico_index');
    }

    /**
     * @param array<string, string> $erroresExtras
     */
    private function redirectConErrores(string $modo, array $erroresExtras = [], ?int $editId = null)
    {
        $errores = $this->validator !== null ? $this->validator->getErrors() : [];

        if ($erroresExtras !== []) {
            $errores = array_merge($errores, $erroresExtras);
        }

        return redirect()->route('admin_tipos_diagnostico_index')
            ->withInput()
            ->with('tipo_errors', $errores)
            ->with('tipo_form_mode', $modo)
            ->with('tipo_edit_id', $editId);
    }

    private function generarSlugUnico(string $nombre, ?int $ignoreId = null): string
    {
        $baseSlug = $this->normalizarSlug($nombre);
        $slug     = $baseSlug;
        $contador = 2;

        while ($this->tipoModel->slugExists($slug, $ignoreId)) {
            $slug = $this->aplicarSufijoSlug($baseSlug, $contador);
            $contador++;
        }

        return $slug;
    }

    private function normalizarSlug(string $texto): string
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);

        if ($slug === false) {
            $slug = $texto;
        }

        $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) $slug)));
        $slug = trim($slug, '-');

        if ($slug === '') {
            $slug = 'tipo';
        }

        return substr($slug, 0, self::SLUG_MAX_LENGTH);
    }

    private function aplicarSufijoSlug(string $baseSlug, int $sufijo): string
    {
        $sufijoTexto = '-' . $sufijo;
        $maxLongitud = self::SLUG_MAX_LENGTH;
        $base        = rtrim(substr($baseSlug, 0, $maxLongitud - strlen($sufijoTexto)), '-');

        if ($base === '') {
            $base = 'tipo';
        }

        return substr($base, 0, $maxLongitud - strlen($sufijoTexto)) . $sufijoTexto;
    }
}

