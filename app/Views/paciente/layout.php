<?php
$brand = null;
$brandUrl = site_url('paciente/home'); // dashboard con métricas personales
$navItems = [
    ['label' => 'Historial Medico', 'url' => site_url('paciente/historial')],
    ['label' => 'Planes de cuidado', 'url' => site_url('paciente/planes')],
];
$userItems = [
    ['label' => 'Mi Perfil', 'url' => site_url('paciente/perfil')],
    ['label' => 'Seleccionar rol', 'url' => site_url('auth/select-role')],
    ['label' => 'Cerrar sesión', 'url' => site_url('auth/logout')],
];
?>

<?= $this->extend('layouts/base') ?>

<?= $this->section('navbar') ?>
<?= view('layouts/partials/navbar', [
    'navItems' => $navItems,
    'userItems' => $userItems,
    'brand' => $brand,
    'brandUrl' => $brandUrl,
    'navbarId' => 'navbarPaciente',
]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <?= $this->renderSection('content') ?>
<?= $this->endSection() ?>
