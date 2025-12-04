<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentoModel extends Model
{
    protected $table            = 'documentacion';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'usuario_id',
        'nombre',
        'tipo',
        'fecha_documento',
        'url',
    ];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $validationRules = [
        'usuario_id'      => 'required|is_natural_no_zero',
        'nombre'          => 'required|min_length[3]|max_length[180]',
        'tipo'            => 'required|in_list[informe,receta,estudio]',
        'fecha_documento' => 'required|valid_date[Y-m-d]',
        'url'             => 'required|max_length[255]',
    ];

    protected $validationMessages = [
        'tipo' => [
            'in_list' => 'El tipo debe ser informe, receta o estudio.',
        ],
        'fecha_documento' => [
            'valid_date' => 'La fecha del documento debe tener formato YYYY-MM-DD.',
        ],
    ];

    public function filtrarPorUsuarioYTpo(int $usuarioId, ?string $tipo = null): array
    {
        $builder = $this->where('usuario_id', $usuarioId)
            ->orderBy('fecha_documento', 'DESC')
            ->orderBy('id', 'DESC');

        $tipoFiltrado = trim((string) $tipo);
        if (in_array($tipoFiltrado, ['informe', 'receta', 'estudio'], true)) {
            $builder->where('tipo', $tipoFiltrado);
        }

        return $builder->findAll();
    }
}

