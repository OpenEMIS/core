<?php
namespace Examination\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Examination',
            'model' => 'ExaminationItemResults',
            'backUrl' => ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamResults']
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateAcademicPeriodsData'] = 'onImportPopulateAcademicPeriodsData';
        $events['Model.import.onImportPopulateExaminationsData'] = 'onImportPopulateExaminationsData';
        $events['Model.import.onImportPopulateExaminationCentresData'] = 'onImportPopulateExaminationCentresData';
        $events['Model.import.onImportPopulateEducationSubjectsData'] = 'onImportPopulateEducationSubjectsData';
        $events['Model.import.onImportPopulateUsersData'] = 'onImportPopulateUsersData';
        $events['Model.import.onImportPopulateExaminationGradingOptionsData'] = 'onImportPopulateExaminationGradingOptionsData';
        $events['Model.import.onImportLookupExaminationsBeforeQuery'] = 'onImportLookupExaminationsBeforeQuery';
        $events['Model.import.onImportLookupExaminationCentresBeforeQuery'] = 'onImportLookupExaminationCentresBeforeQuery';
        $events['Model.import.onImportLookupEducationSubjectsBeforeQuery'] = 'onImportLookupEducationSubjectsBeforeQuery';
        $events['Model.import.onImportLookupUsersBeforeQuery'] = 'onImportLookupUsersBeforeQuery';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
            ->find('years')
            ->select($selectFields);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->$lookupColumn
                ];
            }
        }
    }

    public function onImportPopulateExaminationsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.name', $lookupModel.'.code'];

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        // show examination list distinct by code (user create same exam in different academic period)
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->group(['code'])
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->$lookupColumn
                ];
            }
        }
    }

    public function onImportPopulateExaminationCentresData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.name', $lookupModel.'.code'];

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['name', $lookupColumn];
        // show examination centre list distinct by institution (same school use as exam centre in different academic period and exam)
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->group(['institution_id'])
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->$lookupColumn
                ];
            }
        }
    }

    public function onImportPopulateEducationSubjectsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $order = [$lookupModel.'.name', $lookupModel.'.code'];

        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $ExaminationCentreSubjects = TableRegistry::get('Examination.ExaminationCentreSubjects');
        $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
        $selectFields = [$lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn), $ExaminationItems->aliasField('weight')];
        // show distinct list of subjects which are added as exam items and subject weight is more than zero
        $modelData = $ExaminationCentreSubjects->find('all')
            ->select($selectFields)
            ->matching($lookedUpTable->alias())
            ->innerJoin([$ExaminationItems->alias() => $ExaminationItems->table()], [
                $ExaminationItems->aliasField('examination_id = ') . $ExaminationCentreSubjects->aliasField('examination_id'),
                $ExaminationItems->aliasField('education_subject_id = ') . $ExaminationCentreSubjects->aliasField('education_subject_id'),
                $ExaminationItems->aliasField('weight > ') => 0
            ])
            ->group([$ExaminationCentreSubjects->aliasField('education_subject_id')])
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->_matchingData[$lookedUpTable->alias()]->name,
                    $row->_matchingData[$lookedUpTable->alias()]->$lookupColumn
                ];
            }
        }
    }

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        unset($data[$columnOrder]);
    }

    public function onImportPopulateExaminationGradingOptionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $ExaminationGradingTypes = TableRegistry::get('Examination.ExaminationGradingTypes');
        $selectFields = [$lookedUpTable->aliasField('code'), $lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn), $ExaminationGradingTypes->aliasField('code'), $ExaminationGradingTypes->aliasField('name')];
        $order = [$ExaminationGradingTypes->aliasField('name'), $lookupModel.'.order'];
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->matching($ExaminationGradingTypes->alias())
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, __('Code'), $translatedCol, __('Grading Types')];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->code,
                    $row->$lookupColumn,
                    $row->_matchingData[$ExaminationGradingTypes->alias()]->name
                ];
            }
        }
    }

    public function onImportLookupExaminationsBeforeQuery(Event $event, Query $lookupQuery, $lookedUpTable, $lookupColumn, ArrayObject $tempRow, ArrayObject $originalRow, $cellValue, ArrayObject $rowInvalidCodeCols, $columnName)
    {
        if ($tempRow->offsetExists('academic_period_id') && !empty($tempRow['academic_period_id'])) {
            $where = [$lookedUpTable->aliasField('academic_period_id') => $tempRow['academic_period_id']];
            $lookupQuery->where([$where]);

            if ($lookupQuery->count() == 0) {
                $rowInvalidCodeCols[$columnName] = __('Selected value does not match with Academic Period');
            }
        }
    }

    public function onImportLookupExaminationCentresBeforeQuery(Event $event, Query $lookupQuery, $lookedUpTable, $lookupColumn, ArrayObject $tempRow, ArrayObject $originalRow, $cellValue, ArrayObject $rowInvalidCodeCols, $columnName)
    {
        if ($tempRow->offsetExists('academic_period_id') && $tempRow->offsetExists('examination_id')) {
            if (!empty($tempRow['academic_period_id']) && !empty($tempRow['examination_id'])) {
                $where = [
                    $lookedUpTable->aliasField('academic_period_id') => $tempRow['academic_period_id'],
                    $lookedUpTable->aliasField('examination_id') => $tempRow['examination_id']
                ];
                $lookupQuery->where([$where]);

                if ($lookupQuery->count() == 0) {
                    $rowInvalidCodeCols[$columnName] = __('Selected value does not match with Academic Period and Examination');
                }
            }
        }
    }

    public function onImportLookupEducationSubjectsBeforeQuery(Event $event, Query $lookupQuery, $lookedUpTable, $lookupColumn, ArrayObject $tempRow, ArrayObject $originalRow, $cellValue, ArrayObject $rowInvalidCodeCols, $columnName)
    {
        if ($tempRow->offsetExists('academic_period_id') && $tempRow->offsetExists('examination_id') && $tempRow->offsetExists('examination_centre_id')) {
            if (!empty($tempRow['academic_period_id']) && !empty($tempRow['examination_id']) && !empty($tempRow['examination_centre_id'])) {
                $ExaminationCentreSubjects = TableRegistry::get('Examination.ExaminationCentreSubjects');
                $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
                // overwrite lookup query
                $lookupQuery = $ExaminationCentreSubjects
                    ->find()
                    ->matching($lookedUpTable->alias(), function ($q) use ($lookupColumn, $cellValue) {
                        return $q->where([$lookupColumn => $cellValue]);
                    })
                    ->where([
                        $ExaminationCentreSubjects->aliasField('academic_period_id') => $tempRow['academic_period_id'],
                        $ExaminationCentreSubjects->aliasField('examination_id') => $tempRow['examination_id'],
                        $ExaminationCentreSubjects->aliasField('examination_centre_id') => $tempRow['examination_centre_id']
                    ]);

                if ($lookupQuery->count() == 0) {
                    $rowInvalidCodeCols[$columnName] = __('Selected value does not match with Academic Period and Examination');
                }
            }
        }
    }

    public function onImportLookupUsersBeforeQuery(Event $event, Query $lookupQuery, $lookedUpTable, $lookupColumn, ArrayObject $tempRow, ArrayObject $originalRow, $cellValue, ArrayObject $rowInvalidCodeCols, $columnName)
    {
        if ($tempRow->offsetExists('academic_period_id') && $tempRow->offsetExists('examination_id') && $tempRow->offsetExists('examination_centre_id') && $tempRow->offsetExists('education_subject_id')) {
            if (!empty($tempRow['academic_period_id']) && !empty($tempRow['examination_id']) && !empty($tempRow['examination_centre_id']) && !empty($tempRow['education_subject_id'])) {
                $ExaminationCentreStudents = TableRegistry::get('Examination.ExaminationCentreStudents');
                // overwrite lookup query
                $lookupQuery = $ExaminationCentreStudents
                    ->find()
                    ->matching($lookedUpTable->alias(), function ($q) use ($lookupColumn, $cellValue) {
                        return $q->where([$lookupColumn => $cellValue]);
                    })
                    ->where([
                        $ExaminationCentreStudents->aliasField('academic_period_id') => $tempRow['academic_period_id'],
                        $ExaminationCentreStudents->aliasField('examination_id') => $tempRow['examination_id'],
                        $ExaminationCentreStudents->aliasField('examination_centre_id') => $tempRow['examination_centre_id'],
                        $ExaminationCentreStudents->aliasField('education_subject_id') => $tempRow['education_subject_id']
                    ]);

                if ($lookupQuery->count() == 0) {
                    $rowInvalidCodeCols[$columnName] = __('Selected value does not match with Examination Centre and Education Subject');
                } else {
                    // logic to automatically set institution_id (value = 0 for private candidate)
                    $entity = $lookupQuery->first();
                    $tempRow['institution_id'] = $entity->institution_id;
                }
            }
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
        if ($tempRow->offsetExists('examination_id') && $tempRow->offsetExists('education_subject_id')) {
            if (!empty($tempRow['examination_id']) && !empty($tempRow['education_subject_id'])) {
                $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
                $examinationItemResults = $ExaminationItems
                    ->find()
                    ->contain(['ExaminationGradingTypes.GradingOptions'])
                    ->where([
                        $ExaminationItems->aliasField('examination_id') => $tempRow['examination_id'],
                        $ExaminationItems->aliasField('education_subject_id') => $tempRow['education_subject_id']
                    ])
                    ->all();

                if ($examinationItemResults->isEmpty()) {
                    // will never come to here unless data corrupted as user is not able to delete exam item after register a student to exam
                    $rowInvalidCodeCols['education_subject_id'] = __('Examination Item is not configured in the examination');
                    return false;
                } else {
                    $examinationItemEntity = $examinationItemResults->first();
                    if ($examinationItemEntity->has('examination_grading_type')) {
                        $gradingTypeEntity = $examinationItemEntity->examination_grading_type;
                        if (empty($gradingTypeEntity->grading_options)) {
                            // exam item is linked to a grading type but no grading options is added
                            $rowInvalidCodeCols['education_subject_id'] = __('Examination Grading Options is not configured for '.$examinationItemEntity->examination_grading_type->code_name);
                            return false;
                        } else {
                            if ($tempRow->offsetExists('marks') && $tempRow->offsetExists('examination_grading_option_id')) {
                                $marksCell = $tempRow['marks'];
                                $gradingOptionIdCell = $tempRow['examination_grading_option_id'];

                                if ($gradingTypeEntity->result_type == 'MARKS') {
                                    $validationPass = true;

                                    if (strlen($marksCell) == 0) {
                                        // not allow empty for marks type
                                        $rowInvalidCodeCols['marks'] = __('This field cannot be left empty');
                                        $validationPass = false;
                                    } else {
                                        if ($marksCell > $gradingTypeEntity->max) {
                                            // marks entered cannot be more than the maximum mark configured
                                            $rowInvalidCodeCols['marks'] = __('This field cannot be more than ' . $gradingTypeEntity->max);
                                            $validationPass = false;
                                        }
                                    }

                                    if (strlen($gradingOptionIdCell) > 0) {
                                        // this value is not applicable for marks type
                                        $rowInvalidCodeCols['examination_grading_option_id'] = __('This field is not applicable to subject of Marks type');
                                        $validationPass = false;
                                    }

                                    return $validationPass;
                                } else if ($gradingTypeEntity->result_type == 'GRADES') {
                                    $validationPass = true;

                                    if (strlen($marksCell) > 0) {
                                        // this value is not applicable for grades type
                                        $rowInvalidCodeCols['marks'] = __('This field is not applicable to subject of Grades type');
                                        $validationPass = false;
                                    }

                                    if (strlen($gradingOptionIdCell) == 0) {
                                        // not allow empty for grades type
                                        $rowInvalidCodeCols['examination_grading_option_id'] = __('This field cannot be left empty');
                                        $validationPass = false;
                                    } else {
                                        $valid = false;
                                        $gradingOptions = $gradingTypeEntity->grading_options;
                                        foreach ($gradingOptions as $key => $obj) {
                                            if ($gradingOptionIdCell == $obj->id) {
                                                $valid = true;
                                            }
                                        }

                                        if (!$valid) {
                                            $rowInvalidCodeCols['examination_grading_option_id'] = __('Selected value does not match with Examination Grading Options of the subject');
                                            $validationPass = false;
                                        }
                                    }

                                    return $validationPass;
                                }
                            }
                        }
                    } else {
                        // will never come to here unless orphan record in exam item
                        $rowInvalidCodeCols['education_subject_id'] = __('Examination Grading Type is not configured');
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
