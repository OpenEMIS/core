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
        $dir = new Folder(ROOT . DS . 'webroot' . DS . 'shellprocesses'); // path
        $filesArray = $dir->find('AttendanceAlert.stop');

        while (empty($filesArray)) {
            // no shell stop will keep on running until the shell stop created.
            pr('shell stop not exist');
            $shellCmd = 'ps -ef | grep AttendanceAlert';
            // $shellCmd = 'pkill AttendanceAlert'; // to kill the shell
            $pid = exec($shellCmd);
            pr($pid);
            sleep(5);





            // to check if the shell stop is exist. every shell process will have their own file.
            $filesArray = $dir->find('AttendanceAlert.stop');
        }
    }
}
