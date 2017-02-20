<?php
// php addForeignKeys.php -pass=password -db=dev_blank_core

if (count($argv) < 2) {
    echo "Usage : php addForeignKeys.php -pass=xxx -db=xxx \n";
    exit();
}

$host = '127.0.0.1';
$user = 'root';
$port = '3306';

foreach ($argv as $arg) {
    if (startsWith($arg, '-pass=')) $pass = str_replace('-pass=', '', $arg);
    if (startsWith($arg, '-db=')) $db = str_replace('-db=', '', $arg);
}

$excludedTables = implode("','", [
    'assessment_item_results' // excluded because of table partition
]);

$excludedFields = implode("','", [
    'modified_user_id', 'created_user_id', 'parent_id',
    'reference_id', 'next_programme_id', 'field_option_id',
    'next_workflow_step_id', 'filter_id', 'dependent_question_id',
    'parent_form_id', 'pid', 'process_id', 'repeater_id',
    'custom_filter_id', 'infrastructure_custom_filter_id', 'institution_custom_filter_id',
    'previous_institution_id',
    'training_status_id' // no idea what this is used for
]);

$sql = "
SHOW TABLES;
";

// echo $sql;
$addTemplate = "ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s`(`id`);";
$dropTemplate = "ALTER TABLE `%s` DROP FOREIGN KEY `%s`;";
$commentTemplate = "ALTER TABLE `%s` CHANGE `%s` `%s` %s %s COMMENT 'links to %s.id';";
$dropQueries = [];

$connect = new mysqli($host, $user, $pass, $db, $port);
$columnInfo = $connect->query($sql);

$charReplace = ['s' => 'es', 'y' => 'ies'];

$tableReplace = [
    'staffs' => 'security_users',
    'students' => 'security_users',
    'guardians' => 'security_users',
    'trainees' => 'security_users',
    'trainers' => 'security_users',
    'location_institutions' => 'institutions',
    'new_education_grades' => 'education_grades',
    'institution_staffs' => 'institution_staff',
    'bank_branchs' => 'bank_branches',
    'education_level_isceds' => 'education_level_isced',
    'birthplace_areas' => 'area_administratives',
    'address_areas' => 'area_administratives',
    'statuses' => 'workflow_steps',
    'courses' => 'training_courses',
    'institution_repeater_surveies' => 'institution_repeater_surveys',
    'institution_student_surveies' => 'institution_student_surveys',
    'institution_surveies' => 'institution_surveys',
    'prerequisite_training_courses' => 'training_courses',
    'target_populations' => 'staff_position_titles',
    'salary_addition_types' => 'field_option_values',
    'salary_deduction_types' => 'field_option_values',
    'staff_behaviour_categories' => 'field_option_values',
    'staff_leave_types' => 'field_option_values',
    'staff_training_categories' => 'field_option_values',
    'student_behaviour_categories' => 'field_option_values',
    'student_withdraw_reasons' => 'field_option_values',
    'student_transfer_reasons' => 'field_option_values',
    'training_achievement_types' => 'field_option_values',
    'training_course_types' => 'field_option_values',
    'training_field_of_studies' => 'field_option_values',
    'training_levels' => 'field_option_values',
    'training_mode_of_deliveries' => 'field_option_values',
    'training_need_categories' => 'field_option_values',
    'training_priorities' => 'field_option_values',
    'training_providers' => 'field_option_values',
    'training_requirements' => 'field_option_values',
    'training_result_types' => 'field_option_values',
    'training_specialisations' => 'field_option_values'
];



$counter = 1;
// $addFile = fopen('add_foreign_keys.sql', 'w');
// $addCommentFile = fopen('add_foreign_key_comments.sql', 'w');
$fileContent = '<?php
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class %sFixture extends TestFixture
{
    public $import = [\'table\' => \'%s\'];
    public $records = [];
}

';
while ($info = $columnInfo->fetch_array()) {

    $table = $info[0];
    if (!startsWith($table, 'z_')) {
        $words = str_replace('_', '', ucwords($table, '_'));
        $fileName = $words . 'Fixture.php';
        echo 'app.' . $table . "\n";
        if (!file_exists($fileName)) {
            // $file = fopen($fileName, 'w');
            // fwrite($file, sprintf($fileContent, $words, $table));
            // fclose($file);
            // echo 'app.' . $table . "\n";
            // echo $fileName;
            // echo "\n";
        }
        // echo $fileName;
        // echo "\n";
    }

    // $tableName = $info['table_name'];
    // $columnName = $info['column_name'];
    // $nullable = $info['is_nullable'] == 'Yes' ? 'NULL' : 'NOT NULL';
    // $columnType = $info['column_type'];
    // $targetTable = substr($columnName, 0, strlen($columnName)-3);
    // $lastChar = substr($targetTable, -1);
    // if (array_key_exists($lastChar, $charReplace)) {
    //     $targetTable .= $charReplace[$lastChar];
    //     if ($lastChar == 'y') {
    //         $targetTable = str_replace('yies', 'ies', $targetTable);
    //     }
    // } else {
    //     $targetTable .= 's';
    // }
    // if (array_key_exists($targetTable, $tableReplace)) {
    //     $targetTable = $tableReplace[$targetTable];
    // }

    // $constraintName = getConstraintName($tableName, $columnName);
    // $addSQL = sprintf($addTemplate, $tableName, $constraintName, $columnName, $targetTable);
    // $dropSQL = sprintf($dropTemplate, $tableName, $constraintName);
    // $commentSQL = sprintf($commentTemplate, $tableName, $columnName, $columnName, $columnType, $nullable, $targetTable);
    // $dropQueries[] = $dropSQL;

    // fwrite($addFile, $addSQL . "\r\n");
    // fwrite($addCommentFile, $commentSQL . "\r\n");

    // $queryResult = $connect->query($addSQL);
    // $queryResult = true;
    // $counter++;
    // if ($queryResult == false) {
    //     echo $counter . ': ' . $addSQL . "\n";
    //     echo $connect->error . "\n";
    //     break;
    // }

    // echo $addSQL . "\n" . $dropSQL . "\n";
    // echo $targetTable . ' -> ' . $tableName . ' : ' . $lastChar . "\n";
}
// fclose($addFile);
// fclose($addCommentFile);

// $file = fopen('drop_foreign_keys.sql', 'w');
foreach ($dropQueries as $sql) {
    // $connect->query($sql);
    // fwrite($file, $sql . "\r\n");
}
// fclose($file);

$connect->close();

function startsWith($haystack, $needle) {
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

function getConstraintName($table, $field) {
    $split = explode('_', $table);
    $str = [];
    $exclude = ['training_sessions_trainees', 'training_session_trainers'];
    foreach ($split as $s) {
        $str[] = substr($s, 0, 3);
    }
    // echo $table . " -> " . implode('_', $str) . "\n";
    if (in_array($table, $exclude)) {
        return $table . '_' . $field;
    }
    return implode('_', $str) . '_' . $field;
    // return $table . '_' . $field;
}






























