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

/**
 * Component for working with mPDF class.
 * mPDF has to be in the vendors directory.
 */
App::uses('CakeEmail', 'Network/Email');
class EmailComponent extends Component {

	protected $Mail;

	public function initialize(Controller $controller) {
		$this->Mail = new CakeEmail('smtp');
	}
	
	public function setConfig($config=array()){
		$this->Mail->config($config);
//		foreach($configs AS $key => $value){
//			$this->Mail->{$key}($value);
//		}
	}
	
	public function send(){
		$this->Mail->send();
	}
	
	public function showConfigs(){
		pr($this->Mail);
	}
	
	public function viewVars($arr=array()){
		$this->Mail->viewVars($arr);
	}
}

?>
