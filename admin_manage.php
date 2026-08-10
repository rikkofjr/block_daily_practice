<?php
require_once(__DIR__ . '/../../config.php');
require_login();

// Proteksi Akses: Hanya Admin atau Manager LMS yang bisa masuk
$context = context_system::instance();
require_capability('block/daily_practice:manage', $context); 

$PAGE->set_url(new moodle_url('/blocks/daily_practice/admin_manage.php'));
$PAGE->set_context($context);
$PAGE->set_title('Daily Practice : Manajemen Jabatan');
$PAGE->set_heading('Daily Practice : Manajemen Jabatan');

echo $OUTPUT->header();

// =====================================================================
// [AKSI 1] PROSES TAMBAH JABATAN BARU (FREE TEXT INPUT)
// =====================================================================
if (data_submitted() && optional_param('action', '', PARAM_ALPHA) === 'add' && confirm_sesskey()) {
    $new_jabatan = optional_param('new_jabatan', '', PARAM_TEXT);
    $new_jabatan = strtolower(trim($new_jabatan));

    if (!empty($new_jabatan)) {
        if (!$DB->record_exists('block_daily_practice_map', array('jabatan_value' => $new_jabatan))) {
            $new_record = new stdClass();
            $new_record->jabatan_value = $new_jabatan;
            $new_record->course_id = 0; 
            $DB->insert_record('block_daily_practice_map', $new_record);
            echo html_writer::div('Jabatan <strong>'.strtoupper($new_jabatan).'</strong> berhasil ditambahkan!', 'alert alert-success');
        } else {
            echo html_writer::div('Jabatan tersebut sudah terdaftar di daftar pemetaan.', 'alert alert-warning');
        }
    }
}

// =====================================================================
// [AKSI 2] PROSES HAPUS JABATAN DARI DAFTAR MAPPING
// =====================================================================
$delete_id = optional_param('delete_id', 0, PARAM_INT);
if ($delete_id > 0 && confirm_sesskey()) {
    $DB->delete_records('block_daily_practice_map', array('id' => $delete_id));
    echo html_writer::div('Jabatan berhasil dihapus dari pemetaan.', 'alert alert-info');
}

// =====================================================================
// [AKSI 3] PROSES SIMPAN/UPDATE DATA COURSE ID MAPPING
// =====================================================================
if (data_submitted() && optional_param('action', '', PARAM_ALPHA) === 'save' && confirm_sesskey()) {
    $courses = optional_param_array('course_map', array(), PARAM_INT);
    foreach ($courses as $id => $cid) {
        $record = new stdClass();
        $record->id = $id;
        $record->course_id = $cid;
        $DB->update_record('block_daily_practice_map', $record);
    }
    echo html_writer::div('Konfigurasi pemetaan Course ID berhasil disimpan!', 'alert alert-success');
}

// =====================================================================
// TAMPILAN ANTARMUKA PEMETAAN JABATAN
// =====================================================================
$mapped_jabatans = $DB->get_records('block_daily_practice_map', null, 'jabatan_value ASC');

// Form Interaktif Tambah Jabatan Baru (Free Text)
echo '
<div class="card my-3 bg-light">
    <div class="card-body">
        <h5 class="card-title">Tambahkan Jabatan Peserta Daily Practice</h5>
        <form method="post" action="admin_manage.php" class="form-inline">
            <input type="hidden" name="sesskey" value="'.sesskey().'">
            <input type="hidden" name="action" value="add">
            <div class="form-group mr-2">
                <input type="text" name="new_jabatan" class="form-control" placeholder="Contoh: AREA MANAGER" required style="min-width: 300px;">
            </div>
            <button type="submit" class="btn btn-primary">Tambah Jabatan</button>
        </form>
        <small class="form-text text-muted">Pastikan penulisan harus sama persis dengan yang ada di profile karyawan.</small>
    </div>
</div>
';

if (!empty($mapped_jabatans)) {
    echo '<form method="post" action="admin_manage.php">'; 
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'">';
    echo '<input type="hidden" name="action" value="save">';
    echo '<table class="table table-bordered table-striped mt-3">
            <thead class="thead-dark">
                <tr>
                    <th>Nama Level Jabatan</th>
                    <th>Target Course ID (Daily Practice)</th>
                    <th class="text-center" style="width: 100px;">Aksi</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($mapped_jabatans as $map) {
        $delete_url = new moodle_url('/blocks/daily_practice/admin_manage.php', array('delete_id' => $map->id, 'sesskey' => sesskey()));
        echo '<tr>
                <td class="align-middle"><strong>'.strtoupper($map->jabatan_value).'</strong></td>
                <td><input type="number" name="course_map['.$map->id.']" value="'.$map->course_id.'" class="form-control" style="width:120px;"></td>
                <td class="text-center align-middle">
                    <a href="'.$delete_url.'" class="btn btn-danger btn-sm" onclick="return confirm(\'Hapus jabatan ini dari daftar pemetaan?\')"><i class="fas fa-trash"></i> Hapus</a>
                </td>
              </tr>';
    }
    echo '</tbody></table>';
    echo '<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan Mapping</button>';
    echo '</form>';
} else {
    echo '<div class="alert alert-info mt-3">Belum ada jabatan yang didaftarkan. Silakan tambahkan nama jabatan dari sistem HRIS Anda menggunakan formulir di atas.</div>';
}

echo $OUTPUT->footer();