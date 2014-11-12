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

App::uses('HttpSocket', 'Network/Http');
App::uses('CakeEmail', 'Network/Email');
class AlertsController extends AlertsAppController {
    public $uses = array(
        'Alerts.Alert'
    );
	
	public $modules = array(
		'Alert' => array('plugin' => 'Alerts')
	);
	public $components = array(
		'Option'
	);

    public function beforeFilter() {
        parent::beforeFilter();
        $this->bodyTitle = 'Administration';
        $this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Alerts', array('controller' => $this->name, 'action' => 'messages'));
    }
	
	public function sendEmailByAlert($alertId){
		$this->autoRender = false;
		$data = $this->Alert->getAlertWithRoles($alertId);
		foreach($data AS $record){
			$alert = $record['Alert'];
			$roleId = $record['AlertRole']['security_role_id'];
			
			if($alert['method']  == 'Email'){
				$SecurityRole = ClassRegistry::init('SecurityRole');
				$securityUsers = $SecurityRole->getUsersByRole($roleId);
				
				foreach($securityUsers AS $user){
					
				}
			}
		}
		
		$Email = new CakeEmail('smtp');
		$Email->from(array('kord.testing@gmail.com' => 'OpemEMIS SYSTEM'));
		$Email->to('dzhu@kordit.com');
		$Email->subject('About');
		$Email->send('My message');
	}
}
?>
