<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppModel', 'Model');

class BatchIndicatorResult extends DataProcessingAppModel {
	public $useTable = 'batch_indicator_results';
	
	public function truncate($indicatorId=0) {
		if($indicatorId==0) {
			return $this->query(sprintf('TRUNCATE TABLE %s', $this->useTable));
		} else {
			return $this->query(sprintf('DELETE FROM %s WHERE batch_indicator_id = %d', $this->useTable, $indicatorId));
		}
	}
	
	public function createNew($indicatorId, $subgroups, $data) {
		$model = $this->alias;
		foreach($data as $row) {
			$obj = array($model => $row);
			$obj[$model]['batch_indicator_id'] = $indicatorId;
			$obj[$model]['subgroups'] = $subgroups;
			$this->create();
			$this->save($obj);
		}
	}
	
	public function aggregateByAreaLevel($indicatorId, $levelId) {
		/*
		select
		`batch_indicator_results`.`timeperiod`,
		`area_parent`.`name`,
		SUM(`batch_indicator_results`.`numerator`),
		SUM(`batch_indicator_results`.`denominator`)
		from `batch_indicator_results`
		join `areas` 
			on `areas`.`id` = `batch_indicator_results`.`area_id`
			and `areas`.`area_level_id` = 5
		join `areas` as `area_parent`
			on `area_parent`.`id` = `areas`.`parent_id`
		where `batch_indicator_results`.`batch_indicator_id` = 5
		group by `areas`.`parent_id`, `batch_indicator_results`.`timeperiod`
		*/
		$data = $this->find('all', array(
			'fields' => array(
				'BatchIndicatorResult.batch_indicator_id',
				'BatchIndicatorResult.subgroups',
				'Area.parent_id',
				'BatchIndicatorResult.timeperiod',
				'SUM(BatchIndicatorResult.numerator) AS numerator',
				'SUM(BatchIndicatorResult.denominator) AS denominator'),
				'BatchIndicatorResult.classification',
				'BatchIndicatorResult.created_user_id',
				'BatchIndicatorResult.created'
			'joins' => array(
				array(
					'table' => 'areas',
					'alias' => 'Area',
					'conditions' => array(
						'Area.id = BatchIndicatorResult.area_id',
						'Area.area_level_id = ' . $levelId
					)
				)
			),
			'conditions' => array('BatchIndicatorResult.batch_indicator_id' => $indicatorId)
			'group' => array('Area.parent_id', 'BatchIndicatorResult.timeperiod', 'BatchIndicatorResult.classification', 'BatchIndicatorResult.subgroups')
		));
		return $data;
	}
}
