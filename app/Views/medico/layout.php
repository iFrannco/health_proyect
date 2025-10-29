<?php
$brand = 'HealthPro';
$brandUrl = site_url('medico/home'); // dashboard con estadísticas del médico
$navItems = [
    ['label' => 'Home', 'url' => site_url('medico/home'), 'icon' => 'fas fa-home'],
    ['label' => 'Pacientes', 'url' => site_url('medico/pacientes'), 'icon' => 'fas fa-users'],
    ['label' => 'Diagnosticos', 'url' => site_url('medico/diagnosticos'), 'icon' => 'fas fa-stethoscope'],
    ['label' => 'Planes de cuidado', 'url' => site_url('medico/planes'), 'icon' => 'fas fa-clipboard-list'],
];
$userItems = [
    ['label' => 'Mi Perfil', 'url' => site_url('medico/perfil'), 'icon' => 'fas fa-user-circle'],
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
