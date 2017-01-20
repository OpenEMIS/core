<?php
namespace App\Shell;

// use Cake\I18n\Date;
// use Cake\I18n\Time;
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
        $dir = new Folder(ROOT . DS . 'tmp'); // path

        do {
            $shellData = $this->InstitutionStudentAbsences->getUnexcusedAbsenceData();
            $alias = $this->InstitutionStudentAbsences->alias();

            // inserting data to alert_log table and trigger the sending alert
            $this->AlertLogs->shellDataProcess($shellData, $alias);

            sleep(15); // 15 seconds

            $filesArray = $dir->find('AttendanceAlert.stop');
        } while (empty($filesArray));
    }
}
