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

App::uses('AppController', 'Controller');
App::uses('AppTask', 'Console/Command/Task');
App::uses('IndicatorComponent', 'Datawarehouse.Controller/Component');

class DwIndTask extends AppTask {
	public $limit = 1000;
	public $fileFP;
	public $tasks = array('Common');
	public $Batch;
	public $Controller;
	public $BatchIndicatorResult;
	
	/****
	 * CSV Starts 
	 */
	
	function initialize() { 
		$this->BatchIndicatorResult = ClassRegistry::init('DataProcessing.BatchIndicatorResult');
    } 
	
	public function genIND($settings){
		$this->IndicatorComponent->export($settings);
	

	}
	
	
}
	
?>
