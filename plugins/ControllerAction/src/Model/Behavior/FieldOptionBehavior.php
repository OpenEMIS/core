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

namespace ControllerAction\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Validation\Validator;

class FieldOptionBehavior extends Behavior {
	public function initialize(array $config) {
		$this->_table->table('field_option_values');
	}

	public function getDefaultValue() {
		$value = '';
		$primaryKey = $this->_table->primaryKey();
		$entity = $this->getDefaultEntity();
		return $entity->$primaryKey;
	}

	public function getDefaultEntity() {
		if ($this->_table->table() != 'field_option_values') {
			$query = $this->_table->find();
			$entity = $query
				->where([$this->_table->aliasField('default') => 1])
				->first();

			if (is_null($entity)) {
				$query = $this->_table->find();
				$entity = $query
					->first();
			}
		} else {
			$entity = $this->_table
				->find()
				->innerJoin(
					['FieldOption' => 'field_options'],
					[
						'FieldOption.id = ' . $this->_table->aliasField('field_option_id'),
						'FieldOption.code' => $this->_table->alias()
					]
				)
				->find('order')->find('visible')
				->where([$this->_table->aliasField('default') => 1])
				->first();
				
			if (is_null($entity)) {
				$entity = $this->_table
					->find()
					->innerJoin(
						['FieldOption' => 'field_options'],
						[
							'FieldOption.id = ' . $this->_table->aliasField('field_option_id'),
							'FieldOption.code' => $this->_table->alias()
						]
					)
					->find('order')->find('visible')
					->first();
			}
		}
		return $entity;
	}

	// public function getOptions($options=[]) { // need to cater for visible flag
	// 	$alias = $this->_table->alias();
	// 	$schema = $this->_table->schema();
	// 	$columns = $schema->columns();

	// 	if (!array_key_exists('order', $options) && in_array('order', $columns)) {
	// 		$options['order'] = [$this->_table->aliasField('order') => 'ASC'];
	// 	}

	// 	$query = $this->_table->find('list', $options);
	// 	$query->innerJoin(
	// 		['FieldOption' => 'field_options'],
	// 		[
	// 			'FieldOption.id = ' . $this->_table->aliasField('field_option_id'),
	// 			'FieldOption.code' => $alias
	// 		]
	// 	);
	// 	$data = $query->toArray();
	// 	return $data;
	// }
}
