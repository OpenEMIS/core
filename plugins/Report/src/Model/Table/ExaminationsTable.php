<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ExaminationsTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ExaminationSubjects', ['className' => 'Examination.ExaminationSubjects', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('ExaminationStudentSubjectResults', ['className' => 'Examination.ExaminationStudentSubjectResults', 'dependent' => true, 'cascadeCallbacks' => true]);
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

    public function validationDefault(Validator $validator): Validator {
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'examination_centre_id':
                return __('Examination Centre');
            case 'examination_id':
                return __('Examination');
            case 'institution_id':
                return __('Institution');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id');
        $this->ControllerAction->field('examination_id');
        $this->ControllerAction->field('examination_centre_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $option = $this->controller->getFeatureOptions($this->getAlias());
            $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($option);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
            return $attr;
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
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
        // POCOR-8919 start
        $alias = $this->getAlias();
        if (isset($data[$alias])) {
            if (isset($data[$alias]['examination_id'])) {
                unset($data[$alias]['examination_id']);
            }
            if (isset($data[$alias]['examination_centre_id'])) {
                unset($data[$alias]['examination_centre_id']);
            }
            if (isset($data[$alias]['institution_id'])) {
                unset($data[$alias]['institution_id']);
            }
        }
        // POCOR-8919 end
    }

    public function onUpdateFieldExaminationId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
// POCOR-8919
            $examinationOptions = $this->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->select([
                    'id',
                    'code_name' => $this->find()->func()->concat(['code' => 'literal', ' - ', 'name' => 'literal'])
                ])
// POCOR-8919
                ->toArray();

            if (!(isset($this->request->getData($this->getAlias())['examination_id']))) {
                reset($examinationOptions);
                $this->request->getData($this->getAlias())['examination_id'] = key($examinationOptions);
            }

            $attr['options'] = ['0' => __('Select Examination')] + $examinationOptions;
            $attr['onChangeReload'] = 'changeExaminationId';
            $attr['type'] = 'select';
            $attr['select'] = false;
            return $attr;
        }
    }

    public function addOnChangeExaminationId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->getAlias(), (array)$data)) {
            if (array_key_exists('examination_centre_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['examination_centre_id']);
            }
            if (array_key_exists('institution_id', $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['institution_id']);
            }
        }
    }
    //POCOR-6637::START
    public function addAfterAction(Event $event, Entity $entity)
    {
        if ($entity->has('feature')) {
            $feature = $entity->feature;

            $fieldsOrder = ['feature'];
            switch ($feature) {
                case 'Report.RegisteredStudentsExaminationCentre':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'examination_id';
                    $fieldsOrder[] = 'examination_centre_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.NotRegisteredStudents':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'examination_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.ExaminationResults':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'examination_id';
                    $fieldsOrder[] = 'institution_id';
                    $fieldsOrder[] = 'format';
                    break;
                default:
                    break;
            }

            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }else{
            $fieldsOrder = ['feature'];
// POCOR-8919
            $fieldsOrder[] = 'examination_centre_id';
            $fieldsOrder[] = 'examination_id';
            $fieldsOrder[] = 'institution_id';
            $fieldsOrder[] = 'format';
            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }
    }
    //POCOR-6637::END

    public function onUpdateFieldExaminationCentreId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array($feature, ['Report.RegisteredStudentsExaminationCentre'])) {
                $selectedAcademicPeriod = !empty($request->getData($this->getAlias())['academic_period_id']) ? $request->getData($this->getAlias())['academic_period_id']: $this->AcademicPeriods->getCurrent();

                $examCentreOptions = [];
                if (!empty($request->getData($this->getAlias())['examination_id'])) {
                    $examinationId = $request->getData($this->getAlias())['examination_id'];
                    $examCentreOptions = $this->ExaminationCentres
                        ->find('list' ,[
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->matching('Examinations')
                        ->where([
                            $this->aliasField('id') => $examinationId,
// POCOR-8919
                        ])
                        ->order([$this->ExaminationCentres->aliasField('code')])
                        ->toArray();

                    if (!empty($examCentreOptions)) {
                        $examCentreOptions =  ['0' => __('All Exam Centres')] + $examCentreOptions;
                    }
                }
                /*$attr['options'] = !empty($examCentreOptions)? $examCentreOptions: [];
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['onChangeReload'] = true;
                $attr['select'] = false;*/

                $attr['options'] = $examCentreOptions;
                $attr['onChangeReload'] = true;
                $attr['type'] = 'select';
                $attr['select'] = false;

            } else {
                $attr['type'] = 'hidden';
            }

            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array($feature, ['Report.ExaminationResults'])) {

                $institutionOptions = [];
                if (!empty($request->getData($this->getAlias())['examination_id'])) {
                    $selectedExamination = $request->getData($this->getAlias())['examination_id'];

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
                if (!empty($request->getData($this->getAlias())['examination_id'])) {
                    $selectedExamination = $request->getData($this->getAlias())['examination_id'];
                    $Examinations = TableRegistry::getTableLocator()->get('Examination.Examinations');
                    $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');

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
