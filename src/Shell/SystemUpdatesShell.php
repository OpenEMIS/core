<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use App\Shell\AlertShell;

class SystemUpdatesShell extends AlertShell
{
    public function initialize(): void
    {
        parent::initialize();

        $this->SystemUpdates = $this->fetchTable('System.SystemUpdates');
    }

    public function main()
    {
        $processName = $this->processName;
        $feature = $this->featureName;
        $versionNumber = !empty($this->args[0]) ? $this->args[0] : 0;
        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => Time::now()], ['process_name' => $processName]);

        // $dir = new Folder(ROOT . DS . 'tmp'); // path to tmp folder

        // do {
            $rules = $this->getSystemUpdateAlertRules($feature);
            foreach ($rules as $rule) {
                if (!empty($rule['security_roles'])) { //check if the alertRule have security role
                    $emailList = $this->getSystemUpdateEmailList($rule['security_roles']);
                    $email = !empty($emailList) ? implode(', ', $emailList) : ' ';
                    if(isset($versionNumber)){
                        $vars = $versionNumber;
                    }else{
                        $vars = $versionNumber;
                    }
                    // subject and message for alert email
                    $subject = $rule->subject;
                    $message = $rule->message;

                    if (strpos($subject, '${version}') !== false) {
                        $updateSubject = str_replace('${version}', $versionNumber, $subject);
                        $subject = $updateSubject;
                    } else {
                        $subject = $rule->subject;
                    }

                    if (strpos($message, '${version}') !== false) {
                        $updateMessage = str_replace('${version}', $versionNumber, $message);
                        $message = $updateMessage;
                    } else {
                        $message = $rule->message;
                    }
                    // $subject = $this->AlertLogs->replaceMessage($feature, $rule->subject, $vars);
                    // $message = $this->AlertLogs->replaceMessage($feature, $rule->message, $vars);
                    // insert record to  the alertLog
                    $this->AlertLogs->insertSystemUpdateAlertLog($rule->method, $rule->feature, $email, $subject, $message);
                }
            }

            // $filesArray = $dir->find($processName . '.stop');
        // } while (empty($filesArray));
       

    }
}
