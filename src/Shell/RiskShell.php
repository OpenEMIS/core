<?php
namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;

class RiskShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Start Risk Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];

        $canCopy = $this->checkIfCanCopy($copyTo);
        if ($canCopy) {
            $this->copyProcess($copyFrom, $copyTo);
        }
        $this->out('End Risk Shell');
    }

    private function checkIfCanCopy($copyTo)
    {
        $canCopy = false;

        $RiskTable = TableRegistry::get('Institution.Risks');
        $count = $RiskTable->find()->where([$RiskTable->aliasField('academic_period_id') => $copyTo])->count();
        // can copy if no risk created in current acedemic period before
        if ($count == 0) {
            $canCopy = true;
        }

        return $canCopy;
    }

    private function copyProcess($copyFrom, $copyTo)
    {
        try {
            $RiskTable = TableRegistry::get('Institution.Risks');
            $connection = ConnectionManager::get('default');     
            $risk_res = $connection->execute('SELECT * FROM risks WHERE academic_period_id="'.$copyFrom.'"');
            $risk_data = $risk_res->fetch('assoc');
            $name = $risk_data['name'];
            $risk_id = $risk_data['id'];
            $created_user_id = $risk_data['created_user_id'];

            $risk_arr['name'] = $name;
            $risk_arr['academic_period_id'] = $copyTo;
            $risk_arr['created_user_id'] = $created_user_id;
            $risk_arr['created'] = time();

            $newEntity = $RiskTable->newEntity($risk_arr);
            $RiskTable->save($newEntity);

            $risk_criteria_res = $connection->execute('SELECT * FROM risk_criterias WHERE risk_id='.$risk_id);
            $risk_crieteria_data = $risk_criteria_res ->fetchAll('assoc');
            if(!empty($risk_crieteria_data)){
                foreach($risk_crieteria_data as $key => $value){
                    $crieteria =  $value['criteria'];
                    $operator =  $value['operator'];
                    $threshold =  $value['threshold'];
                    $risk_value =  $value['risk_value'];
                    $created_user_id =  $value['created_user_id'];
                    $connection->execute("INSERT INTO risk_criterias (`criteria`,`operator`,`threshold`,`risk_value`,`risk_id`,`created_user_id`,`created`) VALUES('".$crieteria."', $operator,$threshold,$risk_value,$newEntity->id,$created_user_id,NOW())");

                }
            }

        } catch (Exception $e) {
            pr($e->getMessage());
        }
    }
}
