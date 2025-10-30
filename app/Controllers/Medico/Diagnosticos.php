<?php

namespace App\Controllers\Medico;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\DiagnosticoModel;
use App\Models\TipoDiagnosticoModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageForbiddenException;
use CodeIgniter\Exceptions\PageNotFoundException;

class Diagnosticos extends BaseController
{
    private DiagnosticoModel $diagnosticoModel;
    private UserModel $userModel;
    private TipoDiagnosticoModel $tipoDiagnosticoModel;

    public function __construct()
    {
        helper(['form', 'url']);
        $this->diagnosticoModel    = new DiagnosticoModel();
        $this->userModel           = new UserModel();
        $this->tipoDiagnosticoModel = new TipoDiagnosticoModel();
    }

    public function index()
    {
        $medico        = $this->obtenerMedicoActual();
        $diagnosticos  = $this->diagnosticoModel->findDetallesPorMedico($medico->id);

        $data = [
            'title'         => 'Diagnosticos',
            'medico'        => $medico,
            'diagnosticos'  => $diagnosticos,
        ];

        return view('medico/diagnosticos/index', $data);
    }

    public function create()
    {
        $medico    = $this->obtenerMedicoActual();
        $pacientes = $this->userModel->findActivosPorRol(UserModel::ROLE_PACIENTE);
        $tipos     = $this->tipoDiagnosticoModel->findActivos();

        $data = [
            'title'     => 'Nuevo diagnostico',
            'medico'    => $medico,
            'pacientes' => $pacientes,
            'tipos'     => $tipos,
            'errors'    => session()->getFlashdata('errors') ?? [],
        ];

        return view('medico/diagnosticos/create', $data);
    }

    public function store()
    {
        $medico = $this->obtenerMedicoActual();

        $rules = [
            'paciente_id' => [
                'label' => 'Paciente',
                'rules' => 'required|is_natural_no_zero',
            ],
            'tipo_diagnostico_id' => [
                'label' => 'Tipo de diagnostico',
                'rules' => 'required|is_natural_no_zero',
            ],
            'descripcion' => [
                'label' => 'Descripcion',
                'rules' => 'required|min_length[10]|max_length[2000]',
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Revisa los datos del formulario.')
                ->with('errors', $this->validator->getErrors());
        }

        $pacienteId = (int) $this->request->getPost('paciente_id');
        $tipoId     = (int) $this->request->getPost('tipo_diagnostico_id');

        if ($pacienteId === $medico->id) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Selecciona un paciente valido.')
                ->with('errors', ['paciente_id' => 'No puedes asignarte un diagnostico a ti mismo.']);
        }

        $paciente = $this->userModel->findActivoPorRol($pacienteId, UserModel::ROLE_PACIENTE);
        if (! $paciente) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'El paciente seleccionado no es valido.')
                ->with('errors', ['paciente_id' => 'El paciente seleccionado no existe o no esta activo.']);
        }

        $tipo = $this->tipoDiagnosticoModel->find($tipoId);
        if (! $tipo || ! $tipo->activo) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'El tipo de diagnostico es invalido.')
                ->with('errors', ['tipo_diagnostico_id' => 'Tipo de diagnostico invalido.']);
        }

        $descripcion = (string) $this->request->getPost('descripcion');

        $datos = [
            'autor_user_id'        => $medico->id,
            'destinatario_user_id' => $paciente->id,
            'tipo_diagnostico_id'  => $tipo->id,
            'descripcion'          => trim($descripcion),
        ];

        if (! $this->diagnosticoModel->insert($datos, true)) {
            $errores = $this->diagnosticoModel->errors() ?? ['No se pudo registrar el diagnostico.'];

            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo registrar el diagnostico.')
                ->with('errors', $errores);
        }

        session()->setFlashdata('success', 'Diagnostico registrado con exito.');

        return redirect()->to(route_to('medico_diagnosticos_index'));
    }

    private function obtenerMedicoActual(): User
    {
        $session = session();
        $userId  = $session->get('user_id');

        if ($userId !== null) {
            $medico = $this->userModel->findActivoPorRol((int) $userId, UserModel::ROLE_MEDICO);
            if ($medico !== null) {
                return $medico;
            }

            throw new PageForbiddenException('Acceso denegado para el usuario actual.');
        }

        $medico = $this->userModel->findPrimeroActivoPorRol(UserModel::ROLE_MEDICO);

        if ($medico === null) {
            throw new PageNotFoundException('No existen medicos activos configurados.');
        }

        $session->set('user_id', $medico->id);

        return $medico;
    }
}
