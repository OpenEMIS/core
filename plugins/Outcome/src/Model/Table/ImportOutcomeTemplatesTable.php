<?php
namespace Outcome\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTime;
use PHPExcel_Worksheet;

class ImportOutcomeTemplatesTable extends AppTable {

    public function initialize(array $config) {

        $this->table('import_mapping');

        parent::initialize($config);

        $this->belongsTo('EducationGrade', ['className' => 'Education.EducationGrades']);

        $this->addBehavior('Import.Import', [
            'plugin'=>'Outcome', 
            'model'=>'OutcomeTemplates',
            'backUrl' => ['plugin' => 'Outcome', 'controller' => 'Outcomes', 'action' => 'Templates']
        ]);

        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
    }
    
    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    
    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->ControllerAction->field('academic_period_id', [
            'type' => 'select',
            'select' => false,
            'entity' => $entity,
            'before' => 'select_file'
        ]);
        $this->ControllerAction->field('education_programme_id', [
            'type' => 'select',
            'entity' => $entity,
            'before' => 'select_file',
        ]);
        $this->ControllerAction->field('education_grade_id', [
            'type' => 'select',
            'entity' => $entity,
            'before' => 'select_file'
        ]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriod($this->request->query('period'), true));

        if ($action == 'add') {
            # $attr['default'] = $selectedPeriod;
            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        }
        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['period'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function getAcademicPeriod($querystringPeriod, $withOptions = false)
    {
        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }
        if ($withOptions){
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);
            return compact('periodOptions', 'selectedPeriod');
        } else {
            return $selectedPeriod;
        }
    }

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        if ($action == 'add') {
			$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			if(!empty($this->request->query('period')) && empty($request->data($this->aliasField('academic_period_id')))) {
				$academicPeriodId = $this->request->query('period');
			} else {
				$academicPeriodId = !is_null($request->data($this->aliasField('academic_period_id'))) ? $request->data($this->aliasField('academic_period_id')) : $AcademicPeriod->getCurrent();					
			}	
			
			$programmeOptions = $EducationProgrammes
                    ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                    ->find('availableProgrammes')
					->contain(['EducationCycles.EducationLevels.EducationSystems'])
                    ->where(['EducationSystems.academic_period_id' => $academicPeriodId])
					->toArray();	

            $attr['options'] = $programmeOptions;
            $attr['onChangeReload'] = 'changeEducationProgrammeId';
        }
        return $attr;
    }

    public function addEditOnChangeEducationProgrammeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['programme']);
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_programme_id', $request->data[$this->alias()])) {
                    $request->query['programme'] = $request->data[$this->alias()]['education_programme_id'];
                }
            }
        }
    }

    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        list($gradeOption, $selectedGrade) = array_values($this->getEducationGrade($this->request->query('grade'), true));

        if ($action == 'add') {
            $selectedProgramme = $request->query('programme');
            $gradeOptions = [];
            if (!is_null($selectedProgramme)) {
                $gradeOptions = $this->EducationGrade
                    ->find('list')
                    ->find('visible')
                    ->contain(['EducationProgrammes'])
                    ->where([$this->EducationGrade->aliasField('education_programme_id') => $selectedProgramme])
                    ->order(['EducationProgrammes.order' => 'ASC', $this->EducationGrade->aliasField('order') => 'ASC'])
                    ->toArray();
            }
            $attr['options'] = $gradeOptions;
            $attr['default'] = $selectedGrade;
            $attr['onChangeReload'] = 'changeEducationGrade';
        }
        return $attr;
    }

    public function addEditOnChangeEducationGrade(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_grade_id', $request->data[$this->alias()])) {
                    $request->query['grade'] = $request->data[$this->alias()]['education_grade_id'];
                    $this->isGradeUpdate = true;
                }
            }
        }
    }

    public function getEducationGrade($querystringGrade, $withOptions = false)
    {
        $educationGrades = $this->EducationGrades->getEducationGrades();
        $firstKey = array_key_first($educationGrades);
        if ($querystringGrade) {
            $selectedGrade = $querystringGrade;
        } else {
            $selectedGrade = $educationGrades[$firstKey];
        }
        if ($withOptions){
            $gradeOptions = $educationGrades;
            return compact('gradeOptions', 'selectedGrade');
        } else {
            return $selectedGrade;
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $selectedPeriod = $this->getAcademicPeriod($this->request->query('period'));
        $selectedGrade = $this->getEducationGrade($this->request->query('grade'));

        if ($selectedPeriod) {
            $tempRow['academic_period_id'] = $selectedPeriod;
        }

        if ($selectedGrade) {
            $tempRow['education_grade_id'] = $selectedGrade;
        }

        if (!isset($tempRow['academic_period_id']) || empty($tempRow['academic_period_id'])) {
            $rowInvalidCodeCols['academic_period_id'] = __('Academic Period should not be empty');
            return false;
        }

        if (!isset($tempRow['academic_period_id']) || empty($tempRow['education_grade_id']) || !is_numeric($tempRow['education_grade_id'])) {
            $rowInvalidCodeCols['education_grade_id'] = __('Education Grade should not be empty');
            return false;
        }

        $CompetencyTemplates = TableRegistry::get('Competency.CompetencyTemplates');

        $CompetencyTemplates = $CompetencyTemplates->find()
            ->where([
                'code' => $tempRow['code'],
                'academic_period_id' => $tempRow['academic_period_id']
            ])->count();

        if ($CompetencyTemplates > 0 && (isset($tempRow['academic_period_id']) && !empty($tempRow['academic_period_id']))) {
            $rowInvalidCodeCols['code'] = __('This code already exists');
            return false;
        }
        if (empty($tempRow['name'])) {
            $rowInvalidCodeCols['name'] = __('Name should not be empty');
            return false;
        }
        return true;
    }
}
