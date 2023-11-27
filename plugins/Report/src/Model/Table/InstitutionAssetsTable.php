<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Institution\Model\Table\InstitutionsTable as Institutions;
use Report\Model\Table\InstitutionPositionsTable as InstitutionPositions;
use Cake\Database\Connection;

class InstitutionAssetsTable extends AppTable
{
    use OptionsTrait;

    // filter
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;
    public $currency = '';

    public function initialize(array $config)
    {

        $this->table('institutions');

        parent::initialize($config);
        //$this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'location_institution_id']);
        $this->addBehavior('Excel', [
            'excludes' => ['id'],
            'pages' => false,
            'orientation' => 'landscape'
        ]);
        $this->addBehavior('Report.Csv');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.AreaList');//POCOR-7877

    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $infrastructureLevel = $requestData->infrastructure_level;
        $newFields = [];

        $newFields[] = [
            'key' => '',
            'field' => 'parent_area_name',
            'type' => 'string',
            'label' => 'Parent Area Code'
        ];


        $newFields[] = [
            'key' => '',
            'field' => 'parent_area_name',
            'type' => 'string',
            'label' => 'Parent Area'
        ];


        $newFields[] = [
            'key' => '',
            'field' => 'area_code',
            'type' => 'string',
            'label' => 'Area Code'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_name',
            'type' => 'string',
            'label' => 'Area'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_status',
            'type' => 'string',
            'label' => __('Institution Status')
        ];

        /**end here */
        $newFields[] = [
            'key' => '',
            'field' => 'asset_code',
            'type' => 'string',
            'label' => __('Asset Code')
        ];

        //POCOR-5698 two new columns added here
        $newFields[] = [
            'key' => '',
            'field' => 'asset_description',
            'type' => 'string',
            'label' => __('Asset Description')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'asset_type',
            'type' => 'string',
            'label' => __('Type')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'asset_make',
            'type' => 'string',
            'label' => __('Make')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'asset_model',
            'type' => 'string',
            'label' => __('Model')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'serial_number',
            'type' => 'string',
            'label' => __('Serial Number')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'purchase_order',
            'type' => 'string',
            'label' => __('Purchase Order')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'stocktake_date',
            'type' => 'date',
            'label' => __('Stocktake Date')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'institution_room',
            'type' => 'string',
            'label' => __('Location')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'user_name',
            'type' => 'string',
            'label' => __('User')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'accessibility',
            'type' => 'string',
            'label' => __('Accessibility')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'purpose',
            'type' => 'string',
            'label' => __('Purpose')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'asset_status',
            'type' => 'string',
            'label' => __('Asset Status')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'asset_condition',
            'type' => 'string',
            'label' => __('Condition')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'purchase_date',
            'type' => 'date',
            'label' => __('Purchase Date')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'purchase_cost',
            'type' => 'string',
            'label' => __('Purchase Cost')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'lifespan',
            'type' => 'string',
            'label' => __('Lifespan')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'depreciation_percent',
            'type' => 'string',
            'label' => __('Depreciation %')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'prior_year_accumulated_depreciation',
            'type' => 'string',
            'label' => __('Prior Year Acc. Dep')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'depreciation_sum',
            'type' => 'string',
            'label' => __('Depreciation $')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'accumulated_depreciation',
            'type' => 'string',
            'label' => __('Accumulated depreciation $')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'book_value',
            'type' => 'string',
            'label' => __('Book value')
        ];

//        $newFields[] = [
//            'key' => '',
//            'field' => 'prior_year_accumulated_depreciation',
//            'type' => 'string',
//            'label' => __('Prior Year Accumulated Depreciation')
//        ];
//
//        $newFields[] = [
//            'key' => '',
//            'field' => 'accumulated_depreciation',
//            'type' => 'string',
//            'label' => __('Accumulated Depreciation')
//        ];
//
//        $newFields[] = [
//            'key' => '',
//            'field' => 'book_value',
//            'type' => 'string',
//            'label' => __('Book Value')
//        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $ConfigItems = TableRegistry::get('config_items');
        $this->currency = $ConfigItems->find('all')->where(['code' => 'currency'])->select(['value'])->first()->value;
//        $this->log('$this->currency', 'debug');
//        $this->log($this->currency, 'debug');

        $requestData = json_decode($settings['process']['params']);
//        $this->log(__FUNCTION__, 'debug');
//        $this->log($requestData, 'debug');
        $institutionId = $requestData->institution_id;
        $institutionTypeId = $requestData->institution_type_id;
        $areaId = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id;//POCOR-7877
        $yearStartDay = $requestData->report_start_date;
        $yearEndDay = $requestData->report_end_date;
        $query = $this->getBasicQuery($query, $institutionId, $institutionTypeId, $areaId, $areaLevelId);//POCOR-7877(added area level id)
        $query = $this->getInstitutionAreaQuery($query);
        $query = $this->getInstitutionParentAreaQuery($query);
        $query = $this->getInstitutionStatusQuery($query);
        $query = $this->getAssetTypeQuery($query);
        $query = $this->getAssetMakeQuery($query);
        $query = $this->getAssetModelQuery($query);
        $query = $this->getAssetStatusQuery($query);
        $query = $this->getAssetConditionQuery($query);
        $query = $this->getAssetLocationQuery($query);
        $query = $this->getAssetUserQuery($query);
        $query = $this->getCalculatedFieldsQuery($query, $yearStartDay, $yearEndDay);
//        $this->log($query->sql(), 'debug');
        return $query;
    }

    /**
     * @param Query $query
     * @param $institutionId
     * @param $institutionTypeId
     * @param $areaId
     * @return Query
     */
    private function getBasicQuery(Query $query, $institutionId, $institutionTypeId, $areaId,$areaLevelId)
    {
        $conditions = ["1 = 1"];
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions[$this->aliasField('id')] = $institutionId;
        }
        if (!empty($institutionTypeId) && $institutionTypeId != -1) {
            $conditions[$this->aliasField('institution_type_id')] = $institutionTypeId;
        }
        // POCOR-7877 start for parent area filter
        $areaList = [];
        if (
            $areaLevelId > 1 && $areaId > 1
        ) {
            $areaList = $this->getAreaList($areaLevelId, $areaId);
        } elseif ($areaLevelId > 1) {
            $areaList = $this->getAreaList($areaLevelId, 0);
        } elseif ($areaId > 1) {
            $areaList = $this->getAreaList(0, $areaId);
        }
        if (!empty($areaList)) {
            $conditions[$this->aliasField('area_id In')] = $areaList;
        }
        //POCOR-7877 end
        $institutionAssets = TableRegistry::get('institution_assets');
        $query = $query->select([
            $this->aliasField('id'),
            'institution_code' => $this->aliasField('code'),
            'institution_name' => $this->aliasField('name'),
            'asset_code' => $institutionAssets->aliasField('code'),
            'asset_description' => $institutionAssets->aliasField('description'),
            'serial_number' => $institutionAssets->aliasField('serial_number'),
            'cost' => $institutionAssets->aliasField('cost'),
            'stocktake_date' => $institutionAssets->aliasField('stocktake_date'),
            'lifespan' => $institutionAssets->aliasField('lifespan'),
            'depreciation' => $institutionAssets->aliasField('depreciation'),
            'purchase_date' => $institutionAssets->aliasField('purchase_date'),
            'accessibility' => $institutionAssets->aliasField('accessibility'),
            'purpose' => $institutionAssets->aliasField('purpose'),
            'area_id' => $this->aliasField('area_id'),
            'asset_make_id' => $institutionAssets->aliasField('asset_make_id'),
            'asset_model_id' => $institutionAssets->aliasField('asset_model_id'),
            'institution_room_id' => $institutionAssets->aliasField('institution_room_id'),
            'asset_status_id' => $institutionAssets->aliasField('asset_status_id'),
            'asset_type_id' => $institutionAssets->aliasField('asset_type_id'),
            'asset_condition_id' => $institutionAssets->aliasField('asset_condition_id'),
//            'salvage_value' => $institutionAssets->aliasField('depreciation'),
            $this->aliasField('area_id'),
        ])
            ->leftJoin([$institutionAssets->alias() => $institutionAssets->table()],
                [$institutionAssets->aliasField('institution_id = ') . $this->aliasField('id')])
            ->where($conditions);

        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getInstitutionAreaQuery(Query $query)
    {
        $areas = TableRegistry::get('areas');

        $query = $this->getRelatedQuery($query,
            'areas',
            'area_id',
            'area_name',
            'name',
            'area_code',
            'code');
        $query->select([
            'parent_area_id' => $areas->aliasField('parent_id')]);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getInstitutionParentAreaQuery(Query $query)
    {
        $areas = TableRegistry::get('areas');
        $query = $this->getRelatedQuery($query,
            'areas',
            $areas->aliasField('parent_id'),
            'parent_area_name',
            'name',
            'parent_area_code',
            'code',
            'parent_area');
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getInstitutionStatusQuery(Query $query)
    {
        $query = $this->getRelatedQuery($query,
            'institution_statuses',
            'institution_status_id',
            'institution_status');
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getAssetStatusQuery(Query $query)
    {
        $Table = TableRegistry::get('institution_assets');

        $query = $this->getRelatedQuery($query,
            'asset_statuses',
            $Table->aliasField('asset_status_id'),
            'asset_status');
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getAssetTypeQuery(Query $query)
    {
        $institutionAssets = TableRegistry::get('institution_assets');

        $query = $this->getRelatedQuery($query,
            'asset_types',
            $institutionAssets->aliasField('asset_type_id'),
            'asset_type');
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getAssetMakeQuery(Query $query)
    {
        $Table = TableRegistry::get('institution_assets');

        $query = $this->getRelatedQuery($query,
            'asset_makes',
            $Table->aliasField('asset_make_id'),
            'asset_make');
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getAssetModelQuery(Query $query)
    {
        $Table = TableRegistry::get('institution_assets');

        $query = $this->getRelatedQuery($query,
            'asset_models',
            $Table->aliasField('asset_model_id'),
            'asset_model');
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getAssetConditionQuery(Query $query)
    {
        $Table = TableRegistry::get('institution_assets');

        $query = $this->getRelatedQuery($query,
            'asset_conditions',
            $Table->aliasField('asset_condition_id'),
            'asset_condition');
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getAssetLocationQuery(Query $query)
    {
        $Table = TableRegistry::get('institution_assets');
        $Users = TableRegistry::get('institution_rooms');
        $name = $Users->aliasField('name');
        $code = $Users->aliasField('code');
        $field = "CONCAT({$code}, ': ', {$name})";

        $query = $this->getRelatedQuery($query,
            'institution_rooms',
            $Table->aliasField('institution_room_id'),
            'institution_room',
            $field);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getAssetUserQuery(Query $query)
    {
        $Table = TableRegistry::get('institution_assets');
        $Users = TableRegistry::get('security_users');
        $first_name = $Users->aliasField('first_name');
        $last_name = $Users->aliasField('last_name');
        $field = "CONCAT({$first_name}, ' ', {$last_name})";
        $query = $this->getRelatedQuery($query,
            'security_users',
            $Table->aliasField('user_id'),
            'user_name',
            $field);
        return $query;
    }

    /**
     * @param Query $query
     * @param $yearStartDay
     * @param $yearEndDay
     * @return array|Query
     */
    private function getCalculatedFieldsQuery(Query $query, $yearStartDay, $yearEndDay)
    {
        $currency = $this->currency;
        $query = $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($currency, $yearStartDay, $yearEndDay) {
            return $results->map(function ($row) use ($currency, $yearStartDay, $yearEndDay) {
//                $this->log($currency, 'debug');
                $actual_cost = isset($row['cost']) ? floatval($row['cost']) : 0;
                $total_cost = $actual_cost;
//                $salvageValue = isset($row['salvage_value']) ? floatval($row['salvage_value']) : 0;
                $row['purchase_cost'] = $currency . '' . number_format($actual_cost, 2);
                $row['total_cost'] = $currency . '' . number_format($actual_cost, 2);

//                $row['depreciation'] = number_format($row['depreciation'], 2) . '%';
//                return $row;
                if ($actual_cost == 0) {
                    return $row;
                }

                $lifespan = isset($row['lifespan']) ? floatval($row['lifespan']) : 0;
                if ($lifespan == 0) {
                    return $row;
                }

                $depreciation = 1.0 / $lifespan ;
                $row['depreciation_percent'] = number_format($depreciation, 2) . "%";
                $purchaseDate = $row['purchase_date'];
                if (!$purchaseDate) {
                    return $row;
                }

                $currentTimestamp = time();
                $purchaseTimestamp = strtotime($purchaseDate);
                $yearStartTimestamp = strtotime($yearStartDay);
                $yearEndTimestamp = strtotime($yearEndDay);
                $purchasedate = date_create($purchaseDate);
                $yearstartdate = date_create($yearStartDay);
                $prevyearstartdate = date_create($yearStartDay . ' -1 day');
                $secsinday = 86400;
                $secsinyear = 31536000;
                $fullYears = ($yearStartTimestamp - $secsinday - $purchaseTimestamp) / $secsinyear;
                $fullYearsDaily = intval(date_diff($prevyearstartdate, $purchasedate)->format("%a")) / 365;
//                $row['full_years'] = $fullYears;
//                $row['full_years_daily'] = $fullYearsDaily;

                /**
                 * =IF(
                 * IFERROR(
                 *  IF(($B$6-1-A10)/365>I10
                 * ,H10,
                 * (($B$6-1-A10)/365*H10*J10))
                 *  ,""
                 * )<0,
                 * 0,
                 * IFERROR(IF(($B$6-1-A10)/365>I10,H10,(($B$6-1-A10)/365*H10*J10)),""))
                 */

                if ($fullYears > $lifespan){
                    $priorYearDepreciation = $total_cost;
                }else{
//                    $priorYearDepreciation = $total_cost * $fullYears * $depreciation;
                    $priorYearDepreciation = $total_cost * $fullYearsDaily * $depreciation;
                }
                if($priorYearDepreciation > $total_cost){
                    $priorYearDepreciation = $total_cost;
                }
                if($fullYears < 0){
                    $priorYearDepreciation = 0;
                }

                $row['prior_year_accumulated_depreciation'] =
                    $currency . '' . number_format($priorYearDepreciation, 2);
//                $row['prior_year_accumulated_depreciation_daily'] =
//                    $currency . '' . number_format($priorYearDepreciationDaily, 2);

                /**
                 * =IFERROR(
                 * IF((H10-K10)<(H10*J10),
                 * H10-K10,
                 * (IF(($B$5-A10)<365,(A10-$B$6)/365*H10*J10,H10*J10))),"")
                 */
                $fromthestart = ($yearEndTimestamp - $purchaseTimestamp);
                $yearlyDepreciation = $total_cost * $depreciation;
                $left_cost = $total_cost - $priorYearDepreciation;
                if($left_cost < $yearlyDepreciation ){
                    $depreciationSum = $left_cost;
                }else{
                    if($fromthestart < $secsinyear){
                        $depreciationSum = (($purchaseTimestamp - $yearStartTimestamp) / $secsinyear) * $yearlyDepreciation;
                    }else{
                        $depreciationSum = $yearlyDepreciation;
                    }
                }
                $row['depreciation_sum'] = $currency . '' . number_format($depreciationSum, 2);

                $accumulatedDepreciation = $priorYearDepreciation + $depreciationSum;
                if ($accumulatedDepreciation > ($actual_cost)) {
                    $accumulatedDepreciation = ($actual_cost);
                }
                if ($accumulatedDepreciation < 0) {
                    $accumulatedDepreciation = 0;
                }
                $row['accumulated_depreciation'] =
                    $currency . '' . number_format($accumulatedDepreciation, 2);


                $bookValue = $actual_cost - $accumulatedDepreciation;
                if ($bookValue < 0) {
                    $bookValue = 0;
                }

                $row['book_value'] = $currency . '' . number_format($bookValue, 2);

                return $row;
            });
        });

        return $query;
    }


    public function onExcelGetDepreciation(Event $event, Entity $entity)
    {
        if (!$entity->depreciation) {
            return "";
        }
        $formattedAmount = $this->currency . '' . number_format($entity->depreciation, 2);
        return $formattedAmount; // Output: $1,234.56
    }

    public function onExcelGetPurpose(Event $event, Entity $entity)
    {
        if ($entity->purpose) {
            $purpose = 'Teaching';
        } else {
            $purpose = 'Non-Teaching';
        }
        return $purpose;
    }

    public function onExcelGetAccessibility(Event $event, Entity $entity)
    {
        if ($entity->accessibility) {
            $accessibility = 'Accessible';
        } else {
            $accessibility = 'Not Accessible';
        }
        return $accessibility;
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return null;
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            null;
        }
        return null;
    }

    /**
     * @param Query $query
     * @param $table
     * @param $fk
     * @param $alias_name
     * @param string $name
     * @param null $alias_code
     * @param string $code
     * @param null $table_alias
     * @return Query
     */
    private function getRelatedQuery(Query $query, $table, $fk, $alias_name, $name = 'name', $alias_code = null, $code = 'code', $table_alias = null)
    {
        $Table = TableRegistry::get($table);
        if (!$table_alias) {
            $table_alias = $Table->alias();
        }
        $field = "{$table_alias}.{$name}";
        if (mb_strlen($name) > 0 && ctype_upper(mb_substr($name, 0, 1))) {
            $field = $name;
        }
        $query->select([
            $Table->aliasField('id'),
            $alias_name => $field])
            ->LeftJoin([$table_alias => $Table->table()], [
                "{$table_alias}.id = {$fk}"]);
        if ($alias_code) {
            $query->select([
                $alias_code => "{$table_alias}.{$code}"]);
        }
        return $query;
    }

}