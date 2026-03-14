<?php
//POCOR-9598: Replaces GenerateAllInstitutionReportCardsShell
namespace App\Command;

use Cake\Console\Arguments;

/**
 * Worker command for Institution profile generation.
 * Route: Institutions > General > Profiles > Institutions
 *
 * Replaces the deprecated GenerateAllInstitutionReportCardsShell.
 * Triggered by InstitutionsProfileTable::triggerGenerateAllReportCardsShell().
 */
class GenerateAllInstitutionProfilesCommand extends GenerateProfileCommandBase
{
    public static function defaultName(): string
    {
        return 'generate_institution_profile'; //POCOR-9598
    }

    protected function getSystemProcessName(): string
    {
        return 'GenerateAllInstitutionReportCards';
    }

    protected function getProcessTableAlias(): string
    {
        return 'ReportCard.InstitutionReportCardProcesses';
    }

    protected function getProcessSelectFields(): array
    {
        return ['report_card_id', 'institution_id', 'academic_period_id'];
    }

    protected function getExcelTableAlias(): string
    {
        return 'CustomExcel.InstitutionReportCards';
    }

    /**
     * The profile data (status column) lives in institution_report_cards,
     * accessed via Institution.InstitutionReportCards — NOT CustomExcel.InstitutionReportCards
     * which maps to the institutions table.
     */
    protected function getProfileDataTableAlias(): string
    {
        return 'Institution.InstitutionReportCards';
    }

    protected function getLogFileName(): string
    {
        return 'GenerateAllInstitutionReportCards.log';
    }
}
