<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportInstitutionTextbooksTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'InstitutionTextbooks'
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.import.onImportPopulateTextbooksData'] = 'onImportPopulateTextbooksData';
        $events['Model.import.onImportPopulateUsersData'] = 'onImportPopulateUsersData';
        $events['Model.import.onImportPopulateTextbookStatusesData'] = 'onImportPopulateTextbookStatusesData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function beforeAction($event) {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'TextbookStatuses') {
            return __('Status');
        } else if ($field == 'TextbookConditions') {
            return __('Condition');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onImportPopulateTextbooksData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = [$lookedUpTable->aliasField('title'), $lookedUpTable->aliasField('code'), $lookedUpTable->aliasField($lookupColumn), $lookedUpTable->aliasField('ISBN')];
        $order = [$lookedUpTable->aliasField('title')];

        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'title');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, __('Code'), $translatedCol, __('ISBN')];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->title,
                    $row->code,
                    $row->{$lookupColumn},
                    $row->ISBN
                ];
            }
        }
    }

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        unset($data[$columnOrder]);
    }

    public function onImportPopulateTextbookStatusesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $selectFields = [$lookedUpTable->aliasField('name'), $lookedUpTable->aliasField($lookupColumn)];
        $order = [$lookedUpTable->aliasField('name')];

        $modelData = $lookedUpTable->find('all')
            ->select($selectFields)
            ->order($order);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

     // POCOR-7362 starts

    public function getAssignedStaffId(){

        $staff = TableRegistry::get('institution_staff');
        $query = $staff->find()
                ->select([
                    'su.id'
                ])
                ->join([
                    'table' => 'security_users',
                    'alias' => 'su',
                    'type' => 'INNER',
                    'conditions' => 'institution_staff.staff_id = su.id'
                ])
                ->join([
                    'table' => 'staff_statuses',
                    'alias' => 'ss',
                    'type' => 'INNER',
                    'conditions' => 'institution_staff.staff_status_id = ss.id'
                ])
                ->where([

                    'ss.id' => 1
                ])
                ->hydrate(false);

        $result = $query->toArray();

        foreach ($result as $key => $value) {
            $user = $value['su'];
            $assignedStaffIds[] = $user['id'];
        }

        return $assignedStaffIds;
        }
    
        public function getEnrolledStudentId(){

            $staff = TableRegistry::get('institution_students');
            $query = $staff->find()
                    ->select([
                        'su.id'
                    ])
                    ->join([
                        'table' => 'security_users',
                        'alias' => 'su',
                        'type' => 'INNER',
                        'conditions' => 'institution_students.student_id = su.id'
                    ])
                    ->join([
                        'table' => 'student_statuses',
                        'alias' => 'ss',
                        'type' => 'INNER',
                        'conditions' => 'institution_students.student_status_id = ss.id'
                    ])
                    ->where([
    
                        'ss.id' => 1
                    ])
                    ->hydrate(false);
    
            $result = $query->toArray();
    
            foreach ($result as $key => $value) {
                $user = $value['su'];
                $enrolledStudentIds[] = $user['id'];
            }
    
            return $enrolledStudentIds;
            }
     // POCOR-7362 ends


    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        
         // POCOR-7362 starts

        // In institutionTextbooksTable staff is also added to studentoptions and hence in temprow['student_id'] staff Ids also populate, following methods checks if student or staff id are enrolled/assigned 

        $enrolledStudent = $this->getEnrolledStudentId();
        $assignedStaff = $this->getAssignedStaffId();

        $users = array_merge($enrolledStudent, $assignedStaff);
        
        if(!in_array($tempRow['student_id'], $users)){
            $rowInvalidCodeCols['student_id'] = __('Not a enrolled/assigned user');
            return false;
        }

        // POCOR-7362 ends

        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }
        $tempRow['institution_id'] = $this->institutionId;

        if ($tempRow->offsetExists('textbook_id') && !empty($tempRow['textbook_id'])) {
            $Textbooks = TableRegistry::get('Textbook.Textbooks');
            $textbookResults = $Textbooks
                ->find()
                ->where([$Textbooks->aliasField('id') => $tempRow['textbook_id']])
                ->all();

            if ($textbookResults->isEmpty()) {
                $rowInvalidCodeCols['textbook_id'] = $this->getExcelLabel('Import', 'value_not_in_list');
                return false;
            } else {
                $textbookEntity = $textbookResults->first();
                $tempRow['academic_period_id'] = $textbookEntity->academic_period_id;
                $tempRow['education_subject_id'] = $textbookEntity->education_subject_id;
                $tempRow['education_grade_id'] = $textbookEntity->education_grade_id;
                //check for student being assigned 2 same book.
                $InstitutionTextbooks = TableRegistry::get('Institution.InstitutionTextbooks');

                if ($tempRow->offsetExists('code') && empty($tempRow['code'])) {
                    $InstitutionTextbookData = $InstitutionTextbooks->find('all', [
                                'order' => [$InstitutionTextbooks->aliasField('id') => 'DESC']
                            ])->first();
                    $tempRow['code'] = $textbookEntity->code . '-' . ($InstitutionTextbookData->id + 1);
                }

                if ($tempRow->offsetExists('student_id')) {
                    if (!empty($tempRow['student_id'])) {
                        $query = $InstitutionTextbooks->find()
                                ->where([
                                    $InstitutionTextbooks->aliasField('student_id') => $tempRow['student_id'],
                                    $InstitutionTextbooks->aliasField('textbook_id') => $tempRow['textbook_id'],
                                    $InstitutionTextbooks->aliasField('institution_id') => $tempRow['institution_id'],
                                    $InstitutionTextbooks->aliasField('academic_period_id') => $tempRow['academic_period_id'],
                                    $InstitutionTextbooks->aliasField('education_subject_id') => $tempRow['education_subject_id'],
                                    $InstitutionTextbooks->aliasField('education_grade_id') => $tempRow['education_grade_id']
                                ])
                                ->count();
                        if ($query > 0) { //student assigned to same book before
                            $rowInvalidCodeCols['student_id'] = __('Textbook already assigned to the same student before.');
                            return false;
                        }
                    }
                }
            }
        }
        
        return true;
    }
}
