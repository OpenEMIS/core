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
    public function initialize(array $config) {
        $this->table('examinations');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems']);
        $this->hasMany('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->hasMany('ExaminationCentreStudents', ['className' => 'Examination.ExaminationCentreStudents']);

        $this->addBehavior('Excel', ['pages' => false]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function addAfterAction(Event $event)
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
            if (!empty($request->data[$this->alias()]['academic_period_id'])) {
                $selectedAcademicPeriod = $request->data[$this->alias()]['academic_period_id'];
            } else {
                $selectedAcademicPeriod = $this->AcademicPeriods->getCurrent();
            }

            $examinationOptions = $this->find('list')
                ->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod])
                ->toArray();

            $attr['options'] = $examinationOptions;
            $attr['onChangeReload'] = 'changeExaminationId';
            $attr['type'] = 'select';
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
                if (!empty($request->data[$this->alias()]['examination_id'])) {
                    $selectedExamination = $request->data[$this->alias()]['examination_id'];

                    $examCentreOptions = $this->ExaminationCentres
                        ->find('list' ,[
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([$this->ExaminationCentres->aliasField('examination_id') => $selectedExamination])
                        ->toArray();

                    if (!empty($examCentreOptions)) {
                        $examCentreOptions =  ['-1' => __('All Exam Centres')] + $examCentreOptions;
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
                if (!empty($request->data[$this->alias()]['examination_id'])) {
                    $selectedExamination = $request->data[$this->alias()]['examination_id'];

                    $ExamCentreStudents = $this->ExaminationCentreStudents;
                    $institutionOptions = $ExamCentreStudents
                        ->find('list' ,[
                            'keyField' => 'institution_id',
                            'valueField' => 'institution.code_name'
                        ])
                        ->contain('Institutions')
                        ->where([$ExamCentreStudents->aliasField('examination_id') => $selectedExamination])
                        ->group([$ExamCentreStudents->aliasField('institution_id')])
                        ->toArray();

                    if (!empty($institutionOptions)) {
                        $institutionOptions =  ['-1' => __('All Institutions'), '0' => __('Private Candidate')] + $institutionOptions;
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
                        $institutionOptions =  ['-1' => __('All Institutions')] + $institutionOptions;
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
