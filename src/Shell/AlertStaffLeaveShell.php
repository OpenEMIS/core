<?php
namespace App\Shell;

use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;

class AlertStaffLeaveShell extends AlertShell
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Institution.StaffLeave');
    }

    public function main()
    {
        $model = $this->StaffLeave;
        $processName = $this->processName;
        $feature = $this->featureName;

        $this->Alerts->updateAll([
            'process_id' => getmypid(),
            'modified' => FrozenTime::now()
        ], ['process_name' => $processName]);

        $dir = new Folder(ROOT . DS . 'tmp');

        do {
            $rules = $this->getAlertRules($feature);

            foreach ($rules as $rule) {
                $thresholdArray = json_decode($rule->threshold, true);
                $data = $this->getAlertData($rule->threshold, $model);

                foreach ($data as $vars) {
                    $vars['threshold'] = $thresholdArray;
                    $institutionId = $vars['institution']['id'] ?? null;

                    $dateTo = $vars['date_to'] ?? null;
                    if ($dateTo instanceof \DateTimeInterface) {
                        $diff = $dateTo->diff(FrozenDate::now());
                        $vars['day_difference'] = $diff->days;
                    } else {
                        $vars['day_difference'] = null;
                    }

                    if (!empty($rule['security_roles']) && !empty($institutionId)) {
                        $this->insertAlertLogs($rule, $institutionId, $feature, $vars);
                    }
                }
            }

            sleep(10); // Still hardcoded, optional to extract

            $filesArray = $dir->find($processName . '\.stop'); // escape dot for regex
        } while (empty($filesArray));

        $this->Alerts->updateAll([
            'process_id' => null,
            'modified' => FrozenTime::now()
        ], ['process_name' => $processName]);
    }

    public function logAlert($method, $feature, $recipient, $subject, $message): void
    {
        $this->AlertLogs->insertAlertLog($method, $feature, $recipient, $subject, $message);
    }
}
