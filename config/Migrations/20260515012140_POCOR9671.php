<?php

use Migrations\AbstractMigration;

class POCOR9671 extends AbstractMigration
{
    public function up(): void
    {
        // Backup tables
        $tables = [
            'area_programme_institution_areas',
            'area_programme_institutions',
            'assessment_item_student_exemptions',
            'department_staff',
            'education_grades_cumulative_gpa',
            'education_grades_gpa',
            'gpa_grading_options',
            'gpa_grading_types',
            'institution_class_attendance_records_archived',
            'institution_classes_custom_field_values',
            'institution_consumable_transactions',
            'institution_consumables',
            'institution_departments',
            'institution_infrastructure_attachments',
            'institution_scanned',
            'institution_staff_attendances_archived',
            'institution_staff_leave_entitlements',
            'institution_student_absence_details_archived',
            'institution_student_absences_archived',
            'institution_student_admission',
            'institution_student_programmes',
            'institution_students_gpa',
            'notice_roles',
            'security_user_notices',
            'staff_leave_entitlements',
            'staff_leave_policies',
            'staff_leave_policy_types',
            'stock_units',
            'student_admission_custom_field_values',
            'student_attendance_mark_types_archived',
            'student_attendance_marked_records_archived',
            'student_custom_filters',
            'summary_student_assessments',
            'summary_student_attendances',
            'system_updates',
            'tmp_merge_log',
            'webhook_logs',
            'webhook_queue'
        ];

        foreach ($tables as $table) {
            if (!$this->hasTable($table)) {
                continue; // table doesn't exist in this environment — skip backup, move on
            }

            $backupTable = 'zz_9671_' . $table;

            $this->execute("DROP TABLE IF EXISTS `{$backupTable}`");
            $this->execute("CREATE TABLE `{$backupTable}` LIKE `{$table}`");
            $this->execute("INSERT INTO `{$backupTable}` SELECT * FROM `{$table}`");
        }

        // Table comments
        $comments = [
            'area_programme_institution_areas' => 'Stores the mapping between area programmes and their associated institution geographic areas.',
            'area_programme_institutions' => 'Links institutions to area programmes, defining programme coverage per institution.',
            'assessment_item_student_exemptions' => 'Records exemptions granted to individual students for specific assessment items.',
            'department_staff' => 'Tracks staff members assigned to institution departments and their roles within each department.',
            'education_grades_cumulative_gpa' => 'Stores cumulative GPA values calculated across multiple education grades for a student.',
            'education_grades_gpa' => 'Stores GPA values computed per education grade level for individual students.',
            'gpa_grading_options' => 'Defines the selectable options (e.g. letter grades, grade points) within a GPA grading scheme.',
            'gpa_grading_types' => 'Defines the types of GPA grading systems available in the platform (e.g. 4.0 scale, percentage-based).',
            'institution_class_attendance_records_archived' => 'Archived copy of class-level attendance records no longer in the active dataset.',
            'institution_classes_custom_field_values' => 'Stores values entered for custom fields attached to institution classes.',
            'institution_consumable_transactions' => 'Records individual transactions (issues, returns, adjustments) for institution consumable stock items.',
            'institution_consumables' => 'Catalogue of consumable items tracked and managed within an institution.',
            'institution_departments' => 'Defines the internal departments within an institution.',
            'institution_infrastructure_attachments' => 'Stores file attachments (documents, images) related to institution infrastructure records.',
            'institution_scanned' => 'Holds scanned document references or metadata linked to institution records.',
            'institution_staff_attendances_archived' => 'Archived staff attendance records moved out of the active attendance tables.',
            'institution_staff_leave_entitlements' => 'Tracks leave entitlement balances for staff members within an institution.',
            'institution_student_absence_details_archived' => 'Archived detailed absence records for students, retained for historical reporting.',
            'institution_student_absences_archived' => 'Archived summary-level student absence records removed from the active dataset.',
            'institution_student_admission' => 'Manages student admission applications and their status within an institution.',
            'institution_student_programmes' => 'Links students to the education programmes they are enrolled in at an institution.',
            'institution_students_gpa' => 'Stores computed GPA values for students within a specific institution context.',
            'notice_roles' => 'Maps system notices to the security roles that are permitted to view or act on them.',
            'security_user_notices' => 'Tracks notices sent to or acknowledged by individual security users.',
            'staff_leave_entitlements' => 'Stores global leave entitlement balances for staff, independent of a specific institution.',
            'staff_leave_policies' => 'Defines leave policies governing entitlement rules, accrual, and carry-over for staff.',
            'staff_leave_policy_types' => 'Classifies the types of leave policies available (e.g. annual, sick, maternity).',
            'stock_units' => 'Defines units of measure used for stock and consumable inventory management.',
            'student_admission_custom_field_values' => 'Stores values entered for custom fields on student admission records.',
            'student_attendance_mark_types_archived' => 'Archived attendance mark type definitions that were previously used to classify student attendance.',
            'student_attendance_marked_records_archived' => 'Archived individual marked attendance records for students.',
            'student_custom_filters' => 'Stores user-defined filter configurations for student search and reporting screens.',
            'summary_student_assessments' => 'Aggregated summary of student assessment results used for reporting and dashboards.',
            'summary_student_attendances' => 'Aggregated summary of student attendance figures used for reporting and dashboards.',
            'system_updates' => 'Logs system-level updates and patch applications applied to the OpenEMIS platform.',
            'tmp_merge_log' => 'Temporary log table used during data merge operations to track merge activity and errors.',
            'webhook_logs' => 'Audit log of outbound webhook requests and the responses received from external endpoints.',
            'webhook_queue' => 'Queue of pending outbound webhook events awaiting dispatch to registered external endpoints.',
        ];

        foreach ($comments as $table => $comment) {
            if (!$this->hasTable($table)) {
                continue; // table doesn't exist in this environment — skip comment, move on
            }

            $this->execute("ALTER TABLE `{$table}` COMMENT = '{$comment}'");
        }
    }

   public function down(): void
   {
        $tables = [
            'area_programme_institution_areas',
            'area_programme_institutions',
            'assessment_item_student_exemptions',
            'department_staff',
            'education_grades_cumulative_gpa',
            'education_grades_gpa',
            'gpa_grading_options',
            'gpa_grading_types',
            'institution_class_attendance_records_archived',
            'institution_classes_custom_field_values',
            'institution_consumable_transactions',
            'institution_consumables',
            'institution_departments',
            'institution_infrastructure_attachments',
            'institution_scanned',
            'institution_staff_attendances_archived',
            'institution_staff_leave_entitlements',
            'institution_student_absence_details_archived',
            'institution_student_absences_archived',
            'institution_student_admission',
            'institution_student_programmes',
            'institution_students_gpa',
            'notice_roles',
            'security_user_notices',
            'staff_leave_entitlements',
            'staff_leave_policies',
            'staff_leave_policy_types',
            'stock_units',
            'student_admission_custom_field_values',
            'student_attendance_mark_types_archived',
            'student_attendance_marked_records_archived',
            'student_custom_filters',
            'summary_student_assessments',
            'summary_student_attendances',
            'system_updates',
            'tmp_merge_log',
            'webhook_logs',
            'webhook_queue'
        ];

        foreach ($tables as $table) {
            $backupTable = 'zz_9671_' . $table;
            // Restore only if backup exists
            if ($this->hasTable($backupTable)) {
                $this->execute("DROP TABLE IF EXISTS `{$table}`");
                $this->execute("RENAME TABLE `{$backupTable}` TO `{$table}`");
            }
        }
   }
   
}