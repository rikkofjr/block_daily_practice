<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'block/daily_practice:manage' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW // Memberikan akses ke manager lms
        ]
    ]
];