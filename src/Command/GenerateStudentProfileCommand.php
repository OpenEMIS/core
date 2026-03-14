<?php
//POCOR-9598: New command replacing GenerateAllStudentReportCardsShell
namespace App\Command;

/**
 * Worker command for Student profile generation.
 * Route: Institutions > General > Profiles > Students
 *
 * Replaces the deprecated GenerateAllStudentReportCardsShell.
 * Triggered by StudentProfilesTable::triggerGenerateReportCardsCommand().
 */
class GenerateStudentProfileCommand extends GenerateProfileCommandBase
{
    public static function defaultName(): string
    {
        return 'generate_student_profile'; //POCOR-9598
    }

    protected function getSystemProcessName(): string
    {
        return 'GenerateAllStudentReportCards';
    }

    protected function getProcessTableAlias(): string
    {
        return 'ReportCard.StudentReportCardProcesses';
    }

    protected function getProcessSelectFields(): array
    {
        return ['student_profile_template_id', 'student_id', 'institution_id', 'education_grade_id', 'academic_period_id'];
    }

    protected function getExcelTableAlias(): string
    {
        return 'CustomExcel.StudentReportCards';
    }

    /**
     * The profile data (status column) lives in student_report_cards,
     * accessed via Institution.InstitutionStudentsProfileTemplates.
     */
    protected function getProfileDataTableAlias(): string
    {
        return 'Institution.InstitutionStudentsProfileTemplates';
    }

    protected function getLogFileName(): string
    {
        return 'GenerateStudentProfile.log';
    }
}
