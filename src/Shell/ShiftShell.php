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

        $canCopy = $this->checkIfCanCopy($copyTo, $copyFrom);
        if ($canCopy[0]==true) {
            $this->copyProcess($copyFrom, $copyTo,$canCopy[1]);
        }
        $this->out('End Shift Shell');
    }

    private function checkIfCanCopy($copyTo, $copyFrom)
    {
        $arr=[];
        $canCopy = false;
        $data=[];
        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $InstitutionShifts1 = TableRegistry::get('Institution.InstitutionShifts');
        $count = $InstitutionShifts->find()->where([$InstitutionShifts->aliasField('academic_period_id') => $copyTo])->count();
        // can copy if no shifts created in current acedemic period before
        if ($count == 0) {
            $canCopy = true;
            $ins_shift = $InstitutionShifts->find('all')
                                           ->where(['academic_period_id' =>  $copyTo])
                                           ->toArray();
            $data = [];
                foreach($ins_shift as $ke => $ins_data){
                    $data[] = $ins_data->id;
                }
        }
        elseif($count>0){
             // can copy if some shifts are present in copy to academic period
            $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
            $copiedRecords = $InstitutionShifts->find()
                                ->innerJoin(
                                    ['InstitutionShifts1' => 'institution_shifts'],
                                    [
                                        $InstitutionShifts->aliasField('institution_id') . ' = InstitutionShifts1.institution_id',
                                        $InstitutionShifts->aliasField('location_institution_id') . ' = InstitutionShifts1.location_institution_id',
                                        $InstitutionShifts->aliasField('shift_option_id') . ' = InstitutionShifts1.shift_option_id',
                                        $InstitutionShifts->aliasField('start_time') . ' = InstitutionShifts1.start_time',
                                        $InstitutionShifts->aliasField('end_time') . ' = InstitutionShifts1.end_time'
                                    ]
                                )
                                ->select( [$InstitutionShifts->aliasField('id')])
                                ->where([
                                    $InstitutionShifts->aliasField('academic_period_id') => $copyFrom,
                                    'InstitutionShifts1.academic_period_id' => $copyTo,
                                ])
                                ->toArray();
            if(!empty($copiedRecords)){
                $ids=[];
                foreach($copiedRecords as $key=>$value){
                    $ids[]=$value['id'];
                }
                $allRecords= $InstitutionShifts->find()
                                  ->select( [$InstitutionShifts->aliasField('id')])
                                  ->where([$InstitutionShifts->aliasField('academic_period_id') => $copyFrom,
                                           $InstitutionShifts->aliasField('id not in ')=>$ids])
                                  ->toArray();
                if(!empty( $allRecords)){
                    foreach($allRecords as $key=>$value){
                                $data[]=$value['id'];
                    }
                }
                $canCopy=true;
             }
        }
        $arr[0]=$canCopy;
        $arr[1]=$data;
        return $arr;
    }

    private function copyProcess($copyFrom, $copyTo,$data=null)
    {
        try {
           
            $connection = ConnectionManager::get('default');
            $Institutions = TableRegistry::get('Institution.Institutions');
            $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
         
           if(!empty($data)){
                    $connection->query("INSERT INTO `institution_shifts` (
                        `start_time`, `end_time`, `academic_period_id`, `institution_id`, `location_institution_id`, `shift_option_id`,
                        `previous_shift_id`, `created_user_id`, `created`)
                        SELECT `start_time`, `end_time`, $copyTo, `institution_id`, `location_institution_id`, `shift_option_id`,
                        `id`, `created_user_id`, NOW()
                        FROM `institution_shifts`
                        WHERE `academic_period_id` = $copyFrom and `id` In (" .implode(",",$data). ")");
                }
            }
        

        catch (Exception $e) {
            pr($e->getMessage());
        }
    }
}
