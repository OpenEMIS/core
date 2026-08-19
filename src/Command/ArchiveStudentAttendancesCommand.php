<?php

namespace App\Command;

use App\Command\ArchiveCommandBase;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

//POCOR-8898: concrete archive command for student attendances
class ArchiveStudentAttendancesCommand extends ArchiveCommandBase
{

    protected function getTablesToArchive(): array
    {
        //POCOR-8898
        return [
            'institution_class_attendance_records',
            'institution_student_absences',
            'institution_student_absence_details',
            'student_attendance_marked_records',
            'student_attendance_mark_types',
        ];
    }

    protected function getProcessName(): string
    {
        return 'ArchiveStudentAttendances'; //POCOR-8898
    }

    protected function getFeatureName(): string
    {
        return 'Student Attendances'; //POCOR-8898
    }

}
