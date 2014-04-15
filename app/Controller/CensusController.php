<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundationclas
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppController', 'Controller'); 

class CensusController extends AppController {
	public $institutionSiteId;
	public $source_type=array(
						"dataentry" => 0,
						"external" => 1,
						"internal" => 3,
						"estimate" => 2
						);

	public $uses = array(
		'Institution',
		'InstitutionSite',
		'InstitutionSiteProgramme',
		'EducationCycle',
		'EducationGrade',
		'SchoolYear',
		'CensusVerification',
		'CensusStudent',
		'CensusAttendance',
		'CensusAssessment',
		'CensusBehaviour',
		'CensusGraduate',
		'CensusClass',
		'CensusShift',
		'CensusTextbook',
		'CensusStaff',
		'CensusTeacher',
		'CensusTeacherFte',
		'CensusTeacherTraining',
		'CensusBuildings',
		'CensusFinance',
		'CensusBuilding',
		'CensusResource',
		'CensusFurniture',
		'CensusSanitation',
		'CensusEnergy',   
		'CensusRoom',
		'CensusWater',
		'CensusGrid',
		'CensusGridValue',
		'CensusGridXCategory',
		'CensusGridYCategory',
		'CensusCustomField',
		'CensusCustomValue',
		'FinanceSource',
		'FinanceNature',
		'FinanceType',
		'FinanceCategory',
		'InfrastructureCategory',
		'InfrastructureBuilding',
		'InfrastructureResource',
		'InfrastructureFurniture',
		'InfrastructureEnergy',   
		'InfrastructureRoom',
		'InfrastructureSanitation',
		'InfrastructureWater',
		'InfrastructureStatus',
		'InfrastructureMaterial',
		'Students.StudentCategory',
                'EducationProgramme'
	);
        
        public $modules = array(
            'enrolment' => 'CensusStudent',
            'teachers' => 'CensusTeacher',
            'staff' => 'CensusStaff',
            'classes' => 'CensusClass',
            'shifts' => 'CensusShift',
            'graduates' => 'CensusGraduate',
            'assessments' => 'CensusAssessment',
            'behaviour' => 'CensusBehaviour',
            'textbooks' => 'CensusTextbook',
            'finances' => 'CensusFinance'
        );
	
	public function beforeFilter() {
		parent::beforeFilter();
		if($this->Session->check('InstitutionId')) {
			if($this->Session->check('InstitutionSiteId')) {
				$institutionId = $this->Session->read('InstitutionId');
				$institutionName = $this->Institution->field('name', array('Institution.id' => $institutionId));
				$this->institutionSiteId = $this->Session->read('InstitutionSiteId');
				$institutionSiteName = $this->InstitutionSite->field('name', array('InstitutionSite.id' => $this->institutionSiteId));
				
				$this->bodyTitle = $institutionName . ' - ' . $institutionSiteName;
				$this->Navigation->addCrumb('Institutions', array('controller' => 'Institutions', 'action' => 'index'));
				$this->Navigation->addCrumb($institutionName, array('controller' => 'Institutions', 'action' => 'view'));
				$this->Navigation->addCrumb($institutionSiteName, array('controller' => 'InstitutionSites', 'action' => 'view'));
			} else {
				$this->redirect(array('controller' => 'Institutions', 'action' => 'listSites'));
			}
		} else {
			$this->redirect(array('controller' => 'Institutions', 'action' => 'index'));
		}
		$this->set('source_type', $this->source_type);
	}
	
	public function getAvailableYearId($yearList) {
		$yearId = 0;
		if(isset($this->params['pass'][0])) {
			$yearId = $this->params['pass'][0];
			if(!array_key_exists($yearId, $yearList)) {
				$yearId = key($yearList);
			}
		} else {
			$yearId = key($yearList);
		}
		return $yearId;
	}
	
	public function loadGradeList() {
		$this->autoRender = false;
		$programmeId = $this->params->query['programmeId'];
		$conditions = array('EducationGrade.education_programme_id' => $programmeId, 'EducationGrade.visible' => 1);
		$list = $this->EducationGrade->findList(array('conditions' => $conditions));
		return json_encode($list);
	}
	
	public function verifications() {
		$this->Navigation->addCrumb('Verifications');
		$institutionSiteId = $this->Session->read('InstitutionSiteId');
		$data = $this->CensusVerification->getVerifications($institutionSiteId);
		$verifiedYears = $this->SchoolYear->getYearListForVerification($institutionSiteId);
		$unverifiedYears = $this->SchoolYear->getYearListForVerification($institutionSiteId, false);
		$this->set('data', $data);
		$this->set('allowVerify', count($verifiedYears) > 0);
		$this->set('allowUnverify', count($unverifiedYears) > 0);
	}
	
	public function verifies() {
		if($this->request->is('ajax')) {
			$institutionSiteId = $this->Session->read('InstitutionSiteId');
			if($this->request->is('get')) {
				$this->layout = 'ajax';
				$status = $this->params['pass'][0];
				$msg = $this->Utility->getMessage($status==1 ? 'CENSUS_VERIFY' : 'CENSUS_UNVERIFY');
				$label = '';
				$yearOptions = array();
				
				if($status==1) {
					$label = __('Year to verify');
					$yearOptions = $this->SchoolYear->getYearListForVerification($institutionSiteId);
				} else {
					$label = __('Year to unverify');
					$yearOptions = $this->SchoolYear->getYearListForVerification($institutionSiteId, false);
				}
				$this->set('msg', $msg);
				$this->set('label', $label);
				$this->set('yearOptions', $yearOptions);
			} else { // post
				$this->autoRender = false;
				$status = $this->params['pass'][0];
				$data = array('institution_site_id' => $institutionSiteId, 'status' => $status);
				$data = array_merge($data, $this->data);
				$this->CensusVerification->saveEntry($data);
				return true;
			}
		}
	}
	
	public function attendance() {
		$this->Navigation->addCrumb('Attendance');
		
		$yearList = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearList);
		$programmes = $this->InstitutionSiteProgramme->getSiteProgrammes($this->institutionSiteId, $selectedYear);
		$schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $selectedYear));
		
		$data = array();
		if(empty($programmes)) {
			$this->Utility->alert($this->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
		} else {
			foreach($programmes as $obj) {
				$programmeId = $obj['education_programme_id'];
				$list = $this->CensusAttendance->getCensusData($this->institutionSiteId, $selectedYear, $programmeId);
				$data[$programmeId] = array(
					'name' => $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'],
					'data' => $list
				);
			}
		}
		$this->set('data', $data);
		$this->set('selectedYear', $selectedYear);
		$this->set('years', $yearList);
		$this->set('schoolDays', $schoolDays);
		$this->set('isEditable', $this->CensusVerification->isEditable($this->institutionSiteId, $selectedYear));
	}
	
	public function attendanceEdit() {
		if($this->request->is('get')) {
			$this->Navigation->addCrumb('Edit Attendance');
			
			$yearList = $this->SchoolYear->getYearList();
			$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearList);
			$programmes = $this->InstitutionSiteProgramme->getSiteProgrammes($this->institutionSiteId, $selectedYear);
			$schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $selectedYear));
			
			$data = array();
			$editable = $this->CensusVerification->isEditable($this->institutionSiteId, $selectedYear);
			if(!$editable) {
				$this->redirect(array('action' => 'attendance', $selectedYear));
			} else {
				if(empty($programmes)) {
					$this->Utility->alert($this->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
				} else {
					foreach($programmes as $obj) {
						$programmeId = $obj['education_programme_id'];
						$list = $this->CensusAttendance->getCensusData($this->institutionSiteId, $selectedYear, $programmeId);
						$data[$programmeId] = array(
							'name' => $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'],
							'data' => $list
						);
					}
				}
				$this->set('data', $data);
				$this->set('selectedYear', $selectedYear);
				$this->set('years', $yearList);
				$this->set('schoolDays', $schoolDays);
			}
		} else {
			$data = $this->data['CensusAttendance'];
			$yearId = $data['school_year_id'];
			$this->CensusAttendance->saveCensusData($data, $this->institutionSiteId);
			$this->Utility->alert($this->Utility->getMessage('CENSUS_UPDATED'));
			$this->redirect(array('controller' => 'Census', 'action' => 'attendance', $yearId));
		}
	}
	
	public function infrastructureByMaterial($id,$yr,$is_edit = false,$model,$gender = 'male'){
		$cat  = Inflector::pluralize($model);
		$cat = ($cat == 'Sanitations'?'Sanitation':$cat);
		$data = $this->InfrastructureCategory->find('list',array('conditions'=>array('InfrastructureCategory.visible'=>1,'InfrastructureCategory.name'=>  $cat)));
		
		foreach($data as $key => $v){
			$method = strtolower($cat);
			
			$arrCensusInfra[$cat] = $this->{$method}($yr,$id);
			
			$status =  $this->InfrastructureStatus->find('list',array('conditions'=>array('InfrastructureStatus.infrastructure_category_id'=>$key,'InfrastructureStatus.visible'=>1)));
			//pr($arrCensusInfra[$val]);die;
			$arrCensusInfra[$cat]['status'] = $status;

		}
		
		$this->set('data',$arrCensusInfra);
		$this->set('is_edit',$is_edit);
		$this->set('material_id',$id);
		$this->set('gender',$gender);
	}
	
	public function infrastructure() {
		$this->Navigation->addCrumb('Infrastructure');
		
		$yearList = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearList);
		$arrCensusInfra = array();
		
		$data = $this->InfrastructureCategory->find('list',array('conditions'=>array('InfrastructureCategory.visible'=>1),'order'=>'InfrastructureCategory.order'));
		
		foreach($data as $key => $val){

			if(method_exists($this, $val)){

				$arrCensusInfra[$val] = $this->$val($selectedYear);
				$status =  $this->InfrastructureStatus->find('list',array('conditions'=>array('InfrastructureStatus.infrastructure_category_id'=>$key,'InfrastructureStatus.visible'=>1)));
				//pr($arrCensusInfra[$val]);die;
				$arrCensusInfra[$val]['status'] = $status;
			}
		}
		//pr($arrCensusInfra);
		$this->set('data',$arrCensusInfra);
		$this->set('selectedYear', $selectedYear);
		$this->set('years', $yearList);
		$this->set('isEditable', $this->CensusVerification->isEditable($this->institutionSiteId, $selectedYear));
	}
        
	public function infrastructureEdit() {
		$this->Navigation->addCrumb('Edit Infrastructure');
		$data = $this->InfrastructureCategory->find('list',array('conditions'=>array('InfrastructureCategory.visible'=>1),'order'=>'InfrastructureCategory.order'));
                
		if($this->request->is('post')) {
			//pr($this->request->data);die;
			$sanitationGender = $this->request->data['CensusSanitation']['gender'];
			foreach($data as $InfraCategory){
				
				 $InfraCategory = Inflector::singularize($InfraCategory);
				//echo $InfraCategory.'<br>';
				foreach($this->request->data['Census'.$InfraCategory] as  $k => &$arrVal){
					if(trim($arrVal['value']) == '' && @trim($arrVal['id']) == ''){ 
						unset($this->request->data['Census'.$InfraCategory][$k]);
					}elseif($arrVal['value'] == '' && $arrVal['id'] != ''){//if there's an ID but value was set to blank == delete the record
						$this->{'Census'.$InfraCategory}->delete($arrVal['id']);
						unset($this->request->data['Census'.$InfraCategory][$k]);
					}else{
						if(isset($this->request->data['Census'.$InfraCategory]['material'])){
							$arrVal['infrastructure_material_id'] = $this->request->data['Census'.$InfraCategory]['material'];
							
						}
						if($InfraCategory == 'Sanitation') {
							$arrVal[$sanitationGender] = $arrVal['value'];  
							
							
						}
						unset($this->request->data['Census'.$InfraCategory]['gender']);
						unset($this->request->data['Census'.$InfraCategory]['material']);
						
						$arrVal['school_year_id'] = $this->request->data['CensusInfrastructure']['school_year_id'];
						$arrVal['institution_site_id'] = $this->institutionSiteId;
					}
				}
			   
			   
				if(count($this->request->data['Census'.$InfraCategory])>0){
					/*foreach($this->request->data['Census'.$InfraCategory] as $arrVal){
						$this->{'Census'.$InfraCategory}->create();
						$this->{'Census'.$InfraCategory}->save($arrVal);
					}*/
					//echo 'Census'.$InfraCategory;
					$o = $this->{'Census'.$InfraCategory}->saveAll($this->request->data['Census'.$InfraCategory]);
					
				}
				
			}
		   //pr($this->request->data);die;
		}
		
		$arrCensusInfra = array();
		$yearList = $this->SchoolYear->getAvailableYears();
		$selectedYear = $this->getAvailableYearId($yearList);
		$editable = $this->CensusVerification->isEditable($this->institutionSiteId, $selectedYear);
		if(!$editable) {
			$this->redirect(array('action' => 'infrastructure', $selectedYear));
		} else {
			foreach($data as $key => $val){
				//pr($data);die;
				if(method_exists($this, $val)){
					
					$arrCensusInfra[$val] = $this->$val($selectedYear);
					$status =  $this->InfrastructureStatus->find('list',array(
						'conditions'=>array(
							'InfrastructureStatus.infrastructure_category_id'=>$key,
							'InfrastructureStatus.visible'=>1)
					));
					//pr($arrCensusInfra[$val]);die;
					$arrCensusInfra[$val]['status'] = $status;
				}
			}
			//pr($arrCensusInfra);
			$this->set('data',$arrCensusInfra);
			$this->set('selectedYear', $selectedYear);
			$this->set('years', $yearList);
		}
	}
	
	private function buildings($yr,$materialid = null){
			
		$materials = $this->InfrastructureMaterial->find('list',array('recursive'=>2,'conditions'=>array('InfrastructureMaterial.visible'=>1,'InfrastructureCategory.name'=>'Buildings')));
		
		$materialCondition = array();
		if(!is_null($materialid)){
			$materialCondition = array('CensusBuilding.infrastructure_material_id'=>$materialid);
		}else{
			$materialCondition = array('CensusBuilding.infrastructure_material_id'=>key($materials));
		}
		
		$data =  $this->CensusBuilding->find('all',array('conditions'=>  array_merge(array('CensusBuilding.school_year_id'=>$yr,'CensusBuilding.institution_site_id'=>$this->institutionSiteId),$materialCondition)));
		
		$types =  $this->InfrastructureBuilding->find('list',array('conditions'=>array('InfrastructureBuilding.visible'=>1)));
		$tmp = array();
		foreach($data as $arrV){
		   $tmp[$arrV['CensusBuilding']['infrastructure_building_id']][$arrV['CensusBuilding']['infrastructure_status_id']][$arrV['CensusBuilding']['infrastructure_material_id']] =  $arrV['CensusBuilding'];
		}
		
		$data = $tmp;
		$materials = $this->InfrastructureMaterial->find('list',array('recursive'=>2,'conditions'=>array('InfrastructureMaterial.visible'=>1,'InfrastructureCategory.name'=>'Buildings')));
		
		//pr($materials);
		return $ret = array_merge(array('data'=>$data),array('types'=>$types),array('materials'=>$materials));
	   
	}
	
	
	private function resources($yr){
		$data =  $this->CensusResource->find('all',array('conditions'=>array('CensusResource.school_year_id'=>$yr,'CensusResource.institution_site_id'=>$this->institutionSiteId)));
		$types =  $this->InfrastructureResource->find('list',array('conditions'=>array('InfrastructureResource.visible'=>1)));
		$tmp = array();
		foreach($data as $arrV){
		   $tmp[$arrV['CensusResource']['infrastructure_resource_id']][$arrV['CensusResource']['infrastructure_status_id']] =  $arrV['CensusResource'];
		}
		$data = $tmp;
		return $ret = array_merge(array('data'=>$data),array('types'=>$types));
	   
	}
	
	private function furniture($yr){
		$data =  $this->CensusFurniture->find('all',array('conditions'=>array('CensusFurniture.school_year_id'=>$yr,'CensusFurniture.institution_site_id'=>$this->institutionSiteId)));
		$types =  $this->InfrastructureFurniture->find('list',array('conditions'=>array('InfrastructureFurniture.visible'=>1)));
		$tmp = array();
		foreach($data as $arrV){
		   $tmp[$arrV['CensusFurniture']['infrastructure_furniture_id']][$arrV['CensusFurniture']['infrastructure_status_id']] =  $arrV['CensusFurniture'];
		}
		$data = $tmp;
		return $ret = array_merge(array('data'=>$data),array('types'=>$types));
	   
	}
        
	private function energy($yr){
		$data =  $this->CensusEnergy->find('all',array('conditions'=>array('CensusEnergy.school_year_id'=>$yr,'CensusEnergy.institution_site_id'=>$this->institutionSiteId)));
		$types =  $this->InfrastructureEnergy->find('list',array('conditions'=>array('InfrastructureEnergy.visible'=>1)));
		$tmp = array();
		foreach($data as $arrV){
		   $tmp[$arrV['CensusEnergy']['infrastructure_energy_id']][$arrV['CensusEnergy']['infrastructure_status_id']] =  $arrV['CensusEnergy'];
		}
		$data = $tmp;
		return $ret = array_merge(array('data'=>$data),array('types'=>$types));
	}
	
	private function rooms($yr){
		$data =  $this->CensusRoom->find('all',array('conditions'=>array('CensusRoom.school_year_id'=>$yr,'CensusRoom.institution_site_id'=>$this->institutionSiteId)));
		$types =  $this->InfrastructureRoom->find('list',array('conditions'=>array('InfrastructureRoom.visible'=>1)));
		$tmp = array();
		foreach($data as $arrV){
		   $tmp[$arrV['CensusRoom']['infrastructure_room_id']][$arrV['CensusRoom']['infrastructure_status_id']] =  $arrV['CensusRoom'];
		}
		$data = $tmp;
		return $ret = array_merge(array('data'=>$data),array('types'=>$types));
	   
	}
	
	private function sanitation($yr,$materialid=null){
		$materialCondition = array();
		if(!is_null($materialid)){
			$materialCondition = array('CensusSanitation.infrastructure_material_id'=>$materialid);
		}
		
		$data =  $this->CensusSanitation->find('all',array('conditions'=>  array_merge(array('CensusSanitation.school_year_id'=>$yr,'CensusSanitation.institution_site_id'=>$this->institutionSiteId),$materialCondition)));
		$types =  $this->InfrastructureSanitation->find('list',array('conditions'=>array('InfrastructureSanitation.visible'=>1)));
		$tmp = array();
		foreach($data as $arrV){
		   $tmp[$arrV['CensusSanitation']['infrastructure_sanitation_id']][$arrV['CensusSanitation']['infrastructure_status_id']][$arrV['CensusSanitation']['infrastructure_material_id']] =  $arrV['CensusSanitation'];
		}
		//pr($tmp);die;
		$data = $tmp;
		$materials = $this->InfrastructureMaterial->find('list',array('recursive'=>2,'conditions'=>array('InfrastructureMaterial.visible'=>1,'InfrastructureCategory.name'=>'Sanitation')));
		 $ret = array_merge(array('data'=>$data),array('types'=>$types),array('materials'=>$materials));
		 //pr($ret);die;
		return $ret;
		
	}
	
	private function water($yr){
		$data =  $this->CensusWater->find('all',array('conditions'=>array('CensusWater.school_year_id'=>$yr,'CensusWater.institution_site_id'=>$this->institutionSiteId)));
		$types =  $this->InfrastructureWater->find('list',array('conditions'=>array('InfrastructureWater.visible'=>1)));
		$tmp = array();
		foreach($data as $arrV){
		   $tmp[$arrV['CensusWater']['infrastructure_water_id']][$arrV['CensusWater']['infrastructure_status_id']] =  $arrV['CensusWater'];
		}
		$data = $tmp;
		return $ret = array_merge(array('data'=>$data),array('types'=>$types));
	   
	}
		
	public function getFinanceCatByFinanceType($typeid){
		return $cat = $this->FinanceCategory->find('list',array('conditions'=>array('FinanceCategory.finance_type_id'=>$typeid)));
	}
	
	public function financesDelete($id) {
		if ($this->request->is('get')) {
			throw new MethodNotAllowedException();
		}
		$this->CensusFinance->id = $id;
		$info = $this->CensusFinance->read();
		if ($this->CensusFinance->delete($id)) {
			$message = __('Record Deleted!', true);
		}else{
			$message = __('Error Occured. Please, try again.', true);
		}
		if($this->RequestHandler->isAjax()){
			$this->autoRender = false;
			$this->layout = 'ajax';
			echo json_encode(compact('message'));
		}
	}

	public function financeSource() {
		$this->autoRender = false;
		$data = $this->FinanceSource->find('all',array('conditions'=>array('FinanceSource.visible'=>1)));
		echo json_encode($data);
	}

	public function financeData() {
		$this->autoRender = false;
		$data = $this->FinanceNature->find('all',array('recursive'=>2,'conditions'=>array('FinanceNature.visible'=>1)));
		echo json_encode($data);
	}
        
	public function otherforms(){
		$this->Navigation->addCrumb('Other Forms');
			
		$yearList = $this->SchoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($yearList);
		$arrCensusInfra = array();
		
		$p = $this->InstitutionSite->field('institution_site_type_id', array('InstitutionSite.id' => $this->institutionSiteId));
		$data = $this->CensusGrid->find('all',array('conditions'=>array('CensusGrid.institution_site_type_id'=>array($p,0), 'CensusGrid.visible' => 1), 'order' => array('CensusGrid.institution_site_type_id','CensusGrid.order')));
			
		//pr($data);
		foreach($data as &$arrDataVal){
			$dataAnswer = $this->CensusGridValue->find('all',array('conditions'=>array('CensusGridValue.institution_site_id'=>$this->institutionSiteId,'CensusGridValue.census_grid_id'=>$arrDataVal['CensusGrid']['id'],'CensusGridValue.school_year_id'=>$selectedYear)));
			
			$tmp = array();
			foreach($dataAnswer as $arrV){
			   $tmp[$arrV['CensusGridValue']['census_grid_x_category_id']][$arrV['CensusGridValue']['census_grid_y_category_id']] =  $arrV['CensusGridValue'];
			}
			$dataAnswer = $tmp;
			$arrDataVal['answer'] = $dataAnswer;
			
		}
			
		//pr($data);
		
		/***
		 * CustomFields
		 */
		$site = $this->InstitutionSite->findById($this->institutionSiteId); 
		$datafields = $this->CensusCustomField->find('all',array('conditions'=>array('CensusCustomField.institution_site_type_id'=>array($site['InstitutionSite']['institution_site_type_id'],0)), 'order'=>array('CensusCustomField.institution_site_type_id','CensusCustomField.order')));
		//$datafields = $this->CensusCustomField->find('all',array('conditions'=>array('CensusCustomField.institution_site_type_id'=>$site['InstitutionSite']['institution_site_type_id']), 'order'=>'CensusCustomField.order'));
		//pr($datafields); echo "d2";
		$this->CensusCustomValue->unbindModel(
			array('belongsTo' => array('InstitutionSite'))
		);
		$datavalues = $this->CensusCustomValue->find('all',array('conditions'=>array('CensusCustomValue.institution_site_id'=>$this->institutionSiteId,'CensusCustomValue.school_year_id'=>$selectedYear)));
		$tmp=array();
		foreach($datavalues as $arrV){
			$tmp[$arrV['CensusCustomField']['id']][] = $arrV['CensusCustomValue'];
		}
		$datavalues = $tmp;
		//pr($datafields);
		
		$this->set('datafields',$datafields);
		$this->set('datavalues',$tmp);
		$this->set('data',$data);
		$this->set('selectedYear', $selectedYear);
		$this->set('years', $yearList);
		$this->set('isEditable', $this->CensusVerification->isEditable($this->institutionSiteId, $selectedYear));
	}
	
	public function otherformsEdit(){
		$this->Navigation->addCrumb('Edit Other Forms');
			
		if($this->request->is('post')) {
			//pr($this->request->data);die;
			$schoolYearId = $this->request->data['CensusGridValue']['school_year_id'];
			unset($this->request->data['CensusGridValue']['school_year_id']);
			
			foreach($this->request->data['CensusGridValue'] as  $k => &$arrVal){
				if($arrVal['value'] == '' && $arrVal['id'] == ''){ 
					unset($this->request->data['CensusGridValue'][$k]);
				}elseif($arrVal['value'] == '' && $arrVal['id'] != ''){//if there's an ID but value was set to blank == delete the record
					$this->CensusGridValue->delete($arrVal['id']);
					unset($this->request->data['CensusGridValue'][$k]);
				}else{
					$arrVal['school_year_id'] = $schoolYearId;
					$arrVal['institution_site_id'] = $this->institutionSiteId;
				}
			}
			
			//pr($this->request->data);die;
			if(count($this->request->data['CensusGridValue'])>0){
				
				$this->CensusGridValue->saveAll($this->request->data['CensusGridValue']);
			}
			
			/**
			* Note to Preserve the Primary Key to avoid exhausting the max PK limit
			*/
			
		   $arrFields = array('textbox','dropdown','checkbox','textarea');
		   foreach($arrFields as $fieldVal){
			   if(!isset($this->request->data['CensusCustomValue'][$fieldVal]))  continue;
			   foreach($this->request->data['CensusCustomValue'][$fieldVal] as $key => $val){
				   if($fieldVal == "checkbox"){
					   
					   $arrCustomValues = $this->CensusCustomValue->find('list',array('fields'=>array('value'),'conditions' => array('CensusCustomValue.school_year_id' => $schoolYearId,'CensusCustomValue.institution_site_id' => $this->institutionSiteId,'CensusCustomValue.census_custom_field_id' => $key)));
						
						   $tmp = array();
						   if(count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
						   foreach($arrCustomValues as $pk => $intVal){
							   //pr($val['value']); echo "$intVal";
							   if(!in_array($intVal, $val['value'])){
								   //echo "not in db so remove \n";
								  $this->CensusCustomValue->delete($pk);
							   }
						   }
						   $ctr = 0;
						   if(count($arrCustomValues) < count($val['value'])){ //if answer has greater value than db, insert
							   //pr($arrCustomValues);pr($val['value']);echo $key;die;
							foreach($val['value'] as $intVal){
								//pr($val['value']); echo "$intVal";
								if(!in_array($intVal, $arrCustomValues)){
									$this->CensusCustomValue->create();
									$arrV['census_custom_field_id']  = $key;
									$arrV['value']  = $val['value'][$ctr];
									$arrV['school_year_id'] = $schoolYearId;
									$arrV['institution_site_id']  = $this->institutionSiteId;
									$this->CensusCustomValue->save($arrV);
									unset($arrCustomValues[$ctr]);
								}
								 $ctr++;
							}
						   }
				   }else{ // if editing reuse the Primary KEY; so just update the record
					   
					   
					   $x = $this->CensusCustomValue->find('first',array('fields'=>array('id','value'),'conditions' => array('CensusCustomValue.school_year_id' => $schoolYearId,'CensusCustomValue.institution_site_id' => $this->institutionSiteId,'CensusCustomValue.census_custom_field_id' => $key)));
					  
					   
					   $this->CensusCustomValue->create();
					   if($x) $this->CensusCustomValue->id = $x['CensusCustomValue']['id'];
					   $arrV['census_custom_field_id']  = $key;
					   $arrV['value']  = $val['value'];
					   $arrV['school_year_id'] = $schoolYearId;
					   $arrV['institution_site_id']  = $this->institutionSiteId;
					   
					   $this->CensusCustomValue->save($arrV);
					   
				   }
			   }
		   }
		   $this->redirect(array('action' => 'otherforms'));
		}
		
		$arrCensusInfra = array();
		$yearList = $this->SchoolYear->getAvailableYears();
		$selectedYear = $this->getAvailableYearId($yearList);
		$editable = $this->CensusVerification->isEditable($this->institutionSiteId, $selectedYear);
		if(!$editable) {
			$this->redirect(array('action' => 'otherforms', $selectedYear));
		} else {
			$p = $this->InstitutionSite->field('institution_site_type_id', array('InstitutionSite.id' => $this->institutionSiteId));
                        $data = $this->CensusGrid->find('all',array('conditions'=>array('CensusGrid.institution_site_type_id'=>array($p,0), 'CensusGrid.visible' => 1), 'order' => array('CensusGrid.institution_site_type_id','CensusGrid.order')));
			//$data = $this->CensusGrid->find('all',array('conditions'=>array('CensusGrid.institution_site_type_id'=>$p, 'CensusGrid.visible' => 1), 'order' => 'CensusGrid.order'));
			
			foreach($data as &$arrDataVal){
				$dataAnswer = $this->CensusGridValue->find('all',array('conditions'=>array('CensusGridValue.institution_site_id'=>$this->institutionSiteId,'CensusGridValue.census_grid_id'=>$arrDataVal['CensusGrid']['id'],'CensusGridValue.school_year_id'=>$selectedYear)));
				
				$tmp = array();
				foreach($dataAnswer as $arrV){
				   $tmp[$arrV['CensusGridValue']['census_grid_x_category_id']][$arrV['CensusGridValue']['census_grid_y_category_id']] =  $arrV['CensusGridValue'];
				}
				$dataAnswer = $tmp;
				$arrDataVal['answer'] = $dataAnswer;
				
			}
				
			/***
			 * CustomFields
			 */
			$site = $this->InstitutionSite->findById($this->institutionSiteId); 
			//$data = $this->CensusGrid->find('all',array('conditions'=>array('CensusGrid.institution_site_type_id'=>array($p,0), 'CensusGrid.visible' => 1), 'order' => array('CensusGrid.institution_site_type_id','CensusGrid.order')));
			//$datafields = $this->CensusCustomField->find('all',array('conditions'=>array('CensusCustomField.institution_site_type_id'=>$site['InstitutionSite']['institution_site_type_id'])));
                        $datafields = $this->CensusCustomField->find('all',array('conditions'=>array('CensusCustomField.institution_site_type_id'=>array($site['InstitutionSite']['institution_site_type_id'],0)), 'order' => array('CensusCustomField.institution_site_type_id','CensusCustomField.order')));
			//pr($datafields); echo "d2";
			$this->CensusCustomValue->unbindModel(
				array('belongsTo' => array('InstitutionSite'))
			);
			$datavalues = $this->CensusCustomValue->find('all',array('conditions'=>array('CensusCustomValue.institution_site_id'=>$this->institutionSiteId,'CensusCustomValue.school_year_id'=>$selectedYear)));
			$tmp=array();
			foreach($datavalues as $arrV){
				$tmp[$arrV['CensusCustomField']['id']][] = $arrV['CensusCustomValue'];
			}
			$datavalues = $tmp;
			
			//pr($datafields);
				
			$this->set('datafields',$datafields);
			$this->set('datavalues',$tmp);
			$this->set('data',$data);
			$this->set('selectedYear', $selectedYear);
			$this->set('years', $yearList);
		}
	}
}
