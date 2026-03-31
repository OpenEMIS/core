<?php
//POCOR-9598: Replaces GenerateAllStaffReportCardsShell
namespace App\Command;

/**
 * Worker command for Staff profile generation.
 * Route: Institutions > General > Profiles > Staff
 *
 * Replaces the deprecated GenerateAllStaffReportCardsShell.
 * Triggered by StaffProfilesTable::triggerGenerateAllReportCardsShell().
 */
class GenerateAllStaffProfilesCommand extends GenerateProfileCommandBase
{
    public static function defaultName(): string
    {
        return 'generate_staff_profile'; //POCOR-9598
    }

    protected function getSystemProcessName(): string
    {
        return 'GenerateAllStaffReportCards';
    }

    protected function getProcessTableAlias(): string
    {
        return 'ReportCard.StaffReportCardProcesses';
    }

    protected function getProcessSelectFields(): array
    {
        return ['staff_profile_template_id', 'staff_id', 'institution_id', 'academic_period_id'];
    }

    protected function getExcelTableAlias(): string
    {
        return 'CustomExcel.StaffReportCards';
    }

    /**
     * The profile data (status column) lives in staff_report_cards,
     * accessed via Institution.StaffReportCards.
     */
    protected function getProfileDataTableAlias(): string
    {
        return 'Institution.StaffReportCards';
    }

    protected function getLogFileName(): string
    {
        return 'GenerateAllStaffReportCards.log';
    }
}
