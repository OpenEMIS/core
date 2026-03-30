<?php

namespace App\Shell;

use ArrayObject;
use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\FrozenTime;
use Report\Model\Table\ReportProgressTable as Process;

class ReportShell extends Shell
{
    public function initialize(): void
    {
        parent::initialize();
        $this->ReportProgress = $this->fetchTable('Report.ReportProgress');
    }

    public function main()
    {

        ini_set('memory_limit', '-1'); //  -1 is for infinite , By default it is 128M & it's not sufficient
        $id = $this->args[0];


        try {
            $entity = $this->ReportProgress->get($id);
           // $this->out('Start Processing Record For Ehteram'.$entity);
            if ($entity->status == 1) {
                $params = json_decode($entity->params, true);
                $format = $params['format'];
                switch ($format) {
                    case 'xlsx':
                        $this->doExcel($entity);
                        break;
                    case 'csv':
                        $this->doCsv($entity);
                        break;
                }
            } else {
                // not new process
            }
        } catch (RecordNotFoundException $ex) {
            echo 'Record not found (' . $id . ')';
        } catch (\Exception $e) {
            $this->printErrorAndSetProcessFault($e, $id);
        }
    }

    public function doExcel($entity)
    {
        $id = $entity->id;
        try {
            $params = json_decode($entity->params, true);
            $feature = $params['feature'];
            $name = $entity->name;

            $date = date('d-m-Y H:i:s');
            if ($entity->module == 'CustomReports') {
                $excelParams = new ArrayObject([]);
                $excelParams['className'] = 'Report.CustomReports';
                $excelParams['requestQuery'] = $params;
                $excelParams['process'] = $entity;

                $table = TableRegistry::getTableLocator()->get($excelParams['className']);
                echo "$date: Start Processing $name\n";
                echo "$date: Process ID: $id; Table Report.CustomReports\n";
                try {
                    $table->renderExcelTemplate($excelParams);
                } catch (\Exception $e) {
                    $this->printErrorAndSetProcessFault($e, $id);
                    throw $e;
                }
                // Mark completed (background shell may not trigger event)
                if ($excelParams->offsetExists('file_path')) {
                    $expiryDate = (new FrozenTime())->addDays(5);
                    $this->ReportProgress->updateAll(
                        [
                            'status' => Process::COMPLETED,
                            'file_path' => $excelParams['file_path'],
                            'expiry_date' => $expiryDate,
                            'modified' => new FrozenTime(),
                        ],
                        ['id' => $id]
                    );
                }
                echo "$date: End Processing $name\n";

            } else {
                $table = TableRegistry::getTableLocator()->get($feature);
                echo "$date: Start Processing $name\n";
                echo "$date: Process ID: $id; Table $feature\n";
                try {
                    $table->generateXLXS(['download' => false, 'process' => $entity]);
                } catch (\Exception $e) {
                    $this->printErrorAndSetProcessFault($e, $id);
                    throw $e;
                }
                echo "$date: End Processing $name\n";
            }

        } catch (\Exception $e) {
            $this->printErrorAndSetProcessFault($e, $id);
        }
    }

    public function doCsv($entity)
    {
        $id = $entity->id;
        try {
            $params = json_decode($entity->params, true);
            $feature = $params['feature'];
            $name = $entity->name;

            if ($entity->module == 'CustomReports') {
                $table = TableRegistry::getTableLocator()->get('Report.CustomReports');
                echo date('d-m-Y H:i:s') . ': Start Processing ' . $name . "\n";
                $table->generateCSV(['process' => $entity, 'requestQuery' => $params]);
                echo date('d-m-Y H:i:s') . ': End Processing ' . $name . "\n";
            }
            /*PCORO-6403 Starts*/
            if ($entity->module == 'InstitutionStatistics') {
                $table = TableRegistry::getTableLocator()->get('Institution.InstitutionStatistics');
                echo date('d-m-Y H:i:s') . ': Start Processing ' . $name . "\n";
                $table->generateCSV(['process' => $entity, 'requestQuery' => $params]);
                echo date('d-m-Y H:i:s') . ': End Processing ' . $name . "\n";
            }
            /*POCOR--6403 Ends*/
        } catch (Exception $e) {
            printErrorAndSetProcessFault($e, $id);
        }
    }

    /**
     * @param $e
     * @param $id
     */
    private function printErrorAndSetProcessFault($e, $id)
    {
        $error = $e->getMessage();
        pr($error);
        echo $error;
        $this->ReportProgress->updateAll(
            ['status' => Process::ERROR, 'error_message' => $error],
            ['id' => $id]
        );
        throw $e;
    }
}
