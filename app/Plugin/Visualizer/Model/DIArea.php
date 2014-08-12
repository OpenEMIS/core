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

class DIArea extends VisualizerAppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_area_en';
	public $actsAs = array('Tree'=> array('parent_id' => 'Area_Parent_NId'));
	
	public function getAreaTreaFullPath($data){
		/*
		 * ============ Things to take note. ===============
		 * Make sure the table has a column called [Id].
		 * This [id] is a duplication from [Area_NId].
		 * Basically ONLY Tree behaviour is using this [id].
		 * =================================================
		 */
		if(empty($data)){ return false; }
		
		$fullPathData = array();
		
		foreach($data as $obj){
			$path = $this->getPath($obj[$this->alias]['Area_NId']);
			if(!empty($path)){
				$fullPathData[] = $path;
			}
			else{
				$str = 'Parent_Nid was not found for Area_Nid : '.$obj[$this->alias]['Area_NId'];
				$this->log($str, 'debug');
			}
		}
		
		return $fullPathData;
	}
}
