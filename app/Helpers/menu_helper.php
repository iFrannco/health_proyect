<?php
// app/Helpers/menu_helper.php

/**
 * Devuelve los datos de layout (marca, menú lateral y menú superior)
 * según el único rol del usuario actual.
 */
function buildLayoutData(string $rol): array
{
    $brand = 'HealthPro';

    // Configuración base según el rol
    switch ($rol) {
        case 'admin':
            $brandUrl = site_url('admin/home');
            $navItems = [
                ['label' => 'Home', 'url' => site_url('admin/home'), 'icon' => 'fas fa-home'],
                ['label' => 'Usuarios', 'url' => site_url('admin/usuarios'), 'icon' => 'fas fa-users'],
                ['label' => 'Planes de cuidado estándar', 'url' => site_url('admin/planes-estandar'), 'icon' => 'fas fa-notes-medical'],
                ['label' => 'Tipos de Diagnóstico', 'url' => site_url('admin/tipos-diagnostico'), 'icon' => 'fas fa-file-medical'],
            ];
            $perfilUrl = site_url('admin/perfil');
            break;

        case 'medico':
            $brandUrl = site_url('medico/home');
            $navItems = [
                ['label' => 'Home', 'url' => site_url('medico/home'), 'icon' => 'fas fa-home'],
                ['label' => 'Pacientes', 'url' => site_url('medico/pacientes'), 'icon' => 'fas fa-users'],
                ['label' => 'Diagnósticos', 'url' => site_url('medico/diagnosticos'), 'icon' => 'fas fa-stethoscope'],
                ['label' => 'Planes de cuidado', 'url' => site_url('medico/planes'), 'icon' => 'fas fa-clipboard-list'],
            ];
            $perfilUrl = site_url('medico/perfil');
            break;

        case 'paciente':
            $brandUrl = site_url('paciente/home');
            $navItems = [
                ['label' => 'Home', 'url' => site_url('paciente/home'), 'icon' => 'fas fa-home'],
                ['label' => 'Diagnósticos', 'url' => site_url('paciente/diagnosticos'), 'icon' => 'fas fa-stethoscope'],
                ['label' => 'Historial Médico', 'url' => site_url('paciente/documentacion'), 'icon' => 'fas fa-file-medical'],
                ['label' => 'Planes de cuidado', 'url' => site_url('paciente/planes'), 'icon' => 'fas fa-heartbeat'],
            ];
            $perfilUrl = site_url('paciente/perfil');
            break;

        default:
            $brandUrl = base_url('/');
            $navItems = [];
            $perfilUrl = site_url('perfil');
            break;
    }

    // Menú superior (común)
    $userItems = [
        ['label' => 'Mi Perfil', 'url' => $perfilUrl, 'icon' => 'fas fa-user-circle'],
        ['label' => 'Cerrar sesión', 'url' => site_url('auth/logout'), 'icon' => 'fas fa-sign-out-alt'],
    ];

    return compact('brand', 'brandUrl', 'navItems', 'userItems');
}
