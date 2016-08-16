<?php
namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;

class RoomShell extends Shell {
	public function initialize() {
		parent::initialize();
	}

 	public function main() {
 		$copyFrom = $this->args[0];
		$copyTo = $this->args[1];

		$canCopy = $this->checkIfCanCopy($copyTo);
		if ($canCopy) {
			$this->copyProcess($copyFrom, $copyTo);
		}
	}

	private function checkIfCanCopy($copyTo) {
		$canCopy = false;

		$InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
		$count = $InstitutionRooms->find()->where([$InstitutionRooms->aliasField('academic_period_id') => $copyTo])->count();
		// can copy if no room created in current acedemic period before
		if ($count == 0) {
			$canCopy = true;
		}

		return $canCopy;
	}

	private function copyProcess($copyFrom, $copyTo) {
		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$AcademicPeriodObj = $AcademicPeriods->get($copyTo);
		$startDate = $AcademicPeriodObj->start_date->format('Y-m-d');
		$startYear = $AcademicPeriodObj->start_year;
		$endDate = $AcademicPeriodObj->end_date->format('Y-m-d');
		$endYear = $AcademicPeriodObj->end_year;

		$RoomStatuses = TableRegistry::get('Infrastructure.RoomStatuses');
		$inUseId = $RoomStatuses->getIdByCode('IN_USE');

		try {
			$connection = ConnectionManager::get('default');
			$connection->query("INSERT INTO `institution_rooms` (`code`, `name`, `start_date`, `start_year`, `end_date`, `end_year`, `room_status_id`, `institution_infrastructure_id`, `institution_id`, `academic_period_id`, `room_type_id`, `infrastructure_condition_id`, `previous_room_id`, `created_user_id`, `created`) SELECT `code`, `name`, '".$startDate."', $startYear, '".$endDate."', $endYear, `room_status_id`, `institution_infrastructure_id`, `institution_id`, $copyTo, `room_type_id`, `infrastructure_condition_id`, `id`, `created_user_id`, NOW() FROM `institution_rooms` WHERE `academic_period_id` = $copyFrom AND `room_status_id` = $inUseId");

			// uuid maybe have some unexpected behavior on any database server with replication turn on
			$connection->query("INSERT INTO `room_custom_field_values` (`id`, `text_value`, `number_value`, `textarea_value`, `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, `institution_room_id`, `created_user_id`, `created`) SELECT uuid(), `CustomFieldValues`.`text_value`, `CustomFieldValues`.`number_value`, `CustomFieldValues`.`textarea_value`, `CustomFieldValues`.`date_value`, `CustomFieldValues`.`time_value`, `CustomFieldValues`.`file`, `CustomFieldValues`.`infrastructure_custom_field_id`, `CurrentRooms`.`id`, `CustomFieldValues`.`created_user_id`, NOW() FROM `room_custom_field_values` AS `CustomFieldValues` INNER JOIN `institution_rooms` AS `PreviousRooms` ON `CustomFieldValues`.`institution_room_id` = `PreviousRooms`.`id` AND `PreviousRooms`.`academic_period_id` = $copyFrom AND `PreviousRooms`.`room_status_id` = $inUseId INNER JOIN `institution_rooms` AS `CurrentRooms` ON `CurrentRooms`.`previous_room_id` = `PreviousRooms`.`id` AND `CurrentRooms`.`academic_period_id` = $copyTo AND `CurrentRooms`.`room_status_id` = $inUseId");
		} catch (Exception $e) {
			pr($e->getMessage());
		}
	}
}
