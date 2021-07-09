<?php
namespace Training\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use PHPExcel_Worksheet;

use App\Model\Table\AppTable;

class ImportTrainingSessionTraineeResultsTable extends AppTable
{
    public function initialize(array $config)
    {   
        $this->table('import_mapping');
        parent::initialize($config);

        /*$this->addBehavior('Import.ImportTrainingSessionTraineeResults', [
            'plugin' => 'Training',
            'model' => 'TrainingSessionTraineeResults',
            'backUrl' => ['plugin' => 'Training', 'controller' => 'Trainings', 'action' => 'TrainingSessionResults']
        ]);*/

        $this->addBehavior('Import.Import');

        
        // register table once
        /*$this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $this->InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $this->EducationGrades = TableRegistry::get('Education.EducationGrades');
        $this->StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $this->EducationSubjects = TableRegistry::get('Education.EducationSubjects');
        $this->OutcomeTemplates = TableRegistry::get('Outcome.OutcomeTemplates');
        $this->OutcomePeriods = TableRegistry::get('Outcome.OutcomePeriods');
        $this->OutcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');
        $this->OutcomeGradingTypes = TableRegistry::get('Outcome.OutcomeGradingTypes');*/
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportGetClassificationId' => 'onImportGetClassificationId',
            'Model.import.onImportPopulateAreasData' => 'onImportPopulateAreasData',
            'Model.import.onImportPopulateAreaAdministrativesData' => 'onImportPopulateAreaAdministrativesData',
            'Model.import.onImportPopulateClassificationData' => 'onImportPopulateClassificationData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.custom.onUpdateToolbarButtons' => 'onUpdateToolbarButtons'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $toolbarButtons['back']['url'][0] = $toolbarButtons['back']['url']['action'];
        $toolbarButtons['back']['url']['action'] = 'Results';
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols)
    {
        $columns = new Collection($columns);
        $filtered = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'code';
        });
        $codeIndex = key($filtered->toArray());
        $code = $sheet->getCellByColumnAndRow($codeIndex, $row)->getValue();

        if (in_array($code, $importedUniqueCodes->getArrayCopy())) {
            $rowInvalidCodeCols['code'] = $this->getExcelLabel('Import', 'duplicate_unique_key');
            return false;
        }

        $institution = $this->Institutions->find()->where(['code'=>$code])->first();
        if (!$institution) {
            $tempRow['entity'] = $this->Institutions->newEntity();
        } else {
            $tempRow['entity'] = $institution;
        }
    }

    public function onImportGetClassificationId(Event $event, $cellValue)
    {
        $options = $this->getSelectOptions('Institutions.classifications');
        foreach ($options as $key => $value) {
            if ($cellValue == $key) {
                return $cellValue;
            }
        }
        return null;
    }    

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity)
    {
        $importedUniqueCodes[] = $entity->code;
    }

    public function onImportPopulateAreasData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.area_level_id', $lookupModel.'.order'];

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
                                ->select($selectFields)
                                ->order($order)
                                ;

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateAreaAdministrativesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.area_administrative_level_id', $lookupModel.'.order'];

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
                                ->select($selectFields)
                                ->order($order)
                                ;

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateClassificationData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $translatedReadableCol = $this->getExcelLabel('Classification', 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        $options = $this->getSelectOptions('Institutions.classifications');
        foreach ($options as $key => $value) {
            $data[$columnOrder]['data'][] = [
                $value,
                $key
            ];
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        return true;
    }
}

