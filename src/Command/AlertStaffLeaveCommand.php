<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Filesystem\Folder;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;

class AlertStaffLeaveCommand extends AlertCommandBase
{
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $this->setIo($io);
        $this->loadModel('Institution.StaffLeave');
        $model = $this->StaffLeave;

        $processName = $this->processName;
        $feature = $this->featureName;

        $this->Alerts->updateAll(
            ['process_id' => getmypid(), 'modified' => FrozenTime::now()],
            ['process_name' => $processName]
        );

        $stopFile = ROOT . DS . 'tmp' . DS . "{$processName}.stop";

            // your alert processing logic


        do {
            $rules = $this->getAlertRules($feature);

            foreach ($rules as $rule) {
                $thresholdArray = json_decode($rule->threshold ?? '{}', true);
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

            sleep(10);

        } while (!file_exists($stopFile));

        if (file_exists($stopFile)) {
            unlink($stopFile);
            $this->logMsg("🧹 Cleaned up stop file: {$stopFile}");
        }

        $this->Alerts->updateAll(
            ['process_id' => null, 'modified' => FrozenTime::now()],
            ['process_name' => $processName]
        );

        return Command::SUCCESS;
    }

    public function logAlert($method, $feature, $recipient, $subject, $message): void
    {
        $this->AlertLogs->insertAlertLog($method, $feature, $recipient, $subject, $message);
        $this->logMsg("✅ Alert logged via {$method} to {$recipient}");
    }
}
