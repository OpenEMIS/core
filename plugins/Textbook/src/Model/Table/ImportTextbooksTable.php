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
        $modelData = $lookedUpTable->find('all')
                            ->contain(['EducationProgrammes'])
                            ->select(['code', 'name', 'EducationProgrammes.name'])
                            ->where([
                                $lookedUpTable->aliasField('visible').' = 1'
                            ])
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
                            'EducationGrades.id IS NOT NULL'
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

        //check expiry_date date format and validity
        // pr($tempRow);die;
        // $expiryDate = explode('/', $tempRow['expiry_date']);
        // if (count($expiryDate) == 3) { //check that dd/mm/yyyy format
        //     if (!checkdate($expiryDate[1], $expiryDate[0], $expiryDate[2])) {
        //         $rowInvalidCodeCols['expiry_date'] = __('Invalid Expiry Date.');
        //         $validationResult = false;
        //     }
        // } else {
        //     $rowInvalidCodeCols['expiry_date'] = __('Invalid Expiry Date format.');
        //     $validationResult = false;
        // }

        if (!$validationResult) {
            return false;
        } else {
            return true;
        }

        // if (!$this->institutionId) {
        //     $rowInvalidCodeCols['institution_id'] = __('No active institution');
        //     $tempRow['institution_id'] = false;
        //     return false;
        // }
        // $tempRow['institution_id'] = $this->institutionId;

        // if ($tempRow->offsetExists('textbook_id') && !empty($tempRow['textbook_id'])) {
        //     $Textbooks = TableRegistry::get('Textbook.Textbooks');
        //     $textbookResults = $Textbooks
        //         ->find()
        //         ->where([$Textbooks->aliasField('id') => $tempRow['textbook_id']])
        //         ->all();

        //     if ($textbookResults->isEmpty()) {
        //         $rowInvalidCodeCols['textbook_id'] = $this->getExcelLabel('Import', 'value_not_in_list');
        //         return false;
        //     } else {
        //         $textbookEntity = $textbookResults->first();
        //         $tempRow['academic_period_id'] = $textbookEntity->academic_period_id;
        //         $tempRow['education_subject_id'] = $textbookEntity->education_subject_id;
        //         $tempRow['education_grade_id'] = $textbookEntity->education_grade_id;

        //         //check for student being assigned 2 same book.
        //         $InstitutionTextbooks = TableRegistry::get('Institution.InstitutionTextbooks');
        //         if ($tempRow->offsetExists('student_id')) {
        //             if (!empty($tempRow['student_id'])) {
        //                 $query = $InstitutionTextbooks->find()
        //                         ->where([
        //                             $InstitutionTextbooks->aliasField('student_id') => $tempRow['student_id'],
        //                             $InstitutionTextbooks->aliasField('textbook_id') => $tempRow['textbook_id'],
        //                             $InstitutionTextbooks->aliasField('institution_id') => $tempRow['institution_id'],
        //                             $InstitutionTextbooks->aliasField('academic_period_id') => $tempRow['academic_period_id'],
        //                             $InstitutionTextbooks->aliasField('education_subject_id') => $tempRow['education_subject_id'],
        //                             $InstitutionTextbooks->aliasField('education_grade_id') => $tempRow['education_grade_id']
        //                         ])
        //                         ->count();
        //                 if ($query > 0) { //student assigned to same book before
        //                     $rowInvalidCodeCols['student_id'] = __('Textbook already assigned to the same student before.');
        //                     return false;
        //                 }
        //             }
        //         }
        //     }
        // }

        
    }

    public function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}
