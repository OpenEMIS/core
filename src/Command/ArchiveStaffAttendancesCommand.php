<?php

namespace App\Command;

use App\Command\ArchiveCommandBase;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

//POCOR-8898: concrete archive command for staff attendances
class ArchiveStaffAttendancesCommand extends ArchiveCommandBase
{

    protected function getTablesToArchive(): array
    {
        //POCOR-8898
        return [
            'institution_staff_attendances',
            'institution_staff_leave',
        ];
    }

    protected function getProcessName(): string
    {
        return 'ArchiveStaffAttendances'; //POCOR-8898
    }

    protected function getFeatureName(): string
    {
        return 'Staff Attendances'; //POCOR-8898
    }

}
