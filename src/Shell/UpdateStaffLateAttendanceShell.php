<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

class UpdateStaffLateAttendanceShell extends Shell
{
    CONST SLEEP_TIME = 10;
    CONST ACADEMIC_PERIOD_ID = 18;

    public function initialize(): void
    {
        
        parent::initialize();
        $this->loadModel('Institution.InstitutionStaffShifts');
        $this->loadModel('Institution.InstitutionShifts');
        $this->loadModel('Institution.InstitutionPositions');//POCOR-7225
       
    }

    //POCOR-7225 add institutionId, academicPeriodId , shiftOptionId in shell command, use in where condition
    //POCOR-9138 start
    public function main($staffId, $date, $institutionId, $academicPeriodId, $shiftOptionId){
        try {
            $connection = ConnectionManager::get('default');

            $query = "
                UPDATE institution_staff_attendances AS isa
                INNER JOIN institution_shifts AS isf
                    ON isf.institution_id = :institution_id
                    AND isf.academic_period_id = :academic_period_id
                    AND isf.shift_option_id = :shift_option_id
                    AND isa.date = :date
                SET isa.absence_type_id = 
                    CASE 
                        WHEN TIME(isa.time_in) <= isf.start_time THEN 1  -- Early or On Time
                        WHEN TIME(isa.time_in) > isf.start_time THEN 3   -- Late
                        ELSE isa.absence_type_id                         -- Keep as is
                    END
                WHERE isa.staff_id = :staff_id
            ";

            $result = $connection->execute($query, [
                'staff_id' => $staffId,
                'date' => $date,
                'institution_id' => $institutionId,
                'academic_period_id' => $academicPeriodId,
                'shift_option_id' => $shiftOptionId,
            ]);
            Log::write('debug', 'Updated rows: ' . $result->rowCount());

        } catch (Exception $e) {
            Log::write('error', 'Update Attendance Error: ' . $e->getMessage());
            pr($e->getMessage()); 

        }
    }
     //POCOR-9138 end
}