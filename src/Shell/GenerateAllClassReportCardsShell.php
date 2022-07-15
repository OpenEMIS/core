<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Console\Shell;
/**
 * Class is Shell used for Class report generation
 * @author Anubhav Jain <anubhav.jain@mail.valuecoders.com>
 * 
 */
class GenerateAllClassReportCardsShell extends Shell
{
    private $sleepTime = 5;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('CustomExcel.ClassReportCards');
        $this->loadModel('ReportCard.ClassReportCardProcesses');
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        if (!empty($this->args[0]) && !empty($this->args[1])) {
            $systemProcessId = $this->SystemProcesses->addProcess('GenerateAllClassReportCards', getmypid(), $this->args[0], '', $this->args[1]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);

            $recordToProcess = $this->ClassReportCardProcesses->find()
                ->select([
                    $this->ClassReportCardProcesses->aliasField('report_card_id'),
                    $this->ClassReportCardProcesses->aliasField('institution_id'),
                    $this->ClassReportCardProcesses->aliasField('academic_period_id')
                ])
                ->where([
                    $this->ClassReportCardProcesses->aliasField('status') => $this->ClassReportCardProcesses::NEW_PROCESS
                ])
                ->order([
                    $this->ClassReportCardProcesses->aliasField('created'),
                ])
                ->hydrate(false)
                ->first();

            if (!empty($recordToProcess)) {
                $this->out('Generating report card for Class of Institution '.$recordToProcess['institution_id'].' ('. Time::now() .')');
                $this->ClassReportCardProcesses->updateAll(['status' => $this->ClassReportCardProcesses::RUNNING], [
                    'report_card_id' => $recordToProcess['report_card_id'],
                    'institution_id' => $recordToProcess['institution_id'],
                    'academic_period_id' => $recordToProcess['academic_period_id'],
                ]);

                $excelParams = new ArrayObject([]);
                $excelParams['className'] = 'CustomExcel.ClassReportCards';
                $excelParams['requestQuery'] = $recordToProcess;
				
                try {
                    $this->ClassReportCards->renderExcelTemplate($excelParams);
                } catch (\Exception $e) {
                    $this->out('Error generating Report Card for Class of Institution ' . $recordToProcess['institution_id']);
                    $this->out($e->getMessage());
                }

                $this->out('End generating report card for Class of Institution '.$recordToProcess['institution_id'].' ('. Time::now() .')');
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
        $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllClassReportCards '.$args[0] . " " . $args[1];
        $logs = ROOT . DS . 'logs' . DS . 'GenerateAllClassReportCards.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
        } catch(\Exception $ex) {
            $this->out('error : ' . __METHOD__ . ' exception when recursiveCallToMyself : '. $ex);
        }
    }
}
