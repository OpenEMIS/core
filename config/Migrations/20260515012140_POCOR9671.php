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
            $backupTable = 'zz_9671_' . $table;

            $this->execute("DROP TABLE IF EXISTS `{$backupTable}`");
            $this->execute("CREATE TABLE `{$backupTable}` LIKE `{$table}`");
            $this->execute("INSERT INTO `{$backupTable}` SELECT * FROM `{$table}`");
        }

        // Table comments
        $this->execute("
            ALTER TABLE `area_programme_institution_areas`
            COMMENT = 'Stores the mapping between area programmes and their associated institution geographic areas.'
        ");

        $this->execute("
            ALTER TABLE `area_programme_institutions`
            COMMENT = 'Links institutions to area programmes, defining programme coverage per institution.'
        ");

        $this->execute("
            ALTER TABLE `assessment_item_student_exemptions`
            COMMENT = 'Records exemptions granted to individual students for specific assessment items.'
        ");

        $this->execute("
            ALTER TABLE `department_staff`
            COMMENT = 'Tracks staff members assigned to institution departments and their roles within each department.'
        ");

        $this->execute("
            ALTER TABLE `education_grades_cumulative_gpa`
            COMMENT = 'Stores cumulative GPA values calculated across multiple education grades for a student.'
        ");

        $this->execute("
            ALTER TABLE `education_grades_gpa`
            COMMENT = 'Stores GPA values computed per education grade level for individual students.'
        ");

        $this->execute("
            ALTER TABLE `gpa_grading_options`
            COMMENT = 'Defines the selectable options (e.g. letter grades, grade points) within a GPA grading scheme.'
        ");

        $this->execute("
            ALTER TABLE `gpa_grading_types`
            COMMENT = 'Defines the types of GPA grading systems available in the platform (e.g. 4.0 scale, percentage-based).'
        ");

        $this->execute("
            ALTER TABLE `institution_class_attendance_records_archived`
            COMMENT = 'Archived copy of class-level attendance records no longer in the active dataset.'
        ");

        $this->execute("
            ALTER TABLE `institution_classes_custom_field_values`
            COMMENT = 'Stores values entered for custom fields attached to institution classes.'
        ");

        $this->execute("
            ALTER TABLE `institution_consumable_transactions`
            COMMENT = 'Records individual transactions (issues, returns, adjustments) for institution consumable stock items.'
        ");

        $this->execute("
            ALTER TABLE `institution_consumables`
            COMMENT = 'Catalogue of consumable items tracked and managed within an institution.'
        ");

        $this->execute("
            ALTER TABLE `institution_departments`
            COMMENT = 'Defines the internal departments within an institution.'
        ");

        $this->execute("
            ALTER TABLE `institution_infrastructure_attachments`
            COMMENT = 'Stores file attachments (documents, images) related to institution infrastructure records.'
        ");

        $this->execute("
            ALTER TABLE `institution_scanned`
            COMMENT = 'Holds scanned document references or metadata linked to institution records.'
        ");

        $this->execute("
            ALTER TABLE `institution_staff_attendances_archived`
            COMMENT = 'Archived staff attendance records moved out of the active attendance tables.'
        ");

        $this->execute("
            ALTER TABLE `institution_staff_leave_entitlements`
            COMMENT = 'Tracks leave entitlement balances for staff members within an institution.'
        ");

        $this->execute("
            ALTER TABLE `institution_student_absence_details_archived`
            COMMENT = 'Archived detailed absence records for students, retained for historical reporting.'
        ");

        $this->execute("
            ALTER TABLE `institution_student_absences_archived`
            COMMENT = 'Archived summary-level student absence records removed from the active dataset.'
        ");

        $this->execute("
            ALTER TABLE `institution_student_admission`
            COMMENT = 'Manages student admission applications and their status within an institution.'
        ");

        $this->execute("
            ALTER TABLE `institution_student_programmes`
            COMMENT = 'Links students to the education programmes they are enrolled in at an institution.'
        ");

        $this->execute("
            ALTER TABLE `institution_students_gpa`
            COMMENT = 'Stores computed GPA values for students within a specific institution context.'
        ");

        $this->execute("
            ALTER TABLE `notice_roles`
            COMMENT = 'Maps system notices to the security roles that are permitted to view or act on them.'
        ");

        $this->execute("
            ALTER TABLE `security_user_notices`
            COMMENT = 'Tracks notices sent to or acknowledged by individual security users.'
        ");

        $this->execute("
            ALTER TABLE `staff_leave_entitlements`
            COMMENT = 'Stores global leave entitlement balances for staff, independent of a specific institution.'
        ");

        $this->execute("
            ALTER TABLE `staff_leave_policies`
            COMMENT = 'Defines leave policies governing entitlement rules, accrual, and carry-over for staff.'
        ");

        $this->execute("
            ALTER TABLE `staff_leave_policy_types`
            COMMENT = 'Classifies the types of leave policies available (e.g. annual, sick, maternity).'
        ");

        $this->execute("
            ALTER TABLE `stock_units`
            COMMENT = 'Defines units of measure used for stock and consumable inventory management.'
        ");

        $this->execute("
            ALTER TABLE `student_admission_custom_field_values`
            COMMENT = 'Stores values entered for custom fields on student admission records.'
        ");

        $this->execute("
            ALTER TABLE `student_attendance_mark_types_archived`
            COMMENT = 'Archived attendance mark type definitions that were previously used to classify student attendance.'
        ");

        $this->execute("
            ALTER TABLE `student_attendance_marked_records_archived`
            COMMENT = 'Archived individual marked attendance records for students.'
        ");

        $this->execute("
            ALTER TABLE `student_custom_filters`
            COMMENT = 'Stores user-defined filter configurations for student search and reporting screens.'
        ");

        $this->execute("
            ALTER TABLE `summary_student_assessments`
            COMMENT = 'Aggregated summary of student assessment results used for reporting and dashboards.'
        ");

        $this->execute("
            ALTER TABLE `summary_student_attendances`
            COMMENT = 'Aggregated summary of student attendance figures used for reporting and dashboards.'
        ");

        $this->execute("
            ALTER TABLE `system_updates`
            COMMENT = 'Logs system-level updates and patch applications applied to the OpenEMIS platform.'
        ");

        $this->execute("
            ALTER TABLE `tmp_merge_log`
            COMMENT = 'Temporary log table used during data merge operations to track merge activity and errors.'
        ");

        $this->execute("
            ALTER TABLE `webhook_logs`
            COMMENT = 'Audit log of outbound webhook requests and the responses received from external endpoints.'
        ");

        $this->execute("
            ALTER TABLE `webhook_queue`
            COMMENT = 'Queue of pending outbound webhook events awaiting dispatch to registered external endpoints.'
        ");
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