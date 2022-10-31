<?php
namespace App\Shell;

use Exception;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\I18n\Time;
use Cake\Filesystem\File;

class TriggerUpdateSubjectStudentTotalMarkShell extends Shell {
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.Institutions');
    }

    public function main()
    {
        $selectedPeriodId = !empty($this->args[0]) ? $this->args[0] : 0;
        $restartFlag = !empty($this->args[1]) ? $this->args[1] : 0;

        $pid = getmypid();
        $THREAD_NUM = 4;

        // temporary table to log institution_subject_students that have been processed
        try {
            $connection = ConnectionManager::get('default');
            $connection->query("CREATE TABLE IF NOT EXISTS `updated_subject_students` (`id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL, `student_id` int(11) NOT NULL, `education_subject_id` int(11) NOT NULL, `institution_class_id` int(11) NOT NULL, `institution_id` int(11) NOT NULL, `academic_period_id` int(11) NOT NULL, PRIMARY KEY (`student_id`, `education_subject_id`, `institution_class_id`, `institution_id`, `academic_period_id`), KEY `student_id` (`student_id`), KEY `education_subject_id` (`education_subject_id`), KEY `institution_class_id` (`institution_class_id`), KEY `institution_id` (`institution_id`), KEY `academic_period_id` (`academic_period_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Exception $e) {
            $this->out($e->getMessage());
        }

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
            $institutions = $this->Institutions->find()
                ->order([$this->Institutions->aliasField('id') => 'DESC'])
                ->extract('id')
                ->toArray();

            $files = [];
            for ($i=0; $i<$THREAD_NUM; $i++) {
                $files[$i] = new File('webroot/shellFiles/thread'.$i.'.log');
            }

            while (!empty($institutions)) {
                for ($i=0; $i<$THREAD_NUM; $i++) {
                    if (!$files[$i]->exists()) {
                        $institutionId = array_pop($institutions);
                        if (!empty($institutionId)) {
                            $args = '';
                            $args .= ' '.$i; // thread number
                            $args .= ' '.$selectedPeriodId;
                            $args .= ' '.$institutionId;

                            $cmd = ROOT . DS . 'bin' . DS . 'cake UpdateSubjectStudentTotalMark' . $args;
                            $logs = ROOT . DS . 'logs' . DS . 'UpdateSubjectStudentTotalMark'.$i.'.log & echo $!';
                            $shellCmd = $cmd . ' >> ' . $logs;

                            try {
                                $pid = exec($shellCmd);
                                $files[$i]->create();

                            } catch(\Exception $ex) {
                                $this->out($e->getMessage());
                            }
                        }
                    }
                }

                sleep(10);
            }
        }

        $this->out($pid.': End triggering UpdateSubjectStudentTotalMarkShell with '.$THREAD_NUM.' Threads ('. Time::now() .')');
    }
}
