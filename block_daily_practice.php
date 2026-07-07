<?php
defined('MOODLE_INTERNAL') || die();

class block_daily_practice extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_daily_practice');
    }

    public function has_config() {
        return true;
    }

    public function get_content() {
        global $USER, $DB, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        // 1. Ambil shortname dari setting global
        $shortname = get_config('block_daily_practice', 'profile_field_shortname');
        if (empty($shortname)) {
            $shortname = 'jabatan';
        }
        
        $sql_profile = "SELECT uid.data 
                        FROM {user_info_data} uid
                        JOIN {user_info_field} uif ON uid.fieldid = uif.id
                        WHERE uid.userid = :userid AND uif.shortname = :shortname";
                        
        $user_jabatan = $DB->get_field_sql($sql_profile, array('userid' => $USER->id, 'shortname' => $shortname));

        if (!$user_jabatan) {
            return $this->content;
        }

        // 2. Cari target Course ID dari tabel kustom mapping (pastikan lowercase)
        $course_id = $DB->get_field('block_daily_practice_map', 'course_id', array('jabatan_value' => strtolower(trim($user_jabatan))));

        if ($course_id && $course_id > 0) {
            $today_start = make_timestamp(date('Y'), date('m'), date('d'), 0, 0, 0);
            $today_end = make_timestamp(date('Y'), date('m'), date('d'), 23, 59, 59);

            $sql_quiz = "SELECT q.id, cm.id as cmid 
                         FROM {quiz} q
                         JOIN {course_modules} cm ON cm.instance = q.id
                         JOIN {modules} m ON cm.module = m.id
                         WHERE q.course = :courseid 
                           AND m.name = 'quiz'
                           AND q.timeclose >= :start AND q.timeclose <= :end";
                         
            $quiz_today = $DB->get_record_sql($sql_quiz, array('courseid' => $course_id, 'start' => $today_start, 'end' => $today_end));

            $html = '<div class="daily-practice-block" style="text-align: center; padding: 10px;">';
            
            if ($quiz_today) {
                $has_attempted = $DB->record_exists_select('quiz_attempts', 
                    'quiz = :quizid AND userid = :userid AND timestart >= :start', 
                    array('quizid' => $quiz_today->id, 'userid' => $USER->id, 'start' => $today_start)
                );

                if (!$has_attempted) {
                    $quiz_url = new moodle_url('/mod/quiz/view.php', array('id' => $quiz_today->cmid));
                    
                    // Render Tampilan Tombol Cadangan Tetap di Dalam Blok Dashboard
                    $html .= '<p class="small text-muted">Anda memiliki latihan harian yang belum selesai.</p>';
                    $html .= '<a href="' . $quiz_url . '" class="btn btn-primary btn-sm w-100 font-weight-bold shadow-sm">Buka Daily Practice</a>';
                    
                    // --- STRATEGI POPUP DENGAN MOODLE CORE MODAL AMD (KOMPATIBEL DENGAN TEMA EDLY) ---
                    $enable_popup = get_config('block_daily_practice', 'enable_popup');
                    if ($enable_popup && ($PAGE->pagelayout === 'mydashboard' || $PAGE->pagelayout === 'dashboard')) {
                        
                        // Menyiapkan konten teks di dalam popup modal
                        $modal_title = "Isi Daily Practice Hari Ini";
                        $modal_body = '<div class="text-center" style="padding: 15px 10px;">';
                        $modal_body .= '<p style="font-size: 16px;">Halo! Jabatan Anda terdeteksi sebagai <strong>'.strtoupper(s($user_jabatan)).'</strong>.</p>';
                        $modal_body .= '<p class="text-muted">Anda memiliki 1 latihan harian wajib yang belum diselesaikan hari ini.</p>';
                        $modal_body .= '<a href="' . $quiz_url . '" class="btn btn-primary btn-lg w-100 font-weight-bold shadow-sm my-3" style="display:block;">MULAI KERJAKAN SEKARANG</a>';
                        $modal_body .= '</div>';

                        // Memanggil pustaka Modal Factory resmi milik Moodle Core via JavaScript AMD
                        // Metode ini melompati ketergantungan script HTML Bootstrap mentah dan langsung dieksekusi oleh core engine tema Edly
                        $PAGE->requires->js_call_amd('core/modal_factory', 'create', array(
                            array(
                                'type' => 'DEFAULT',
                                'title' => $modal_title,
                                'body' => $modal_body,
                                'large' => false
                            )
                        ));

                        // Script pelengkap untuk memaksa modal langsung tampil terbuka (show) begitu instans terbentuk
                        $PAGE->requires->js_init_code("
                            require(['core/modal_factory', 'core/modal_events'], function(ModalFactory, ModalEvents) {
                                ModalFactory.create({
                                    type: ModalFactory.types.DEFAULT,
                                    title: '" . addslashes($modal_title) . "',
                                    body: '" . addslashes($modal_body) . "',
                                    backdrop: 'static',
                                    keyboard: false
                                }).then(function(modal) {
                                    modal.show();
                                    // Hilangkan tombol close bawaan Moodle di footer agar fokus ke tombol utama Anda
                                    modal.getRoot().find('[data-action=\"cancel\"]').remove();
                                    modal.getRoot().find('[data-action=\"save\"]').remove();
                                });
                            });
                        ");
                    }
                    
                } else {
                    $html .= '<p class="text-success" style="font-size: 20px; margin-bottom:5px;">✅</p>';
                    $html .= '<p class="text-muted small m-0">Latihan hari ini selesai!</p>';
                }
            } else {
                $html .= '<p class="text-muted small m-0">Tidak ada latihan terjadwal hari ini.</p>';
            }
            $html .= '</div>';
            
            $this->content->text = $html;
        }

        return $this->content;
    }

    public function applicable_formats() {
        return array('my' => true, 'site-index' => true);
    }
}