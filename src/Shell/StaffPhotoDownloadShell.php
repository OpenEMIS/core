<?php

namespace App\Shell;

use ArrayObject;
use DateTime;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;
use ZipArchive;
use Cake\Log\Log;

class StaffPhotoDownloadShell extends Shell
{ //POCOR-7939 refactured
    CONST SLEEP_TIME = 10;
    CONST ACADEMIC_PERIOD_ID = 18;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Security.Users');

    }

    public function main()
    {

        try {
            $connection = ConnectionManager::get('default');
            $model = "Staff";
            $model_table = 'institution_staff';
            $userTable = TableRegistry::get($model_table);
            $model_field = 'staff_id';
            $target_dir = ROOT . DS . "webroot/downloads/{$model}-photo/";   // POCOR-6309
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $report_progress_id = $this->args[0];

            $ReportProgress = TableRegistry::get('Report.ReportProgress');
            $date = date('d-m-Y H:i:s');
            if (!empty($report_progress_id)) {
                $report_progress = $ReportProgress
                    ->get($report_progress_id);
            } else {
                return 0;
            }
            echo "$date: Start Processing $report_progress_id\n";

            $requestData = json_decode($report_progress->params);
            $areaId = $requestData->area_education_id;
            $institutionId = $requestData->institution_id;
            $academicPeriodId = $requestData->academic_period_id;
            $where = [];

            if ($academicPeriodId != 0) {
                if ($model == 'Students') {
                    $where['academic_period_id'] = $academicPeriodId;
                }
                if ($model == 'Staff') {
                    $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                    $periodEntity = $AcademicPeriods->get($academicPeriodId);
                    $startDate = $periodEntity->start_date->format('Y-m-d');
                    $endDate = $periodEntity->end_date->format('Y-m-d');
                    $where['OR'] = [
                        'OR' => [
                            [
                                $userTable->aliasField('end_date') . ' IS NOT NULL',
                                $userTable->aliasField('start_date') . ' <=' => $startDate,
                                $userTable->aliasField('end_date') . ' >=' => $startDate
                            ],
                            [
                                $userTable->aliasField('end_date') . ' IS NOT NULL',
                                $userTable->aliasField('start_date') . ' <=' => $endDate,
                                $userTable->aliasField('end_date') . ' >=' => $endDate
                            ],
                            [
                                $userTable->aliasField('end_date') . ' IS NOT NULL',
                                $userTable->aliasField('start_date') . ' >=' => $startDate,
                                $userTable->aliasField('end_date') . ' <=' => $endDate
                            ]
                        ],
                        [
                            $userTable->aliasField('end_date') . ' IS NULL',
                            $userTable->aliasField('start_date') . ' <=' => $endDate
                        ]
                    ];
                }
            }

            $Institutions = TableRegistry::get('institutions');
            if ($institutionId > 0) {
                $where['institution_id'] = $institutionId;
            } else {
                $areaList = [];
                if ($areaId > 1) {
                    $areaList = $this->getAreaList($areaId);
                }
                if (!empty($areaList)) {
                    $where[$Institutions->aliasField('area_id IN')] = $areaList;
                }
            }
            $Users = TableRegistry::get('security_users');
            $where[$Users->aliasField('photo_content !=')] = '';


            $userQuery = $userTable->find()
                ->innerJoin([$Institutions->alias() => $Institutions->table()],
                    [$Institutions->aliasField('id') . ' = ' . $userTable->aliasField('institution_id')]
                )->innerJoin([$Users->alias() => $Users->table()],
                    [$Users->aliasField('id') . ' = ' . $userTable->aliasField($model_field)])->
                group([
                    $Users->aliasField('id')])
                ->select([
                    'institution_code' => $Institutions->aliasField('code'),
                    'openemis_no' => $Users->aliasField('openemis_no'),
                    'photo_name' => $Users->aliasField('photo_name'),
                    'photo_content' => $Users->aliasField('photo_content')])
                ->where($where);

            $userData = $userQuery->toList();


            if (!empty($userData)) {

                $zipName = $model . 'PhotoReport' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
                $filepath = $target_dir . $zipName;
                $zip = new ZipArchive;
                $zip->open($filepath, ZipArchive::CREATE);
                foreach ($userData as $userRecord) {
                    $this->writePhotoToZip($userRecord, $zip);
                }
                $zip->close();
                $filepathForDB = str_replace('\\', '\\\\', $filepath); //POCOR-8247
                $this->setOKStatus($connection, $filepathForDB, $report_progress_id);
            } else {
                $this->setErrorStatus($connection, $report_progress_id);
            }


        } catch (Exception $e) {
            pr($e->getMessage());
        }
    }

    public function getAreaList($areaId)
    {
        $Areas = TableRegistry::get('areas');
        $areaList = [];
        if ($areaId > 1) {
            array_push($areaList, $areaId);
        }
        $values = array($areaId);

        while (!empty($values)) {
            $areas2 = $Areas
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([$Areas->aliasField('parent_id In (') . implode(",", $values) . ')'])
                ->order([$Areas->aliasField('order')])->toArray();

            if (!empty($areas2)) {
                $areaList2 = array_keys($areas2);
                $areaList = array_merge($areaList, $areaList2);
                $values = $areaList2;
            } else {
                $values = [];
            }
        }
        return $areaList;
    }

    /**
     * @param \Cake\Datasource\ConnectionInterface $connection
     * @param $report_progress_id
     */
    private function setErrorStatus(\Cake\Datasource\ConnectionInterface $connection, $report_progress_id)
    {
        $connection->execute('UPDATE report_progress SET file_path = NULL, status=-2 WHERE id="' . $report_progress_id . '"');
    }

    /**
     * @param \Cake\Datasource\ConnectionInterface $connection
     * @param $fullpath
     * @param $report_progress_id
     */
    private function setOKStatus(\Cake\Datasource\ConnectionInterface $connection, $fullpath, $report_progress_id)
    {
        $connection->execute('UPDATE report_progress SET file_path= "' . $fullpath . '", status=0 WHERE id="' . $report_progress_id . '"');
    }

    /**
     * @param $userRecord
     * @param ZipArchive $zip
     */
    private function writePhotoToZip($userRecord, ZipArchive $zip)
    {
        $target_file = basename($userRecord->photo_name);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $extensions_arr = array("jpg", "jpeg", "png", "gif");

        if (in_array($imageFileType, $extensions_arr)) {
            $photo_name = $userRecord->institution_code . '_' . $userRecord->openemis_no . '.' . $imageFileType;
            $photo_content = $userRecord->photo_content;
            $zip->addFromString($photo_name, $photo_content);
        }
    }
}
