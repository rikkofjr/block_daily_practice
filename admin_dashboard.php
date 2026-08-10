<?php
require_once(__DIR__ . '/../../config.php');
require_login();

// Proteksi Akses: Hanya Admin atau Manager LMS yang bisa masuk
$context = context_system::instance();
require_capability('block/daily_practice:manage', $context); 

$PAGE->set_url(new moodle_url('/blocks/daily_practice/admin_dashboard.php'));
$PAGE->set_context($context);
$PAGE->set_title('Dashboard Monitor: Daily Practice');
$PAGE->set_heading('📊 Laporan Partisipasi Daily Practice Hari Ini');

echo $OUTPUT->header();

$shortname = get_config('block_daily_practice', 'profile_field_shortname');
if (empty($shortname)) {
    $shortname = 'jabatan';
}

$today_start = make_timestamp(date('Y'), date('m'), date('d'), 0, 0, 0);
$today_end = make_timestamp(date('Y'), date('m'), date('d'), 23, 59, 59);

// Query Laporan Berkinerja Tinggi (Hanya memproses baris jabatan yang terdaftar di pemetaan)
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

echo '<h4 class="mt-3 text-muted">Status Keaktifan Karyawan Tanggal: '.date('d F Y').'</h4>';

echo '<table class="table table-bordered table-hover mt-3 bg-white shadow-sm">
        <thead class="thead-light">
            <tr>
                <th>Level Jabatan</th>
                <th>Total Karyawan Aktif</th>
                <th>Sudah Mengikuti Latihan</th>
                <th>Persentase Partisipasi</th>
            </tr>
        </thead>
        <tbody>';

if (!empty($reports)) {
    foreach ($reports as $rep) {
        $pct = $rep->total_karyawan > 0 ? round(($rep->sudah_mengerjakan / $rep->total_karyawan) * 100) : 0;
        
        // Memberikan warna teks indikator berdasarkan persentase keaktifan
        $text_color = 'text-danger';
        if ($pct >= 80) {
            $text_color = 'text-success';
        } else if ($pct >= 50) {
            $text_color = 'text-warning';
        }

        echo '<tr>
                <td class="align-middle"><strong>'.strtoupper($rep->jabatan_value).'</strong></td>
                <td class="align-middle">'.$rep->total_karyawan.' Orang</td>
                <td class="align-middle">'.$rep->sudah_mengerjakan.' Orang</td>
                <td class="align-middle"><span class="'.$text_color.'" style="font-size:1.1em; font-weight:bold;">'.$pct.'%</span></td>
              </tr>';
    }
} else {
    echo '<tr><td colspan="4" class="text-center text-muted p-4">Belum ada data pengerjaan atau data jabatan terdaftar belum tersinkronisasi dari HRIS.</td></tr>';
}
echo '</tbody></table>';

echo $OUTPUT->footer();