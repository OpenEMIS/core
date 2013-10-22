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

class CustomFieldComponent extends Component {
	public $components = array('Session');
	private $customField;
	private $customFieldOption;
	private $customValue;
	private $yr;
	
	
	public function __construct(\ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		try {
			$this->customField = ClassRegistry::init($settings['CustomField']);
			$this->customFieldOption = ClassRegistry::init($settings['CustomFieldOption']);
			$this->customValue = ClassRegistry::init($settings['CustomValue']);
			$this->yr = ClassRegistry::init($settings['Year']);
                        $this->institutionSiteTypeId = @$settings['institutionSiteTypeId'];
			
		} catch(MissingModelException $e) {
			// Model not found!
			echo $e->getMessage();
		}
		
	}
	
	
	
	public function getCustomValuebyCond($findtype='all',$arrConds = array()){
		return $this->customValue->find($findtype,$arrConds);
	}
	
	public function getCustomFields(){
		$this->customField->bindModel(array('hasMany'=>array($this->settings['CustomFieldOption'] => array('order'=>'order'))));
                $arrCond = array('visible'=> '1');
                if($this->institutionSiteTypeId >= 0 && isset($this->institutionSiteTypeId)) {
                    $arrCond = array_merge($arrCond,array($this->settings['CustomField'].'.institution_site_type_id' => $this->institutionSiteTypeId));
                }
                
		$arr = $this->customField->find('all',array('conditions' => $arrCond,'order'=>'order'));
		
		return ($arr) ? $arr : array();
	}
	
	public function getCustomFieldValues($arrCond){
		$cond = $arrCond;
		
		$this->customValue->bindModel(
			array('belongsTo' => array($this->settings['CustomField']))
		);
		$arr = $this->customValue->find('all',array('conditions'=>$cond));
		$tmp=array();
		foreach($arr as $arrV){
			$tmp[$arrV[$this->settings['CustomField']]['id']][] = $arrV[$this->settings['CustomValue']];
		}
		$arr = $tmp;
		return ($arr) ? $arr : array();
	}
	
	public function getCustomFieldView($arrCond){
		$dataFields = $this->getCustomFields();
		$dataValues = $this->getCustomFieldValues($arrCond);
		return compact('dataFields','dataValues') ;
		
	}
	
	
	public function saveCustomFields($data,$cond = array()){
		$arrFields = array('textbox','dropdown','checkbox','textarea');
		/**
		 * Note to Preserve the Primary Key to avoid exhausting the max PK limit
		 */
		foreach($arrFields as $fieldVal){
			if(!isset($data[$this->settings['CustomValue']][$fieldVal]))  continue;
			foreach($data[$this->settings['CustomValue']][$fieldVal] as $key => $val){
				$custFieldFKid = Inflector::underscore($this->settings['CustomField']).'_id';
				$cond = array_merge($cond, array( $custFieldFKid => $key));//attached the customfield id
				if($fieldVal == "checkbox"){
					$arrCustomValues = $this->customValue->find('list',array('fields'=>array('value'),'conditions' => $cond));
						$tmp = array();
						if(count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
						foreach($arrCustomValues as $pk => $intVal){
							if(!in_array($intVal, $val['value'])){
							   $this->customValue->delete($pk);
							}
						}
						$ctr = 0;
						if(count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
						foreach($val['value'] as $intVal){
							if(!in_array($intVal, $arrCustomValues)){
								$this->customValue->create();
								$param = array_merge($cond,array('value'=>$val['value'][$ctr]));
								$this->customValue->save($param);
								unset($arrCustomValues[$ctr]);
							}
							 $ctr++;
						}
				}else{ // if editing reuse the Primary KEY; so just update the record
					$x = $this->customValue->find('first',array('fields'=>array('id','value'), 'conditions' => $cond));
					$this->customValue->create();
					if($x) $this->customValue->id = $x[$this->settings['CustomValue']]['id'];
					$param = array_merge($cond,array('value'=>$val['value']));
					$this->customValue->save($param);
					
				}
			}
			
		}
	}
        
        
        public function Search(){
            
            
        }
}
?>