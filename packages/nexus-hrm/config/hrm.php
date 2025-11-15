<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Employee Management Configuration
    |--------------------------------------------------------------------------
    */
    'employee' => [
        'probation_period_days' => env('HRM_PROBATION_PERIOD_DAYS', 90),
        'notice_period_days' => env('HRM_NOTICE_PERIOD_DAYS', 30),
        'retirement_age' => env('HRM_RETIREMENT_AGE', 60),
        'auto_generate_employee_number' => env('HRM_AUTO_EMPLOYEE_NUMBER', true),
        'employee_number_prefix' => env('HRM_EMPLOYEE_NUMBER_PREFIX', 'EMP'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Leave Management Configuration
    |--------------------------------------------------------------------------
    */
    'leave' => [
        'enable_negative_balance' => env('HRM_LEAVE_NEGATIVE_BALANCE', false),
        'max_negative_balance_days' => env('HRM_LEAVE_MAX_NEGATIVE_DAYS', 5),
        'enable_carry_forward' => env('HRM_LEAVE_CARRY_FORWARD', true),
        'max_carry_forward_days' => env('HRM_LEAVE_MAX_CARRY_FORWARD', 10),
        'enable_pro_rata' => env('HRM_LEAVE_PRO_RATA', true),
        'financial_year_start_month' => env('HRM_LEAVE_FY_START_MONTH', 1), // January
        'require_workflow_approval' => env('HRM_LEAVE_REQUIRE_APPROVAL', true),
        'auto_approve_threshold_days' => env('HRM_LEAVE_AUTO_APPROVE_DAYS', 0), // 0 = always require approval
    ],

    /*
    |--------------------------------------------------------------------------
    | Attendance & Time Tracking Configuration
    |--------------------------------------------------------------------------
    */
    'attendance' => [
        'enable_geolocation' => env('HRM_ATTENDANCE_GEOLOCATION', false),
        'enable_overtime' => env('HRM_ATTENDANCE_OVERTIME', true),
        'overtime_rate_multiplier' => env('HRM_OVERTIME_RATE', 1.5),
        'standard_work_hours_per_day' => env('HRM_STANDARD_HOURS', 8),
        'break_time_minutes' => env('HRM_BREAK_MINUTES', 60),
        'grace_period_minutes' => env('HRM_GRACE_PERIOD', 15), // Tardiness grace period
        'enable_shift_management' => env('HRM_ENABLE_SHIFTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Management Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'enable_360_feedback' => env('HRM_ENABLE_360_FEEDBACK', true),
        'review_frequency' => env('HRM_REVIEW_FREQUENCY', 'annual'), // annual, biannual, quarterly
        'enable_goal_tracking' => env('HRM_ENABLE_GOALS', true),
        'enable_calibration' => env('HRM_ENABLE_CALIBRATION', false),
        'rating_scale_max' => env('HRM_RATING_SCALE_MAX', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Disciplinary & Grievance Configuration
    |--------------------------------------------------------------------------
    */
    'disciplinary' => [
        'enable_workflow' => env('HRM_DISCIPLINARY_WORKFLOW', true),
        'auto_escalate_days' => env('HRM_DISCIPLINARY_ESCALATE_DAYS', 7),
        'require_evidence_attachment' => env('HRM_DISCIPLINARY_REQUIRE_EVIDENCE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Training & Development Configuration
    |--------------------------------------------------------------------------
    */
    'training' => [
        'enable_certification_tracking' => env('HRM_ENABLE_CERTIFICATIONS', true),
        'certification_expiry_reminder_days' => env('HRM_CERT_REMINDER_DAYS', 30),
        'enable_training_budget' => env('HRM_ENABLE_TRAINING_BUDGET', true),
        'annual_training_budget_per_employee' => env('HRM_TRAINING_BUDGET', 5000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit & Compliance Configuration
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enable_activity_logging' => env('HRM_ENABLE_AUDIT_LOG', true),
        'log_all_changes' => env('HRM_LOG_ALL_CHANGES', true),
        'retention_days' => env('HRM_AUDIT_RETENTION_DAYS', 2555), // 7 years
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Configuration
    |--------------------------------------------------------------------------
    */
    'integration' => [
        'backoffice_enabled' => env('HRM_BACKOFFICE_INTEGRATION', true),
        'workflow_enabled' => env('HRM_WORKFLOW_INTEGRATION', true),
        'payroll_enabled' => env('HRM_PAYROLL_INTEGRATION', false),
        'audit_log_enabled' => env('HRM_AUDIT_LOG_INTEGRATION', true),
    ],
];
