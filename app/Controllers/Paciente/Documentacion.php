<?php

namespace App\Controllers\Paciente;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\DocumentoModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Documentacion extends BaseController
{
    private UserModel $userModel;

    private DocumentoModel $documentoModel;

    private const TIPOS = [
        'informe' => 'Informe',
        'receta'  => 'Receta',
        'estudio' => 'Estudio',
    ];

    public function __construct()
    {
        $this->userModel      = new UserModel();
        $this->documentoModel = new DocumentoModel();
    }

    public function index()
    {
        $paciente = $this->obtenerPacienteActual();
        $tipo     = (string) ($this->request->getGet('tipo') ?? '');

        $documentos = $this->documentoModel->filtrarPorUsuarioYTpo((int) $paciente->id, $tipo);

        return view('paciente/documentacion/index', $this->layoutData() + [
            'title'       => 'Historial médico',
            'usuario'     => $paciente,
            'documentos'  => $documentos,
            'tipos'       => self::TIPOS,
            'tipoActual'  => $tipo,
            'errorsDatos' => session()->getFlashdata('errors_datos') ?? [],
        ]);
    }

    public function store()
    {
        $paciente = $this->obtenerPacienteActual();

        $rules = [
            'nombre'          => 'required|min_length[3]|max_length[180]',
            'tipo'            => 'required|in_list[informe,receta,estudio]',
            'fecha_documento' => 'required|valid_date[Y-m-d]',
            'archivo'         => 'uploaded[archivo]|max_size[archivo,5120]|ext_in[archivo,pdf,png,jpg,jpeg]',
        ];

        $messages = [
            'tipo' => ['in_list' => 'El tipo debe ser informe, receta o estudio.'],
            'archivo' => [
                'uploaded' => 'Debes seleccionar un archivo.',
                'max_size' => 'El archivo supera el tamaño permitido (5MB).',
                'ext_in'   => 'Tipo de archivo no permitido (usa pdf, png, jpg, jpeg).',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors_datos', $this->validator->getErrors());
        }

        $archivo = $this->request->getFile('archivo');
        if (! $archivo || ! $archivo->isValid()) {
            return redirect()->back()->withInput()->with('errors_datos', ['archivo' => 'Archivo inválido.']);
        }

        $nuevoNombre = $archivo->getRandomName();
        $uploadPath  = FCPATH . 'uploads';

        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        if (! $archivo->move($uploadPath, $nuevoNombre)) {
            return redirect()->back()->withInput()->with('errors_datos', ['archivo' => 'No se pudo subir el archivo.']);
        }

        $payload = [
            'usuario_id'      => (int) $paciente->id,
            'nombre'          => trim((string) $this->request->getPost('nombre')),
            'tipo'            => trim((string) $this->request->getPost('tipo')),
            'fecha_documento' => (string) $this->request->getPost('fecha_documento'),
            'url'             => 'uploads/' . $nuevoNombre,
        ];

        try {
            $this->documentoModel->insert($payload);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al guardar documentación: {exception}', ['exception' => $exception]);
            return redirect()->back()->withInput()->with('errors_datos', ['general' => 'No se pudo guardar el documento.']);
        }

        session()->setFlashdata('success', 'Documento cargado correctamente.');
        return redirect()->route('paciente_documentacion_index');
    }

    public function update(int $documentoId)
    {
        $paciente  = $this->obtenerPacienteActual();
        $documento = $this->documentoModel->find($documentoId);

        if (! $documento || (int) $documento['usuario_id'] !== (int) $paciente->id) {
            throw new PageNotFoundException('Documento no encontrado.');
        }

        $rules = [
            'nombre'          => 'required|min_length[3]|max_length[180]',
            'tipo'            => 'required|in_list[informe,receta,estudio]',
            'fecha_documento' => 'required|valid_date[Y-m-d]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors_datos', $this->validator->getErrors());
        }

        $payload = [
            'nombre'          => trim((string) $this->request->getPost('nombre')),
            'tipo'            => trim((string) $this->request->getPost('tipo')),
            'fecha_documento' => (string) $this->request->getPost('fecha_documento'),
        ];

        try {
            $this->documentoModel->update($documentoId, $payload);
        } catch (\Throwable $exception) {
            log_message('error', 'Error al actualizar documentación: {exception}', ['exception' => $exception]);
            return redirect()->back()->withInput()->with('errors_datos', ['general' => 'No se pudo actualizar el documento.']);
        }

        session()->setFlashdata('success', 'Documento actualizado correctamente.');
        return redirect()->route('paciente_documentacion_index');
    }

    public function delete(int $documentoId)
    {
        $paciente  = $this->obtenerPacienteActual();
        $documento = $this->documentoModel->find($documentoId);

        if (! $documento || (int) $documento['usuario_id'] !== (int) $paciente->id) {
            throw new PageNotFoundException('Documento no encontrado.');
        }

        $ruta = $documento['url'] ?? '';
        try {
            $this->documentoModel->delete($documentoId);
            if ($ruta !== '') {
                $fullPath = FCPATH . $ruta;
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
        } catch (\Throwable $exception) {
            log_message('error', 'Error al eliminar documentación: {exception}', ['exception' => $exception]);
            session()->setFlashdata('error', 'No se pudo eliminar el documento.');
            return redirect()->route('paciente_documentacion_index');
        }

        session()->setFlashdata('success', 'Documento eliminado.');
        return redirect()->route('paciente_documentacion_index');
    }

    public function descargar(int $documentoId)
    {
        $paciente  = $this->obtenerPacienteActual();
        $documento = $this->documentoModel->find($documentoId);

        if (! $documento || (int) $documento['usuario_id'] !== (int) $paciente->id) {
            throw new PageNotFoundException('Documento no encontrado.');
        }

        $ruta = FCPATH . ($documento['url'] ?? '');
        if (! is_file($ruta)) {
            session()->setFlashdata('error', 'El archivo no está disponible. Súbelo nuevamente.');
            return redirect()->route('paciente_documentacion_index');
        }

        return $this->response->download($ruta, null, true);
    }

    private function obtenerPacienteActual(): User
    {
        $session = session();
        $userId  = $session->get('user_id');

        if ($userId !== null) {
            $paciente = $this->userModel->findActivoPorRol((int) $userId, UserModel::ROLE_PACIENTE);
            if ($paciente instanceof User) {
                return $paciente;
            }
        }

        $paciente = $this->userModel->findPrimeroActivoPorRol(UserModel::ROLE_PACIENTE);
        if ($paciente === null) {
            throw new PageNotFoundException('No existen pacientes activos configurados.');
        }

        $session->set('user_id', $paciente->id);

        return $paciente;
    }
}
