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
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems', 'dependent' => true, 'cascadeCallbacks' => true]);


        // $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $this->field('academic_period_id', ['type' => 'select', 'empty' => true, 'onChangeReload' => true]);
        $this->field('examination_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('special_needs_required', ['type' => 'chosenSelect']);
        $this->field('examination_centre_id', ['type' => 'select', 'onChangeReload' => true]);
        $this->field('capacity', ['type' => 'readonly']);
        $this->field('special_needs', ['type' => 'readonly']);
        $this->field('education_subject_id', ['type' => 'chosenSelect']);
        $this->field('student_id');

        $this->setFieldOrder([
            'academic_period_id', 'examination_id', 'special_needs_required', 'examination_centre_id', 'capacity', 'special_needs', 'education_subject_id', 'student'
        ]);
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

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, $request) {
        $examinationCentreOptions = [];

        if ($action == 'add' || $action == 'edit') {
            if(isset($request->data[$this->alias()]['academic_period_id']) && isset($request->data[$this->alias()]['examination_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
                $selectedExamination = $request->data[$this->alias()]['examination_id'];
            } else if ($action == 'edit') {

            }

            if (!empty($selectedAcademicPeriod)) {
                $ExaminationCentres = $this->ExaminationCentres;
                $examinationCentreOptions = $ExaminationCentres->find('list')
                    ->where([$ExaminationCentres->aliasField('academic_period_id') => $selectedAcademicPeriod, $ExaminationCentres->aliasField('examination_id') => $selectedExamination])
                    ->toArray();
            }
        }

        $attr['options'] = $examinationCentreOptions;
        return $attr;
    }

    public function onUpdateFieldCapacity(Event $event, array $attr, $action, $request) {

        if (!empty($request->data[$this->alias()]['examination_centre_id'])) {
            $examinationCentreId = $request->data[$this->alias()]['examination_centre_id'];
            $examinationCentres = $this->ExaminationCentres
                ->get($examinationCentreId)
                ->toArray();
            if (!empty($examinationCentres)) {
                $capacity = $examinationCentres['capacity'];
            }

            $attr['attr']['value'] = $capacity;
        }

        return $attr;
    }

    // public function onUpdateFieldStaffPositionTitleId(Event $event, array $attr, $action, $request) {

}
