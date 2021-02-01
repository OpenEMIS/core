<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use PHPExcel_Worksheet;

use App\Model\Table\AppTable;

class ImportAssessmentItemResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.ImportOutcomeResult', [
            'plugin' => 'Institution',
            'model' => 'AssessmentItemResults',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'AssessmentItemResults']
        ]);

        // register table once
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
         $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->notEmpty(['education_grade', 'select_file']);
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        $request = $this->request;
        if (empty($request->query('education_grade'))) {
            unset($buttons[0]);
            unset($buttons[1]);
        }
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        $request = $this->request;
        unset($request->query['education_grade']);
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->dependency = [];
        $this->ControllerAction->field('education_grade', ['type' => 'select', 'visible' => false]);
        $this->ControllerAction->field('select_file', ['visible' => false]);
        $this->ControllerAction->setFieldOrder(['education_grade', 'select_file']);

        //Assumptiopn - onChangeReload must be named in this format: change<field_name>. E.g changeClass
        $currentFieldName = strtolower(str_replace("change", "", $entity->submit));

        if (isset($this->request->data[$this->alias()])) {
            $unsetFlag = false;
            $aryRequestData = $this->request->data[$this->alias()];
            foreach ($aryRequestData as $requestData => $value) {
                if ($unsetFlag) {
                    unset($this->request->query[$requestData]);
                    $this->request->data[$this->alias()][$requestData] = 0;
                }

                if ($currentFieldName == str_replace("_", "", $requestData)) {
                    $unsetFlag = true;
                }
            }
            $aryRequestData = $this->request->data[$this->alias()];
            foreach ($aryRequestData as $requestData => $value) {
                if (isset($this->dependency[$requestData]) && $value) {
                    $aryDependencies = $this->dependency[$requestData];
                    foreach ($aryDependencies as $dependency) {
                        $this->request->query = $this->request->data[$this->alias()];
                        $this->ControllerAction->field($dependency, ['visible' => true]);
                    }
                }
            }
        }
    }

    public function onUpdateFieldEducationGrade(Event $event, array $attr, $action, Request $request)
{
    if ($action == 'add') {
        $attr['type'] = 'element';
        $attr['element'] = 'Institution.Programmes/grades';

        if ($request->is(['post', 'put'])) {
            $programmeId = $request->data($this->aliasField('programme'));

            if (empty($programmeId)) {
                $programmeId = 0;
            }

            $data = $this->EducationGrades->find('list')
            ->find('visible')
            ->find('order')
            ->where(['EducationGrades.education_programme_id' => $programmeId])
            ->toArray();

            $institutionId = $this->Session->read('Institution.Institutions.id');
            $exists = $this->find('list', ['keyField' => 'education_grade_id', 'valueField' => 'education_grade_id'])
            ->where([$this->aliasField('institution_id') => $institutionId])
            ->toArray();

            // useing onChangeReload to do visible
            $attr['onChangeReload'] = 'changeEducationGrade';
        }

    } else if ($action == 'edit') {
        $attr['type'] = 'readonly';
    }
    return $attr;
}

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $requestData = $this->request->data[$this->alias()];
        $tempRow['education_grade'] = $requestData['education_grade'];
        $tempRow['institution_class_id'] = $requestData['class'];
        $tempRow['institution_id'] = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $this->request->session()->read('Institution.Institutions.id');

        $outcomeCriteriaEntity = $this->OutcomeCriterias->find()
            ->matching('Templates')
            ->contain('OutcomeGradingTypes.GradingOptions')
            ->where([
                $this->OutcomeCriterias->aliasField('id') => $tempRow['outcome_criteria_id'],
                $this->OutcomeCriterias->aliasField('outcome_template_id') => $tempRow['outcome_template_id'],
                $this->OutcomeCriterias->aliasField('academic_period_id') => $tempRow['academic_period_id']
            ])
            ->first();

            $tempRow['education_subject_id'] = $outcomeCriteriaEntity->education_subject_id;
            $tempRow['education_grade_id'] = $outcomeCriteriaEntity->_matchingData['Templates']->education_grade_id;

        return true;
    }
}    