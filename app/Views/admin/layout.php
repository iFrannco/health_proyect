<?php
$brand = null;
$brandUrl = site_url('admin/home'); // futura ruta dashboard admin
$navItems = [
    ['label' => 'Home', 'url' => site_url('admin/home')],
    ['label' => 'Pacientes', 'url' => site_url('admin/usuarios?role=paciente')],
    ['label' => 'Medicos', 'url' => site_url('admin/usuarios?role=medico')],
    ['label' => 'Planes de cuidado estandar', 'url' => site_url('admin/planes-estandar')],
];
$userItems = [
    ['label' => 'Mi Perfil', 'url' => site_url('admin/perfil')],
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
    'navbarId' => 'navbarAdmin',
]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <?= $this->renderSection('content') ?>
<?= $this->endSection() ?>
