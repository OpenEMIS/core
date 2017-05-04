<?php
namespace App\Shell;

use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\I18n\Time;

class LinkAllExamCentresShell extends Shell {
    public function initialize() {
        parent::initialize();
        $this->loadModel('Examination.ExaminationCentres');
        $this->loadModel('Examination.ExaminationCentresExaminations');
        $this->loadModel('Institution.Institutions');
    }

    public function main() {
        if (!empty($this->args[0])) {
            $args = explode(" ", $this->args[0]);

            $PAGE_LIMIT = 500;
            $pid = getmypid();
            $SystemProcesses = TableRegistry::get('SystemProcesses');
            $systemProcessId = !empty($args[0]) ? $args[0] : 0;
            $examinationId = !empty($args[1]) ? $args[1] : 0;
            $academicPeriodId = !empty($args[2]) ? $args[2] : 0;
            $examCentreTypeId = !empty($args[3]) ? $args[3] : 0;

            $executedCount = 0;
            $this->out($pid.': Initialize Link All Exam Centres to Exam ('. Time::now() .')');
            $SystemProcesses->updateProcess($systemProcessId, null, $SystemProcesses::RUNNING, $executedCount);

            $obj = [];
            $obj['academic_period_id'] = $academicPeriodId;
            $obj['examination_id'] = $examinationId;

            // get subjects from SystemProcesses params
            if (!empty($systemProcessId)) {
                $SystemProcesses->updatePid($systemProcessId, $pid);
                $processData = $SystemProcesses->get($systemProcessId);
                if (!empty($processData)) {
                    $params = $processData->params;
                    $paramsObj = json_decode($params, true);
                }
            }

            if (isset($paramsObj)) {
                if (isset($paramsObj['examination_items']) && !empty($paramsObj['examination_items'])) {
                    $obj['examination_items'] = [];
                    foreach($paramsObj['examination_items'] as $examItem) {
                        $obj['examination_items'][] = [
                            'id' => $examItem['examination_item_id'],
                            '_joinData' => [
                                'education_subject_id' => $examItem['education_subject_id']
                            ]
                        ];
                    }
                }
            }

            // get all exam centres based on type (if type is selected)
            $examCentreQuery = $this->ExaminationCentres
                ->find('NotLinkedExamCentres', ['examination_id' => $examinationId, 'academic_period_id' => $academicPeriodId, 'examination_centre_type' => $examCentreTypeId])
                ->order([$this->ExaminationCentres->aliasField('code')]);

            $examCentresCount = $examCentreQuery->count();
            $this->out($pid.': Total number records to save: '. $examCentresCount);
            $this->out($pid.': Processing '. $PAGE_LIMIT .' records on each page');
            $saveError = 0;
            $page = 1;
            $loop = ($examCentresCount > 0);

            while ($loop) {
                $examCentrePageData = $examCentreQuery->limit($PAGE_LIMIT)->toArray();

                if (!empty($examCentrePageData)) {
                    $this->out($pid.': Processing PAGE '.$page.' ('. Time::now() .')');

                    $patchOptions = [
                        'validate' => false,
                        'associated' => ['ExaminationItems._joinData' => ['validate' => false]]
                    ];

                    $newEntities = [];
                    foreach ($examCentrePageData as $key => $name) {
                        $examCentreId = $key;

                        // check if this exam centre was linked while shell is still running
                        $existingExamCentre = $this->ExaminationCentresExaminations->find()
                            ->where([
                                $this->ExaminationCentresExaminations->aliasField('examination_centre_id') => $examCentreId,
                                $this->ExaminationCentresExaminations->aliasField('examination_id') => $examinationId,
                            ])
                            ->first();

                        if (empty($existingExamCentre)) {
                            $obj['examination_centre_id'] = $examCentreId;
                            $newEntities[] = $this->ExaminationCentresExaminations->newEntity($obj, $patchOptions);
                        }
                    }

                    try {
                        $this->out($pid.': Saving page '.$page.' ('. Time::now() .')');
                        $this->ExaminationCentresExaminations->saveMany($newEntities);

                    } catch (\Exception $e) {
                        $this->out($pid.': Error encoutered saving PAGE '. $page .' ('.Time::now() .')');
                        $this->out($e->getMessage());
                        $saveError = 1;
                    }

                    $executedCount += count($newEntities);
                    $SystemProcesses->updateProcess($systemProcessId, null, $SystemProcesses::RUNNING, $executedCount);
                    $this->out($pid.': End processing PAGE '.$page.' ('. Time::now() .')');
                    $page++;

                } else {
                    $loop = false;
                }
            }

            if ($saveError != 1) {
                $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::COMPLETED);
            } else {
                $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
            }

            $this->out($pid.': End Link All Exam Centres to Exam ('. Time::now() .')');
        }
    }
}
