<?php
//POCOR-9598: Replaces GenerateAllClassProfilesShell
namespace App\Command;

use Cake\Console\Arguments;

/**
 * Worker command for Class profile generation.
 * Route: Institutions > General > Profiles > Classes
 *
 * Replaces the deprecated GenerateAllClassProfilesShell.
 * Triggered by ClassesProfilesTable::triggerGenerateAllReportCardsShell().
 */
class GenerateAllClassProfilesCommand extends GenerateProfileCommandBase
{
    public static function defaultName(): string
    {
        return 'generate_class_profile'; //POCOR-9598
    }

    protected function getSystemProcessName(): string
    {
        return 'GenerateAllClassProfiles';
    }

    protected function getProcessTableAlias(): string
    {
        return 'ReportCard.ClassProfileProcesses';
    }

    protected function getProcessSelectFields(): array
    {
        return ['class_profile_template_id', 'institution_id', 'institution_class_id', 'academic_period_id'];
    }

    protected function getExcelTableAlias(): string
    {
        return 'CustomExcel.ClassProfiles';
    }

    /**
     * The profile data (status column) lives in class_profiles,
     * accessed via Institution.ClassProfiles.
     */
    protected function getProfileDataTableAlias(): string
    {
        return 'Institution.ClassProfiles';
    }

    protected function getLogFileName(): string
    {
        return 'GenerateAllClassProfiles.log';
    }

    /**
     * Class profiles support an optional area_id argument (POCOR-7382).
     */
    protected function enrichRecord(array $record, Arguments $args): array
    {
        $areaId = $args->getArgument('area_id') ?? null; //POCOR-9598: optional area_id for class profiles
        if (!empty($areaId)) {
            $record['area_id'] = $areaId;
        }
        return $record;
    }
}
