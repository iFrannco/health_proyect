<?= $this->extend('medico/layout') ?>

<?= $this->section('content') ?>
<div class="row">
    <div class="col-12 col-lg-10">
        <h1 class="mb-4">Nuevo diagnostico</h1>

        <?= view('layouts/partials/alerts') ?>

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title mb-0">Registrar diagnostico</h3>
            </div>
            <form action="<?= route_to('medico_diagnosticos_store') ?>" method="post">
                <?= csrf_field() ?>
                <div class="card-body">
                    <?php $errorList = $errors ?? []; ?>

                    <div class="form-group">
                        <label for="paciente_id">Paciente</label>
                        <select
                            id="paciente_id"
                            name="paciente_id"
                            class="form-control<?= isset($errorList['paciente_id']) ? ' is-invalid' : '' ?>"
                            required
                        >
                            <option value="">Selecciona un paciente</option>
                            <?php foreach ($pacientes as $paciente): ?>
                                <?php
                                $pacienteId     = (int) ($paciente['id'] ?? 0);
                                $isSelected     = (int) old('paciente_id') === $pacienteId;
                                $nombrePaciente = trim(($paciente['apellido'] ?? '') . ', ' . ($paciente['nombre'] ?? ''));
                                ?>
                                <option value="<?= $pacienteId ?>"<?= $isSelected ? ' selected' : '' ?>>
                                    <?= esc($nombrePaciente) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errorList['paciente_id'])): ?>
                            <span class="invalid-feedback d-block">
                                <?= esc($errorList['paciente_id']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="tipo_diagnostico_id">Tipo de diagnostico</label>
                        <select
                            id="tipo_diagnostico_id"
                            name="tipo_diagnostico_id"
                            class="form-control<?= isset($errorList['tipo_diagnostico_id']) ? ' is-invalid' : '' ?>"
                            required
                        >
                            <option value="">Selecciona un tipo</option>
                            <?php foreach ($tipos as $tipo): ?>
                                <?php
                                $tipoId     = (int) $tipo->id;
                                $isSelected = (int) old('tipo_diagnostico_id') === $tipoId;
                                ?>
                                <option value="<?= $tipoId ?>"<?= $isSelected ? ' selected' : '' ?>>
                                    <?= esc($tipo->nombre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errorList['tipo_diagnostico_id'])): ?>
                            <span class="invalid-feedback d-block">
                                <?= esc($errorList['tipo_diagnostico_id']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripcion clinica</label>
                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="5"
                            class="form-control<?= isset($errorList['descripcion']) ? ' is-invalid' : '' ?>"
                            minlength="10"
                            maxlength="2000"
                            required
                        ><?= esc(old('descripcion')) ?></textarea>
                        <small class="form-text text-muted">
                            Debe contener entre 10 y 2000 caracteres.
                        </small>
                        <?php if (isset($errorList['descripcion'])): ?>
                            <span class="invalid-feedback d-block">
                                <?= esc($errorList['descripcion']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= route_to('medico_diagnosticos_index') ?>" class="btn btn-outline-secondary">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Guardar diagnostico
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

