<?php

declare(strict_types=1);

use App\Models\DiagnosticoModel;
use App\Models\TipoDiagnosticoModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class DiagnosticosTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $seed        = 'InitialSeeder';

    public function testMedicoPuedeCrearDiagnostico(): void
    {
        $userModel      = new UserModel();
        $tipoModel      = new TipoDiagnosticoModel();
        $diagnosticoModel = new DiagnosticoModel();

        $medico   = $userModel->findPrimeroActivoPorRol(UserModel::ROLE_MEDICO);
        $paciente = $userModel->findPrimeroActivoPorRol(UserModel::ROLE_PACIENTE);
        $tipo     = $tipoModel->where('activo', 1)->first();

        $descripcion = 'Diagnostico detallado para el paciente en seguimiento.';

        $response = $this->withSession(['user_id' => $medico->id])
            ->post(route_to('medico_diagnosticos_store'), [
                'paciente_id'         => $paciente->id,
                'tipo_diagnostico_id' => $tipo->id,
                'descripcion'         => $descripcion,
            ]);

        $response->assertRedirectTo(route_to('medico_diagnosticos_index'));

        $diagnostico = $diagnosticoModel
            ->where('autor_user_id', $medico->id)
            ->where('destinatario_user_id', $paciente->id)
            ->orderBy('id', 'DESC')
            ->first();

        $this->assertNotNull($diagnostico, 'No se encontro el diagnostico registrado.');
        $this->assertSame($tipo->id, $diagnostico->tipo_diagnostico_id);
        $this->assertSame($medico->id, $diagnostico->autor_user_id);
        $this->assertSame($paciente->id, $diagnostico->destinatario_user_id);
        $this->assertNotEmpty($diagnostico->fecha_creacion);
    }

    public function testValidacionImpideDescripcionCorta(): void
    {
        $userModel = new UserModel();
        $tipoModel = new TipoDiagnosticoModel();

        $medico   = $userModel->findPrimeroActivoPorRol(UserModel::ROLE_MEDICO);
        $paciente = $userModel->findPrimeroActivoPorRol(UserModel::ROLE_PACIENTE);
        $tipo     = $tipoModel->where('activo', 1)->first();

        $response = $this->withSession(['user_id' => $medico->id])
            ->post(route_to('medico_diagnosticos_store'), [
                'paciente_id'         => $paciente->id,
                'tipo_diagnostico_id' => $tipo->id,
                'descripcion'         => 'Muy corto',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $response->assertSessionHas('errors');

        $diagnosticos = (new DiagnosticoModel())
            ->where('autor_user_id', $medico->id)
            ->findAll();

        $this->assertCount(0, $diagnosticos);
    }
}
