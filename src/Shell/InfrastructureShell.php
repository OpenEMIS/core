<?php
namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;

class InfrastructureShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Initialize Infrastructure Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];
        $this->out('Processing ...');
        $this->copyProcess($copyFrom, $copyTo);
        $this->out('End Infrastructure Shell');
    }

    private function checkIfCanCopy($modelAlias, $copyTo)
    {
        $model = TableRegistry::get($modelAlias);
        $count = $model->find()->where([$model->aliasField('academic_period_id') => $copyTo])->count();
        // can copy if no room created in current acedemic period before
        if ($count > 0) {
            return false;
        } else {
            return true;
        }
    }

    private function copyProcess($copyFrom, $copyTo)
    {
        $this->out('Start infrastructure copy process');
        $containCount = 0;
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $AcademicPeriodObj = $AcademicPeriods->get($copyTo);
        $startDate = $AcademicPeriodObj->start_date;
        $startYear = $AcademicPeriodObj->start_year;
        $endDate = $AcademicPeriodObj->end_date;
        $endYear = $AcademicPeriodObj->end_year;
        $InfrastructureStatuses = TableRegistry::get('Infrastructure.InfrastructureStatuses');
        $inUseId = $InfrastructureStatuses->getIdByCode('IN_USE');
        $query = null;
        $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
        if ($this->checkIfCanCopy('Institution.InstitutionLands', $copyTo)) {
            $query = $InstitutionLands->find()->where([
                $InstitutionLands->aliasField('land_status_id') => $inUseId,
                $InstitutionLands->aliasField('academic_period_id') => $copyFrom
            ]);
            $containModels = ['InstitutionBuildings', 'InstitutionFloors', 'InstitutionRooms'];
            $contain = '';
            foreach ($containModels as $model) {
                if ($this->checkIfCanCopy('Institution.'.$model, $copyTo)) {
                    $contain .= $model. '.';
                } else {
                    break;
                }
            }
            $contain = rtrim($contain, '.');
            if (!empty($contain)) {
                $containCount = count(explode('.', $contain));
                $containQuery = [];
                switch ($containCount) {
                    case 1:
                        $containQuery = [
                            'InstitutionBuildings' => function ($query) use ($inUseId) {
                                return $query->where([
                                    'InstitutionBuildings.building_status_id' => $inUseId,
                                ]);
                            }
                        ];
                        break;
                    case 2:
                        $containQuery = [
                            'InstitutionBuildings' => function ($query) use ($inUseId) {
                                return $query->where([
                                    'InstitutionBuildings.building_status_id' => $inUseId,
                                ]);
                            },
                            'InstitutionBuildings.InstitutionFloors' => function ($query) use ($inUseId) {
                                return $query->where([
                                    'InstitutionFloors.floor_status_id' => $inUseId,
                                ]);
                            }
                        ];
                        break;
                    case 3:
                        $containQuery = [
                            'InstitutionBuildings' => function ($query) use ($inUseId) {
                                return $query->where([
                                    'InstitutionBuildings.building_status_id' => $inUseId,
                                ]);
                            },
                            'InstitutionBuildings.InstitutionFloors' => function ($query) use ($inUseId) {
                                return $query->where([
                                    'InstitutionFloors.floor_status_id' => $inUseId,
                                ]);
                            },
                            'InstitutionBuildings.InstitutionFloors.InstitutionRooms' => function ($query) use ($inUseId) {
                                return $query->where([
                                    'InstitutionRooms.room_status_id' => $inUseId
                                ]);
                            }
                        ];
                        break;
                }
                $query = $query->contain($containQuery);
            }
        }
        if (!is_null($query)) {
            $totalRecords = $query->count();
            $this->out('Total Records to be processed: ' . $totalRecords);
        } else {
            $this->out('No records processed');
        }

        $limit = 100;
        $pageCount = 1;
        $countRecords = 0;

        $saveOptions = [
            'validate' => 'savingByAssociation'
        ];

        while (!is_null($query)) {
            $executedQuery = $query->page($pageCount++, $limit)->hydrate(false)->toArray();
            if (empty($executedQuery)) {
                break;
            }
            foreach ($executedQuery as $land) {
                $this->out('Start Processing Record '. ++$countRecords . ' of ' . $totalRecords);
                $saveOptions = [];
                $land['previous_institution_land_id'] = $land['id'];
                $land['start_date'] = $startDate;
                $land['end_date'] = $endDate;
                $land['start_year'] = $startYear;
                $land['end_year'] = $endYear;
                $land['academic_period_id'] = $copyTo;
                unset($land['id']);
                if (isset($land['institution_buildings']) && !empty($land['institution_buildings'])) {
                    $saveOptions = [
                        'associated' => [
                            'InstitutionBuildings' => [
                                'validate' => 'savingByAssociation'
                            ]
                        ],
                        'validate' => 'savingByAssociation'
                    ];
                    foreach ($land['institution_buildings'] as &$building) {
                        $building['previous_institution_building_id'] = $building['id'];
                        $building['start_date'] = $startDate;
                        $building['end_date'] = $endDate;
                        $building['start_year'] = $startYear;
                        $building['end_year'] = $endYear;
                        $building['academic_period_id'] = $copyTo;
                        unset($building['id']);
                        unset($building['institution_land_id']);
                        if (isset($building['institution_floors']) && !empty($building['institution_floors'])) {
                            $saveOptions = [

                                'associated' => [
                                    'InstitutionBuildings' =>
                                        [
                                            'associated' => [
                                                'InstitutionFloors' => [
                                                    'validate' => 'savingByAssociation'
                                                ]
                                            ],
                                            'validate' => 'savingByAssociation'
                                        ]
                                    ],
                                'validate' => 'savingByAssociation'
                            ];
                            foreach ($building['institution_floors'] as &$floor) {
                                $floor['previous_institution_floor_id'] = $floor['id'];
                                $floor['start_date'] = $startDate;
                                $floor['end_date'] = $endDate;
                                $floor['start_year'] = $startYear;
                                $floor['end_year'] = $endYear;
                                $floor['academic_period_id'] = $copyTo;
                                unset($floor['id']);
                                unset($floor['institution_building_id']);
                                if (isset($floor['institution_rooms']) && !empty($floor['institution_rooms'])) {
                                    $saveOptions = [

                                        'associated' => [
                                            'InstitutionBuildings' =>
                                                [
                                                    'associated' => [
                                                        'InstitutionFloors' => [
                                                            'associated' => [
                                                                'InstitutionRooms' => [
                                                                    'validate' => 'savingByAssociation'
                                                                ]
                                                            ],
                                                            'validate' => 'savingByAssociation'
                                                        ]
                                                    ],
                                                    'validate' => 'savingByAssociation'
                                                ]
                                            ],
                                        'validate' => 'savingByAssociation'
                                    ];
                                    foreach ($floor['institution_rooms'] as &$room) {
                                        $room['previous_institution_room_id'] = $room['id'];
                                        $room['start_date'] = $startDate;
                                        $room['end_date'] = $endDate;
                                        $room['start_year'] = $startYear;
                                        $room['end_year'] = $endYear;
                                        $room['academic_period_id'] = $copyTo;
                                        unset($room['id']);
                                        unset($room['institution_floor_id']);
                                    }
                                }
                            }
                        }
                    }
                }
                $newEntity = $InstitutionLands->newEntity($land, $saveOptions);
                $InstitutionLands->save($newEntity, $saveOptions);
                if ($newEntity->errors()) {
                    $this->out('Error Processing Record '. $countRecords . ' of ' . $totalRecords);
                    $this->out($newEntity);
                } else {
                    $this->out('Finish Processing Record '. $countRecords . ' of ' . $totalRecords);
                }
            }

        }
        if (!is_null($query)) {
            try {
                $connection = ConnectionManager::get('default');
                $this->out('Processing Custom Field Records');
                switch ($containCount) {
                    case 3:
                        // uuid maybe have some unexpected behavior on any database server with replication turn on
                        $connection->query("INSERT INTO `room_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `created_user_id`, `created`) SELECT uuid(), `CustomFieldValues`.`text_value`, `CustomFieldValues`.`number_value`, `CustomFieldValues`.`decimal_value`, `CustomFieldValues`.`textarea_value`, `CustomFieldValues`.`date_value`, `CustomFieldValues`.`time_value`, `CustomFieldValues`.`file`, `CustomFieldValues`.`infrastructure_custom_field_id`, `CurrentRooms`.`id`, `CustomFieldValues`.`created_user_id`, NOW() FROM `room_custom_field_values` AS `CustomFieldValues` INNER JOIN `institution_rooms` AS `PreviousRooms` ON `CustomFieldValues`.`institution_room_id` = `PreviousRooms`.`id` AND `PreviousRooms`.`academic_period_id` = $copyFrom AND `PreviousRooms`.`room_status_id` = $inUseId INNER JOIN `institution_rooms` AS `CurrentRooms` ON `CurrentRooms`.`previous_institution_room_id` = `PreviousRooms`.`id` AND `CurrentRooms`.`academic_period_id` = $copyTo AND `CurrentRooms`.`room_status_id` = $inUseId");
                        // no break
                    case 2:
                        // uuid maybe have some unexpected behavior on any database server with replication turn on
                        $connection->query("INSERT INTO `floor_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_floor_id`, `created_user_id`, `created`) SELECT uuid(), `CustomFieldValues`.`text_value`, `CustomFieldValues`.`number_value`, `CustomFieldValues`.`decimal_value`, `CustomFieldValues`.`textarea_value`, `CustomFieldValues`.`date_value`, `CustomFieldValues`.`time_value`, `CustomFieldValues`.`file`, `CustomFieldValues`.`infrastructure_custom_field_id`, `CurrentFloors`.`id`, `CustomFieldValues`.`created_user_id`, NOW() FROM `floor_custom_field_values` AS `CustomFieldValues` INNER JOIN `institution_floors` AS `PreviousFloors` ON `CustomFieldValues`.`institution_floor_id` = `PreviousFloors`.`id` AND `PreviousFloors`.`academic_period_id` = $copyFrom AND `PreviousFloors`.`floor_status_id` = $inUseId INNER JOIN `institution_floors` AS `CurrentFloors` ON `CurrentFloors`.`previous_institution_floor_id` = `PreviousFloors`.`id` AND `CurrentFloors`.`academic_period_id` = $copyTo AND `CurrentFloors`.`floor_status_id` = $inUseId");
                        // no break
                    case 1:
                        $connection->query("INSERT INTO `building_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_building_id`, `created_user_id`, `created`) SELECT uuid(), `CustomFieldValues`.`text_value`, `CustomFieldValues`.`number_value`, `CustomFieldValues`.`decimal_value`, `CustomFieldValues`.`textarea_value`, `CustomFieldValues`.`date_value`, `CustomFieldValues`.`time_value`, `CustomFieldValues`.`file`, `CustomFieldValues`.`infrastructure_custom_field_id`, `CurrentBuildings`.`id`, `CustomFieldValues`.`created_user_id`, NOW() FROM `building_custom_field_values` AS `CustomFieldValues` INNER JOIN `institution_buildings` AS `PreviousBuildings` ON `CustomFieldValues`.`institution_building_id` = `PreviousBuildings`.`id` AND `PreviousBuildings`.`academic_period_id` = $copyFrom AND `PreviousBuildings`.`building_status_id` = $inUseId INNER JOIN `institution_buildings` AS `CurrentBuildings` ON `CurrentBuildings`.`previous_institution_building_id` = `PreviousBuildings`.`id` AND `CurrentBuildings`.`academic_period_id` = $copyTo AND `CurrentBuildings`.`building_status_id` = $inUseId");
                        // no break
                    default:
                        // uuid maybe have some unexpected behavior on any database server with replication turn on
                        $connection->query("INSERT INTO `land_custom_field_values` (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_land_id`, `created_user_id`, `created`) SELECT uuid(), `CustomFieldValues`.`text_value`, `CustomFieldValues`.`number_value`, `CustomFieldValues`.`decimal_value`, `CustomFieldValues`.`textarea_value`, `CustomFieldValues`.`date_value`, `CustomFieldValues`.`time_value`, `CustomFieldValues`.`file`, `CustomFieldValues`.`infrastructure_custom_field_id`, `CurrentLands`.`id`, `CustomFieldValues`.`created_user_id`, NOW() FROM `land_custom_field_values` AS `CustomFieldValues` INNER JOIN `institution_lands` AS `PreviousLands` ON `CustomFieldValues`.`institution_land_id` = `PreviousLands`.`id` AND `PreviousLands`.`academic_period_id` = $copyFrom AND `PreviousLands`.`land_status_id` = $inUseId INNER JOIN `institution_lands` AS `CurrentLands` ON `CurrentLands`.`previous_institution_land_id` = `PreviousLands`.`id` AND `CurrentLands`.`academic_period_id` = $copyTo AND `CurrentLands`.`land_status_id` = $inUseId");
                        break;
                }
                $this->out('End infrastructure copy process');
                $this->out('Finish Processing Custom Field Records');
            } catch (Exception $e) {
                $this->out('Error in infrastructure copy process');
                $this->out('Error Message' . $e->getMessage());
            }
        }
    }
}
