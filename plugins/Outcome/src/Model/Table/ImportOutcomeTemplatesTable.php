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

    private $_currentData = null;

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
        $this->competencyTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
    }
    
    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportPopulateEducationSubjectCodeData' => 'onImportPopulateEducationSubjectCodeData',
            'Model.import.onImportPopulateOutcomeGradingTypesData' => 'onImportPopulateOutcomeGradingTypesData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols)
    {
        $selectedPeriod = $this->getAcademicPeriod($this->request->query('period'));
        $columns = new Collection($columns);
        $extractedOutcomeTemplateCode = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'outcome_template_code';
        });
        $outcomeTemplateCodeIndex = key($extractedOutcomeTemplateCode->toArray());
        $outcomeTemplateCode = $sheet->getCellByColumnAndRow($outcomeTemplateCodeIndex, $row)->getValue();
        $CompetencyTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
        $competencyTemplatesObject = $CompetencyTemplates->find()->where([
            'code' => $outcomeTemplateCode,
            'academic_period_id' => $selectedPeriod
        ])->first();
        if ($competencyTemplatesObject) {
            $tempRow['entity'] = $competencyTemplatesObject;
        } else {
            $tempRow['entity'] = $this->competencyTemplates->newEntity();
        }
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

    public function onImportPopulateEducationProgrammesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];        
        $modelData = $lookedUpTable->find('visible')
                            ->select(['code', 'name'])
                            ->order([$lookupModel.'.order']);
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->code,
                    $row->name
                ];
            }
        }        
    }

    public function onImportPopulateEducationSubjectsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $gradeId = (int) $this->request->query('grade');
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        $modelData = $lookedUpTable->find('visible')->select(['code', 'name']);
        if ($gradeId > 0) {
            $modelData->innerJoinWith('EducationGrades', function ($q) use ($gradeId) {
                return $q->where(['EducationGrades.id' => $gradeId]);
            });
        }
        $modelData->order([$lookupModel.'.order']);
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->code,
                    $row->code
                ];
            }
        }
    }

    public function onImportPopulateOutcomeGradingTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'code');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        $modelData = $lookedUpTable->find()
            ->select(['code', 'name'])
            ->order([$lookupModel.'.code']);
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->code,
                    $row->code
                ];
            }
        }
    }

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        foreach ($this->_currentData AS $criteriaData) {
            $competencyTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
            $latest = $competencyTemplates->find()->where([
                'code' => $criteriaData['outcome_template_code'],
                'academic_period_id' => $criteriaData['academic_period_id']
            ])->order($competencyTemplates->aliasField('id') . ' DESC')->first();

            if ($latest) {
                $competencyTemplateInsertedId = $latest->id;
                $ContactTable = TableRegistry::get('Outcome.OutcomeCriterias');
                $data = [
                    'code' => $criteriaData['criteria_code'],
                    'name' => $criteriaData['criteria_name'],
                    'academic_period_id' => $criteriaData['academic_period_id'],
                    'outcome_template_id' => $competencyTemplateInsertedId,
                    'education_grade_id' =>$criteriaData['education_grade_id'],
                    'education_subject_id' => $criteriaData['education_subject_code'],
                    'outcome_grading_type_id' => $criteriaData['outcome_grading_type'],
                ];
                $contactEntity = $ContactTable->newEntity($data);
                $ContactTable->save($contactEntity);
            }
        }  
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $CompetencyTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
        $selectedPeriod = $this->getAcademicPeriod($this->request->query('period'));
        $selectedGrade = $this->getEducationGrade($this->request->query('grade'));
        $tempRow['code'] = $tempRow['outcome_template_code'];
        $tempRow['name'] = $tempRow['outcome_template_name'];

        if ($selectedPeriod) {
            $tempRow['academic_period_id'] = $selectedPeriod;
        }

        if ($selectedGrade) {
            $tempRow['education_grade_id'] = $selectedGrade;
        }

        if ($tempRow['code'] == '' || empty($tempRow['code'])) {
            $rowInvalidCodeCols['outcome_template_code'] = __('Outcome Template Code should not be empty');
            return false;
        }

        if ($tempRow['name'] == '' || empty($tempRow['name'])) {
            $rowInvalidCodeCols['outcome_template_name'] = __('Outcome Template Name should not be empty');
            return false;
        }
        
        if (!isset($tempRow['academic_period_id']) || empty($tempRow['academic_period_id'])) {
            $rowInvalidCodeCols['academic_period_id'] = __('Academic Period should not be empty');
            return false;
        }

        if (!isset($tempRow['academic_period_id']) || empty($tempRow['education_grade_id']) || !is_numeric($tempRow['education_grade_id'])) {
            $rowInvalidCodeCols['education_grade_id'] = __('Education Grade should not be empty');
            return false;
        }

        /** START: Criteria Validations */
        if (empty($tempRow['criteria_code'])) {
            $rowInvalidCodeCols['criteria_code'] = __('Criteria Code should not be empty');
            return false;
        }

        if (empty($tempRow['criteria_name'])) {
            $rowInvalidCodeCols['criteria_name'] = __('Criteria Name should not be empty');
            return false;
        }

        if ($tempRow['education_subject_code'] < 1) {
            $rowInvalidCodeCols['education_subject_code'] = __('Education Subject should not be empty');
            return false;
        }

        if ($tempRow['outcome_grading_type'] < 1) {
            $rowInvalidCodeCols['outcome_grading_type'] = __('Outcome Grading Type should not be empty');
            return false;
        }

        $outcomeCriteriaCount = 1;
        $CompetencyTemplatesCriteria = $CompetencyTemplates->find()->where([
            'code' => $tempRow['code'],
            'academic_period_id' => $selectedPeriod
        ])->first();
        
        if ($CompetencyTemplatesCriteria) {
            $outcomeCriteria = TableRegistry::get('Outcome.OutcomeCriterias');
            $outcomeCriteriaCount = $outcomeCriteria->find()->where([
                'code' => $tempRow['criteria_code'],
                'academic_period_id' => $selectedPeriod,
                'education_grade_id' => $selectedGrade,
                'education_subject_id' => $tempRow['education_subject_code'],
                'outcome_template_id' => $CompetencyTemplatesCriteria->id,
            ])->count();
            
            if ($outcomeCriteriaCount > 0) {
                $rowInvalidCodeCols['criteria_code'] = __('This criteria code already exists');
                return false;
            }
        }
        /** END: Criteria Validations */


        /** START: Outcome template validations */
        $CompetencyTemplates = $CompetencyTemplates->find()
            ->where([
                'code' => $tempRow['code'],
                'academic_period_id' => $tempRow['academic_period_id']
            ])->count();

        if ($outcomeCriteriaCount > 0 && $CompetencyTemplates > 0 && (isset($tempRow['academic_period_id']) && !empty($tempRow['academic_period_id']))) {
            $rowInvalidCodeCols['outcome_template_code'] = __('This code already exists');
            return false;
        }
        if (empty($tempRow['name'])) {
            $rowInvalidCodeCols['outcome_template_name'] = __('Name should not be empty');
            return false;
        }
        /** END: Outcome template validations */


        /** START: Insert the Outcome criterias */
        /*
        $competencyTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
        $latest = $competencyTemplates->find()->order($competencyTemplates->aliasField('id') . ' DESC')->first();
        $competencyTemplateInsertedId = $latest->id + 1;
        if ($CompetencyTemplatesCriteria) {
            $competencyTemplateInsertedId = $CompetencyTemplatesCriteria->id;
        }
        $ContactTable = TableRegistry::get('Outcome.OutcomeCriterias');
        $creatable = [
            'code' => $tempRow['criteria_code'],
            'name' => $tempRow['criteria_name'],
            'academic_period_id' => $selectedPeriod,
            'outcome_template_id' => $competencyTemplateInsertedId,
            'education_grade_id' => $selectedGrade,
            'education_subject_id' => $tempRow['education_subject_code'],
            'outcome_grading_type_id' => $tempRow['outcome_grading_type'],
        ];
        $contactEntity = $ContactTable->newEntity($creatable);
        $ContactTable->save($contactEntity);
        */
        /** END: Insert Outcome criterias */

        $this->_currentData[] = $tempRow;
        return true;
    }
}
