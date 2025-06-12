<?php
namespace App\Shell;

use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

class AlertAttendanceShell extends AlertShell
{
    public function initialize(): void
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

                    if (isset($vars['institution'])) {
                        $institutionId = $vars['institution']['id'];
                    }

                    if (!empty($rule['security_roles']) && !empty($institutionId)) { //check if the alertRule have security role and institution id
                        $this->insertAlertLogs($rule, $institutionId, $feature, $vars);
                    }
                }
            }
            sleep(10);

            $filesArray = $dir->find($processName . '.stop');
        } while (empty($filesArray));

        $this->Alerts->updateAll(['process_id' => NULL, 'modified' => Time::now()], ['process_name' => $processName]);
    }
}
