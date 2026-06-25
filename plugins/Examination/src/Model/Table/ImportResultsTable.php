<?php
namespace Examination\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportResultsTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Examination',
            'model' => 'ExaminationStudentSubjectResults',
            'backUrl' => ['plugin' => 'Examination', 'controller' => 'Examinations', 'action' => 'ExamResults']
        ]);

        $this->addBehavior('ControllerAction.FileUpload');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateExaminationsData'] = 'onImportPopulateExaminationsData';
        $events['Model.import.onImportPopulateExaminationSubjectsData'] = 'onImportPopulateExaminationSubjectsData';
        $events['Model.import.onImportPopulateUsersData'] = 'onImportPopulateUsersData';
        $events['Model.import.onImportPopulateExaminationGradingOptionsData'] = 'onImportPopulateExaminationGradingOptionsData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function onImportPopulateExaminationsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

        $selectFields = [$lookedUpTable->aliasField('code'), $lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn), $AcademicPeriods->aliasField('code'), $AcademicPeriods->aliasField('name')];
        // $order = [$lookupModel.'.name', $lookupModel.'.code'];
        $order = [$AcademicPeriods->aliasField('order') => 'DESC', $lookedUpTable->aliasField('name')];
        // show examination list distinct by code (user create same exam in different academic period)
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->matching($AcademicPeriods->getAlias())
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [__('Code'), $translatedReadableCol, $translatedCol, __('Academic Period')];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->code,
                    $row->name,
                    $row->{$lookupColumn},
                    $row->_matchingData[$AcademicPeriods->getAlias()]->name
                ];
            }
        }
    }

    public function onImportPopulateExaminationSubjectsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $Examinations = TableRegistry::getTableLocator()->get('Examination.Examinations');

        $ExaminationCentreSubjects = TableRegistry::getTableLocator()->get('Examination.ExaminationCentresExaminationsSubjects');
        $selectFields = [$lookedUpTable->aliasField('code'), $lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn), $lookedUpTable->aliasField('weight'), $Examinations->aliasField('id'), ];

        $order = ['AcademicPeriods.order DESC', $lookedUpTable->aliasField('name'), $lookupModel.'.name', $lookupModel.'.code'];

        // show distinct list of subjects which are added as exam items and subject weight is more than zero
        $modelData = $ExaminationCentreSubjects->find('all')
            ->select($selectFields)
            ->matching($lookedUpTable->getAlias())
            ->matching('Examinations.AcademicPeriods')
            ->where([$lookedUpTable->aliasField('weight > ') => 0])
            ->group([$ExaminationCentreSubjects->aliasField('examination_subject_id')])
            ->order($order);
        
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [ $translatedReadableCol, __('Code'), $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    //$row->_matchingData[$Examinations->getAlias()]->id,
                    $row->_matchingData[$lookedUpTable->getAlias()]->name,
                    $row->_matchingData[$lookedUpTable->getAlias()]->code,
                    $row->_matchingData[$lookedUpTable->getAlias()]->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateUsersData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        // $order = [$lookupModel.'.area_level_id', $lookupModel.'.order'];

        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $selectFields = ['openemis_no', $lookupColumn];
        $modelData = $lookedUpTable->find('all')
                                ->select($selectFields)
                                ;

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'openemis_no');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->openemis_no,
                    $row->{$lookupColumn}
                ];
            }
        }
        // unset($data[$columnOrder]);
    }

    public function onImportPopulateExaminationGradingOptionsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $ExaminationGradingTypes = TableRegistry::getTableLocator()->get('Examination.ExaminationGradingTypes');
        $selectFields = [$lookedUpTable->aliasField('code'), $lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn), $ExaminationGradingTypes->aliasField('code'), $ExaminationGradingTypes->aliasField('name'), $ExaminationGradingTypes->aliasField('id')];
        $order = [$ExaminationGradingTypes->aliasField('name'), $lookupModel.'.order'];
        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->matching($ExaminationGradingTypes->getAlias())
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, __('Code'), $translatedCol, __('Grading ID'),  __('Grading Type'),__('Grading Type Id')]; //POCOR-9236
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->code,
                    $row->{$lookupColumn}, //POCOR-9236
                    $row->{$lookupColumn},
                    $row->_matchingData[$ExaminationGradingTypes->getAlias()]->name,
                    $row->_matchingData[$ExaminationGradingTypes->getAlias()]->id
                ];
            }
        }
    }

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        if ($tempRow->offsetExists('examination_id') && $tempRow->offsetExists('examination_subject_id') && $tempRow->offsetExists('student_id')) {
            if (!empty($tempRow['examination_id']) && !empty($tempRow['examination_subject_id']) && !empty($tempRow['student_id'])) {
                $ExaminationSubjects = TableRegistry::getTableLocator()->get('Examination.ExaminationSubjects');
                $ExaminationStudentSubjectResults = $ExaminationSubjects
                    ->find()
                    ->contain(['ExaminationGradingTypes.GradingOptions'])
                    ->where([
                        $ExaminationSubjects->aliasField('examination_id') => $tempRow['examination_id'],
                        $ExaminationSubjects->aliasField('id') => $tempRow['examination_subject_id']
                    ])
                    ->all();

                if ($ExaminationStudentSubjectResults->isEmpty()) {
                    // Subject is not added to the exam
                    $rowInvalidCodeCols['examination_subject_id'] = __('Examination Item is not configured in the examination');
                    return false;
                } else {
                    $examinationItemEntity = $ExaminationStudentSubjectResults->first();
                    $tempRow['education_subject_id'] = $examinationItemEntity->education_subject_id;

                    $ExaminationCentreStudents = TableRegistry::getTableLocator()->get('Examination.ExaminationCentresExaminationsStudents');
                    $registeredStudentQuery = $ExaminationCentreStudents
                        ->find()
                        ->where([
                            $ExaminationCentreStudents->aliasField('examination_id') => $tempRow['examination_id'],
                            $ExaminationCentreStudents->aliasField('student_id') => $tempRow['student_id']
                        ])
                        ->all();

                    if ($registeredStudentQuery->isEmpty()) {
                        // Student is registered to the exam
                        $rowInvalidCodeCols['student_id'] = __('Student is not registered for the Examination');
                        return false;
                    } else {
                        $registeredStudentEntity = $registeredStudentQuery->first();

                        $tempRow['academic_period_id'] = $registeredStudentEntity->academic_period_id;
                        $tempRow['examination_centre_id'] = $registeredStudentEntity->examination_centre_id;
                        $tempRow['institution_id'] = $registeredStudentEntity->institution_id;
                    }

                    if ($examinationItemEntity->has('examination_grading_type')) {
                        $gradingTypeEntity = $examinationItemEntity->examination_grading_type;
                        if (empty($gradingTypeEntity->grading_options)) {
                            // exam item is linked to a grading type but no grading options is added
                            $rowInvalidCodeCols['examination_subject_id'] = __('Examination Grading Options is not configured for '.$examinationItemEntity->examination_grading_type->code_name);
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
                                        // check without precision
                                        $pattern = '/^[0-9]*(\.[0-9]+)?$/';
                                        $match = preg_match($pattern, $marksCell);
                                        if (!$match) {
                                            $rowInvalidCodeCols['marks'] = __('This field is not in valid format');
                                            $validationPass = false;
                                        }

                                        // round to 2 decimal places
                                        $marksCell = round($marksCell, 2);

                                        if ($marksCell > $gradingTypeEntity->max) {
                                            // marks entered cannot be more than the maximum mark configured
                                            $rowInvalidCodeCols['marks'] = __('This field cannot be more than ' . $gradingTypeEntity->max);
                                            $validationPass = false;
                                        }

                                        $tempRow['marks'] = $marksCell;
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
                        $rowInvalidCodeCols['examination_subject_id'] = __('Examination Grading Type is not configured');
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'file_input') {
            return  __('File');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
