<?php

namespace Institution\Model\Table;

use App\Model\Traits\OptionsTrait;
use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Collection\Collection;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use PHPExcel_Worksheet;
use Cake\ORM\Locator\TableLocator;

class ImportInstitutionAssetsTable extends AppTable
{
    use OptionsTrait;
    private $institutionId;

    public function initialize(array $config)
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'InstitutionAssets'
        ]);
        $tableLocator = new TableLocator();
    }

    /**
     * @return array
     *
     */
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

    /**
     * @param EventInterface $event
     * @param $cellValue
     * @return |null
     *
     */
    public function onImportGetAssetTypesId(EventInterface $event, $cellValue)
    {
        //$table_name = 'asset_types';
        $table_name = 'AssetTypes';
        $result = $this->checkLookupIdFromTable($cellValue, $table_name);
        return $result;
    }

    /**
     * @param EventInterface $event
     * @param $cellValue
     * @return |null
     *
     */
    public function onImportGetAssetMakesId(EventInterface $event, $cellValue)
    {
        //$table_name = 'asset_makes';
        $table_name = 'AssetMakes';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    /**
     * @param EventInterface $event
     * @param $cellValue
     * @return |null
     *
     */
    public function onImportGetAssetModelsId(EventInterface $event, $cellValue)
    {
        //$table_name = 'asset_models';
        $table_name = 'AssetModels';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    /**
     * @param EventInterface $event
     * @param $cellValue
     * @return |null
     *
     */
    public function onImportGetAssetStatusesId(EventInterface $event, $cellValue)
    {
        //$table_name = 'asset_statuses';
        $table_name = 'AssetStatuses';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    /**
     * @param EventInterface $event
     * @param $cellValue
     * @return |null
     *
     */
    public function onImportGetAssetConditionsId(EventInterface $event, $cellValue)
    {
        //$table_name = 'asset_conditions';
        $table_name = 'AssetConditions';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    /**
     * @param EventInterface $event
     * @param $cellValue
     * @return |null
     *
     */
    public function onImportGetInstitutionRoomsId(EventInterface $event, $cellValue)
    {
        //$table_name = 'institution_rooms';
        $table_name = 'InstitutionRooms';
        return $this->checkLookupIdFromTable($cellValue, $table_name);
    }

    /**
     * @param EventInterface $event
     * @param $cellValue
     * @return int|string|null
     *
     */
    public function onImportGetAccessibilityId(EventInterface $event, $cellValue)
    {
        $code = "InstitutionAssets.accessibility";
        return $this->checkLookupIdFromOptions($cellValue, $code);
    }

    /**
     * @param EventInterface $event
     * @param $cellValue
     * @return int|string|null
     *
     */
    public function onImportGetPurposeId(EventInterface $event, $cellValue)
    {
        $code = "InstitutionAssets.purpose";
        return $this->checkLookupIdFromOptions($cellValue, $code);
    }

    /**
     * @param $event
     *
     */
    public function beforeAction($event)
    {
        $session = $this->request->getSession();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
    }

    /**
     * @param EventInterface $event
     * @param $lookupPlugin
     * @param $lookupModel
     * @param $lookupColumn
     * @param $translatedCol
     * @param ArrayObject $data
     * @param $columnOrder
     *
     */
    public function onImportPopulateAccessibilityData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $Label = 'Accessibility';
        $code = 'accessibility';
        $data = $this->populateLookupOptions($data, $columnOrder, $Label, $code);
    }

    /**
     * @param EventInterface $event
     * @param $lookupPlugin
     * @param $lookupModel
     * @param $lookupColumn
     * @param $translatedCol
     * @param ArrayObject $data
     * @param $columnOrder
     *
     */
    public function onImportPopulatePurposeData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $Label = 'Purpose';
        $code = 'purpose';
        $data = $this->populateLookupOptions($data, $columnOrder, $Label, $code);
    }

    /**
     * @param EventInterface $event
     * @param $lookupPlugin
     * @param $lookupModel
     * @param $lookupColumn
     * @param $translatedCol
     * @param ArrayObject $data
     * @param $columnOrder
     * @return bool
     *
     */
    public function onImportPopulateInstitutionRoomsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
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
        $lookedUpTable = TableRegistry::getTableLocator()->get($tableName);
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

    /**
     * @param EventInterface $event
     * @param $lookupPlugin
     * @param $lookupModel
     * @param $lookupColumn
     * @param $translatedCol
     * @param ArrayObject $data
     * @param $columnOrder
     *
     */
    public function onImportPopulateSelectData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $tableName = $lookupPlugin . '.' . $lookupModel;
        //        $this->log($tableName, 'debug');
        $lookedUpTable = TableRegistry::getTableLocator()->get($tableName);

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
        //        $this->log($modelData, 'debug');
        //        die;
    }

    /**
     * @param EventInterface $event
     * @param $module
     * @param $field
     * @param $language
     * @param bool $autoHumanize
     * @return mixed|string|null
     *
     */
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
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

    /**
     * @param EventInterface $event
     * @param $lookupPlugin
     * @param $lookupModel
     * @param $lookupColumn
     * @param $translatedCol
     * @param ArrayObject $data
     * @param $columnOrder
     *
     */
    public function onImportPopulateRemoveData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        unset($data[$columnOrder]);
    }

    // POCOR-7362 starts

    /**
     * @return array
     */
    public function getAssignedStaffId()
    {

        $staff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
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
            ->enableHydration(false);

        $result = $query->toArray();

        foreach ($result as $key => $value) {
            $user = $value['su'];
            $assignedStaffIds[] = $user['id'];
        }

        return $assignedStaffIds;
    }

    /**
     * @return array
     */
    public function getEnrolledStudentId()
    {
        $staff = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
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
            ->enableHydration(false);

        $result = $query->toArray();

        foreach ($result as $key => $value) {
            $user = $value['su'];
            $enrolledStudentIds[] = $user['id'];
        }

        return $enrolledStudentIds;
    }

    // POCOR-7362 ends

    /**
     * @param EventInterface $event
     * @param $references
     * @param ArrayObject $tempRow
     * @param ArrayObject $originalRow
     * @param ArrayObject $rowInvalidCodeCols
     * @return bool|mixed
     *
     */
    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $result = true;
        list($tempRow, $rowInvalidCodeCols, $result) = $this->checkFinalUser($tempRow, $rowInvalidCodeCols, $result);

        // POCOR-7362 ends

        list($tempRow, $rowInvalidCodeCols, $result) = $this->checkFinalInstitution($tempRow, $rowInvalidCodeCols, $result);

        list($tempRow, $rowInvalidCodeCols, $result) = $this->checkFinalMakeModel($tempRow, $rowInvalidCodeCols, $result);

        //todo add maker and model check

        return $result;
    }

    /**
     * @param $cellValue
     * @param $table_name
     * @return |null
     *
     */
    private function checkLookupIdFromTable($cellValue, $table_name)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($table_name);
        $lookupField = 'name';
        $where = ['1 = 1'];
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
     *
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
     *
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

    /**
     * @param ArrayObject $tempRow
     * @param ArrayObject $rowInvalidCodeCols
     * @param $result
     * @return array
     *
     */
    private function checkFinalUser(ArrayObject $tempRow, ArrayObject $rowInvalidCodeCols, $result)
    {
        $tempRow['security_user_id'] = $tempRow['user_id'];
        $enrolledStudent = $this->getEnrolledStudentId();
        $assignedStaff = $this->getAssignedStaffId();

        $users = array_merge($enrolledStudent, $assignedStaff);
        if (isset($tempRow['security_user_id'])) {
            if (!in_array($tempRow['security_user_id'], $users)) {
                $rowInvalidCodeCols['user_id'] = __('Not a enrolled/assigned user');
                $result = false;
            }
        }
        return array($tempRow, $rowInvalidCodeCols, $result);
    }

    /**
     * @param ArrayObject $tempRow
     * @param ArrayObject $rowInvalidCodeCols
     * @param $result
     * @return array
     *
     */
    private function checkFinalInstitution(ArrayObject $tempRow, ArrayObject $rowInvalidCodeCols, $result)
    {
        if ($result && !$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            $result = false;
        }
        if ($result) {
            $tempRow['institution_id'] = $this->institutionId;
        }
        return array($tempRow, $rowInvalidCodeCols, $result);
    }

    /**
     * @param ArrayObject $tempRow
     * @param ArrayObject $rowInvalidCodeCols
     * @param $result
     * @return array
     *
     */
    private function checkFinalMakeModel(ArrayObject $tempRow, ArrayObject $rowInvalidCodeCols, $result)
    {
        if ($result) {
            $asset_make_id = $tempRow['asset_make_id'];
            $asset_model_id = $tempRow['asset_model_id'];
            if($asset_model_id){
                $model = self::getRelatedRecord('asset_models', $asset_model_id);
                $asset_make_id = $model['asset_make_id'];
                $tempRow['asset_make_id'] = $asset_make_id;
            }

            if($asset_make_id){
                $make = self::getRelatedRecord('asset_makes', $asset_make_id);
                $asset_type_id = $make['asset_type_id'];
                $tempRow['asset_type_id'] = $asset_type_id;
            }
        }
        return array($tempRow, $rowInvalidCodeCols, $result);
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     *
     */
    private static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return null;
        }
        $Table = TableRegistry::getTableLocator()->get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            return null;
        }
        return null;
    }

}
