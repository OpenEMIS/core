<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Console\Shell;

class AutomateAlertSchedulerShell extends Shell
{
    CONST SLEEP_TIME = 10;
    CONST LIMIT = 15;
    

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Alert.Alerts');
    }

    public function main()
    {
       $this->Alerts->alertToProcess();
    }

//    private function alertToProcess()
//    {   
//        
//        $now = Time::now()->format('Y-m-d H:i:s');
//        $from = Time::parse($now)->modify('-15 minutes')->format('Y-m-d H:i:s');
//        $to = Time::parse($now)->modify('+15 minutes')->format('Y-m-d H:i:s');
//                
//                
//                
//        $recordToProcess = $this->Alerts->find()->select(
//                        [
//                            $this->Alerts->aliasField('process_name'),
//                            $this->Alerts->aliasField('next_triggered_on'),
//                            $this->Alerts->aliasField('triggered_on')
//                        ]
//                )
//                ->where([
//                    'next_triggered_on >= ' => $from,
//                    'next_triggered_on <= ' => $to
//                ])
//                ->hydrate(false)
//                ->limit(self::LIMIT);
//
//
//        if (!empty($recordToProcess)) 
//        {
//            
//            foreach ($recordToProcess as $key => $alertProcess) {
//                $nextTriggeredOn = Time::parse($alertProcess['next_triggered_on'])->format('Y-m-d');
//                $today = Time::now()->format('Y-m-d');
//                $currentHrs = Time::now()->format('H:i:s');
//                $minHrs = Time::parse($alertProcess['triggered_on'])->modify('-2 minutes')->format('H:i:s');
//                $maxHrs = Time::parse($alertProcess['triggered_on'])->modify('+6 minutes')->format('H:i:s');
//                $isTriggeredOn = ($today == $nextTriggeredOn AND $currentHrs > $minHrs AND $currentHrs < $maxHrs);
//
//                if ($isTriggeredOn) {
//                    $this->Alerts->stopShell($alertProcess['process_name']); // create and remove the shell stop of the shell
//                    $this->Alerts->triggerAlertFeatureShell($alertProcess['process_name']); // trigger the feature shell
//                    $this->Alerts->UpdateNextTrigger($alertProcess['next_triggered_on'], $alertProcess['process_name']);
//                   sleep(self::SLEEP_TIME);
//                } else if (!$this->Alerts->isShellStopExist($alertProcess['process_name'])) {
//                    $this->Alerts->stopShell($alertProcess['process_name']);
//                }
//            }
//        }
//        
//    }
    
    
    
}
