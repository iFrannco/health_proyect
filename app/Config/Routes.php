<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Rutas temporales para previsualizar layouts sin controladores dedicados.
$routes->get('preview/admin', static fn () => view('admin/layout', ['title' => 'Preview Admin']));
$routes->get('preview/medico', static fn () => view('medico/layout', ['title' => 'Preview Medico']));
$routes->get('preview/paciente', static fn () => view('paciente/layout', ['title' => 'Preview Paciente']));

$routes->group('medico', static function (RouteCollection $routes): void {
    $routes->get('diagnosticos', 'Medico\Diagnosticos::index', ['as' => 'medico_diagnosticos_index']);
    $routes->get('diagnosticos/nuevo', 'Medico\Diagnosticos::create', ['as' => 'medico_diagnosticos_create']);
    $routes->post('diagnosticos', 'Medico\Diagnosticos::store', ['as' => 'medico_diagnosticos_store']);
});
