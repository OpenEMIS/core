<?php

namespace App\Command;

use App\Command\ArchiveCommandBase;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

//POCOR-8898: concrete archive command for student report cards
class ArchiveStudentReportCardsCommand extends ArchiveCommandBase
{

    protected function getTablesToArchive(): array
    {
        //POCOR-8898
        return [
            'institution_students_report_cards',
        ];
    }

    protected function getProcessName(): string
    {
        return 'ArchiveStudentReportCards'; //POCOR-8898
    }

    protected function getFeatureName(): string
    {
        return 'Student Report Cards'; //POCOR-8898
    }

}
