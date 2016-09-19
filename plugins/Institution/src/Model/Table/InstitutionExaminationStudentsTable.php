<?php
namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;

class InstitutionExaminationStudentsTable extends ControllerActionTable {

    public function initialize(array $config) {
        $this->table('examination_students');
        parent::initialize($config);
        //$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        // $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        // $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds']);


        // $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('academic_period_id', ['type' => 'select', 'empty' => true]);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('special_needs_required', ['type' => 'chosenSelect', 'onChangeReload' => true]);
        $this->field('examination_centre_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('capacity', ['type' => 'readonly']);
        $this->field('special_needs', ['type' => 'readonly']);
        $this->field('examination_centre_subject_id', ['type' => 'chosenSelect']);
        $this->field('student_id');

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'special_needs_required', 'examination_centre_id', 'capacity', 'special_needs', 'examination_centre_subject_id', 'student'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
                $attr['onChangeReload'] = 'changeAcademicPeriodId';
        }

        return $attr;
    }

    public function addOnChangeAcademicPeriodId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        // unset($this->request->query['from_period']);
        // unset($this->request->query['grade_to_promote']);
        // unset($this->request->query['class']);
        // unset($this->request->query['student_status']);

        if ($this->request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $data)) {
                // if (array_key_exists('from_academic_period_id', $data[$this->alias()])) {
                //     $this->request->query['from_period'] = $data[$this->alias()]['from_academic_period_id'];
                // }
                if (array_key_exists('examination_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['examination_id']);
                }
                if (array_key_exists('examination_centre_id', $data[$this->alias()])) {
                    unset($data[$this->alias()]['examination_centre_id']);
                }
            }
        }
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, $request) {
        $examinationOptions = [];

        if ($action == 'add' || $action == 'edit') {
            if(isset($request->data[$this->alias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
            } else if ($action == 'edit') {
                // $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent;
            }

            if (!empty($selectedAcademicPeriod)) {
                $Examinations = $this->Examinations;
                $examinationOptions = $Examinations->find('list')
                    ->where([$Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
                    ->toArray();
            }
        }

        $attr['options'] = $examinationOptions;
        return $attr;
    }

    public function onUpdateFieldSpecialNeedsRequired(Event $event, array $attr, $action, $request) {
        if ($action == 'add' || $action == 'edit') {
            $SpecialNeedTypes = TableRegistry::get('FieldOption.SpecialNeedTypes');
            $types = $SpecialNeedTypes->findVisibleNeedTypes();
            $attr['options'] = $types;
        }

        return $attr;
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, $request) {
        $examinationCentreOptions = [];

        if ($action == 'add' || $action == 'edit') {
            if(isset($request->data[$this->alias()]['academic_period_id']) && isset($request->data[$this->alias()]['examination_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
                $selectedExamination = $request->data[$this->alias()]['examination_id'];
                $selectedSpecialNeeds = $request->data[$this->alias()]['special_needs_required']['_ids'];
            } else if ($action == 'edit') {

            }

            if (!empty($selectedAcademicPeriod)) {
                $ExaminationCentres = $this->ExaminationCentres;
                $examinationCentreOptions = $ExaminationCentres
                    ->find('list')
                    ->select([
                        'count' => $this->find()->func()->count('*')
                    ])
                    ->innerJoinWith('ExaminationCentreSpecialNeeds')
                    ->where([$ExaminationCentres->aliasField('academic_period_id') => $selectedAcademicPeriod, $ExaminationCentres->aliasField('examination_id') => $selectedExamination, $this->ExaminationCentreSpecialNeeds->aliasField('special_need_type_id IN') => $selectedSpecialNeeds])
                    ->group($ExaminationCentres->aliasField('id'))
                    ->having(['count =' => count($selectedSpecialNeeds)])
                    ->toArray();
            }
        }

        $attr['options'] = $examinationCentreOptions;
        return $attr;
    }

    public function onUpdateFieldCapacity(Event $event, array $attr, $action, $request) {
        $capacity = '';

        if (!empty($request->data[$this->alias()]['examination_centre_id'])) {
            $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];
            $examinationCentres = $this->ExaminationCentres
                ->get($examinationCentreId)
                ->toArray();
            if (!empty($examinationCentres)) {
                $capacity = $examinationCentres['capacity'];
            }
        }

        $attr['attr']['value'] = $capacity;
        return $attr;
    }

    public function onUpdateFieldSpecialNeeds(Event $event, array $attr, $action, $request) {
        $specialNeeds = [];

        if (!empty($request->data[$this->alias()]['examination_centre_id'])) {
            $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];
            $query = $this->ExaminationCentreSpecialNeeds
                ->find('list', [
                    'keyField' => 'special_need_type_id',
                    'valueField' => 'special_need_type.name'
                ])
                ->contain(['SpecialNeedTypes'])
                ->where([$this->ExaminationCentreSpecialNeeds->aliasField('examination_centre_id') => $examinationCentreId])
                ->toArray();

            if (!empty($query)) {
                $specialNeeds = implode(', ', $query);

            }
        }
        $attr['attr']['value'] = $specialNeeds;
        return $attr;
    }

    public function onUpdateFieldExaminationItemId(Event $event, array $attr, $action, $request) {
        if ($action == 'add' || $action == 'edit') {
            if(isset($request->data[$this->alias()]['examination_id'])) {
                $selectedExamination = $request->data[$this->alias()]['examination_id'];
            } else if ($action == 'edit') {

            }

            if (!empty($selectedExamination)) {
                $ExaminationItems = $this->ExaminationItems;
                $subjects = $ExaminationItems->getExaminationItemSubjects($selectedExamination);
                // pr($subjects);
                $attr['options'] = $subjects;
            }
        }
    }

    // public function onUpdateFieldStaffPositionTitleId(Event $event, array $attr, $action, $request) {

}
