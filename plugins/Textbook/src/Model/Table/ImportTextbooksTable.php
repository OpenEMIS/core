<?php
namespace Textbook\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportTextbooksTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Textbook',
            'model' => 'Textbooks',
            'backUrl' => ['plugin' => 'Textbook', 'controller' => 'Textbooks', 'action' => 'Textbooks']
        ]);

        $this->EducationGradesSubjects = TableRegistry::get('Education.EducationGradesSubjects');
        $this->Textbooks = TableRegistry::get('Textbook.Textbooks');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateAcademicPeriodsData'] = 'onImportPopulateAcademicPeriodsData';
        $events['Model.import.onImportPopulateEducationGradesData'] = 'onImportPopulateEducationGradesData';
        $events['Model.import.onImportPopulateEducationSubjectsData'] = 'onImportPopulateEducationSubjectsData';
        return $events;
    }

    public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
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

    public function onImportPopulateEducationGradesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
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

    public function onImportPopulateEducationSubjectsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $gradeHeader = $this->getExcelLabel($lookedUpTable, 'education_grade_id');
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$gradeHeader, $translatedReadableCol, $translatedCol];
        
        $modelData = $this->EducationGradesSubjects->find('all')
                        ->contain(['EducationGrades.EducationProgrammes', 'EducationSubjects'])
                        ->where([
                            'EducationGrades.visible = 1'
                        ])
                        ->order([
                            'EducationProgrammes.order',
                            'EducationGrades.order',
                            'EducationSubjects.order'
                        ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->education_grade->name,
                    $row->education_subject->name,
                    $row->education_subject->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $validationResult = true;

        //check combination of Grade and Subject
        $query = $this->EducationGradesSubjects->find()
                    ->where([
                        $this->EducationGradesSubjects->aliasField('education_grade_id') => $tempRow['education_grade_id'],
                        $this->EducationGradesSubjects->aliasField('education_subject_id') => $tempRow['education_subject_id']
                    ])
                    ->count();

        if ($query <= 0) { //combinatin not found
            $rowInvalidCodeCols['education_grade_id, education_subject_id'] = __('Wrong combination of Education Grade and Subject.');
            $validationResult = false;
        }

        //check unique code
        $query = $this->Textbooks->find()
                    ->where([
                        $this->Textbooks->aliasField('code') => $tempRow['code']
                    ])
                    ->count();

        if ($query > 0) { //code already exist
            $rowInvalidCodeCols['code'] = __('Textbook Code already exist.');
            $validationResult = false;
        }

        //check year_published must be following config
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $lowestYear = $ConfigItems->value('lowest_year');

        if (!is_numeric($tempRow['year_published'])) {
            $rowInvalidCodeCols['year_published'] = __('Invalid format.');
            $validationResult = false;
        } else {
            if ($tempRow['year_published'] < $lowestYear || $tempRow['year_published'] > date("Y")) {
                $rowInvalidCodeCols['year_published'] = __('Year is out of the Configuration range.');
                $validationResult = false;
            }
        }

        if (!$validationResult) {
            return false;
        } else {
            return true;
        }
    }
}
