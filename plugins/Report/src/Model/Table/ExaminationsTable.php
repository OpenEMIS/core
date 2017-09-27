<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ExaminationsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationItemResults', ['className' => 'Examination.ExaminationItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('ExaminationCentres', [
            'className' => 'Examination.ExaminationCentres',
            'joinTable' => 'examination_centres_examinations',
            'foreignKey' => 'examination_id',
            'targetForeignKey' => 'examination_centre_id',
            'through' => 'Examination.ExaminationCentresExaminations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('ExaminationCentreRooms', [
            'className' => 'Examination.ExaminationCentreRooms',
            'joinTable' => 'examination_centre_rooms_examinations',
            'foreignKey' => 'examination_id',
            'targetForeignKey' => 'examination_centre_room_id',
            'through' => 'Examination.ExaminationCentreRoomsExaminations',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Excel', ['pages' => false]);
        $this->addBehavior('Report.ReportList');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
                ->requirePresence('examination_id')
                ->notEmpty('examination_centre_id', __('This field cannot be left empty'), function ($context) {
                    if (isset($context['data']['feature'])) {
                        return in_array($context['data']['feature'], ['Report.RegisteredStudentsExaminationCentre']);
                    }
                    return false;
                })
                ->notEmpty('institution_id', __('This field cannot be left empty'), function ($context) {
                    if (isset($context['data']['feature'])) {
                        return in_array($context['data']['feature'], ['Report.NotRegisteredStudents', 'Report.ExaminationResults']);
                    }
                    return false;
                });
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id');
        $this->ControllerAction->field('examination_id');
        $this->ControllerAction->field('examination_centre_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = $this->AcademicPeriods->getCurrent();

            $attr['onChangeReload'] = 'changeAcademicPeriodId';
            $attr['options'] = $periodOptions;
            $attr['default'] = $selectedPeriod;
            $attr['type'] = 'select';
            $attr['select'] = false;
            return $attr;
        }
    }

    public function addOnChangeAcademicPeriodId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('examination_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_id']);
            }
            if (array_key_exists('examination_centre_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_centre_id']);
            }
            if (array_key_exists('institution_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['institution_id']);
            }
        }
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
             $selectedAcademicPeriod = !empty($request->data[$this->alias()]['academic_period_id']) ? $request->data[$this->alias()]['academic_period_id']: $this->AcademicPeriods->getCurrent();

            $examinationOptions = $this->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->select([
                    'id',
                    'code_name' => $this->find()->func()->concat(['code' => 'literal', ' - ', 'name' => 'literal'])
                ])
                ->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod])
                ->toArray();

            if (!(isset($this->request->data[$this->alias()]['examination_id']))) {
                reset($examinationOptions);
                $this->request->data[$this->alias()]['examination_id'] = key($examinationOptions);
            }

            $attr['options'] = $examinationOptions;
            $attr['onChangeReload'] = 'changeExaminationId';
            $attr['type'] = 'select';
            $attr['select'] = false;
            return $attr;
        }
    }

    public function addOnChangeExaminationId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('examination_centre_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['examination_centre_id']);
            }
            if (array_key_exists('institution_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['institution_id']);
            }
        }
    }

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.RegisteredStudentsExaminationCentre'])) {
                $selectedAcademicPeriod = !empty($request->data[$this->alias()]['academic_period_id']) ? $request->data[$this->alias()]['academic_period_id']: $this->AcademicPeriods->getCurrent();

                $examCentreOptions = [];
                if (!empty($request->data[$this->alias()]['examination_id'])) {
                    $examinationId = $request->data[$this->alias()]['examination_id'];
                    $examCentreOptions = $this->ExaminationCentres
                        ->find('list' ,[
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->matching('Examinations')
                        ->where([
                            $this->aliasField('id') => $examinationId,
                            $this->ExaminationCentres->aliasField('academic_period_id') => $selectedAcademicPeriod
                        ])
                        ->order([$this->ExaminationCentres->aliasField('code')])
                        ->toArray();

                    if (!empty($examCentreOptions)) {
                        $examCentreOptions =  ['0' => __('All Exam Centres')] + $examCentreOptions;
                    }
                }

                $attr['options'] = !empty($examCentreOptions)? $examCentreOptions: [];
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = false;

            } else {
                $attr['type'] = 'hidden';
            }

            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.ExaminationResults'])) {

                $institutionOptions = [];
                if (!empty($request->data[$this->alias()]['examination_id'])) {
                    $selectedExamination = $request->data[$this->alias()]['examination_id'];

                    $ExamCentreStudents = TableRegistry::get('Examination.ExaminationCentresExaminationsStudents');
                    $institutionOptions = $ExamCentreStudents
                        ->find('list', [
                            'keyField' => 'institution_id',
                            'valueField' => 'institution.code_name'
                        ])
                        ->contain('Institutions')
                        ->where([$ExamCentreStudents->aliasField('examination_id') => $selectedExamination])
                        ->group([$ExamCentreStudents->aliasField('institution_id')])
                        ->toArray();

                    if (!empty($institutionOptions)) {
                        $institutionOptions =  ['0' => __('All Institutions'), '-1' => __('Private Candidate')] + $institutionOptions;
                    }
                }

                $attr['options'] = !empty($institutionOptions)? $institutionOptions: [];
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = false;

            } else if (in_array($feature, ['Report.NotRegisteredStudents'])) {
                if (!empty($request->data[$this->alias()]['examination_id'])) {
                    $selectedExamination = $request->data[$this->alias()]['examination_id'];
                    $Examinations = TableRegistry::get('Examination.Examinations');
                    $Institutions = TableRegistry::get('Institution.Institutions');

                    $examInfo = $Examinations->find()
                        ->where([$Examinations->aliasField('id') => $selectedExamination])
                        ->first();
                    $selectedGrade = $examInfo->education_grade_id;

                    $institutionOptions = $Institutions
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name',
                        ])
                        ->matching('InstitutionGrades')
                        ->where(['InstitutionGrades.education_grade_id' => $selectedGrade])
                        ->toArray();

                    if (!empty($institutionOptions)) {
                        $institutionOptions =  ['0' => __('All Institutions')] + $institutionOptions;
                    }
                }

                $attr['options'] = !empty($institutionOptions)? $institutionOptions: [];
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['select'] = false;

            } else {
                $attr['type'] = 'hidden';
            }

            return $attr;
        }
    }
}
