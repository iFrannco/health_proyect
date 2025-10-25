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
