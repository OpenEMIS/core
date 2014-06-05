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

App::uses('AppHelper', 'View/Helper');
App::uses('String', 'Utility');
App::uses('Workflow', 'Controller/Component');

class TrainingUtilityHelper extends AppHelper {
	public function ellipsis($string, $length = '30') {
		return String::truncate($string, $length, array('ellipsis' => '...', 'exact' => false));
	}

	public function getTrainingStatus($module, $id, $status, $value) {
		$workflow = new WorkflowComponent(new ComponentCollection);

		if($value==3){
			if($module=='TrainingCourse'){
				$status='Accredited';
			}
			if($module=='TrainingSession'){
				$status='Registered';
			}
			if($module=='TrainingSessionResult'){
				$status='Posted';
			}
			if($module=='StaffTrainingNeed'){
				$status='Approved';
			}
			if($module=='StaffTrainingSelfStudy'){
				$status='Accredited';
			}
		}else if($value==2){
			$newStatus = $workflow->getWorkflowStatus($module,$id);
			$status = !empty($newStatus) ? $newStatus : $status;
		}

		return $status;
	}




}