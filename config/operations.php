<?php

return [
    'retention' => [
        'successful_automation_logs_days' => env('SUCCESSFUL_AUTOMATION_LOG_RETENTION_DAYS', 30),
        'failed_automation_logs_days' => env('FAILED_AUTOMATION_LOG_RETENTION_DAYS', 90),
        'team_invites_days' => env('TEAM_INVITE_RETENTION_DAYS', 30),
    ],
    'health' => [
        'queue_warning_depth' => env('QUEUE_WARNING_DEPTH', 100),
        'stale_reserved_job_minutes' => env('STALE_RESERVED_JOB_MINUTES', 10),
    ],
];
