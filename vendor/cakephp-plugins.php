<?php
$baseDir = dirname(dirname(__FILE__));
return [
    'plugins' => [
        'AcademicPeriod' => $baseDir . '/plugins/AcademicPeriod/',
        'Area' => $baseDir . '/plugins/Area/',
        'Assessment' => $baseDir . '/plugins/Assessment/',
        'ControllerAction' => $baseDir . '/plugins/ControllerAction/',
        'CustomField' => $baseDir . '/plugins/CustomField/',
        'DebugKit' => $baseDir . '/vendor/cakephp/debug_kit/',
        'Education' => $baseDir . '/plugins/Education/',
        'Guardian' => $baseDir . '/plugins/Guardian/',
        'Infrastructure' => $baseDir . '/plugins/Infrastructure/',
        'Institution' => $baseDir . '/plugins/Institution/',
        'Localization' => $baseDir . '/plugins/Localization/',
        'Migrations' => $baseDir . '/vendor/cakephp/migrations/',
        'OpenEmis' => $baseDir . '/plugins/OpenEmis/',
        'Rubric' => $baseDir . '/plugins/Rubric/',
        'Security' => $baseDir . '/plugins/Security/',
        'Staff' => $baseDir . '/plugins/Staff/',
        'Student' => $baseDir . '/plugins/Student/',
        'Survey' => $baseDir . '/plugins/Survey/',
        'User' => $baseDir . '/plugins/User/',
        'Workflow' => $baseDir . '/plugins/Workflow/'
    ]
];
