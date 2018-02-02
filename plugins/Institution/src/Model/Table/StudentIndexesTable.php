<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\ResultSet;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class StudentIndexesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_risks');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Risks', ['className' => 'Risk.Risks', 'foreignKey' =>'index_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' =>'student_id']);

        $this->hasMany('StudentIndexesCriterias', ['className' => 'Institution.StudentIndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('average_index', ['visible' => false]);
        $this->field('student_id', ['visible' => false]);
        $this->field('total_index', ['after' => 'index_id']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('generated_by', ['visible' => false]);
        $this->field('generated_on', ['visible' => false]);
        $this->field('institution', ['after' => 'academic_period_id']);

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
        $this->field('indexes_criterias', ['type' => 'custom_criterias', 'after' => 'total_index']);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
    }

    public function onGetInstitution(Event $event, Entity $entity)
    {
        return $entity->institution->name;
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
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $studentId = $session->read('Student.Students.id');

        $query = $query
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']
            ])
            ->order(['index_id'])
            ;

        return $query;
    }

    private function setupTabElements()
    {
        $options['type'] = 'student';
        $tabElements = $this->controller->getAcademicTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        // from indexes table
        $Indexes = TableRegistry::get('Risk.Risks');
        $indexId = $entity->index->id;
        $generatedById = $Indexes->get($indexId)->generated_by;

        $userName = '';
        if (!empty($generatedById)) {
            $userName = $this->Users->get($generatedById)->first_name . ' ' . $this->Users->get($generatedById)->last_name;
        }

        return $userName;
    }

    public function onGetGeneratedOn(Event $event, Entity $entity)
    {
        // from indexes table
        $Indexes = TableRegistry::get('Risk.Risks');
        $indexId = $entity->index->id;
        $indexesGeneratedOn = $Indexes->get($indexId)->generated_on;

        $generatedOn = 0;
        if (isset($indexesGeneratedOn)) {
            $generatedOn = $indexesGeneratedOn->format('F d, Y - H:i:s');
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
        $fieldKey = 'indexes_criterias';

        $indexId = $entity->index->id;
        $institutionId = $entity->institution->id;
        $studentId = $entity->user->id;
        $academicPeriodId = $entity->academic_period_id;

        $institutionStudentIndexId = $this->paramsDecode($this->paramsPass(0))['id']; // paramsPass(0) after the hash of Id

        if ($action == 'view') {
            $studentIndexesCriteriasResults = $this->StudentIndexesCriterias->find()
                ->contain(['RiskCriterias'])
                ->where([
                    $this->StudentIndexesCriterias->aliasField('institution_student_index_id') => $institutionStudentIndexId,
                    $this->StudentIndexesCriterias->aliasField('value') . ' IS NOT NULL'
                ])
                ->order(['criteria','threshold'])
                ->all();

            foreach ($studentIndexesCriteriasResults as $key => $obj) {
                if (isset($obj->indexes_criteria)) {
                    $indexesCriteriasId = $obj->indexes_criteria->id;

                    $criteriaName = $obj->indexes_criteria->criteria;
                    $operatorId = $obj->indexes_criteria->operator;
                    $operator = $this->Indexes->getOperatorDetails($operatorId);
                    $threshold = $obj->indexes_criteria->threshold;

                    $value = $this->StudentIndexesCriterias->getValue($institutionStudentIndexId, $indexesCriteriasId);

                    $criteriaDetails = $this->Indexes->getCriteriasDetails($criteriaName);
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

                        $indexValue = '<div style="color : red">' . $obj->indexes_criteria->index_value . $quantity  .'</div>';

                        // for reference tooltip
                        $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);

                        // for threshold name
                        $thresholdName = $LookupModel->get($threshold)->name;
                        $threshold = $thresholdName;
                        if ($thresholdName == 'Repeated') {
                            $threshold = $this->Indexes->getCriteriasDetails($criteriaName)['threshold']['value']; // 'Yes'
                        }
                    } else {
                        // numeric value come here (absence quantity, results)
                        // for value
                        $indexValue = '<div style="color : red">'.$obj->indexes_criteria->index_value.'</div>';

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
                    $rowData[] = __($this->Indexes->getCriteriasDetails($criteriaName)['name']);
                    $rowData[] = __($operator);
                    $rowData[] = $threshold;
                    $rowData[] = $value;
                    $rowData[] = $indexValue;
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
