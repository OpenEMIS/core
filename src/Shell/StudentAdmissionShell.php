<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use App\Shell\AlertShell;

class StudentAdmissionShell extends AlertShell
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadModel('System.StudentAdmission');
    }

    public function main()
    {
        $processName = $this->processName;
        $feature = $this->featureName;
        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => Time::now()], ['process_name' => $processName]);

        // $dir = new Folder(ROOT . DS . 'tmp'); // path to tmp folder

        // do {
            $rules = $this->getAlertRules($feature);
            foreach ($rules as $rule) {
                if (!empty($rule['security_roles'])) { //check if the alertRule have security role
                    $emailList = $this->getStudentAdmissionEmailList($rule['security_roles']);

                    $email = !empty($emailList) ? implode(', ', $emailList) : ' ';

                    // subject and message for alert email
                    $subject = $rule->subject;
                    $message = $rule->message;
                    // insert record to  the alertLog
                    $this->AlertLogs->insertStudentAdmissionAlertLog($rule->method, $rule->feature, $email, $subject, $message);
                }
            }

            // $filesArray = $dir->find($processName . '.stop');
        // } while (empty($filesArray));
       

    }
}
