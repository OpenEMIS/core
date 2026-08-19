<?php

namespace App\Command;

use App\Command\ArchiveCommandBase;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

//POCOR-8898: concrete archive command for student assessments
class ArchiveStudentAssessmentsCommand extends ArchiveCommandBase
{

    protected function getTablesToArchive(): array
    {
        //POCOR-8898
        return [
            'assessment_item_results',
        ];
    }

    protected function getProcessName(): string
    {
        return 'ArchiveStudentAssessments'; //POCOR-8898
    }

    protected function getFeatureName(): string
    {
        return 'Student Assessments'; //POCOR-8898
    }

}
