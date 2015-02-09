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

class ActivityBehavior extends ModelBehavior {
	// used by SearchComponent
	public function getSearchConditions(Model $model, $search) {
		$search = '%' . $search . '%';
		$conditions['OR'] = array(
			$model->alias . '.model LIKE' => $search,
			$model->alias . '.field LIKE' => $search,
			$model->alias . '.old_value LIKE' => $search,
			$model->alias . '.new_value LIKE' => $search,
			$model->alias . '.created LIKE' => $search,
			'ModifiedUser.first_name LIKE' => $search,
			'ModifiedUser.last_name LIKE' => $search
		);
		return $conditions;
	}
}
