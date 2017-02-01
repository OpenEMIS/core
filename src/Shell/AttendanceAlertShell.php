<?php
namespace App\Shell;

use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\Mailer\Email;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

class AttendanceAlertShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Alert.AlertLogs');
        $this->loadModel('Alert.AlertRules');
        $this->loadModel('Alert.Alerts');
        $this->loadModel('Institution.InstitutionStudentAbsences');
    }

    public function main()
    {
        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => Time::now()], ['process_name' => 'AttendanceAlert']);

        $dir = new Folder(ROOT . DS . 'tmp'); // path to tmp folder

        do {
            $data = $this->InstitutionStudentAbsences->getUnexcusedAbsenceData();
            $alias = $this->InstitutionStudentAbsences->alias();

            // inserting data to alert_log table and trigger the sending alert
            // $this->AlertLogs->shellDataProcess($shellData, $alias);

            $rules = $this->AlertRules->find()
                ->contain(['SecurityRoles'])
                ->where([
                    'feature' => 'Attendance',
                    'enabled' => 1
                ])
                ->all();

            foreach ($rules as $rule) {
                foreach ($data as $institutionId => $record) {

                }
            }

            sleep(15); // 15 seconds

            $filesArray = $dir->find('AttendanceAlert.stop');
        } while (empty($filesArray));

        $this->Alerts->updateAll(['process_id' => NULL, 'modified' => Time::now()], ['process_name' => 'AttendanceAlert']);
    }
}
