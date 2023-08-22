<?php

namespace Report\Model\Table;

use ArrayObject;
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
            'field' => 'region_name',
            'type' => 'string',
            'label' => 'Region Name'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
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
            'field' => 'institution_status_name',
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
            'field' => 'cost',
            'type' => 'money',
            'label' => __('Cost')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'stocktake_date',
            'type' => 'date',
            'label' => __('Stocktake Date')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'lifespan',
            'type' => 'numeric',
            'label' => __('Accessibility')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {

        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id;
        $institutionTypeId = $requestData->institution_type_id;
        $areaId = $requestData->area_education_id;

        $infrastructureCondition = TableRegistry::get('infrastructure_conditions');
        $infrastructureStatus = TableRegistry::get('infrastructure_statuses');
        $institutionStatus = TableRegistry::get('institution_statuses');
        $infrastructureOwnerships = TableRegistry::get('infrastructure_ownerships');
        $infrastructureLevels = TableRegistry::get('infrastructure_levels');
        $areas = TableRegistry::get('areas');

        $query = $this->getBasicQuery($query, $institutionId, $institutionTypeId, $areaId);
        $this->log($query->sql(), 'debug');
        return $query;

        $conditions = [];
        $institutions = TableRegistry::get('Institution.Institutions');
        $institutionIds = $institutions->find('list', [
            'keyField' => 'id',
            'valueField' => 'id'
        ])
            ->where(['institution_type_id' => $institutionTypeId])
            ->toArray();

        if (!empty($institutionTypeId)) {
            $conditions['Institution' . $level . '.' . 'institution_id IN'] = $institutionIds;

        }

        if ($infrastructureLevel == 1 || $infrastructureLevel == 2) {
            $query
                ->select(['land_infrastructure_code' => 'Institution' . $level . '.' . 'code',
                    'land_infrastructure_name' => 'Institution' . $level . '.' . 'name',
                    'area_id' => 'Institutions.area_id',
                    'area_code' => $areas->aliasField('code'),
                    'area_name' => $areas->aliasField('name'),
                    'level_id' => 'Institution' . $level . '.' . 'id',
                    'land_start_date' => 'Institution' . $level . '.' . 'start_date',
                    'area' => 'Institution' . $level . '.' . 'area',
                    'year_acquired' => 'Institution' . $level . '.' . 'year_acquired',
                    'year_disposed' => 'Institution' . $level . '.' . 'year_disposed',
                    'land_infrastructure_type' => 'InfrastructureTypes.name',
                    'land_infrastructure_condition' => $infrastructureCondition->aliasField('name'),
                    'land_infrastructure_status' => $infrastructureStatus->aliasField('name'),
                    //POCOR-5698 two new columns added here
                    'shift_name' => 'ShiftOptions.name',
                    'institution_status_name' => 'InstitutionStatuses.name',
                    //POCOR-5698 ends here
                    'land_infrastructure_ownership' => $infrastructureOwnerships->aliasField('name'),
                    'land_infrastructure_accessibility' => 'Institution' . $level . '.' . 'accessibility',
                ])
                ->LeftJoin(['Institution' . $level => 'institution_' . lcfirst($level)], [
                    'Institution' . $level . '.' . 'institution_id = ' . $this->aliasField('id'),
                ])
                ->LeftJoin(['InfrastructureTypes' => $type . '_types'], [
                    'InfrastructureTypes.id = ' . $type . '_type_id',
                ])
                ->LeftJoin([$infrastructureCondition->alias() => $infrastructureCondition->table()], ['Institution' . $level . '.' . 'infrastructure_condition_id = ' . $infrastructureCondition->aliasField('id'),
                ])
                ->LeftJoin([$infrastructureStatus->alias() => $infrastructureStatus->table()], [
                    'Institution' . $level . '.' . $type . '_status_id = ' . $infrastructureStatus->aliasField('id'),
                ])
                //POCOR-5698 two new columns added here
                //status
                ->LeftJoin(['Institutions' => $institutions->table()], [
                    'Institution' . $level . '.' . 'institution_id = Institutions.id',
                ])
                ->LeftJoin([$areas->alias() => $areas->table()], [
                    'Institutions.area_id = ' . $areas->aliasField('id'),
                ])
                ->LeftJoin(['InstitutionStatuses' => $institutionStatus->table()], [
                    'InstitutionStatuses.id = Institutions.institution_status_id',
                ])
                //shift
                ->LeftJoin(['InstitutionShifts' => 'institution_shifts'], [
                    'Institution' . $level . '.' . 'institution_id = InstitutionShifts.institution_id',
                    'Institution' . $level . '.' . 'academic_period_id = InstitutionShifts.academic_period_id'
                ])
                ->LeftJoin(['ShiftOptions' => 'shift_options'], [
                    'ShiftOptions.id = InstitutionShifts.shift_option_id'
                ])
                //POCOR-5698 two new columns ends here
                ->LeftJoin([$infrastructureOwnerships->alias() => $infrastructureOwnerships->table()], [
                    'Institution' . $level . '.' . $type . '_status_id = ' . $infrastructureOwnerships->aliasField('id'),
                ])
                ->where($conditions);
        } else if ($infrastructureLevel == 3) {
            $query
                ->select(['land_infrastructure_code' => 'Institution' . $level . '.' . 'code',
                    'land_infrastructure_name' => 'Institution' . $level . '.' . 'name',
                    'area_id' => 'Institutions.area_id',
                    'area_code' => $areas->aliasField('code'),
                    'area_name' => $areas->aliasField('name'),
                    'level_id' => 'Institution' . $level . '.' . 'id',
                    'land_start_date' => 'Institution' . $level . '.' . 'start_date',
                    'area' => 'Institution' . $level . '.' . 'area',
                    'land_infrastructure_type' => 'InfrastructureTypes.name',
                    'land_infrastructure_condition' => $infrastructureCondition->aliasField('name'),
                    'land_infrastructure_status' => $infrastructureStatus->aliasField('name'),
                    //POCOR-5698 two new columns added here
                    'shift_name' => 'ShiftOptions.name',
                    'institution_status_name' => 'InstitutionStatuses.name',
                    //POCOR-5698 ends here
                    'land_infrastructure_ownership' => $infrastructureOwnerships->aliasField('name'),
                    'land_infrastructure_accessibility' => 'Institution' . $level . '.' . 'accessibility',
                ])
                ->LeftJoin(['Institution' . $level => 'institution_' . lcfirst($level)], [
                    'Institution' . $level . '.' . 'institution_id = ' . $this->aliasField('id'),
                ])
                ->LeftJoin(['InfrastructureTypes' => $type . '_types'], [
                    'InfrastructureTypes.id = ' . $type . '_type_id',
                ])
                ->LeftJoin([$infrastructureCondition->alias() => $infrastructureCondition->table()], ['Institution' . $level . '.' . 'infrastructure_condition_id = ' . $infrastructureCondition->aliasField('id'),
                ])
                ->LeftJoin([$infrastructureStatus->alias() => $infrastructureStatus->table()], [
                    'Institution' . $level . '.' . $type . '_status_id = ' . $infrastructureStatus->aliasField('id'),
                ])
                //POCOR-5698 two new columns added here
                //status
                ->LeftJoin(['Institutions' => $institutions->table()], [
                    'Institution' . $level . '.' . 'institution_id = Institutions.id',
                ])
                ->LeftJoin([$areas->alias() => $areas->table()], [
                    'Institutions.area_id = ' . $areas->aliasField('id'),
                ])
                ->LeftJoin(['InstitutionStatuses' => $institutionStatus->table()], [
                    'InstitutionStatuses.id = Institutions.institution_status_id',
                ])
                //shift
                ->LeftJoin(['InstitutionShifts' => 'institution_shifts'], [
                    'Institution' . $level . '.' . 'institution_id = InstitutionShifts.institution_id',
                    'Institution' . $level . '.' . 'academic_period_id = InstitutionShifts.academic_period_id'
                ])
                ->LeftJoin(['ShiftOptions' => 'shift_options'], [
                    'ShiftOptions.id = InstitutionShifts.shift_option_id'
                ])
                //POCOR-5698 two new columns ends here
                ->LeftJoin([$infrastructureOwnerships->alias() => $infrastructureOwnerships->table()], [
                    'Institution' . $level . '.' . $type . '_status_id = ' . $infrastructureOwnerships->aliasField('id'),
                ])
                ->where($conditions);
        } else {
            $query
                ->select(['land_infrastructure_code' => 'Institution' . $level . '.' . 'code',
                    'land_infrastructure_name' => 'Institution' . $level . '.' . 'name',
                    'area_id' => 'Institutions.area_id',
                    'area_code' => $areas->aliasField('code'),
                    'area_name' => $areas->aliasField('name'),
                    'level_id' => 'Institution' . $level . '.' . 'id',
                    'land_start_date' => 'Institution' . $level . '.' . 'start_date',
                    'land_infrastructure_type' => 'InfrastructureTypes.name',
                    'land_infrastructure_condition' => $infrastructureCondition->aliasField('name'),
                    'land_infrastructure_status' => $infrastructureStatus->aliasField('name'),
                    //POCOR-5698 two new columns added here
                    'shift_name' => 'ShiftOptions.name',
                    'institution_status_name' => 'InstitutionStatuses.name',
                    //POCOR-5698 ends here
                    'land_infrastructure_ownership' => $infrastructureOwnerships->aliasField('name'),
                    'land_infrastructure_accessibility' => 'Institution' . $level . '.' . 'accessibility',
                ])
                ->LeftJoin(['Institution' . $level => 'institution_' . lcfirst($level)], [
                    'Institution' . $level . '.' . 'institution_id = ' . $this->aliasField('id'),
                ])
                ->LeftJoin(['InfrastructureTypes' => $type . '_types'], [
                    'InfrastructureTypes.id = ' . $type . '_type_id',
                ])
                ->LeftJoin([$infrastructureCondition->alias() => $infrastructureCondition->table()], ['Institution' . $level . '.' . 'infrastructure_condition_id = ' . $infrastructureCondition->aliasField('id'),
                ])
                ->LeftJoin([$infrastructureStatus->alias() => $infrastructureStatus->table()], [
                    'Institution' . $level . '.' . $type . '_status_id = ' . $infrastructureStatus->aliasField('id'),
                ])
                //POCOR-5698 two new columns added here
                //status
                ->LeftJoin(['Institutions' => $institutions->table()], [
                    'Institution' . $level . '.' . 'institution_id = Institutions.id',
                ])
                ->LeftJoin([$areas->alias() => $areas->table()], [
                    'Institutions.area_id = ' . $areas->aliasField('id'),
                ])
                ->LeftJoin(['InstitutionStatuses' => $institutionStatus->table()], [
                    'InstitutionStatuses.id = Institutions.institution_status_id',
                ])
                //shift
                ->LeftJoin(['InstitutionShifts' => 'institution_shifts'], [
                    'Institution' . $level . '.' . 'institution_id = InstitutionShifts.institution_id',
                    'Institution' . $level . '.' . 'academic_period_id = InstitutionShifts.academic_period_id'
                ])
                ->LeftJoin(['ShiftOptions' => 'shift_options'], [
                    'ShiftOptions.id = InstitutionShifts.shift_option_id'
                ])
                //POCOR-5698 two new columns ends here
                ->LeftJoin([$infrastructureOwnerships->alias() => $infrastructureOwnerships->table()], [
                    'Institution' . $level . '.' . $type . '_status_id = ' . $infrastructureOwnerships->aliasField('id'),
                ])
                ->where($conditions);
        }
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($type) {
            return $results->map(function ($row) use ($type) {

                $areas1 = TableRegistry::get('areas');
                $areasData = $areas1
                    ->find()
                    ->where([$areas1->alias('code') => $row->area_code])
                    ->first();
                $row['region_code'] = '';
                $row['region_name'] = '';
                if (!empty($areasData)) {
                    $areas = TableRegistry::get('areas');
                    $areaLevels = TableRegistry::get('area_levels');
                    $institutions = TableRegistry::get('institutions');
                    $val = $areas
                        ->find()
                        ->select([
                            $areas1->aliasField('code'),
                            $areas1->aliasField('name'),
                        ])
                        ->leftJoin(
                            [$areaLevels->alias() => $areaLevels->table()],
                            [
                                $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                            ]
                        )
                        ->leftJoin(
                            [$institutions->alias() => $institutions->table()],
                            [
                                $areas->aliasField('id  = ') . $institutions->aliasField('area_id')
                            ]
                        )
                        ->where([
                            $areaLevels->aliasField('level !=') => 1,
                            $areas->aliasField('id') => $areasData->parent_id
                        ])->first();

                    if (!empty($val->name) && !empty($val->code)) {
                        $row['region_code'] = $val->code;
                        $row['region_name'] = $val->name;
                    }
                }

                $InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
                if (!empty($row['level_id'])) {
                    $customFieldData = $InfrastructureCustomFields->find()
                        ->select([
                            'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                            'custom_field' => $InfrastructureCustomFields->aliasfield('name'),
                            'text_value' => 'CustomFieldValues.text_value',
                            'number_value' => 'CustomFieldValues.number_value',
                            'decimal_value' => 'CustomFieldValues.decimal_value',
                            'textarea_value' => 'CustomFieldValues.textarea_value',
                            'date_value' => 'CustomFieldValues.date_value',
                            'time_value' => 'CustomFieldValues.time_value'
                        ])
                        ->innerJoin(['CustomFieldValues' => lcfirst($type) . '_custom_field_values'], [
                            'CustomFieldValues.infrastructure_custom_field_id = ' . $InfrastructureCustomFields->aliasField('id'),
                            'CustomFieldValues.institution_' . lcfirst($type) . '_id  = ' . $row['level_id']
                        ])
                        ->toArray();
                }
                if (!empty($customFieldData)) {
                    foreach ($customFieldData as $data) {
                        if (!empty($data->text_value)) {
                            $row[$data->custom_field_id] = $data->text_value;
                        }
                        if (!empty($data->number_value)) {
                            $row[$data->custom_field_id] = $data->number_value;
                        }
                        if (!empty($data->decimal_value)) {
                            $row[$data->custom_field_id] = $data->decimal_value;
                        }
                        if (!empty($data->textarea_value)) {
                            $row[$data->custom_field_id] = $data->textarea_value;
                        }
                        if (!empty($data->date_value)) {
                            $row[$data->custom_field_id] = $data->date_value;

                        }
                        if (!empty($data->time_value)) {
                            $row[$data->custom_field_id] = $data->time_value;

                        }
                    }
                }
                return $row;
            });
        });
    }

    /**
     * @param Query $query
     * @param $institutionId
     * @param $institutionTypeId
     * @param $areaId
     * @return Query
     */
    private function getBasicQuery(Query $query, $institutionId, $institutionTypeId, $areaId)
    {
        $conditions = ["1 = 1"];
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions[$this->aliasField('id')] = $institutionId;
        }
        if (!empty($institutionTypeId) && $institutionTypeId != -1) {
            $conditions[$this->aliasField('institution_type_id')] = $institutionTypeId;
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions[$this->aliasField('area_id')] = $areaId;
        }
        $institutionAssets = TableRegistry::get('institution_assets');
        $query = $query->select([
            $this->aliasField('id'),
            'institution_code' => $this->aliasField('code'),
            'institution_name' => $this->aliasField('name'),
            'asset_code' => $institutionAssets->aliasField('code'),
            'asset_description' => $institutionAssets->aliasField('description'),
            'serial_number' => $institutionAssets->aliasField('serial_number'),
            $this->aliasField('area_id'),
        ])
            ->leftJoin([$institutionAssets->alias() => $institutionAssets->table()],
                [$institutionAssets->aliasField('institution_id = ') . $this->aliasField('id')])
            ->where($conditions);

        return $query;
    }
}