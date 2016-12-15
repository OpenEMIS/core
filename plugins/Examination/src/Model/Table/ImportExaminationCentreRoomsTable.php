<?php
namespace Examination\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportExaminationCentreRoomsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Examination',
            'model' => 'ExaminationCentreRooms',
            'backUrl' => ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamCentres']
        ]);

        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateExaminationsData'] = 'onImportPopulateExaminationsData';
        $events['Model.import.onImportPopulateExaminationCentresData'] = 'onImportPopulateExaminationCentresData';
        // $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $this->ControllerAction->field('academic_period_id', [
            'type' => 'select',
            'select' => false,
            'before' => 'select_file'
        ]);

        // pr($this->fields);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        list($periodOptions, $selectedPeriod) = array_values($this->getAcademicPeriodOptions($this->request->query('period')));

        if ($action == 'add') {
            $attr['default'] = $selectedPeriod;
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

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function onImportPopulateExaminationsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $selectFields = [$lookedUpTable->aliasField('code'), $lookedUpTable->aliasField('name'), $AcademicPeriods->aliasField('code'), $AcademicPeriods->aliasField('name')];
        // $order = [$lookupModel.'.name', $lookupModel.'.code'];
        $order = [$AcademicPeriods->aliasField('order') => 'DESC', $lookedUpTable->aliasField('name')];
        // show examination list distinct by code (user create same exam in different academic period)
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->matching($AcademicPeriods->alias())
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 1;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, __('Code'), $translatedCol, __('Academic Period')];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->code,
                    $row->name,
                    $row->$lookupColumn,
                    $row->_matchingData[$AcademicPeriods->alias()]->name
                ];
            }
        }        
    }

    public function onImportPopulateExaminationCentresData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $selectFields = [$lookedUpTable->aliasField('code'), $lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn), $AcademicPeriods->aliasField('code'), $AcademicPeriods->aliasField('name')];
        // $order = [$lookupModel.'.name', $lookupModel.'.code'];
        $order = [$AcademicPeriods->aliasField('order') => 'DESC', $lookedUpTable->aliasField('name')];
        // show examination list distinct by code (user create same exam in different academic period)
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->matching($AcademicPeriods->alias())
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, __('Code'), $translatedCol, __('Academic Period')];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->code,
                    $row->name,
                    $row->$lookupColumn,
                    $row->_matchingData[$AcademicPeriods->alias()]->name
                ];
            }
        }        
    }

    // public function onImportPopulateEducationSubjectsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    // {
    //     $order = [$lookupModel.'.name', $lookupModel.'.code'];

    //     $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
    //     $ExaminationCentreSubjects = TableRegistry::get('Examination.ExaminationCentreSubjects');
    //     $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
    //     $selectFields = [$lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn), $ExaminationItems->aliasField('weight')];
    //     // show distinct list of subjects which are added as exam items and subject weight is more than zero
    //     $modelData = $ExaminationCentreSubjects->find('all')
    //         ->select($selectFields)
    //         ->matching($lookedUpTable->alias())
    //         ->innerJoin([$ExaminationItems->alias() => $ExaminationItems->table()], [
    //             $ExaminationItems->aliasField('examination_id = ') . $ExaminationCentreSubjects->aliasField('examination_id'),
    //             $ExaminationItems->aliasField('education_subject_id = ') . $ExaminationCentreSubjects->aliasField('education_subject_id'),
    //             $ExaminationItems->aliasField('weight > ') => 0
    //         ])
    //         ->group([$ExaminationCentreSubjects->aliasField('education_subject_id')])
    //         ->order($order);

    //     $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
    //     $data[$columnOrder]['lookupColumn'] = 2;
    //     $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
    //     if (!empty($modelData)) {
    //         foreach($modelData->toArray() as $row) {
    //             $data[$columnOrder]['data'][] = [
    //                 $row->_matchingData[$lookedUpTable->alias()]->name,
    //                 $row->_matchingData[$lookedUpTable->alias()]->$lookupColumn
    //             ];
    //         }
    //     }
    // }

    // public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    // {
    //     unset($data[$columnOrder]);
    // }

    // public function onImportPopulateExaminationGradingOptionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    // {
    //     $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
    //     $ExaminationGradingTypes = TableRegistry::get('Examination.ExaminationGradingTypes');
    //     $selectFields = [$lookedUpTable->aliasField('code'), $lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn), $ExaminationGradingTypes->aliasField('code'), $ExaminationGradingTypes->aliasField('name')];
    //     $order = [$ExaminationGradingTypes->aliasField('name'), $lookupModel.'.order'];
    //     $modelData = $lookedUpTable->find('all')
    //         ->select($selectFields)
    //         ->matching($ExaminationGradingTypes->alias())
    //         ->order($order);

    //     $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
    //     $data[$columnOrder]['lookupColumn'] = 3;
    //     $data[$columnOrder]['data'][] = [$translatedReadableCol, __('Code'), $translatedCol, __('Grading Type')];
    //     if (!empty($modelData)) {
    //         foreach($modelData->toArray() as $row) {
    //             $data[$columnOrder]['data'][] = [
    //                 $row->name,
    //                 $row->code,
    //                 $row->$lookupColumn,
    //                 $row->_matchingData[$ExaminationGradingTypes->alias()]->name
    //             ];
    //         }
    //     }
    // }

    // public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    // {
    //     if ($tempRow->offsetExists('examination_id') && $tempRow->offsetExists('education_subject_id') && $tempRow->offsetExists('student_id')) {
    //         if (!empty($tempRow['examination_id']) && !empty($tempRow['education_subject_id']) && !empty($tempRow['student_id'])) {
    //             $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
    //             $examinationItemResults = $ExaminationItems
    //                 ->find()
    //                 ->contain(['ExaminationGradingTypes.GradingOptions'])
    //                 ->where([
    //                     $ExaminationItems->aliasField('examination_id') => $tempRow['examination_id'],
    //                     $ExaminationItems->aliasField('education_subject_id') => $tempRow['education_subject_id']
    //                 ])
    //                 ->all();

    //             if ($examinationItemResults->isEmpty()) {
    //                 // Subject is not added to the exam
    //                 $rowInvalidCodeCols['education_subject_id'] = __('Education Subject is not configured in the examination');
    //                 return false;
    //             } else {
    //                 $ExaminationCentreStudents = TableRegistry::get('Examination.ExaminationCentreStudents');
    //                 $registeredStudentQuery = $ExaminationCentreStudents
    //                     ->find()
    //                     ->where([
    //                         $ExaminationCentreStudents->aliasField('examination_id') => $tempRow['examination_id'],
    //                         $ExaminationCentreStudents->aliasField('education_subject_id') => $tempRow['education_subject_id'],
    //                         $ExaminationCentreStudents->aliasField('student_id') => $tempRow['student_id']
    //                     ]);

    //                 if ($registeredStudentQuery->count() == 0) {
    //                     // Student is registered to the exam
    //                     $rowInvalidCodeCols['student_id'] = __('Student is not registered for the Examination');
    //                     return false;
    //                 } else {
    //                     $registeredStudentEntity = $registeredStudentQuery->first();

    //                     $tempRow['academic_period_id'] = $registeredStudentEntity->academic_period_id;
    //                     $tempRow['examination_centre_id'] = $registeredStudentEntity->examination_centre_id;
    //                     $tempRow['institution_id'] = $registeredStudentEntity->institution_id;
    //                 }

    //                 $examinationItemEntity = $examinationItemResults->first();
    //                 if ($examinationItemEntity->has('examination_grading_type')) {
    //                     $gradingTypeEntity = $examinationItemEntity->examination_grading_type;
    //                     if (empty($gradingTypeEntity->grading_options)) {
    //                         // exam item is linked to a grading type but no grading options is added
    //                         $rowInvalidCodeCols['education_subject_id'] = __('Examination Grading Options is not configured for '.$examinationItemEntity->examination_grading_type->code_name);
    //                         return false;
    //                     } else {
    //                         if ($tempRow->offsetExists('marks') && $tempRow->offsetExists('examination_grading_option_id')) {
    //                             $marksCell = $tempRow['marks'];
    //                             $gradingOptionIdCell = $tempRow['examination_grading_option_id'];

    //                             if ($gradingTypeEntity->result_type == 'MARKS') {
    //                                 $validationPass = true;

    //                                 if (strlen($marksCell) == 0) {
    //                                     // not allow empty for marks type
    //                                     $rowInvalidCodeCols['marks'] = __('This field cannot be left empty');
    //                                     $validationPass = false;
    //                                 } else {
    //                                     // check without precision
    //                                     $pattern = '/^[0-9]*(\.[0-9]+)?$/';
    //                                     $match = preg_match($pattern, $marksCell);
    //                                     if (!$match) {
    //                                         $rowInvalidCodeCols['marks'] = __('This field is not in valid format');
    //                                         $validationPass = false;
    //                                     }

    //                                     // round to 2 decimal places
    //                                     $marksCell = round($marksCell, 2);

    //                                     if ($marksCell > $gradingTypeEntity->max) {
    //                                         // marks entered cannot be more than the maximum mark configured
    //                                         $rowInvalidCodeCols['marks'] = __('This field cannot be more than ' . $gradingTypeEntity->max);
    //                                         $validationPass = false;
    //                                     }

    //                                     $tempRow['marks'] = $marksCell;
    //                                 }

    //                                 if (strlen($gradingOptionIdCell) > 0) {
    //                                     // this value is not applicable for marks type
    //                                     $rowInvalidCodeCols['examination_grading_option_id'] = __('This field is not applicable to subject of Marks type');
    //                                     $validationPass = false;
    //                                 }

    //                                 return $validationPass;
    //                             } else if ($gradingTypeEntity->result_type == 'GRADES') {
    //                                 $validationPass = true;

    //                                 if (strlen($marksCell) > 0) {
    //                                     // this value is not applicable for grades type
    //                                     $rowInvalidCodeCols['marks'] = __('This field is not applicable to subject of Grades type');
    //                                     $validationPass = false;
    //                                 }

    //                                 if (strlen($gradingOptionIdCell) == 0) {
    //                                     // not allow empty for grades type
    //                                     $rowInvalidCodeCols['examination_grading_option_id'] = __('This field cannot be left empty');
    //                                     $validationPass = false;
    //                                 } else {
    //                                     $valid = false;
    //                                     $gradingOptions = $gradingTypeEntity->grading_options;
    //                                     foreach ($gradingOptions as $key => $obj) {
    //                                         if ($gradingOptionIdCell == $obj->id) {
    //                                             $valid = true;
    //                                         }
    //                                     }

    //                                     if (!$valid) {
    //                                         $rowInvalidCodeCols['examination_grading_option_id'] = __('Selected value does not match with Examination Grading Options of the subject');
    //                                         $validationPass = false;
    //                                     }
    //                                 }

    //                                 return $validationPass;
    //                             }
    //                         }
    //                     }
    //                 } else {
    //                     // will never come to here unless orphan record in exam item
    //                     $rowInvalidCodeCols['education_subject_id'] = __('Examination Grading Type is not configured');
    //                     return false;
    //                 }
    //             }
    //         }
    //     }

    //     return true;
    // }
}
