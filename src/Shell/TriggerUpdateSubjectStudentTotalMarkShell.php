<?php
namespace App\Shell;

use Exception;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\I18n\Time;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

class TriggerUpdateSubjectStudentTotalMarkShell extends Shell {
    public function initialize() {
        parent::initialize();
        $this->loadModel('Institution.Institutions');
    }

    public function main() {
        $pid = getmypid();
        $THREAD_NUM = 4;
        $selectedPeriodId = !empty($this->args[0]) ? $this->args[0] : 0;
        $restartFlag = !empty($this->args[1]) ? $this->args[1] : 0;

        // truncate table if restart is required
        if ($restartFlag == 1) {
            try {
                $this->out($pid.': TRUNCATING updated_subject_students table ('. Time::now() .')');
                $connection = ConnectionManager::get('default');
                $connection->query("TRUNCATE TABLE `updated_subject_students`");
            } catch (Exception $e) {
                $this->out($e->getMessage());
            }
        }

        $this->out($pid.': Triggering UpdateSubjectStudentTotalMarkShell with '.$THREAD_NUM.' Threads ('. Time::now() .')');

        if ($selectedPeriodId != 0) {
            $institutions = $this->Institutions->find()->extract('id')->toArray();

            for ($i=0; $i<count($institutions); $i+=$THREAD_NUM) {
                $file = [];

                for ($j=0; $j<$THREAD_NUM; $j++) {
                    $args = '';
                    $args .= ' '.$selectedPeriodId;
                    $args .= ' '.$institutions[$i+$j];

                    $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateSubjectStudentTotalMark' . $args;
                    $logs = ROOT . DS . 'logs' . DS . 'UpdateSubjectStudentTotalMark'.$j.'.log & echo $!';
                    $shellCmd = $cmd . ' >> ' . $logs;

                    try {
                        $pid = exec($shellCmd);
                        $file[$j] = new File('tmp/shellFiles/'.$pid.'.txt', true);

                    } catch(\Exception $ex) {
                        $this->out($e->getMessage());
                    }
                }

                $processing = true;
                while ($processing) {
                    $totalThreadsRunning = $THREAD_NUM;

                    for ($j=0; $j<$THREAD_NUM; $j++) {
                        $fileExists = $file[$j]->exists();
                        if (!$fileExists) {
                            $totalThreadsRunning -= 1;
                        }
                    }

                    if ($totalThreadsRunning < 1) {
                        $processing = false;
                        break;
                    }

                    sleep(20);
                }
            }
        }

        $this->out($pid.': End triggering UpdateSubjectStudentTotalMarkShell with '.$THREAD_NUM.' Threads ('. Time::now() .')');
    }
}
