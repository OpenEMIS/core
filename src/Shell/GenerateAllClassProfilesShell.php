<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Console\Shell;
/**
 * Class is Shell used for Class profile report generation
 * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
 * 
 */
class GenerateAllClassProfilesShell extends Shell
{
    private $sleepTime = 5;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('CustomExcel.ClassProfiles');
        $this->loadModel('ReportCard.ClassProfileProcesses');
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        if (!empty($this->args[0]) && !empty($this->args[1])) {
            $systemProcessId = $this->SystemProcesses->addProcess('GenerateAllClassProfiles', getmypid(), $this->args[0], '', $this->args[1]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);

            $recordToProcess = $this->ClassProfileProcesses->find()
                ->select([
                    $this->ClassProfileProcesses->aliasField('class_profile_template_id'),
                    $this->ClassProfileProcesses->aliasField('institution_id'),
                    $this->ClassProfileProcesses->aliasField('institution_class_id'),
                    $this->ClassProfileProcesses->aliasField('academic_period_id')
                ])
                ->where([
                    $this->ClassProfileProcesses->aliasField('status') => $this->ClassProfileProcesses::NEW_PROCESS
                ])
                ->order([
                    $this->ClassProfileProcesses->aliasField('created'),
                ])
                ->hydrate(false)
                ->first();

            if (!empty($recordToProcess)) {
                $this->out('Generating report card for Class '.$recordToProcess['institution_class_id'].' of Institution '.$recordToProcess['institution_id'].' ('. Time::now() .')');
                $this->ClassProfileProcesses->updateAll(['status' => $this->ClassProfileProcesses::RUNNING], [
                    'class_profile_template_id' => $recordToProcess['class_profile_template_id'],
                    'institution_id' => $recordToProcess['institution_id'],
                    'academic_period_id' => $recordToProcess['academic_period_id'],
                    'institution_class_id' => $recordToProcess['institution_class_id']
                ]);

                $excelParams = new ArrayObject([]);
                $excelParams['className'] = 'CustomExcel.ClassProfiles';
                //POCOR-7382 starts
                if(isset($this->args[2]) && !empty($this->args[2])){
                    $areaId = $this->args[2];
                    $recordToProcess['area_id'] = $areaId;
                }//POCOR-7382 ends
                $excelParams['requestQuery'] = $recordToProcess;
				try {
                    $this->ClassProfiles->renderExcelTemplate($excelParams);
                } catch (\Exception $e) {
                    $this->out('Error generating Report Card for Class '.$recordToProcess['institution_class_id'].' of Institution ' . $recordToProcess['institution_id']);
                    $this->out($e->getMessage());
                }

                $this->out('End generating report card for Class '.$recordToProcess['institution_class_id'].' of Institution '.$recordToProcess['institution_id'].' ('. Time::now() .')');
                $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
                $this->recursiveCallToMyself($this->args);
            } else {
                $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
            }
        }
        posix_kill(getmypid(), SIGKILL);
    }

    private function recursiveCallToMyself($args)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllClassProfiles '.$args[0] . " " . $args[1];
        $logs = ROOT . DS . 'logs' . DS . 'GenerateAllClassProfiles.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
        } catch(\Exception $ex) {
            $this->out('error : ' . __METHOD__ . ' exception when recursiveCallToMyself : '. $ex);
        }
    }
}
