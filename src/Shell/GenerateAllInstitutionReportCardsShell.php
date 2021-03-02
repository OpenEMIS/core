<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\Time;
use Cake\Console\Shell;

class GenerateAllInstitutionReportCardsShell extends Shell
{
    private $sleepTime = 5;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('CustomExcel.InstitutionReportCards');
        $this->loadModel('ReportCard.InstitutionReportCardProcesses');
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        if (!empty($this->args[0]) && !empty($this->args[1])) {
            $systemProcessId = $this->SystemProcesses->addProcess('GenerateAllInstitutionReportCards', getmypid(), $this->args[0], '', $this->args[1]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);

            $recordToProcess = $this->InstitutionReportCardProcesses->find()
                ->select([
                    $this->InstitutionReportCardProcesses->aliasField('report_card_id'),
                    $this->InstitutionReportCardProcesses->aliasField('institution_id'),
                    $this->InstitutionReportCardProcesses->aliasField('academic_period_id')
                ])
                ->where([
                    $this->InstitutionReportCardProcesses->aliasField('status') => $this->InstitutionReportCardProcesses::NEW_PROCESS
                ])
                ->order([
                    $this->InstitutionReportCardProcesses->aliasField('created'),
                ])
                ->hydrate(false)
                ->first();

            if (!empty($recordToProcess)) {
                $this->out('Generating report card for Institution '.$recordToProcess['institution_id'].' ('. Time::now() .')');
                $this->InstitutionReportCardProcesses->updateAll(['status' => $this->InstitutionReportCardProcesses::RUNNING], [
                    'report_card_id' => $recordToProcess['report_card_id'],
                    'institution_id' => $recordToProcess['institution_id'],
                    'academic_period_id' => $recordToProcess['academic_period_id'],
                ]);

                $excelParams = new ArrayObject([]);
                $excelParams['className'] = 'CustomExcel.InstitutionReportCards';
                $excelParams['requestQuery'] = $recordToProcess;
				
                try {
                    $this->InstitutionReportCards->renderExcelTemplate($excelParams);
                } catch (\Exception $e) {
                    $this->out('Error generating Report Card for Institution ' . $recordToProcess['institution_id']);
                    $this->out($e->getMessage());
                }

                $this->out('End generating report card for Institution '.$recordToProcess['institution_id'].' ('. Time::now() .')');
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
        $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllInstitutionReportCards '.$args[0] . " " . $args[1];
        $logs = ROOT . DS . 'logs' . DS . 'GenerateAllInstitutionReportCards.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
        } catch(\Exception $ex) {
            $this->out('error : ' . __METHOD__ . ' exception when recursiveCallToMyself : '. $ex);
        }
    }
}
