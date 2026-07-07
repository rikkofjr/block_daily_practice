<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context); 

$PAGE->set_url(new moodle_url('/blocks/daily_practice/admin_manage.php'));
$PAGE->set_context($context);
$PAGE->set_title('Panel Manager: Daily Practice');
$PAGE->set_heading('Panel Manajemen & Laporan Daily Practice');

echo $OUTPUT->header();

// =====================================================================
// Aksi 1: PROSES TAMBAH JABATAN BARU (FREE TEXT INPUT)
// =====================================================================
if (data_submitted() && optional_param('action', '', PARAM_ALPHA) === 'add' && confirm_sesskey()) {
    $new_jabatan = optional_param('new_jabatan', '', PARAM_TEXT);
    $new_jabatan = strtolower(trim($new_jabatan));

    if (!empty($new_jabatan)) {
        // Cek apakah jabatan ini sudah terdaftar di tabel mapping
        if (!$DB->record_exists('block_daily_practice_map', array('jabatan_value' => $new_jabatan))) {
            $new_record = new stdClass();
            $new_record->jabatan_value = $new_jabatan;
            $new_record->course_id = 0; // Default 0 sebelum di-mapping
            $DB->insert_record('block_daily_practice_map', $new_record);
            echo html_writer::div('Jabatan <strong>'.strtoupper($new_jabatan).'</strong> berhasil ditambahkan ke daftar!', 'alert alert-success');
        } else {
            echo html_writer::div('Jabatan tersebut sudah ada di dalam daftar.', 'alert alert-warning');
        }
    }
}

// =====================================================================
// Aksi 2: PROSES HAPUS JABATAN DARI DAFTAR MAPPING
// =====================================================================
$delete_id = optional_param('delete_id', 0, PARAM_INT);
if ($delete_id > 0 && confirm_sesskey()) {
    $DB->delete_records('block_daily_practice_map', array('id' => $delete_id));
    echo html_writer::div('Jabatan berhasil dihapus dari daftar pemetaan.', 'alert alert-info');
}

// =====================================================================
// Aksi 3: PROSES SIMPAN/UPDATE DATA COURSE ID MAPPING
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
// AMBIL DATA HANYA DARI TABEL MAPPING (Sangat Cepat & Efisien)
// =====================================================================
$mapped_jabatans = $DB->get_records('block_daily_practice_map', null, 'jabatan_value ASC');

// --- TAMPILAN ANTARMUKA TABS ---
echo '
<ul class="nav nav-tabs" id="myTab" role="tablist">
  <li class="nav-item"><a class="nav-link active" id="mapping-tab" data-toggle="tab" href="#mapping" role="tab">🗺️ Pemetaan Jabatan</a></li>
  <li class="nav-item"><a class="nav-link" id="report-tab" data-toggle="tab" href="#report" role="tab">📊 Laporan & Monitoring Hari Ini</a></li>
</ul>
<div class="tab-content border border-top-0 p-3 bg-white" id="myTabContent">
';

// TAB 1: MAPPING FORM & INPUT BARU
echo '<div class="tab-pane fade show active" id="mapping" role="tabpanel">';

// Form Form Tambah Jabatan Baru (Free Text)
echo '
<div class="card my-3 bg-light">
    <div class="card-body">
        <h5 class="card-title">➕ Daftarkan Jabatan Baru dari HRIS</h5>
        <form method="post" action="admin_manage.php" class="form-inline">
            <input type="hidden" name="sesskey" value="'.sesskey().'">
            <input type="hidden" name="action" value="add">
            <div class="form-group mr-2">
                <input type="text" name="new_jabatan" class="form-control" placeholder="Contoh: AREA MANAGER" required style="min-width: 300px;">
            </div>
            <button type="submit" class="btn btn-primary">Tambah Jabatan</button>
        </form>
        <small class="form-text text-muted">Ketik nama jabatan persis seperti string teks yang dikirimkan oleh sistem HRIS ke Moodle.</small>
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
                    <th>Nama Level Jabatan (Teks HRIS)</th>
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
                    <a href="'.$delete_url.'" class="btn btn-outline-danger btn-sm" onclick="return confirm(\'Hapus jabatan ini dari daftar pemetaan?\')">🗑️ Hapus</a>
                </td>
              </tr>';
    }
    echo '</tbody></table>';
    echo '<button type="submit" class="btn btn-success">💾 Simpan Perubahan Mapping</button>';
    echo '</form>';
} else {
    echo '<div class="alert alert-info mt-3">Belum ada jabatan yang didaftarkan. Silakan tambahkan nama jabatan dari sistem HRIS Anda menggunakan form di atas.</div>';
}
echo '</div>';

// TAB 2: REPORT (Sudah Dioptimalkan agar Query Tetap Ringan)
echo '<div class="tab-pane fade" id="report" role="tabpanel">';
echo '<h4 class="mt-3">Status Partisipasi Hari Ini ('.date('d F Y').')</h4>';

$shortname = get_config('block_daily_practice', 'profile_field_shortname');
if (empty($shortname)) {
    $shortname = 'jabatan';
}

$today_start = make_timestamp(date('Y'), date('m'), date('d'), 0, 0, 0);
$today_end = make_timestamp(date('Y'), date('m'), date('d'), 23, 59, 59);

// Query Laporan yang Efisien karena di-JOIN langsung dengan batasan tabel kustom mapping
$sql_report = "
    SELECT m.id, m.jabatan_value, COUNT(DISTINCT uid.userid) as total_karyawan,
           (SELECT COUNT(DISTINCT qa.userid) 
            FROM {quiz_attempts} qa 
            JOIN {quiz} q ON qa.quiz = q.id 
            WHERE q.course = m.course_id AND qa.timestart >= :start AND qa.timestart <= :end) as sudah_mengerjakan
    FROM {block_daily_practice_map} m
    JOIN {user_info_field} uif ON uif.shortname = :shortname
    JOIN {user_info_data} uid ON uid.fieldid = uif.id AND LOWER(TRIM(uid.data)) = m.jabatan_value
    GROUP BY m.id, m.jabatan_value, m.course_id
";

$reports = $DB->get_records_sql($sql_report, array('start' => $today_start, 'end' => $today_end, 'shortname' => $shortname));

echo '<table class="table table-hover mt-3">
        <thead class="bg-light"><tr><th>Jabatan</th><th>Total Karyawan Aktif</th><th>Sudah Ikut Hari Ini</th><th>Persentase Partisipasi</th></tr></thead><tbody>';
if (!empty($reports)) {
    foreach ($reports as $rep) {
        $pct = $rep->total_karyawan > 0 ? round(($rep->sudah_mengerjakan / $rep->total_karyawan) * 100) : 0;
        echo '<tr>
                <td>'.strtoupper($rep->jabatan_value).'</td>
                <td>'.$rep->total_karyawan.' Orang</td>
                <td>'.$rep->sudah_mengerjakan.' Orang</td>
                <td><strong>'.$pct.'%</strong></td>
              </tr>';
    }
} else {
    echo '<tr><td colspan="4" class="text-center text-muted">Belum ada data pengerjaan atau data karyawan untuk jabatan terdaftar belum tersinkronisasi.</td></tr>';
}
echo '</tbody></table>';
echo '</div>';

echo '</div>';
echo $OUTPUT->footer();