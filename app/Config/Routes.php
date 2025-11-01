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
});

$routes->group('medico', function ($routes) {
    $routes->get('home', 'Medico\Home::index');
    $routes->get('diagnosticos', 'Medico\Diagnosticos::index', ['as' => 'medico_diagnosticos_index']);
    $routes->get('diagnosticos/nuevo', 'Medico\Diagnosticos::create', ['as' => 'medico_diagnosticos_create']);
    $routes->post('diagnosticos', 'Medico\Diagnosticos::store', ['as' => 'medico_diagnosticos_store']);
});
