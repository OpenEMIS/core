<?php

namespace Institution\Model\Table;

use App\Model\Traits\OptionsTrait;
use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;

class ImportInstitutionAssetsTable extends AppTable
{
    use OptionsTrait;
    private $institutionId;

    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'InstitutionAssets'
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
//        $events['Model.import.onImportPopulateTextbooksData'] = 'onImportPopulateRemoveData';
        $events['Model.import.onImportPopulateAssetTypesData'] = 'onImportPopulateSelectData';
        $events['Model.import.onImportGetAssetTypesId'] = 'onImportGetAssetTypesId';

        $events['Model.import.onImportPopulateAssetMakesData'] = 'onImportPopulateSelectData';
        $events['Model.import.onImportGetAssetMakesId'] = 'onImportGetAssetMakesId';

        $events['Model.import.onImportPopulateAssetModelsData'] = 'onImportPopulateSelectData';
        $events['Model.import.onImportGetAssetModelsId'] = 'onImportGetAssetModelsId';

        $events['Model.import.onImportPopulateUsersData'] = 'onImportPopulateRemoveData';
//        $events['Model.import.onImportGetAssetTypesId'] = 'onImportGetAssetTypesId';

        $events['Model.import.onImportPopulateAssetStatusesData'] = 'onImportPopulateSelectData';
        $events['Model.import.onImportGetAssetStatusesId'] = 'onImportGetAssetStatusesId';

        $events['Model.import.onImportPopulateAssetConditionsData'] = 'onImportPopulateSelectData';
        $events['Model.import.onImportGetAssetConditionsId'] = 'onImportGetAssetConditionsId';

        $events['Model.import.onImportPopulateInstitutionRoomsData'] = 'onImportPopulateInstitutionRoomsData';
        $events['Model.import.onImportGetInstitutionRoomsId'] = 'onImportGetInstitutionRoomsId';

        $events['Model.import.onImportPopulateAccessibilityData'] = 'onImportPopulateAccessibilityData';
        $events['Model.import.onImportGetAccessibilityId'] = 'onImportGetAccessibilityId';

        $events['Model.import.onImportPopulatePurposeData'] = 'onImportPopulatePurposeData';
        $events['Model.import.onImportGetPurposeId'] = 'onImportGetPurposeId';

        //        $events['Model.import.onImportPopulateTextbookConditionsData'] = 'onImportPopulateRemoveData';
//        $events['Model.import.onImportPopulateTextbookStatusesData'] = 'onImportPopulateRemoveData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }


    public function onImportGetAssetTypesId(Event $event, $cellValue)
    {
        $table_name = 'asset_types';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    public function onImportGetAssetMakesId(Event $event, $cellValue)
    {
        $table_name = 'asset_makes';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    public function onImportGetAssetModelsId(Event $event, $cellValue)
    {
        $table_name = 'asset_models';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    public function onImportGetAssetStatusesId(Event $event, $cellValue)
    {
        $table_name = 'asset_statuses';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    public function onImportGetAssetConditionsId(Event $event, $cellValue)
    {
        $table_name = 'asset_conditions';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    public function onImportGetInstitutionRoomsId(Event $event, $cellValue)
    {
        $table_name = 'institution_rooms';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    public function onImportGetAccessibilityId(Event $event, $cellValue)
    {
        $code = "InstitutionAssets.accessibility";
        return $this->checkLookupIdFromOptions($cellValue, $code);
    }

    public function onImportGetPurposeId(Event $event, $cellValue)
    {
        $code = "InstitutionAssets.purpose";
        return $this->checkLookupIdFromOptions($cellValue, $code);
    }

    public function beforeAction($event)
    {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
    }

    public function onImportPopulateAccessibilityData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $Label = 'Accessibility';
        $code = 'accessibility';
        $data = $this->populateLookupOptions($data, $columnOrder, $Label, $code);
    }

    public function onImportPopulatePurposeData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $Label = 'Purpose';
        $code = 'purpose';
        $data = $this->populateLookupOptions($data, $columnOrder, $Label, $code);
    }

    public function onImportPopulateInstitutionRoomsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $translatedReadableCol = $this->getExcelLabel('InstitutionRooms', 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!$this->institutionId) {
            return false;
        }
        $institution_id = $this->institutionId;
        $tableName = $lookupPlugin . '.' . $lookupModel;
//        $this->log($tableName, 'debug');
        $lookedUpTable = TableRegistry::get($tableName);
        $modelOptions = $lookedUpTable->find('all')
            ->select(['id', 'name', $lookupColumn])
            ->where([$lookedUpTable->aliasField('institution_id') => $institution_id])
            ->toArray();
        if (!empty($modelOptions)) {
            foreach ($modelOptions as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateSelectData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $tableName = $lookupPlugin . '.' . $lookupModel;
//        $this->log($tableName, 'debug');
        $lookedUpTable = TableRegistry::get($tableName);


        $modelData = $lookedUpTable->find('all')->select(['id', 'name', $lookupColumn]);

        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);

        if ($lookupModel == 'AssetTypes') {
            $lookupColumnNo = 1;
        }
        if ($lookupModel == 'AssetMakes') {
            $lookupColumnNo = 1;
        }
        if ($lookupModel == 'AssetModels') {
            $lookupColumnNo = 1;
        }
        if ($lookupModel == 'AssetStatuses') {
            $lookupColumnNo = 1;
        }
        if ($lookupModel == 'AssetConditions') {
            $lookupColumnNo = 1;
        }
        $data[$columnOrder]['lookupColumn'] = $lookupColumnNo;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
//            $columnHeader
        ];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
//                    $row->{$lookupColumn}
                ];
            }
        }
        $this->log($data, 'debug');
//        die;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'AssetStatuses') {
            return __('Status');
        }
        if ($field == 'AssetConditions') {
            return __('Condition');
        }
        if ($field == 'InstitutionRooms') {
            return __('Location');
        }
        if ($field == 'Users') {
            return __('User');
        }
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function onImportPopulateRemoveData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        unset($data[$columnOrder]);
    }

    // POCOR-7362 starts

    public function getAssignedStaffId()
    {

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

    public function getEnrolledStudentId()
    {

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
        $tempRow['security_user_id'] = $tempRow['user_id'];
        // POCOR-7362 starts

        // In institutionTextbooksTable staff is also added to studentoptions and hence in temprow['student_id'] staff Ids also populate, following methods checks if student or staff id are enrolled/assigned

        $enrolledStudent = $this->getEnrolledStudentId();
        $assignedStaff = $this->getAssignedStaffId();

        $users = array_merge($enrolledStudent, $assignedStaff);
        if (isset($tempRow['security_user_id'])){
            if (!in_array($tempRow['security_user_id'], $users)) {
                $rowInvalidCodeCols['user_id'] = __('Not a enrolled/assigned user');
                return false;
            }
        }

        // POCOR-7362 ends

        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }
        $tempRow['institution_id'] = $this->institutionId;

//        if ($tempRow->offsetExists('textbook_id') && !empty($tempRow['textbook_id'])) {
//            $Textbooks = TableRegistry::get('Textbook.Textbooks');
//            $textbookResults = $Textbooks
//                ->find()
//                ->where([$Textbooks->aliasField('id') => $tempRow['textbook_id']])
//                ->all();
//
//            if ($textbookResults->isEmpty()) {
//                $rowInvalidCodeCols['textbook_id'] = $this->getExcelLabel('Import', 'value_not_in_list');
//                return false;
//            } else {
//                $textbookEntity = $textbookResults->first();
//                $tempRow['academic_period_id'] = $textbookEntity->academic_period_id;
//                $tempRow['education_subject_id'] = $textbookEntity->education_subject_id;
//                $tempRow['education_grade_id'] = $textbookEntity->education_grade_id;
//                //check for student being assigned 2 same book.
//                $InstitutionTextbooks = TableRegistry::get('Institution.InstitutionTextbooks');
//
//                if ($tempRow->offsetExists('code') && empty($tempRow['code'])) {
//                    $InstitutionTextbookData = $InstitutionTextbooks->find('all', [
//                                'order' => [$InstitutionTextbooks->aliasField('id') => 'DESC']
//                            ])->first();
//                    $tempRow['code'] = $textbookEntity->code . '-' . ($InstitutionTextbookData->id + 1);
//                }
//
//                if ($tempRow->offsetExists('security_user_id')) {
//                    if (!empty($tempRow['security_user_id'])) {
//                        $query = $InstitutionTextbooks->find()
//                                ->where([
//                                    $InstitutionTextbooks->aliasField('security_user_id') => $tempRow['security_user_id'],
//                                    $InstitutionTextbooks->aliasField('textbook_id') => $tempRow['textbook_id'],
//                                    $InstitutionTextbooks->aliasField('institution_id') => $tempRow['institution_id'],
//                                    $InstitutionTextbooks->aliasField('academic_period_id') => $tempRow['academic_period_id'],
//                                    $InstitutionTextbooks->aliasField('education_subject_id') => $tempRow['education_subject_id'],
//                                    $InstitutionTextbooks->aliasField('education_grade_id') => $tempRow['education_grade_id']
//                                ])
//                                ->count();
//                        if ($query > 0) { //student assigned to same book before
//                            $rowInvalidCodeCols['student_id'] = __('Textbook already assigned to the same student before.');
//                            return false;
//                        }
//                    }
//                }
//            }
//        }

        return true;
    }

    /**
     * @param $cellValue
     * @param $table_name
     * @return |null
     */
    private function checkLookupIdFromTable($cellValue, $table_name)
    {
        $lookedUpTable = TableRegistry::get($table_name);
        $lookupField = 'name';
        $where = [];
        if ($table_name == 'institution_rooms') {
            $lookupField = 'code';
            $where = [$lookedUpTable->aliasField('institution_id') => $this->institutionId];
        }
        $modelOptions = $lookedUpTable->find('all')
            ->select(['id', $lookupField])
            ->where($where)
            ->toArray();

        if (!empty($modelOptions)) {
            foreach ($modelOptions as $row) {
                if ($cellValue == $row['id']) {
                    return $row['id'];
                }
                if ($cellValue == $row[$lookupField]) {
                    return $row['id'];
                }
            }
        }
        return null;
    }

    /**
     * @param ArrayObject $data
     * @param $columnOrder
     * @param $Label
     * @param $code
     * @return ArrayObject
     */
    private function populateLookupOptions(ArrayObject $data, $columnOrder, $Label, $code)
    {
        $translatedReadableCol = $this->getExcelLabel($Label, 'name');
        $data[$columnOrder]['lookupColumn'] = 1;
        $data[$columnOrder]['data'][] = [$translatedReadableCol
        ];
        $options = $this->getSelectOptions("InstitutionAssets.$code");
        foreach ($options as $key => $value) {
            $data[$columnOrder]['data'][] = [
                $value,
            ];
        }
        return $data;
    }

    /**
     * @param $cellValue
     * @param $code
     * @return int|string|null
     */
    private function checkLookupIdFromOptions($cellValue, $code)
    {
        $options = $this->getSelectOptions($code);
        foreach ($options as $key => $value) {
            if ($cellValue == $value) {
                return $key;
            }
            if ($cellValue == $key) {
                return $key;
            }
        }
        return null;
    }
}
