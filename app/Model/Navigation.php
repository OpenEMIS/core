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
class Navigation extends AppModel {
	public function getByModule($module, $format = false) {
		$data = $this->find('all', array(
			'conditions' => array('module' => $module),
			'order' => array('order')
		));
		if($format) {
			$data = $this->format($data);
		}
		return $data;
	}

	public function getWizardByModule($module, $format = false){
			$data = $this->find('all', array(
			'conditions' => array('module' => $module, 'is_wizard'=>'1'),
			'order' => array('order')
		));
		if($format) {
			$data = $this->format($data);
		}
		return $data;
	}
	
	public function format($data) {
		$links = array();
		foreach($data as $item) {
			$obj = $item[$this->alias];
			$id = $obj['id'];
			$parent = $obj['parent'];
			$header = $obj['header'];
			
			$attr = !is_null($obj['attributes']) ? (array) json_decode($obj['attributes']) : array();
			$attr['title'] = $obj['title'];
			$attr['display'] = false;
			$attr['selected'] = false;
			$attr['controller'] = $obj['controller'];
			$attr['plugin'] = $obj['plugin'];
			$attr['action'] = $obj['action'];
			$attr['pattern'] = $obj['pattern'];
			$attr['wizard'] = $obj['is_wizard'];
			
			if($parent == -1) {
				$key = $id;
				$links[$key] = array();
			} else {
				$key = $parent;
			}
			if(!empty($header)) {
				if(!array_key_exists($header, $links[$key])) {
					$links[$key][$header] = array();
				}
				$links[$key][$header][] = $attr;
				
			} else {
				$links[$key][0][] = $attr;
			}
		}
		return $links;
	}
}
