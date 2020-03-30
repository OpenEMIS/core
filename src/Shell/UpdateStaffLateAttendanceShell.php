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

class UpdateStaffLateAttendanceShell extends Shell
{
    CONST SLEEP_TIME = 10;
    CONST ACADEMIC_PERIOD_ID = 18;

    public function initialize()
    {
        
        parent::initialize();
        $this->loadModel('Institution.InstitutionStaffShifts');
        $this->loadModel('Institution.InstitutionShifts');
       
    }

    public function main($staffId, $date)
    {   
        try {
            $connection = ConnectionManager::get('default');
            $connection->query("UPDATE `institution_shifts`
                INNER JOIN `institution_staff_attendances`
                ON `institution_staff_attendances`.time_in > `institution_shifts`.start_time OR `institution_staff_attendances`.time_in = `institution_shifts`.start_time
                SET `institution_staff_attendances`.absence_type_id = CASE
                WHEN `institution_staff_attendances`.time_in = `institution_shifts`.start_time   THEN 1 
                WHEN `institution_staff_attendances`.time_in > `institution_shifts`.start_time   THEN 3
                END
                WHERE `institution_staff_attendances`.`staff_id` = $staffId 
                       AND `institution_staff_attendances`.date= '" . $date . "' " );
            
            } catch (Exception $e) {
                 pr($e->getMessage());
        }
    }
}
