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

use ArrayObject;

use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\I18n\Time;

class DatePickerBehavior extends Behavior {
	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options) {
		$format = 'Y-m-d';
		foreach ($this->config() as $field) {
			if (!empty($data[$field])) {
				if (!$data[$field] instanceof Time) {
					// to handle both d-m-y and d-m-Y because datepicker and cake doesnt validate
					if (date_create_from_format("d-m-y",$data[$field])) {
						$dateObj = date_create_from_format("d-m-y",$data[$field]);
					} else if (date_create_from_format("d-m-Y",$data[$field])) {
						$dateObj = date_create_from_format("d-m-Y",$data[$field]);
					}
					$data[$field] = date_format($dateObj, $format);
				}
			}
		}
	}
}
