<?php
defined('MOODLE_INTERNAL') || die();

// Gunakan pemeriksaan standar block Moodle
if ($ADMIN->fulltree) {

    // 1. Toggle Aktifkan Pop-up Modals di Dashboard
    $settings->add(new admin_setting_configcheckbox(
        'block_daily_practice/enable_popup',
        'Aktifkan Pop-up Pengingat',
        'Jika dicentang, kuis harian akan muncul sebagai Pop-up/Modal saat karyawan pertama kali membuka Dashboard jika mereka belum mengerjakannya.',
        0
    ));

    // 2. Input Dinamis untuk Nama Kolom Profile Field Jabatan
    $settings->add(new admin_setting_configtext(
        'block_daily_practice/profile_field_shortname',
        'Shortname Custom Profile Field Jabatan',
        'Masukkan kode shortname dari Custom Profile Field yang Anda gunakan untuk mengidentifikasi jabatan karyawan (Contoh: jabatan, level_posisi, grade).',
        'jabatan',
        PARAM_ALPHANUMEXT
    ));

    // 3. Tautan Langsung ke Panel Manajemen Operasional Anda
    // PENTING: Sesuaikan nama file tujuan di bawah ini (apakah manage.php atau admin_manage.php)
    $manage_url = new moodle_url('/blocks/daily_practice/admin_manage.php');
    $settings->add(new admin_setting_heading(
        'block_daily_practice_manage_link',
        'Panel Operasional Manager LMS',
        '<a href="'.$manage_url.'" class="btn btn-primary font-weight-bold">📂 Buka Panel Manajemen & Laporan Daily Practice</a>'
    ));
}