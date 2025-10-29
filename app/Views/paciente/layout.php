<?php
$brand = 'HealthPro';
$brandUrl = site_url('paciente/home'); // dashboard con métricas personales
$navItems = [
    ['label' => 'Historial Medico', 'url' => site_url('paciente/historial'), 'icon' => 'fas fa-file-medical'],
    ['label' => 'Planes de cuidado', 'url' => site_url('paciente/planes'), 'icon' => 'fas fa-heartbeat'],
];
$userItems = [
    ['label' => 'Mi Perfil', 'url' => site_url('paciente/perfil'), 'icon' => 'fas fa-user-circle'],
    ['label' => 'Seleccionar rol', 'url' => site_url('auth/select-role'), 'icon' => 'fas fa-user-tag'],
    ['label' => 'Cerrar sesión', 'url' => site_url('auth/logout'), 'icon' => 'fas fa-sign-out-alt'],
];
?>

<?= $this->extend('layouts/base') ?>

<?= $this->section('navbar') ?>
<?= view('layouts/partials/navbar', [
    'userItems' => $userItems,
    'brand' => $brand,
    'brandUrl' => $brandUrl,
]) ?>
<?= $this->endSection() ?>

<?= $this->section('sidebar') ?>
<?= view('layouts/partials/sidebar', [
    'navItems' => $navItems,
    'brand' => $brand,
    'brandUrl' => $brandUrl,
]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <?= $this->renderSection('content') ?>
<?= $this->endSection() ?>
