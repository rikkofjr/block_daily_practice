<?php
defined('MOODLE_INTERNAL') || die();

class block_daily_practice extends block_base {

    public function init() {
        $this->title = get_string('daily_practice:blockname', 'block_daily_practice');
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

        // 2. Cari target Course ID dari tabel kustom mapping
        $course_id = $DB->get_field('block_daily_practice_map', 'course_id', array('jabatan_value' => strtolower(trim($user_jabatan))));

        if ($course_id && $course_id > 0) {
            $today_start = make_timestamp(date('Y'), date('m'), date('d'), 0, 0, 0);
            $today_end   = make_timestamp(date('Y'), date('m'), date('d'), 23, 59, 59);

            // Timeframe Bulan Berjalan (Tanggal 1 Jam 00:00 s/d Akhir Bulan Jam 23:59)
            $month_start = make_timestamp(date('Y'), date('m'), 1, 0, 0, 0);
            $month_end   = make_timestamp(date('Y'), date('m'), date('t'), 23, 59, 59);

            // A. Ambil Kuis Hari Ini
            $sql_quiz_today = "SELECT q.id, cm.id as cmid 
                                 FROM {quiz} q
                                 JOIN {course_modules} cm ON cm.instance = q.id
                                 JOIN {modules} m ON cm.module = m.id
                                WHERE q.course = :courseid 
                                  AND m.name = 'quiz'
                                  AND q.timeclose >= :start AND q.timeclose <= :end";
                         
            $quiz_today = $DB->get_record_sql($sql_quiz_today, array('courseid' => $course_id, 'start' => $today_start, 'end' => $today_end));

            // B. Ambil Seluruh Kuis Bulan Berjalan (Untuk GitHub Grid)
            $sql_month_quizzes = "SELECT q.id, q.name, q.timeclose, cm.id as cmid
                                    FROM {quiz} q
                                    JOIN {course_modules} cm ON cm.instance = q.id
                                    JOIN {modules} m ON cm.module = m.id
                                   WHERE q.course = :courseid 
                                     AND m.name = 'quiz'
                                     AND q.timeclose >= :mstart AND q.timeclose <= :mend
                                ORDER BY q.timeclose ASC";

            $month_quizzes = $DB->get_records_sql($sql_month_quizzes, array('courseid' => $course_id, 'mstart' => $month_start, 'mend' => $month_end));

            // Ambil semua percobaan kuis user di bulan ini
            $user_attempts = $DB->get_records_sql(
                "SELECT quiz, sumgrades FROM {quiz_attempts} WHERE userid = :userid AND state = 'finished'",
                array('userid' => $USER->id)
            );

            // --- CSS GITHUB GRID ---
            $html = '
            <style>
                .dp-grid-container {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 5px;
                    justify-content: left;
                    margin: 12px 0;
                    padding: 10px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    border: 1px solid #e9ecef;
                }
                .dp-box {
                    width: 18px;
                    height: 18px;
                    border-radius: 3px;
                    position: relative;
                    cursor: pointer;
                    transition: transform 0.15s ease;
                }
                .dp-box:hover {
                    transform: scale(1.25);
                    z-index: 10;
                }
                .dp-box-green { background-color: #28a745; }
                .dp-box-grey  { background-color: #ced4da; }
                .dp-box-locked{ background-color: #e9ecef; border: 1px dashed #adb5bd; }
                .dp-box-today {
                    border: 2px solid #0056b3;
                    animation: pulse-border 1.5s infinite;
                }
                @keyframes pulse-border {
                    0% { box-shadow: 0 0 0 0 rgba(0, 86, 179, 0.5); }
                    70% { box-shadow: 0 0 0 5px rgba(0, 86, 179, 0); }
                    100% { box-shadow: 0 0 0 0 rgba(0, 86, 179, 0); }
                }
            </style>
            <div class="daily-practice-block" style="text-align: center; padding: 5px;">';

            // --- RENDER GITHUB CONTRIBUTION GRID ---
            if (!empty($month_quizzes)) {
                $html .= '<div class="d-flex justify-content-between align-items-center px-1 mb-1">';
                $html .= '<small class="font-weight-bold text-muted">Aktivitas ' . date('F Y') . '</small>';
                $html .= '</div>';
                
                $html .= '<div class="dp-grid-container">';
                $done_count = 0;
                $total_month_quizzes = count($month_quizzes);

                foreach ($month_quizzes as $mq) {
                    $is_done = isset($user_attempts[$mq->id]);
                    $is_today_quiz = ($mq->timeclose >= $today_start && $mq->timeclose <= $today_end);
                    $is_future = ($mq->timeclose > $today_end);
                    $date_str = date('d M', $mq->timeclose);

                    if ($is_done) {
                        $done_count++;
                        $status_class = 'dp-box-green';
                        $tooltip = "Latihan {$date_str}: Selesai (Nilai: " . round($user_attempts[$mq->id]->sumgrades, 1) . ")";
                    } else if ($is_future) {
                        $status_class = 'dp-box-locked';
                        $tooltip = "Latihan {$date_str}: Belum Terbuka";
                    } else {
                        $status_class = 'dp-box-grey';
                        $tooltip = "Latihan {$date_str}: Belum Dikerjakan";
                    }

                    if ($is_today_quiz && !$is_done) {
                        $status_class .= ' dp-box-today';
                    }

                    $html .= '<div class="dp-box ' . $status_class . '" title="' . s($tooltip) . '" data-toggle="tooltip"></div>';
                }
                $html .= '</div>';
                
                $html .= '<div class="small text-muted mb-3" style="font-size: 11px;">';
                $html .= 'Tercapai: <strong>' . $done_count . ' / ' . $total_month_quizzes . '</strong> Latihan Bulan Ini';
                $html .= '</div>';
            }

            // --- RENDER STATUS HARI INI & POPUP MODAL ---
            if ($quiz_today) {
                $has_attempted = $DB->record_exists_select('quiz_attempts', 
                    'quiz = :quizid AND userid = :userid AND timestart >= :start', 
                    array('quizid' => $quiz_today->id, 'userid' => $USER->id, 'start' => $today_start)
                );

                if (!$has_attempted) {
                    $quiz_url = new moodle_url('/mod/quiz/view.php', array('id' => $quiz_today->cmid));
                    
                    // Render Tombol Utama di Blok Dashboard
                    $html .= '<p class="small text-danger font-weight-bold mb-1">Luangkan waktu sejenak untuk jadi lebih baik setiap hari!</p>';
                    $html .= '<a href="' . $quiz_url . '" class="btn btn-primary btn-sm w-100 font-weight-bold shadow-sm">🚀 Buka Daily Practice</a>';
                    
                    // --- STRATEGI POPUP DENGAN MOODLE CORE MODAL AMD ---
                    $enable_popup = get_config('block_daily_practice', 'enable_popup');
                    if ($enable_popup && ($PAGE->pagelayout === 'mydashboard' || $PAGE->pagelayout === 'dashboard')) {
                        
                        $modal_title = "Daily practice menantimu hari ini";
                        $modal_body = '<div class="text-center" style="padding: 15px 10px;">';
                        //$modal_body .= '<p style="font-size: 16px;">Halo! Jabatan Anda terdeteksi sebagai <small>'.strtoupper(s($user_jabatan)).'</small>.</p>';
                        $modal_body .= '<p class="text-muted">Yuk, luangkan beberapa menit untuk terus berkembang!</p>';
                        $modal_body .= '<a href="' . $quiz_url . '" class="btn btn-primary btn-lg w-100 font-weight-bold shadow-sm my-3" style="display:block;background-color:#F4197D;">MULAI KERJAKAN SEKARANG</a>';
                        $modal_body .= '</div>';

                        $PAGE->requires->js_call_amd('core/modal_factory', 'create', array(
                            array(
                                'type' => 'DEFAULT',
                                'title' => $modal_title,
                                'body' => $modal_body,
                                'large' => false
                            )
                        ));

                        $PAGE->requires->js_init_code("
                            require(['core/modal_factory', 'core/modal_events', 'jquery'], function(ModalFactory, ModalEvents, $) {
                                ModalFactory.create({
                                    type: ModalFactory.types.DEFAULT,
                                    title: '" . addslashes($modal_title) . "',
                                    body: '" . addslashes($modal_body) . "',
                                    backdrop: 'static',
                                    keyboard: false
                                }).then(function(modal) {
                                    modal.show();
                                    modal.getRoot().find('[data-action=\"cancel\"]').remove();
                                    modal.getRoot().find('[data-action=\"save\"]').remove();
                                });
                                $(function () {
                                  $('[data-toggle=\"tooltip\"]').tooltip();
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