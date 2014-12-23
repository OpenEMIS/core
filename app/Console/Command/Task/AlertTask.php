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

class AlertTask extends Shell {
	public $uses = array('Alerts.Alert');

    public function getObject($code = null) {
		if (is_null($code)) {
			$code = get_class($this);
			$code = str_replace('Alert', '', $code);
			$code = str_replace('Task', '', $code);
		}
		return $this->Alert->findByCode($code);
	}
	
	public function getRoleIds($code = null) {
		if (is_null($code)) {
			$code = get_class($this);
			$code = str_replace('Alert', '', $code);
			$code = str_replace('Task', '', $code);
		}
		
		$data = $this->Alert->find('all', array(
			'recursive' => -1,
			'fields' => array('AlertRole.security_role_id'),
			'joins' => array(
				array(
					'table' => 'alert_roles',
					'alias' => 'AlertRole',
					'conditions' => array('Alert.id = AlertRole.alert_id')
				)
			),
			'conditions' => array('Alert.code' => $code)
		));
		
		return $data;
	}
	
}
