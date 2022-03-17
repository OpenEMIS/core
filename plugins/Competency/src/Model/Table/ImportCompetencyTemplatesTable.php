<?php
namespace Competency\Model\Table;

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

class ImportCompetencyTemplatesTable extends AppTable {

     public function initialize(array $config) {

        $this->table('import_mapping');
        parent::initialize($config);
         
        $this->addBehavior('Import.Import', [
            'plugin'=>'Competency', 
            'model'=>'CompetencyTemplates',
            'backUrl' => ['plugin' => 'Competency', 'controller' => 'Competencies', 'action' => 'Templates']
        ]);  
        //POCOR-6616 start
        $this->belongsTo('EducationGrade', ['className' => 'Education.EducationGrades']);  
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
        $this->competencyTemplates = TableRegistry::get('Competency.CompetencyTemplates');
        //POCOR-6616 end
    }    

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            /*'Model.import.onImportPopulateAcademicPeriodsData' => 'onImportPopulateAcademicPeriodsData',*/
            'Model.import.onImportPopulateEducationProgrammesData' => 'onImportPopulateEducationProgrammesData',
            'Model.import.onImportPopulateEducationGradesData' => 'onImportPopulateEducationGradesData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    /*public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData as $row) {
                if ($row->academic_period_level_id == 1) { //validate that only period level "year" will be shown
                    $date = $row->start_date;
                    $data[$columnOrder]['data'][] = [
                        $row->name,
                        $row->start_date->format('d/m/Y'),
                        $row->end_date->format('d/m/Y'),
                        $row->{$lookupColumn}
                    ];
                }
            }
        }
    }
*/
    public function onImportPopulateEducationProgrammesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $request = $this->request;
        $selectedperiod = $request->query('period'); //POCOR-6616
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2; //POCOR-6616
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if($selectedperiod!=null){
            $modelData = $lookedUpTable->find('visible')
                                ->select(['code', 'name'])
                                ->contain(['EducationCycles.EducationLevels.EducationSystems'])//POCOR-6616
                                ->where(['EducationSystems.academic_period_id' => $selectedperiod])//POCOR-6616
                                ->order([
                                    $lookupModel.'.order'
                                ]);
        }else{
            $modelData = $lookedUpTable->find('visible')
                                ->select(['code', 'name'])
                                ->order([
                                    $lookupModel.'.order'
                                ]);
        }

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    //POCOR-6616 Start
                    $row->name,
                    $row->code
                    //POCOR-6616 End
                ];
            }
        }        
    }

    public function onImportPopulateEducationGradesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $request = $this->request;
        $selectedperiod = $request->query('period'); //POCOR-6616
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $programmeHeader = $this->getExcelLabel($lookedUpTable, 'education_programme_id');
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;//POCOR-6616
        $data[$columnOrder]['data'][] = [$programmeHeader, $translatedReadableCol, $translatedCol];
        if($selectedperiod!=null){
            $modelData = $lookedUpTable->find('visible')
                                //->contain(['EducationProgrammes'])
                                ->select(['code', 'name', 'EducationProgrammes.name'])
                                //POCOR-6616
                                ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])//
                                ->where(['EducationSystems.academic_period_id' => $selectedperiod])
                                ->group([$lookupModel.'.name'])
                                //POCOR-6616
                                ->order([
                                    'EducationProgrammes.order',
                                    $lookupModel.'.order'
                                ]);
        }else{
            $modelData = $lookedUpTable->find('visible')
                                ->contain(['EducationProgrammes'])
                                ->select(['code', 'name', 'EducationProgrammes.name'])
                                ->order([
                                    'EducationProgrammes.order',
                                    $lookupModel.'.order'
                                ]);

        }
    
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->education_programme->name,
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }        
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $CompetencyTemplates = TableRegistry::get('Competency.CompetencyTemplates');
        //POCOR-6616 start
        $request = $this->request;
        $tempRow['academic_period_id'] = $request->query('period');
        //POCOR-6616 end
        $CompetencyTemplates = $CompetencyTemplates->find()
            ->where([
                'code' => $tempRow['code'],
                'academic_period_id' => $tempRow['academic_period_id']
            ])
            ->count();

        if ($CompetencyTemplates > 0) {
            $rowInvalidCodeCols['code'] = __('This code already exists');
            return false;
        }

        if (empty($tempRow['name'])) {
            $rowInvalidCodeCols['name'] = __('Name should not be empty');
            return false;
        }
        if (empty($tempRow['academic_period_id'])) {
            $rowInvalidCodeCols['academic_period_id'] = __('Please select academic period'); //POCOR-6616 start
            return false;
        }
        
        if (empty($tempRow['education_grade_id'])) {
            $rowInvalidCodeCols['education_grade_id'] = __('Education Grade should not be empty');
            return false;
        }

        return true;
    }

    /**
    * POCOR-6616 
    * add filter in template page
    */
    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->ControllerAction->field('academic_period_id', [
            'type' => 'select',
            'select' => false,
            'entity' => $entity,
            'before' => 'select_file'
        ]);
        /*$this->ControllerAction->field('education_programme_id', [
            'type' => 'select',
            'entity' => $entity,
            'before' => 'select_file',
        ]);
        $this->ControllerAction->field('education_grade_id', [
            'type' => 'select',
            'entity' => $entity,
            'before' => 'select_file'
        ]);*/
    }

    /**
    * POCOR-6616 
    */
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriod($this->request->query('period'), true));

        if ($action == 'add') {
             $attr['default'] = $selectedPeriod;
            $attr['options'] = $periodOptions;
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        }
        return $attr;
    }

    /**
    * POCOR-6616 
    */
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

    /**
    * POCOR-6616 
    */
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

    /**
    * POCOR-6616 
    */
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

    /**
    * POCOR-6616 
    */
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

    /**
    * POCOR-6616 
    */
    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        list($gradeOption, $selectedGrade) = array_values($this->getEducationGrade($this->request->query('grade'), true));

        if ($action == 'add') {
            $request = $this->request;
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

    /**
    * POCOR-6616 
    */
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

    /**
    * POCOR-6616 
    */
    public function getEducationGrade($querystringGrade, $withOptions = false)
    {
        $educationGrades = $this->EducationGrades->getEducationGrades();
        // $firstKey = array_key_first($educationGrades);
        if ($querystringGrade) {
            $selectedGrade = $querystringGrade;
        } else {
            $selectedGrade = '';//$educationGrades[$firstKey];
        }
        if ($withOptions){
            $gradeOptions = $educationGrades;
            return compact('gradeOptions', 'selectedGrade');
        } else {
            return $selectedGrade;
        }
    }



    
}
