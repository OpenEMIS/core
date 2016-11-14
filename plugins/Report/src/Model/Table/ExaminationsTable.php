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
use App\Model\Table\AppTable;

class ExaminationsTable extends AppTable
{
    public function initialize(array $config) {
        $this->table('examination_centre_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->hasMany('ExaminationItems', ['className' => 'Examination.ExaminationItems', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('ExaminationCentreSpecialNeeds', ['className' => 'Examination.ExaminationCentreSpecialNeeds']);

        // $this->addBehavior('Excel', ['excludes' => ['security_group_id'], 'pages' => false]);
        $this->addBehavior('Report.ReportList');
        // $this->addBehavior('Report.CustomFieldList', [
        //     'model' => 'Institution.Institutions',
        //     'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
        //     'fieldValueClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFieldValues', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true],
        //     'tableCellClass' => ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true]
        // ]);
        // $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
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

    public function onGetReportName(Event $event, ArrayObject $data)
    {
        return __('Overview');
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id');
        $this->ControllerAction->field('examination_id');
        $this->ControllerAction->field('examination_centre_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = $this->AcademicPeriods->getCurrent();

            $attr['onChangeReload'] = true;
            $attr['options'] = $periodOptions;
            $attr['default'] = $selectedPeriod;
            $attr['type'] = 'select';
            $attr['select'] = false;
            return $attr;
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

            $Examinations = $this->Examinations;
            $examinationOptions = $Examinations->find('list')
                ->where([$Examinations->aliasField('academic_period_id') => $selectedAcademicPeriod])
                ->toArray();

            $attr['options'] = $examinationOptions;
            $attr['onChangeReload'] = 'changeExaminationId';
            return $attr;
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
                        ->find('list' ,['keyField' => 'id', 'valueField' => 'code_name'])
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

            if (in_array($feature, ['Report.NotRegisteredStudents', 'Report.ExaminationResults'])) {
                if (!empty($request->data[$this->alias()]['examination_id'])) {
                    $selectedExamination = $request->data[$this->alias()]['examination_id'];

                    $institutionOptions = $this
                        ->find('list' ,['keyField' => 'institution_id', 'valueField' => 'institution.code_name'])
                        ->contain('Institutions')
                        ->where([$this->aliasField('examination_id') => $selectedExamination])
                        ->group([$this->aliasField('institution_id')])
                        ->toArray();

                    // cater for 0
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
