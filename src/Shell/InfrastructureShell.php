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
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $AcademicPeriodObj = $AcademicPeriods->get($copyTo);
        $startDate = $AcademicPeriodObj->start_date->format('Y-m-d');
        $startYear = $AcademicPeriodObj->start_year;
        $endDate = $AcademicPeriodObj->end_date->format('Y-m-d');
        $endYear = $AcademicPeriodObj->end_year;

        $InfrastructureStatuses = TableRegistry::get('Infrastructure.InfrastructureStatuses');
        $inUseId = $InfrastructureStatuses->getIdByCode('IN_USE');
        $query = null;
        $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
        if ($this->checkIfCanCopy('Institution.InstitutionLands', $copyTo)) {
            $query = $InstitutionLands->find()->where([$InstitutionLands->aliasField('land_status_id') => $inUseId]);
            $containModels = ['InstitutionBuildings', 'InstitutionFloors', 'InstitutionRooms'];
            $contain = '';
            $canCopyModels = [];
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
                            $contain => function ($query) use ($inUseId) {
                                return $query->where([
                                    'InstitutionBuildings.building_status_id' => $inUseId
                                ]);
                            }
                        ];
                        break;
                    case 2:
                        $containQuery = [
                            $contain => function ($query) use ($inUseId) {
                                return $query->where([
                                    'InstitutionBuildings.building_status_id' => $inUseId,
                                    'InstitutionFloors.floor_status_id' => $inUseId
                                ]);
                            }
                        ];
                        break;
                    case 3:
                        $containQuery = [
                            $contain => function ($query) use ($inUseId) {
                                return $query->where([
                                    'InstitutionBuildings.building_status_id' => $inUseId,
                                    'InstitutionFloors.floor_status_id' => $inUseId,
                                    'InstitutionRooms.room_status_id' => $inUseId
                                ]);
                            }
                        ];
                        break;
                }
                $query = $query->contain($containQuery);
            }
        }

        $limit = 100;
        $page = 1;



        while (!is_null($query) && $query->page($page, $limit)->count() > 0) {
            $executedQuery = $query->page($page, $limit);
            foreach ($executedQuery->toArray() as $land) {
                $saveOptions = [];
                $land->previous_institution_land_id = $land->id;
                $land->unsetProperty('id');
                if ($land->offsetExists('institution_buildings')) {
                    $saveOptions = ['associated' => 'InstitutionBuildings'];
                    foreach ($land->institution_buildings as &$building) {
                        $building->previous_institution_building_id = $building->id;
                        $building->unsetProperty('id');
                        $building->unsetProperty('institution_land_id');
                        if ($building->offsetExists('institution_floors')) {
                            $saveOptions = ['associated' => [
                                'InstitutionBuildings' =>
                                    ['associated' => 'InstitutionFloors']
                                ]
                            ];
                            foreach ($building->institution_floors as &$floor) {
                                $floor->previous_institution_floor_id = $floor->id;
                                $floor->unsetProperty('id');
                                $floor->unsetProperty('institution_building_id');
                                if ($floor->offsetExists('institution_rooms')) {
                                    $saveOptions = ['associated' => [
                                        'InstitutionBuildings' =>
                                            ['associated' => [
                                                    'InstitutionFloors' => [
                                                        'associated' => 'InstitutionRooms'
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ];
                                    foreach ($building->institution_rooms as &$room) {
                                        $room->previous_room_id = $room->id;
                                        $floor->unsetProperty('id');
                                        $floor->unsetProperty('institution_floor_id');
                                    }
                                }
                            }
                        }
                    }
                    $InstitutionLands->save($land);
                }
            }
            $page++;
        }

        if (!is_null($query)) {
            try {
                $connection = ConnectionManager::get('default');
                // $connection->query("INSERT INTO `institution_rooms` (`code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `room_status_id`, `institution_infrastructure_id`, `institution_id`, `academic_period_id`, `room_type_id`, `infrastructure_condition_id`, `previous_room_id`, `created_user_id`, `created`) SELECT `code`, `name`, '".$startDate."', $startYear, '".$endDate."', $endYear, `room_status_id`, `institution_infrastructure_id`, `institution_id`, $copyTo, `room_type_id`, `infrastructure_condition_id`, `id`, `created_user_id`, NOW() FROM `institution_rooms` WHERE `academic_period_id` = $copyFrom AND `room_status_id` = $inUseId");

                // uuid maybe have some unexpected behavior on any database server with replication turn on
                $connection->query("INSERT INTO `room_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `created_user_id`, `created`) SELECT uuid(), `CustomFieldValues`.`text_value`, `CustomFieldValues`.`number_value`, `CustomFieldValues`.`textarea_value`, `CustomFieldValues`.`date_value`, `CustomFieldValues`.`time_value`, `CustomFieldValues`.`file`, `CustomFieldValues`.`infrastructure_custom_field_id`, `CurrentRooms`.`id`, `CustomFieldValues`.`created_user_id`, NOW() FROM `room_custom_field_values` AS `CustomFieldValues` INNER JOIN `institution_rooms` AS `PreviousRooms` ON `CustomFieldValues`.`institution_room_id` = `PreviousRooms`.`id` AND `PreviousRooms`.`academic_period_id` = $copyFrom AND `PreviousRooms`.`room_status_id` = $inUseId INNER JOIN `institution_rooms` AS `CurrentRooms` ON `CurrentRooms`.`previous_room_id` = `PreviousRooms`.`id` AND `CurrentRooms`.`academic_period_id` = $copyTo AND `CurrentRooms`.`room_status_id` = $inUseId");
            } catch (Exception $e) {
                pr($e->getMessage());
            }
        }
    }
}
