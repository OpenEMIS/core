<?php
namespace App\Shell;

use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\I18n\Time;

class AddAllInstitutionsExamCentreShell extends Shell {
    const NEW_PROCESS = 1;
    const COMPLETED = 3;
    const RUNNING = 2;
    const ERROR = -2;
    const ABORT = -1;

    public function initialize() {
        parent::initialize();
        $this->loadModel('Examination.ExaminationCentres');
        $this->loadModel('Institution.Institutions');
    }

    public function main() {
        if (!empty($this->args[0])) {
            $pid = getmypid();
            $SystemProcesses = TableRegistry::get('SystemProcesses');
            $examinationId = !empty($this->args[0]) ? $this->args[0] : 0;
            $academicPeriodId = !empty($this->args[1]) ? $this->args[1] : 0;
            $systemProcessId = !empty($this->args[2]) ? $this->args[2] : 0;
            $institutionTypeId = !empty($this->args[3]) ? $this->args[3] : 0;

            $executedCount = 0;
            $this->out('Initialize Add All Institutions As Exam Centres: '.$pid);
            $SystemProcesses->updateProcess($systemProcessId, null, self::RUNNING);

            $obj = [];
            $obj['academic_period_id'] = $academicPeriodId;
            $obj['examination_id'] = $examinationId;

            // get special needs and subjects from SystemProcesses params
            if (!empty($systemProcessId)) {
                $SystemProcesses->updatePid($systemProcessId, $pid);
                $params = $SystemProcesses->get($systemProcessId)->params;
                $paramsObj = json_decode($params);
            }

            if (isset($paramsObj)) {
                if (isset($paramsObj->special_needs) && !empty($paramsObj->special_needs)) {
                    $obj['examination_centre_special_needs'] = [];
                    foreach($paramsObj->special_needs as $need) {
                        $obj['examination_centre_special_needs'][] = [
                            'examination_id' => $examinationId,
                            'academic_period_id' => $academicPeriodId,
                            'special_need_type_id' => $need
                        ];
                    }
                }

                if (isset($paramsObj->subjects) && !empty($paramsObj->subjects)) {
                    $obj['examination_centre_subjects'] = [];
                    foreach($paramsObj->subjects as $subject) {
                        $obj['examination_centre_subjects'][] = [
                            'examination_id' => $examinationId,
                            'academic_period_id' => $academicPeriodId,
                            'education_subject_id' => $subject
                        ];
                    }
                }
            }

            // get all institutions based on type (if type is selected)
            $institutionQuery = $this->Institutions->find('NotExamCentres', ['examination_id' => $examinationId]);
            if (!empty($institutionTypeId)) {
                $institutionQuery->where([$this->Institutions->aliasField('institution_type_id') => $institutionTypeId]);
            }
            $allInstitutionsData = $institutionQuery->toArray();

            $newEntities = [];
            foreach ($allInstitutionsData as $institution) {
                // check if this exam centre was added while shell is still running
                $existingExamCentre = $this->ExaminationCentres->find()
                    ->where([
                        $this->ExaminationCentres->aliasField('institution_id') => $institution->id,
                        $this->ExaminationCentres->aliasField('examination_id') => $examinationId,
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
                $this->out('Saving All Institutions ('. count($newEntities) .') As Exam Centres: '.$pid);
                $this->ExaminationCentres->saveMany($newEntities);

                $SystemProcesses->updateProcess($systemProcessId, Time::now(), self::COMPLETED);
                $this->out('End Add All Institutions As Exam Centres: '.$pid);
            } catch (\Exception $e) {
                $this->out('Error during Saving All Institutions As Exam Centres: '.$pid);
                $this->out($e->getMessage());
                $SystemProcesses->updateProcess($systemProcessId, Time::now(), self::ERROR);
            }
        }
    }
}
