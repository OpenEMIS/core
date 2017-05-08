<?php
namespace App\Shell;

use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\I18n\Time;

class AddAllInstitutionsExamCentreShell extends Shell {
    public function initialize() {
        parent::initialize();
        $this->loadModel('Examination.ExaminationCentres');
        $this->loadModel('Institution.Institutions');
    }

    public function main() {
        if (!empty($this->args[0])) {
            $PAGE_LIMIT = 500;
            $pid = getmypid();
            $SystemProcesses = TableRegistry::get('SystemProcesses');
            $systemProcessId = !empty($this->args[0]) ? $this->args[0] : 0;
            $academicPeriodId = !empty($this->args[1]) ? $this->args[1] : 0;
            $institutionTypeId = !empty($this->args[2]) ? $this->args[2] : 0;

            $executedCount = 0;
            $this->out($pid.': Initialize Add All Institutions As Exam Centres ('. Time::now() .')');
            $SystemProcesses->updateProcess($systemProcessId, null, $SystemProcesses::RUNNING, $executedCount);

            $obj = [];
            $obj['academic_period_id'] = $academicPeriodId;

            // get special needs from SystemProcesses params
            if (!empty($systemProcessId)) {
                $SystemProcesses->updatePid($systemProcessId, $pid);
                $processData = $SystemProcesses->get($systemProcessId);
                if (!empty($processData)) {
                    $params = $processData->params;
                    $paramsObj = json_decode($params, true);
                }
            }

            if (isset($paramsObj)) {
                if (isset($paramsObj['special_needs']) && !empty($paramsObj['special_needs'])) {
                    $obj['examination_centre_special_needs'] = [];
                    foreach($paramsObj['special_needs'] as $need) {
                        $obj['examination_centre_special_needs'][] = [
                            'special_need_type_id' => $need
                        ];
                    }
                }
            }

            // get all institutions based on type (if type is selected)
            $institutionQuery = $this->Institutions->find('NotExamCentres', ['academic_period_id' => $academicPeriodId]);
            if (!empty($institutionTypeId)) {
                $institutionQuery->where([$this->Institutions->aliasField('institution_type_id') => $institutionTypeId]);
            }

            $institutionCount = $institutionQuery->count();
            $this->out($pid.': Total number records to save: '. $institutionCount);
            $this->out($pid.': Processing '. $PAGE_LIMIT .' records on each page');
            $saveError = 0;
            $page = 1;
            $loop = ($institutionCount > 0);

            while ($loop) {
                $institutionPageData = $institutionQuery->limit($PAGE_LIMIT)->toArray();

                if (!empty($institutionPageData)) {
                    $this->out($pid.': Processing PAGE '.$page.' ('. Time::now() .')');
                    $newEntities = [];

                    foreach ($institutionPageData as $institution) {
                        // check if this exam centre was added while shell is still running
                        $existingExamCentre = $this->ExaminationCentres->find()
                            ->where([
                                $this->ExaminationCentres->aliasField('institution_id') => $institution->id,
                                $this->ExaminationCentres->aliasField('academic_period_id') => $academicPeriodId
                            ])
                            ->first();

                        if (empty($existingExamCentre)) {
                            $obj['institution_id'] = $institution->id;
                            $obj['area_id'] = $institution->area_id;
                            $obj['name'] = $institution->name;
                            $obj['code'] = $institution->code;
                            $obj['address'] = $institution->address;
                            $obj['postal_code'] = $institution->postal_code;
                            $obj['contact_person'] = $institution->contact_person;
                            $obj['telephone'] = $institution->telephone;
                            $obj['fax'] = $institution->fax;
                            $obj['email'] = $institution->email;
                            $obj['website'] = $institution->website;
                            $newEntities[] = $this->ExaminationCentres->newEntity($obj, ['validate' => false]);
                        }
                    }

                    try {
                        $this->out($pid.': Saving page '.$page.' ('. Time::now() .')');
                        $this->ExaminationCentres->saveMany($newEntities);

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

            $this->out($pid.': End Add All Institutions As Exam Centres ('. Time::now() .')');
        }
    }
}
