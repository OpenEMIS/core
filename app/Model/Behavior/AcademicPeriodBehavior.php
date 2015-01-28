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

class AcademicPeriodBehavior extends ModelBehavior {
	public function getAcademicPeriodOptions(Model $model, $conditions=array()) {
		$options = array();
		$options['fields'] = array('AcademicPeriod.id', 'AcademicPeriod.name');
		$options['joins'] = array(
			array(
				'table' => 'academic_periods', 'alias' => 'AcademicPeriod',
				'conditions' => array('AcademicPeriod.id = ' . $model->alias . '.academic_period_id')
			)
		);
		$options['order'] = array('AcademicPeriod.order');
		$options['conditions'] = array('AcademicPeriod.parent_id >' => 0);
		
		if (!empty($conditions)) {
			$options['conditions'] = array_merge($options['conditions'], $conditions);
		}
		$list = $model->find('list', $options);
		return $list;
	}
}
