<?php
namespace App\Shell;

use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use App\Model\Table\SystemProcessesTable as ProcessCode;

class UpdateWithdrawalStudentShell extends Shell {
    const SLEEP_TIME = 86400;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.StudentWithdraw');
        $this->loadModel('Institution.Students');
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        $this->out('UpdateWithdrawalStudentShell triggered >>>>>>>>>>>> ');
        try {
            $model = $this->StudentWithdraw->registryAlias();
            $pid = getmypid();
            $systemProcess = $this->SystemProcesses
                ->find()
                ->where([$this->SystemProcesses->aliasField('model') => $model])
                ->first();

            $systemProcessId = $systemProcess->id;
            $existingPid = $systemProcess->process_id;

            if ($systemProcess) {
                $this->out('Kill existing system_processes >>>>>>>>>>>> ' . $existingPid);
                $this->SystemProcesses->killProcess($existingPid);
                $this->out('Updating pid >>>>>>>>>>>> ' . $pid);
                $this->SystemProcesses->updatePid($systemProcessId, $pid);
                $this->out('Updating process status to NEW_PROCESS >>>>>>>>>>>> ');
                $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), ProcessCode::NEW_PROCESS);
            } else {
                $systemProcessId = $this->SystemProcesses->addProcess('UpdateWithdrawalStudent', getmypid(), $model);
            }

            $this->out('Retrieving student withdrawal records >>>>>>>>>>>> ');
            $studentWithdrawRecords = $this->getStudentWithdrawRecords();

            if (!empty($studentWithdrawRecords)) {
                $this->out('Student withdrawal records found....');
                $this->out('Updating process status to RUNNING >>>>>>>>>>>> ');
                $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), ProcessCode::RUNNING);

                foreach ($studentWithdrawRecords as $key => $studentWithdrawRecord) {
                    $this->out("Dispatching event to update student status to withdraw for StudentWithdrawTable: " . $studentWithdrawRecord->id);
                    $event = $this->StudentWithdraw->dispatchEvent('Shell.StudentWithdraw.updateStudentStatusId', [$studentWithdrawRecord]);
                    $this->out("End dispatch event for studentWithdrawRecordId " . $studentWithdrawRecord->id);
                }

                $this->out('Updating system process to COMPLETED status');
                $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), ProcessCode::COMPLETED);
            }
            $this->_recursiveCall();
        } catch (\Exception $e) {
            $this->out('Exception Caught: ' . $e->getMessage());
            $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), ProcessCode::ERROR);
            $this->_recursiveCall();
        }
    }

    private function _recursiveCall()
    {
        $this->out('Sleeping.......');
        sleep(self::SLEEP_TIME);
        $this->out('Recursive calling self');
        $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateWithdrawalStudent';
        $logs = ROOT . DS . 'logs' . DS . 'UpdateWithdrawalStudent.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        try {
            $pid = exec($shellCmd);
        } catch(\Exception $e) {
            $this->out('error : ' . __METHOD__ . ' exception when _recursiveCall : ' . $e->getMessage());
        }
        exit();
    }

    private function getStudentWithdrawRecords()
    {
        $model = $this->StudentWithdraw->alias();
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $StudentStatusUpdates = TableRegistry::get('StudentStatusUpdates');
        $today = Time::now();
        $statuses = $StudentStatuses->findCodeList();
        // $workflowSteps = $this->getStepsWithOnApprovalEvent();

        $studentWithdrawRecords = $StudentStatusUpdates
            ->find()
            ->where([
                $StudentStatusUpdates->aliasField('status_id') => $statuses['WITHDRAWN'],
                $StudentStatusUpdates->aliasField('effective_date') => $today,
                $StudentStatusUpdates->aliasField('model') => $model,
            ])
            ->order(['created' => 'asc'])
            ->toArray();
        return $studentWithdrawRecords;
    }
    // most probably can remove because of the new table
    // private function getStepsWithOnApprovalEvent()
    // {
    //     $WorkflowActionsTable = TableRegistry::get('Workflow.WorkflowActions');
    //     $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
    //     $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
    //     $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');

    //     $stepList = $WorkflowActionsTable
    //         ->find()
    //         ->select([
    //             'workflow_step_id' => $WorkflowStepsTable->aliasField('id')
    //         ])
    //         ->where([
    //             $WorkflowActionsTable->aliasField('event_key LIKE') => '%Workflow.onApproval%',
    //             $WorkflowModelsTable->aliasField('model') => 'Institution.StudentWithdraw'
    //         ])
    //         ->innerJoin([$WorkflowStepsTable->alias() => $WorkflowStepsTable->table()], [
    //             $WorkflowStepsTable->aliasField('id = ') . $WorkflowActionsTable->aliasField('next_workflow_step_id')
    //         ])
    //         ->innerJoin([$WorkflowsTable->alias() => $WorkflowsTable->table()], [
    //             $WorkflowsTable->aliasField('id = ') . $WorkflowStepsTable->aliasField('workflow_id')
    //         ])
    //         ->innerJoin([$WorkflowModelsTable->alias() => $WorkflowModelsTable->table()], [
    //             $WorkflowModelsTable->aliasField('id = ') . $WorkflowsTable->aliasField('workflow_model_id')
    //         ])
    //         ->group(['workflow_step_id'])
    //         ->extract('workflow_step_id')
    //         ->toArray();

    //     return $stepList;
    // }
}