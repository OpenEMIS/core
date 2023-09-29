<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use Cake\Log\Log;

use App\Shell\AlertShell;

class AlertScholarshipApplicationShell extends AlertShell
{
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Scholarship.Applications');
        $this->loadModel('Security.Users');
    }

    public function main()
    {
        $model = $this->Applications;
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
                    $scholarshipApplicationRecords = $this->getAlertData($threshold, $model);

                    foreach ($scholarshipApplicationRecords as $key => $vars) {
                        $closedDate = $vars['scholarship']['application_close_date'];
                        $diff = date_diff($closedDate, new Date());
                        $vars['day_difference'] = $diff->days;
                        $vars['threshold'] = $thresholdArray;
                        
                        $assigneeId = $vars['assignee_id'];
                        $assigneeEntity = $this->Users
                            ->find()
                            ->select([
                                $this->Users->aliasField('first_name'),
                                $this->Users->aliasField('middle_name'),
                                $this->Users->aliasField('third_name'),
                                $this->Users->aliasField('last_name'),
                                $this->Users->aliasField('preferred_name'),
                                $this->Users->aliasField('email')
                            ])
                            ->where([
                                $this->Users->aliasField('id') => $assigneeId
                            ])
                            ->first();

                        if (!is_null($assigneeEntity)) {
                            $email = $assigneeEntity->email;
                            $name = $assigneeEntity->name;

                            if (!is_null($email) && $email !== '') {
                                $assigneeEmail = $name . ' <' . $email . '>';
                                $subject = $this->AlertLogs->replaceMessage($feature, $rule->subject, $vars);
                                $message = $this->AlertLogs->replaceMessage($feature, $rule->message, $vars);
                                $this->AlertLogs->insertAlertLog($rule->method, $rule->feature, $assigneeEmail, $subject, $message);
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
