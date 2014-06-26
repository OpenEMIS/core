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

class LDAPComponent extends Component {
	public $components = array('Session');
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {
	}
	
	public function checkConn($settings=array()){
		
		if(!isset($settings['host']) || empty($settings['host'])) return false;
		$ldap_host = $settings['host'].":".$settings['port'];
		$ldap = ldap_connect($ldap_host) or die("Could not connect to LDAP");
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $settings['version']);
		return (@ldap_bind($ldap))?true:false;
	}
	
	public function verifyUser($settings=array()) {
		$username = $settings['username'];//"ldap.testathon.net:389";
		$password = $settings['password'];//"ldap.testathon.net:389";
		$host = $settings['host'].":".$settings['port'];//"ldap.testathon.net:389";
		$basedn = $settings['base_dn'];
		$version = ($settings['version'])?$settings['version']:3;//"3";
		$ldap = ldap_connect($host) or die(__("Could not connect to LDAP"));
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $version);

		//if($settings['test_connection'] == 1){
		//		$p = ldap_bind($host) or die(__("Could not connect to LDAP"));
		//}else{
		if($ldap){
			$ldap_user = "CN=".$username.(($basedn != '')?",".$basedn:'');
			$ldap_password = $password;
			$p = @ldap_bind($ldap, $ldap_user, $ldap_password);
			
		}
		//}
		if($p){
			return true;
		}else{
			return ldap_err2str(ldap_errno($ldap)) ;
		}
	}
	
	public function ldapAuth($message, $settings=array()) {
		$types = array('ok', 'error', 'info', 'warn');
		$_settings = array(
			'type' => 'ok',
			'dismissOnClick' => true,
		);
		$_settings = array_merge($_settings, $settings);
		if(!in_array($_settings['type'], $types)) {
			$_settings['type'] =  $types[0];
		}
		$_settings['message'] = __($message);
		if(!$this->Session->check('_alert')) {
			$this->Session->write('_alert', $_settings);
		}
	}
	
	
	
}
?>