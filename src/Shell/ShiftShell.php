<?php
namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;

class ShiftShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Start Shift Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];

        $canCopy = $this->checkIfCanCopy($copyTo);
        if ($canCopy) {
            $this->copyProcess($copyFrom, $copyTo);
        }
        $this->out('End Shift Shell');
    }

    private function checkIfCanCopy($copyTo)
    {
        $canCopy = false;

        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $count = $InstitutionShifts->find()->where([$InstitutionShifts->aliasField('academic_period_id') => $copyTo])->count();
        // can copy if no shifts created in current acedemic period before
        if ($count == 0) {
            $canCopy = true;
        }

        return $canCopy;
    }

    private function copyProcess($copyFrom, $copyTo)
    {
        try {
            $connection = ConnectionManager::get('default');
            $Institutions = TableRegistry::get('Institution.Institutions');
            $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
            $institutions = $Institutions->find('all')->toArray();

            $ins_shift = $InstitutionShifts
            ->find('all')
            ->where(['academic_period_id' =>  $copyTo])
            ->toArray();

            $InsIds = [];
            foreach($ins_shift as $ke => $ins_data){
                $InsIds[] = $ins_data->institution_id;
            }

            foreach($institutions as $k => $Insti){ 
                if (!in_array($Insti->id, $InsIds)) {
                    $connection->query("INSERT INTO `institution_shifts` (
                        `start_time`, `end_time`, `academic_period_id`, `institution_id`, `location_institution_id`, `shift_option_id`,
                        `previous_shift_id`, `created_user_id`, `created`)
                        SELECT `start_time`, `end_time`, $copyTo, `institution_id`, `location_institution_id`, `shift_option_id`,
                        `id`, `created_user_id`, NOW()
                        FROM `institution_shifts`
                        WHERE `academic_period_id` = $copyFrom AND `institution_id` = $Insti->id");
                }
            }

        } catch (Exception $e) {
            pr($e->getMessage());
        }
    }
}
