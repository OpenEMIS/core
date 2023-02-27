<?php
namespace Institution\Model\Table;

use ArrayObject;
use stdClass;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Collection\Collection;
use Cake\I18n\Date;
use Cake\Log\Log;
use Cake\Routing\Router;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Datasource\ResultSetInterface;
use Cake\Network\Session;
use Cake\I18n\Time;

class InstitutionCurricularStudentsTable extends ControllerActionTable
{	
	use MessagesTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('InstitutionCurriculars', ['className' => 'Institution.InstitutionCurriculars']);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);
        $this->addBehavior('Excel', ['pages' => ['index','view']]);
        

    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->request->query;
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $curricularIdGet = $_SESSION['curricularId'];
        $curriculars = TableRegistry::get('institution_curriculars');
        $getAcademicPeriodId = $curriculars->find()
                            ->where([$curriculars->aliasField('id') => $curricularIdGet])
                            ->first()->academic_period_id;
        $selectedAcademicPeriodId = $getAcademicPeriodId;
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $extra['selectedEducationGradeId'] = $selectedEducationGradeId;
        if (!empty($selectedAcademicPeriodId)) {
            $this->request->query['academic_period_id'] = $selectedAcademicPeriodId;
            $gradeOptions = $this->Institutions->InstitutionGrades->getGradeOptionsForIndex($institutionId, $selectedAcademicPeriodId);
            $gradeOptions = [-1 => __('All Grades')] + $gradeOptions;
            $selectedEducationGradeId = $this->queryString('education_grade_id', $gradeOptions);
            $this->request->query['education_grade_id'] = $selectedEducationGradeId;
        }
        $extra['elements']['control'] = [
            'name' => 'Institution.Classes/controls',
            'data' => [
              //  'academicPeriodOptions'=>$academicPeriodOptions,
                //'selectedAcademicPeriod'=>$selectedAcademicPeriodId,
                'gradeOptions'=>$gradeOptions,
                'selectedGrade'=>$selectedEducationGradeId,
            ],
            'options' => [],
            'order' => 3
        ];
        if ($this->action == 'index') {
            $tabElements = $this->controller->getCurricularsTabElements();
            $this->controller->set('tabElements', $tabElements);
            $this->controller->set('selectedAction', 'InstitutionCurricularStudents');
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $curricularIdGet = $_SESSION['curricularId'];
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $grades = TableRegistry::get('education_grades');
        $institutionClass = TableRegistry::get('institution_classes');
        $curricularPositions = TableRegistry::get('curricular_positions');
        $Users = TableRegistry::get('security_users');
        $query
            ->select([
                          'openemis_no'=>  $Users->aliasField('openemis_no'),
                          'studentName' =>  $Users->aliasField('first_name'),
                         /* .''.$Users->aliasField('middle_name').''.$Users->aliasField('third_name').''.$Users->aliasField('last_name'),*/
                          'education_class' => $grades->aliasField('name'),
                          'institution_class' => $institutionClass->aliasField('name'),
                           'curricularPositions' => $curricularPositions->aliasField('name')       
                ])
                ->LeftJoin([$institutionClassStudents->alias() => $institutionClassStudents->table()],
                    [$institutionClassStudents->aliasField('student_id').' = ' . $this->aliasField('student_id')
                ])
                ->LeftJoin([$Users->alias() => $Users->table()],
                    [$Users->aliasField('id').' = ' . $this->aliasField('student_id')
                ])
                ->LeftJoin([$grades->alias() => $grades->table()],
                    [$grades->aliasField('id').' = ' . $institutionClassStudents->aliasField('education_grade_id')
                ])
                ->LeftJoin([$institutionClass->alias() => $institutionClass->table()],
                    [$institutionClass->aliasField('id').' = ' . $institutionClassStudents->aliasField('institution_class_id')
                ])
                ->LeftJoin([$curricularPositions->alias() => $curricularPositions->table()],
                    [$curricularPositions->aliasField('id').' = ' . $this->aliasField('curricular_position_id')
                ]);
                $this->field('institution_curricular_id', ['visible' => true]);
                $this->field('start_date', ['visible' => false]);
                $this->field('end_date', ['visible' => false]);
                $this->field('hours', ['visible' => false]);
                $this->field('points', ['visible' => false]);
                $this->field('location', ['visible' => false]);
                $this->field('comment', ['visible' => false]);
                $this->field('comment', ['visible' => false]);

        
               
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $curricularIdGet = $_SESSION['curricularId'];
        $curriculars = TableRegistry::get('institution_curriculars');
        $academicPeriod = TableRegistry::get('academic_periods');
        $curricularType = TableRegistry::get('curricular_types');
        $curricularData = $curriculars->find()
                            ->select(['name'=>$curriculars->aliasField('name'),'category'=>$curriculars->aliasField('category'),
                                'academicPeriod'=>$academicPeriod->aliasField('name'),
                                'curricularType'=>$curricularType->aliasField('name')
                                        ])
                            ->LeftJoin([$academicPeriod->alias() => $academicPeriod->table()],[
                                $academicPeriod->aliasField('id').' = ' . $curriculars->aliasField('academic_period_id')
                            ])
                            ->LeftJoin([$curricularType->alias() => $curricularType->table()],[
                                $curricularType->aliasField('id').' = ' . $curriculars->aliasField('curricular_type_id')
                            ])
                            ->where([$curriculars->aliasField('id') => $curricularIdGet])->first();
        
        $entity->name = $curricularData->name;
        $entity->category = $curricularData->category ? __('Curricular') : __('Extracurricular');
        $entity->academicPeriod = $curricularData->academicPeriod;
        $entity->curricularType = $curricularData->curricularType;
        $this->field('academic_period_id', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $entity->academicPeriod, 'required' => true]]);
        $this->field('name', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $entity->name, 'required' => true]]);
        $this->field('curricular_type_id', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $entity->curricularType, 'required' => true]]);
        $this->field('category', ['visible' => true, 'type' => 'disabled', 'attr' => ['value' => $entity->category, 'required' => true]]);
        $this->field('student_id', ['type' => 'select','visible' => true]);
        $this->field('institution_curricular_id', ['visible' => false]);
        $this->field('start_date',['attr' => ['label' => __('Start Date')]]);
        $this->field('end_date',['attr' => ['label' => __('End Date')]]);
        $this->field('hours', ['visible' => true,]);
        $this->field('points', ['visible' => true,]);
        $this->field('location', ['visible' => true,]);
        $this->field('curricular_position_id', ['type' => 'select']);
        $this->field('comment', ['visible' => true]);
    }

   public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('start_date', $attr, $request);
        }
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            return $this->updateDateRangeField('end_date', $attr, $request);
        }
    }

    // Misc
    private function updateDateRangeField($key, $attr, Request $request)
    {
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $requestData = $request->data;
        if (array_key_exists($this->alias(), $requestData) && array_key_exists('academic_period_id', $requestData[$this->alias()])) {
            $selectedPeriodId = $requestData[$this->alias()]['academic_period_id'];
        } else {
            $selectedPeriodId = $this->AcademicPeriods->getCurrent();
        }

        $selectedPeriod = $this->AcademicPeriods->get($selectedPeriodId);
        $attr['type'] = 'date';
        $attr['date_options']['startDate'] = $selectedPeriod->start_date->format('d-m-Y');
        $attr['date_options']['endDate'] = $selectedPeriod->end_date->format('d-m-Y');
        
        if (!array_key_exists($this->alias(), $requestData) || !array_key_exists($key, $requestData[$this->alias()])) {
            if ($selectedPeriodId != $this->AcademicPeriods->getCurrent()) {
                $attr['value'] = $selectedPeriod->start_date;
            } else {
                $attr['value'] = Time::now();
            }
        }

        return $attr;
    }

    public function onUpdateFieldCurricularPositionId(Event $event, array $attr, $action, Request $request)
    {
        $curricularPositions = TableRegistry::get('curricular_positions');
        $curricularPositionsList = $curricularPositions->find('list')->where(['visible'=>1])->toArray();
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Position') . ' --'] + $curricularPositionsList;
            $attr['onChangeReload'] = false;  
        }
        return $attr;
    }

    public function onUpdateFieldStudentId(Event $event, array $attr, $action, Request $request)
    {
        $institutionStudents = TableRegistry::get('institution_students');
        $securityUsers = TableRegistry::get('security_users');
        $InstitutionCurriculars = TableRegistry::get('institution_curriculars');
        $curricularIdGet = $_SESSION['curricularId'];
        $academicPeriodId = $InstitutionCurriculars->find()->where(['id'=>$curricularIdGet])->first()->academic_period_id;
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $studentData = $institutionStudents->find('all')->select
                        ([
                            'openemis_no' => $securityUsers->aliasField('openemis_no'),
                            'id' => $securityUsers->aliasField('id'),
                            'first_name' => $securityUsers->aliasField('first_name'),
                            'last_name' => $securityUsers->aliasField('last_name'),
                        ])
                        ->LeftJoin([$securityUsers->alias() => $securityUsers->table()],[
                            $securityUsers->aliasField('id').' = ' . $institutionStudents->aliasField('student_id')
                        ])
                        ->where(['student_status_id'=>1,'institution_id'=>$institutionId,'academic_period_id'=>$academicPeriodId])->toArray();
        $studentList = [] ;
        foreach($studentData as $student){
                $studentList[$student->id] = $student->openemis_no.' - '.$student->first_name.' '.$student->last_name;
        }
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['id' => '-- ' . __('Select Student') . ' --'] + $studentList;
            $attr['onChangeReload'] = false;  
        }
        return $attr;
    }
	
}
