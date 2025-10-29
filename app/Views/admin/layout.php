<?php
$brand = 'HealthPro';
$brandUrl = site_url('admin/home'); // futura ruta dashboard admin
$navItems = [
    ['label' => 'Home', 'url' => site_url('admin/home'), 'icon' => 'fas fa-home'],
    ['label' => 'Pacientes', 'url' => site_url('admin/usuarios?role=paciente'), 'icon' => 'fas fa-user-injured'],
    ['label' => 'Medicos', 'url' => site_url('admin/usuarios?role=medico'), 'icon' => 'fas fa-user-md'],
    ['label' => 'Planes de cuidado estandar', 'url' => site_url('admin/planes-estandar'), 'icon' => 'fas fa-notes-medical'],
];
$userItems = [
    ['label' => 'Mi Perfil', 'url' => site_url('admin/perfil'), 'icon' => 'fas fa-user-circle'],
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
