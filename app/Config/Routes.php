<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth\\Login::index');

// Autenticación
$routes->group('auth', function ($routes) {
    $routes->get('login', 'Auth\Login::index', ['as' => 'auth_login']);
    $routes->post('login', 'Auth\Login::autenticar', ['as' => 'auth_login_post']);
    $routes->get('register', 'Auth\Register::index', ['as' => 'auth_register']);
    $routes->post('register', 'Auth\Register::store', ['as' => 'auth_register_post']);
    $routes->get('logout', 'Auth\Logout::index', ['as' => 'auth_logout']);
});

$routes->group('admin', ['filter' => 'auth'], function ($routes) {
    $routes->get('home', 'Admin\Home::index');
    $routes->get('usuarios', 'Admin\Usuarios::index', ['as' => 'admin_usuarios_index']);
    $routes->get('usuarios/nuevo', 'Admin\Usuarios::create', ['as' => 'admin_usuarios_create']);
    $routes->post('usuarios', 'Admin\Usuarios::store', ['as' => 'admin_usuarios_store']);
    $routes->get('usuarios/(:num)/editar', 'Admin\Usuarios::edit/$1', ['as' => 'admin_usuarios_edit']);
    $routes->post('usuarios/(:num)/actualizar', 'Admin\Usuarios::update/$1', ['as' => 'admin_usuarios_update']);
    $routes->post('usuarios/(:num)/reset-password', 'Admin\Usuarios::resetPassword/$1', ['as' => 'admin_usuarios_reset_password']);
    $routes->post('usuarios/(:num)/estado', 'Admin\Usuarios::cambiarEstado/$1', ['as' => 'admin_usuarios_toggle_estado']);

    $routes->get('tipos-diagnostico', 'Admin\TiposDiagnostico::index', ['as' => 'admin_tipos_diagnostico_index']);
    $routes->post('tipos-diagnostico', 'Admin\TiposDiagnostico::store', ['as' => 'admin_tipos_diagnostico_store']);
    $routes->post('tipos-diagnostico/(:num)/actualizar', 'Admin\TiposDiagnostico::update/$1', ['as' => 'admin_tipos_diagnostico_update']);
    $routes->post('tipos-diagnostico/(:num)/estado', 'Admin\TiposDiagnostico::toggle/$1', ['as' => 'admin_tipos_diagnostico_toggle']);

    $routes->get('perfil', 'Admin\Perfil::index', ['as' => 'admin_perfil_index']);
    $routes->post('perfil/datos', 'Admin\Perfil::actualizarDatos', ['as' => 'admin_perfil_actualizar_datos']);
    $routes->post('perfil/password', 'Admin\Perfil::actualizarPassword', ['as' => 'admin_perfil_actualizar_password']);
});

$routes->group('paciente', ['filter' => 'auth'], function ($routes) {
    $routes->get('home', 'Paciente\Home::index');
    $routes->get('home/resumen', 'Paciente\Home::resumen', ['as' => 'paciente_dashboard_resumen']);
    $routes->get('planes', 'Paciente\Planes::index', ['as' => 'paciente_planes_index']);
    $routes->get('planes/(:num)', 'Paciente\Planes::show/$1', ['as' => 'paciente_planes_show']);
    $routes->post('planes/actividades/(:num)/marcar', 'Paciente\Planes::marcarActividad/$1', ['as' => 'paciente_planes_actividad_marcar']);
    $routes->post('planes/actividades/(:num)/desmarcar', 'Paciente\Planes::desmarcarActividad/$1', ['as' => 'paciente_planes_actividad_desmarcar']);
    $routes->get('perfil', 'Paciente\Perfil::index', ['as' => 'paciente_perfil_index']);
    $routes->post('perfil/datos', 'Paciente\Perfil::actualizarDatos', ['as' => 'paciente_perfil_actualizar_datos']);
    $routes->post('perfil/password', 'Paciente\Perfil::actualizarPassword', ['as' => 'paciente_perfil_actualizar_password']);
});

$routes->group('medico', ['filter' => 'auth'], function ($routes) {
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
    $routes->get('perfil', 'Medico\Perfil::index', ['as' => 'medico_perfil_index']);
    $routes->post('perfil/datos', 'Medico\Perfil::actualizarDatos', ['as' => 'medico_perfil_actualizar_datos']);
    $routes->post('perfil/password', 'Medico\Perfil::actualizarPassword', ['as' => 'medico_perfil_actualizar_password']);
});
