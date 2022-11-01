<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use Cake\Log\Log;

use App\Shell\AlertShell;

class AlertScholarshipDisbursementShell extends AlertShell
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Scholarship.RecipientPaymentStructureEstimates');
    }

    public function main()
    {
        $model = $this->RecipientPaymentStructureEstimates;
        $processName = $this->processName;
        $feature = $this->featureName;

        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => Time::now()], ['process_name' => $processName]);

        $dir = new Folder(ROOT . DS . 'tmp'); // path to tmp folder

        do {
            $rulesResult = $this->getAlertRules($feature);

            if (!$rulesResult->isEmpty()) {
                $rules = $rulesResult->toArray();

                foreach ($rules as $rule) {
                    $threshold = $rule->threshold;
                    $thresholdArray = json_decode($threshold, true);
                    $scholarshipEstimatesRecords = $this->getAlertData($threshold, $model);

                    foreach ($scholarshipEstimatesRecords as $key => $vars) {
                        $disbursementDate = $vars['estimated_disbursement_date'];
                        $diff = date_diff($disbursementDate, new Date());
                        $vars['day_difference'] = $diff->days;
                        $vars['threshold'] = $thresholdArray;

                        if (!empty($rule['security_roles'])) {
                            $emailList = $this->getEmailList($rule['security_roles']);
                            $email = !empty($emailList) ? implode(', ', $emailList) : ' ';
                            $subject = $this->AlertLogs->replaceMessage($feature, $rule->subject, $vars);
                            $message = $this->AlertLogs->replaceMessage($feature, $rule->message, $vars);
                            $this->AlertLogs->insertAlertLog($rule->method, $rule->feature, $email, $subject, $message);
                        }
                    }

                    

                    
                }
            }
            
            sleep(10);
            $filesArray = $dir->find($processName . '.stop');
        } while (empty($filesArray));

        $this->Alerts->updateAll(['process_id' => NULL, 'modified' => Time::now()], ['process_name' => $processName]);
    }
}
