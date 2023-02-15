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
        $this->addBehavior('Excel', ['pages' => ['index']]);

    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->request->query;
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $session = $this->controller->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $selectedAcademicPeriodId = !is_null($this->request->query('academic_period_id')) ? $this->request->query('academic_period_id') : $this->AcademicPeriods->getCurrent();
       
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId);
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

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
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId,
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
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        
        $this->field('academic_period_id', ['visible' => true,'type' => 'readonly']);
        $this->field('name', ['visible' => true,'type' => 'readonly']);
        $this->field('type', ['type' => 'select','type' => 'readonly']);
        $this->field('category', ['type' => 'select','type' => 'readonly']);
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
            $attr['options'] = ['id' => '-- ' . __('Select Position') . ' --']+$curricularPositionsList;
            $attr['onChangeReload'] = false;  
        }
        return $attr;
    }
	
}
