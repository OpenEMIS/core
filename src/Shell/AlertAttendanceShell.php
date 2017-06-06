<?php
namespace App\Shell;

use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use App\Shell\AlertShell;

class AlertAttendanceShell extends AlertShell
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Institution.InstitutionStudentAbsences');
    }

    public function main()
    {
        $model = $this->InstitutionStudentAbsences;
        $processName = $this->processName;
        $feature = $this->featureName;

        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => Time::now()], ['process_name' => $processName]);

        $dir = new Folder(ROOT . DS . 'tmp'); // path to tmp folder

        do {
            $rules = $this->getAlertRules($feature);

            foreach ($rules as $rule) {
                $threshold = $rule->threshold;

                $data = $this->getAlertData($threshold, $model);

                foreach ($data as $key => $vars) {
                    $vars['threshold'] = $threshold;

                    if (array_key_exists('institution', $vars)) {
                        $institutionId = $vars['institution']['id'];
                    }

                    if (!empty($rule['security_roles']) && !empty($institutionId)) { //check if the alertRule have security role and institution id
                        $emailList = $this->getEmailList($rule['security_roles'], $institutionId);

                        $email = !empty($emailList) ? implode(', ', $emailList) : ' ';

                        // subject and message for alert email
                        $subject = $this->AlertLogs->replaceMessage($feature, $rule->subject, $vars);
                        $message = $this->AlertLogs->replaceMessage($feature, $rule->message, $vars);

                        // insert record to  the alertLog
                        $this->AlertLogs->insertAlertLog($rule->method, $rule->feature, $email, $subject, $message);
                    }
                }
            }
            sleep(10);

            $filesArray = $dir->find($processName . '.stop');
        } while (empty($filesArray));

        $this->Alerts->updateAll(['process_id' => NULL, 'modified' => Time::now()], ['process_name' => $processName]);
    }
}
