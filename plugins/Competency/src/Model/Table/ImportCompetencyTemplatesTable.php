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
    }    

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportPopulateAcademicPeriodsData' => 'onImportPopulateAcademicPeriodsData',
            'Model.import.onImportPopulateEducationProgrammesData' => 'onImportPopulateEducationProgrammesData',
            'Model.import.onImportPopulateEducationGradesData' => 'onImportPopulateEducationGradesData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
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

    public function onImportPopulateEducationProgrammesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        
        $modelData = $lookedUpTable->find('visible')
                            ->select(['code', 'name'])
                            ->order([
                                $lookupModel.'.order'
                            ]);
    
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->code,
                    $row->name
                ];
            }
        }        
    }

    public function onImportPopulateEducationGradesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $programmeHeader = $this->getExcelLabel($lookedUpTable, 'education_programme_id');
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$programmeHeader, $translatedReadableCol, $translatedCol];

        $modelData = $lookedUpTable->find('visible')
                            ->contain(['EducationProgrammes'])
                            ->select(['code', 'name', 'EducationProgrammes.name'])
                            ->order([
                                'EducationProgrammes.order',
                                $lookupModel.'.order'
                            ]);
    
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
            $rowInvalidCodeCols['academic_period_id'] = __('Academic Period should not be empty');
            return false;
        }

        if (empty($tempRow['education_grade_id'])) {
            $rowInvalidCodeCols['education_grade_id'] = __('Education Grade should not be empty');
            return false;
        }

        return true;
    }
}
