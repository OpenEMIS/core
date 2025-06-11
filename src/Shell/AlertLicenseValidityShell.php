<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use App\Shell\AlertShell;

class AlertLicenseValidityShell extends AlertShell
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadModel('Staff.Licenses');
    }

    public function main()
    {
        $model = $this->Licenses;
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

                    // license don't have institution_id, check in institution staff if staff is assigned
                    $institutionStaffRecords = $this->Staff
                        ->find()
                        ->contain(['StaffStatuses', 'Institutions'])
                        ->where([
                            $this->Staff->aliasField('staff_id') => $vars['user']['id'],
                            $this->Staff->StaffStatuses->aliasField('code') => 'ASSIGNED'
                        ])
                        ->disableHydration() // POCOR-8533
                        ->all();

                    if (!empty($institutionStaffRecords)) {
                        foreach ($institutionStaffRecords as $institutionStaffObj) {
                            $vars['institution'] = $institutionStaffObj['institution'];
                            $institutionId = $vars['institution']['id'];

                            // add the date difference to $vars.
                            $expiryDate = $vars['expiry_date'];
                            $diff = date_diff($expiryDate, new Date());
                            $diffDays = $diff->days;

                            $vars['day_difference'] = $diffDays;
                            // end

                            if (!empty($rule['security_roles']) && !empty($institutionId)) { //check if the alertRule have security role and institution id
                                $this->insertAlertLogs($rule, $institutionId, $feature, $vars);
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
