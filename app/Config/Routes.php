<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->group('admin', function ($routes) {
    $routes->get('home', 'Admin\Home::index');
});

$routes->group('paciente', function ($routes) {
    $routes->get('home', 'Paciente\Home::index');
    $routes->get('planes', 'Paciente\Planes::index', ['as' => 'paciente_planes_index']);
    $routes->get('planes/(:num)', 'Paciente\Planes::show/$1', ['as' => 'paciente_planes_show']);
    $routes->post('planes/actividades/(:num)/marcar', 'Paciente\Planes::marcarActividad/$1', ['as' => 'paciente_planes_actividad_marcar']);
    $routes->post('planes/actividades/(:num)/desmarcar', 'Paciente\Planes::desmarcarActividad/$1', ['as' => 'paciente_planes_actividad_desmarcar']);
});

$routes->group('medico', function ($routes) {
    $routes->get('home', 'Medico\Home::index');
    $routes->get('pacientes', 'Medico\Pacientes::index', ['as' => 'medico_pacientes_index']);
    $routes->get('diagnosticos', 'Medico\Diagnosticos::index', ['as' => 'medico_diagnosticos_index']);
    $routes->get('diagnosticos/nuevo', 'Medico\Diagnosticos::create', ['as' => 'medico_diagnosticos_create']);
    $routes->post('diagnosticos', 'Medico\Diagnosticos::store', ['as' => 'medico_diagnosticos_store']);
    $routes->get('planes', 'Medico\Planes::index', ['as' => 'medico_planes_index']);
    $routes->get('planes/nuevo', 'Medico\Planes::create', ['as' => 'medico_planes_create']);
    $routes->post('planes', 'Medico\Planes::store', ['as' => 'medico_planes_store']);
    $routes->get('planes/(:num)', 'Medico\Planes::show/$1', ['as' => 'medico_planes_show']);
    $routes->get('planes/(:num)/editar', 'Medico\Planes::edit/$1', ['as' => 'medico_planes_edit']);
    $routes->put('planes/(:num)', 'Medico\Planes::update/$1', ['as' => 'medico_planes_update']);
    $routes->delete('planes/(:num)', 'Medico\Planes::delete/$1', ['as' => 'medico_planes_delete']);
    $routes->post('planes/actividades/(:num)/validar', 'Medico\Planes::validarActividad/$1', ['as' => 'medico_planes_actividad_validar']);
    $routes->post('planes/actividades/(:num)/desvalidar', 'Medico\Planes::desvalidarActividad/$1', ['as' => 'medico_planes_actividad_desvalidar']);
});
