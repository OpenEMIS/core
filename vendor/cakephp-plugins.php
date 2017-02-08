<?php
$baseDir = dirname(dirname(__FILE__));
return [
    'plugins' => [
        'ADmad/JwtAuth' => $baseDir . '/vendor/admad/cakephp-jwt-auth/',
        'API' => $baseDir . '/plugins/API/',
        'AcademicPeriod' => $baseDir . '/plugins/AcademicPeriod/',
        'Alert' => $baseDir . '/plugins/Alert/',
        'Angular' => $baseDir . '/plugins/Angular/',
        'Area' => $baseDir . '/plugins/Area/',
        'Assessment' => $baseDir . '/plugins/Assessment/',
        'Bake' => $baseDir . '/vendor/cakephp/bake/',
        'Cache' => $baseDir . '/plugins/Cache/',
        'Competency' => $baseDir . '/plugins/Competency/',
        'Configuration' => $baseDir . '/plugins/Configuration/',
        'ControllerAction' => $baseDir . '/plugins/ControllerAction/',
        'CustomExcel' => $baseDir . '/plugins/CustomExcel/',
        'CustomField' => $baseDir . '/plugins/CustomField/',
        'DebugKit' => $baseDir . '/vendor/cakephp/debug_kit/',
        'Directory' => $baseDir . '/plugins/Directory/',
        'Education' => $baseDir . '/plugins/Education/',
        'Error' => $baseDir . '/plugins/Error/',
        'Examination' => $baseDir . '/plugins/Examination/',
        'FieldOption' => $baseDir . '/plugins/FieldOption/',
        'Health' => $baseDir . '/plugins/Health/',
        'Import' => $baseDir . '/plugins/Import/',
        'Infrastructure' => $baseDir . '/plugins/Infrastructure/',
        'Institution' => $baseDir . '/plugins/Institution/',
        'InstitutionCustomField' => $baseDir . '/plugins/InstitutionCustomField/',
        'InstitutionRepeater' => $baseDir . '/plugins/InstitutionRepeater/',
        'Localization' => $baseDir . '/plugins/Localization/',
        'Log' => $baseDir . '/plugins/Log/',
        'Map' => $baseDir . '/plugins/Map/',
        'Migrations' => $baseDir . '/vendor/cakephp/migrations/',
        'OpenEmis' => $baseDir . '/plugins/OpenEmis/',
        'Report' => $baseDir . '/plugins/Report/',
        'Rest' => $baseDir . '/plugins/Rest/',
        'Restful' => $baseDir . '/vendor/korditpteltd/kd-cakephp-restful/',
        'Rubric' => $baseDir . '/plugins/Rubric/',
        'SSO' => $baseDir . '/vendor/korditpteltd/kd-cakephp-sso/',
        'Security' => $baseDir . '/plugins/Security/',
        'Staff' => $baseDir . '/plugins/Staff/',
        'StaffCustomField' => $baseDir . '/plugins/StaffCustomField/',
        'Student' => $baseDir . '/plugins/Student/',
        'StudentCustomField' => $baseDir . '/plugins/StudentCustomField/',
        'Survey' => $baseDir . '/plugins/Survey/',
        'System' => $baseDir . '/plugins/System/',
        'Textbook' => $baseDir . '/plugins/Textbook/',
        'Training' => $baseDir . '/plugins/Training/',
        'User' => $baseDir . '/plugins/User/',
        'Workflow' => $baseDir . '/plugins/Workflow/'
    ]
];