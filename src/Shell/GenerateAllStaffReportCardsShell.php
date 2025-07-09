<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\I18n\FrozenTime;
use Cake\Console\Shell;

class GenerateAllStaffReportCardsShell extends Shell
{
    private $sleepTime = 5;

    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('CustomExcel.StaffReportCards');
        $this->loadModel('ReportCard.StaffReportCardProcesses');
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        if (!empty($this->args[0]) && !empty($this->args[1])) {
            $systemProcessId = $this->SystemProcesses->addProcess('GenerateAllStaffReportCards', getmypid(), $this->args[0], '', $this->args[1]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);

            $recordToProcess = $this->StaffReportCardProcesses->find()
                ->select([
                    $this->StaffReportCardProcesses->aliasField('staff_profile_template_id'),
                    $this->StaffReportCardProcesses->aliasField('staff_id'),
                    $this->StaffReportCardProcesses->aliasField('institution_id'),
                    $this->StaffReportCardProcesses->aliasField('academic_period_id')
                ])
                ->where([
                    $this->StaffReportCardProcesses->aliasField('status') => $this->StaffReportCardProcesses::NEW_PROCESS
                ])
                ->order([
                    $this->StaffReportCardProcesses->aliasField('created'),
                ])
                ->enableHydration(false)
                ->first();

            if (!empty($recordToProcess)) {
                $this->out('Generating report card for Staff '.$recordToProcess['staff_id'].' ('. FrozenTime::now() .')');
                $this->StaffReportCardProcesses->updateAll(['status' => $this->StaffReportCardProcesses::RUNNING], [
                    'staff_profile_template_id' => $recordToProcess['staff_profile_template_id'],
                    'institution_id' => $recordToProcess['institution_id'],
                    'staff_id' => $recordToProcess['staff_id'],
                ]);

                $excelParams = new ArrayObject([]);
                $excelParams['className'] = 'CustomExcel.StaffReportCards';
                $excelParams['requestQuery'] = $recordToProcess;

                try {
                    $this->StaffReportCards->renderExcelTemplate($excelParams);
                } catch (\Exception $e) {
                    $this->out('Error generating Report Card for Staff ' . $recordToProcess['staff_id']);
                    $this->out($e->getMessage());
                }

                $this->out('End generating report card for Staff '.$recordToProcess['staff_id'].' ('. FrozenTime::now() .')');
                $this->SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $this->SystemProcesses::COMPLETED);
                $this->recursiveCallToMyself($this->args);
            } else {
                $this->SystemProcesses->updateProcess($systemProcessId, FrozenTime::now(), $this->SystemProcesses::COMPLETED);
            }
        }
        try {
            $pid = getmypid();
            if (function_exists('posix_kill')) {
                posix_kill($pid, 9);
            } else {
                exec("kill -15 $pid"); // Works on Unix-like systems
            }
        } catch (\Exception $exception) {
            $this->out($exception->getMessage());
        }
    }

    private function recursiveCallToMyself($args)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake GenerateAllStaffReportCards '.$args[0] . " " . $args[1];
        $logs = ROOT . DS . 'logs' . DS . 'GenerateAllStaffReportCards.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
        } catch(\Exception $ex) {
            $this->out('error : ' . __METHOD__ . ' exception when recursiveCallToMyself : '. $ex);
        }
    }
}
