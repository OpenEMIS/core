<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use App\Shell\AlertShell;

class AlertStaffEmploymentShell extends AlertShell
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Staff.Employments');
    }

    public function main()
    {
        $model = $this->Employments;
        $processName = $this->processName;
        $feature = $this->featureName;

        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => Time::now()], ['process_name' => $processName]);

        $dir = new Folder(ROOT . DS . 'tmp'); // path to tmp folder

        do {
            $rules = $this->getAlertRules($feature);

            foreach ($rules as $rule) {
                $threshold = $rule->threshold;
                $thresholdArray = json_decode($threshold, true);

                $data = $this->getAlertData($threshold, $model);

                foreach ($data as $key => $vars) {
                    $vars['threshold'] = $thresholdArray;

                    // employment table don't have institution_id, check in institution staff if staff is assigned
                    $institutionStaffRecords = $this->Staff
                        ->find()
                        ->contain(['StaffStatuses', 'Institutions'])
                        ->where([
                            $this->Staff->aliasField('staff_id') => $vars['user']['id'],
                            $this->Staff->StaffStatuses->aliasField('code') => 'ASSIGNED'
                        ])
                        ->hydrate(false)
                        ->all();

                    if (!empty($institutionStaffRecords)) {
                        foreach ($institutionStaffRecords as $institutionStaffObj) {
                            $vars['institution'] = $institutionStaffObj['institution'];
                            $institutionId = $vars['institution']['id'];

                            // add the employment period to $vars.
                            $employmentDate = $vars['employment_date'];
                            $diff = date_diff(new Date(), $employmentDate);
                            $diffDays = $diff->days;

                            $vars['employment_period'] = $diffDays;
                            // end of adding age to $vars

                            if (!empty($rule['security_roles']) && !empty($institutionId)) { //check if the alertRule have security role and institution id
                                $emailList = $this->getEmailList($rule['security_roles'], $institutionId);

                                $email = !empty($emailList) ? implode(', ', $emailList) : ' ';

                                // subject and message for alert email
                                $subject = $this->AlertLogs->replaceMessage($feature, $rule->subject, $vars);
                                $message = $this->AlertLogs->replaceMessage($feature, $rule->message, $vars);

                                // insert record to  the alertLog if email available
                                $this->AlertLogs->insertAlertLog($rule->method, $rule->feature, $email, $subject, $message);
                            }
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
