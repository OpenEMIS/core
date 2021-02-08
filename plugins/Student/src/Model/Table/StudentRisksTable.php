<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class StudentRisksTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_risks');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Risks', ['className' => 'Risk.Risks', 'foreignKey' =>'risk_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' =>'student_id']);

        $this->hasMany('StudentRisksCriterias', ['className' => 'Institution.StudentRisksCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('average_risk', ['visible' => false]);
        $this->field('student_id', ['visible' => false]);
        $this->field('institution_id', ['type' => 'integer']);
        $this->field('total_risk', ['after' => 'risk_id']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('generated_by', ['visible' => false]);
        $this->field('generated_on', ['visible' => false]);
        $this->field('institution_id', ['after' => 'academic_period_id']);

        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->query;

        $selectedAcademicPeriodId = !empty($requestQuery) && array_key_exists('academic_period_id', $requestQuery) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $extra['elements']['control'] = [
            'name' => 'Risks/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('grade');
        $this->field('class');
        $this->field('risk_criterias', ['type' => 'custom_criterias', 'after' => 'total_risk']);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return $entity->user->name;
    }

    public function onGetGrade(Event $event, Entity $entity)
    {
        // some class not configure in the institutionClassStudents, therefore using the institutionStudents
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $studentId = $entity->student_id;
        $academicPeriodId = $entity->academic_period_id;

        $educationGradeData = $InstitutionStudents->find()
            ->where([
                'student_id' => $studentId,
                'academic_period_id' => $academicPeriodId,
                'student_status_id' => 1 // enrolled status
            ])
            ->first();

        $educationGradesName = '';
        if (isset($educationGradeData->education_grade_id)) {
            $educationGradesName = $EducationGrades->get($educationGradeData->education_grade_id)->name;
        }

        return $educationGradesName;
    }

    public function onGetClass(Event $event, Entity $entity)
    {
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $studentId = $entity->student_id;
        $academicPeriodId = $entity->academic_period_id;

        $institutionClassesData = $InstitutionClassStudents->find()
            ->where([
                'student_id' => $studentId,
                'academic_period_id' => $academicPeriodId,
                'student_status_id' => 1 // enrolled status
            ])
            ->first();

        $institutionClassesName = '';
        if (isset($institutionClassesData->institution_class_id)) {
            $institutionClassesName = $InstitutionClasses->get($institutionClassesData->institution_class_id)->name;
        }

        return $institutionClassesName;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $conditions = [];

        $conditions[$this->aliasField('academic_period_id')] = $extra['selectedAcademicPeriodId'];
        $user = $this->Auth->user();

        $session = $this->request->session();
        $studentId = $session->read('Student.Students.id');
/*
        if ($this->controller->name == 'Profiles' && $this->action == 'index') {
            $session = $this->request->session();
            $studentId = $this->ControllerAction->paramsDecode($studentId)['id'];
        }*/
         
        if ($user['is_student'] == 1) {
            $query = $query
            ->where([
                $this->aliasField('student_id') => $user['id'],
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']
            ])
            ->order(['risk_id']);
        }
        else{
            $query = $query
                ->where([
                    $this->aliasField('student_id') => $studentId,
                    $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']
                ])
                ->order(['risk_id']);
        }
     
        
        return $query;
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Risks');
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        // from indexes table
        $Risks = TableRegistry::get('Risk.Risks');
        $riskId = $entity->risk->id;
        $generatedById = $Risks->get($riskId)->generated_by;

        $userName = '';
        if (!empty($generatedById)) {
            $userName = $this->Users->get($generatedById)->first_name . ' ' . $this->Users->get($generatedById)->last_name;
        }

        return $userName;
    }

    public function onGetGeneratedOn(Event $event, Entity $entity)
    {
        // from indexes table
        $Risks = TableRegistry::get('Risk.Risks');
        $riskId = $entity->risk->id;
        $risksGeneratedOn = $Risks->get($riskId)->generated_on;

        $generatedOn = 0;
        if (isset($risksGeneratedOn)) {
            $generatedOn = $risksGeneratedOn->format('F d, Y - H:i:s');
        }

        return $generatedOn;
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options = [])
    {
        // $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
        $tableHeaders = $this->getMessage('Risk.TableHeader');
        array_splice($tableHeaders, 3, 0, __('Value')); // adding value header
        $tableHeaders[] = __('References');
        $tableCells = [];
        $fieldKey = 'risk_criterias';

        $riskId = $entity->risk->id;
        $institutionId = $entity->institution->id;
        $studentId = $entity->user->id;
        $academicPeriodId = $entity->academic_period_id;

        $institutionStudentRiskId = $this->paramsDecode($this->paramsPass(0))['id']; // paramsPass(0) after the hash of Id

        if ($action == 'view') {
            $studentRisksCriteriasResults = $this->StudentRisksCriterias->find()
                ->contain(['RiskCriterias'])
                ->where([
                    $this->StudentRisksCriterias->aliasField('institution_student_risk_id') => $institutionStudentRiskId,
                    $this->StudentRisksCriterias->aliasField('value') . ' IS NOT NULL'
                ])
                ->order(['criteria','threshold'])
                ->all();

            foreach ($studentRisksCriteriasResults as $key => $obj) {
                if (isset($obj->risk_criteria)) {
                    $riskCriteriasId = $obj->risk_criteria->id;

                    $criteriaName = $obj->risk_criteria->criteria;
                    $operatorId = $obj->risk_criteria->operator;
                    $operator = $this->Risks->getOperatorDetails($operatorId);
                    $threshold = $obj->risk_criteria->threshold;

                    $value = $this->StudentRisksCriterias->getValue($institutionStudentRiskId, $riskCriteriasId);

                    $criteriaDetails = $this->Risks->getCriteriasDetails($criteriaName);
                    $CriteriaModel = TableRegistry::get($criteriaDetails['model']);

                    if ($value == 'True') {
                        // Comparison like behaviour
                        $LookupModel = TableRegistry::get($criteriaDetails['threshold']['lookupModel']);

                        // to get total number of behaviour
                        $getValueIndex = $CriteriaModel->getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName);
                        $quantity = '';
                        if ($getValueIndex[$threshold] > 1) {
                            $quantity = ' ( x'. $getValueIndex[$threshold]. ' )';
                        }

                        $riskValue = '<div style="color : red">' . $obj->risk_criteria->risk_value . $quantity  .'</div>';

                        // for reference tooltip
                        $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);

                        // for threshold name
                        $thresholdName = $LookupModel->get($threshold)->name;
                        $threshold = $thresholdName;
                        if ($thresholdName == 'Repeated') {
                            $threshold = $this->Risks->getCriteriasDetails($criteriaName)['threshold']['value']; // 'Yes'
                        }
                    } else {
                        // numeric value come here (absence quantity, results)
                        // for value
                        $riskValue = '<div style="color : red">'.$obj->risk_criteria->risk_value.'</div>';

                        // for the reference tooltip
                        $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);
                    }

                    // blue info tooltip
                    $tooltipReference = '<i class="fa fa-info-circle fa-lg icon-blue" data-placement="left" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="'.$reference.'"></i>';

                    if (!is_numeric($threshold)) {
                        $threshold = __($threshold);
                    }

                    if (!is_numeric($value)) {
                        $value = __($value);
                    }

                    // to put in the table
                    $rowData = [];
                    $rowData[] = __($this->Risks->getCriteriasDetails($criteriaName)['name']);
                    $rowData[] = __($operator);
                    $rowData[] = $threshold;
                    $rowData[] = $value;
                    $rowData[] = $riskValue;
                    $rowData[] = $tooltipReference;

                    $tableCells [] = $rowData;
                }
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Risk.Risks/' . $fieldKey, ['attr' => $attr]);
    }
}
