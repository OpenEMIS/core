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


class DashboardsController extends DashboardsAppController {
	public $institutionSiteId,$institutionSiteAreaId;
	public $uses = array();
    public $helpers = array('Js' => array('Jquery'));
    public $components = array('UserSession', 'Dashboards.QADashboard', 'HighCharts.HighCharts' );
    public $modules = array(
        'InstitutionQA' => 'Dashboards.DashboardInstitutionQA',
    );

	public function beforeFilter() {
		parent::beforeFilter();
		$this->set('modelName', 'Dashboards');
		
		if($this->action == 'dashboardReport'){
			$this->bodyTitle = 'Reports';
			$this->Navigation->addCrumb('Reports', array('controller' => 'Reports', 'action' => 'index', 'plugin' => 'Reports'));
		}
		else if($this->action == 'overview'){
			$this->Navigation->addCrumb('Reports', array('controller' => 'Reports', 'action' => 'index', 'plugin' => 'Reports'));
			$this->Navigation->addCrumb('Dashboards', array('controller' => 'Dashboards', 'action' => 'dashboardReport', 'plugin' => 'Dashboards'));
		}
		else if($this->action == 'InstitutionQA' || $this->action == 'general'){
			if($this->action == 'general'){
				$this->bodyTitle = 'InstitutionSite';
			}
			$this->Navigation->addCrumb('Institutions', array('controller' => 'InstitutionSites', 'action' => 'index'));
		
			if ($this->Session->check('InstitutionSiteId')) {
				$this->institutionSiteId = $this->Session->read('InstitutionSiteId');

				$InstitutionSiteModel = ClassRegistry::init('InstitutionSite');
				$institutionSiteName = $InstitutionSiteModel->field('name', array('InstitutionSite.id' => $this->institutionSiteId));
				$this->institutionSiteAreaId = $InstitutionSiteModel->field('institution_site_area_id', array('InstitutionSite.id' => $this->institutionSiteId));
				$this->bodyTitle = $institutionSiteName;

				$this->Navigation->addCrumb($institutionSiteName, array('controller' => 'InstitutionSites', 'action' => 'view'));
				$this->Navigation->addCrumb('Reports', array('controller' => 'InstitutionReports', 'action' => 'index'));
			} else {
				$this->redirect(array('controller' => 'InstitutionSites', 'action' => 'index'));
			}
		}
		
	}

	public function dashboardReport(){
		$this->Navigation->addCrumb('Dashboards');
		$header = __('Dashboards');
		$this->set('enabled',true);
		$reportType = 'dashboard';
		$Report = ClassRegistry::init('Report');
        $reportData = $Report->find('all',array('conditions'=>array('Report.visible' => 1, 'category'=>$reportType.' Reports'), 'order' => array('Report.order')));
  
        $checkFileExist = array();
        $data = array();
        
        //arrange and sort according to grounp
        foreach($reportData as $k => $val){
            //$pathFile = ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$val['Report']['category']).DS.$val['Report']['module'].DS.str_replace(' ','_',$val['Report']['name']).'.'.$val['Report']['file_type'];
            $module = $val['Report']['module'];
            $category = $val['Report']['category'];
            $name = $val['Report']['name'];
            $val['Report']['file_type'] = ($val['Report']['file_type']=='ind'?'csv':$val['Report']['file_type']);
            $data[$reportType.' Reports'][$module][$name] =  $val['Report']; 
        }
		$controllerName = $this->controller;
        $msg = (isset($_GET['processing']))?'processing':'';
		$this->set(compact('header', 'msg','data', 'controllerName'));
	}
	
	
	public function getHeader($id){
		$header = array();
		$header[4001] = 'Early Childhood Education Quality Assurance';
		
		if (array_key_exists($id, $header)) {
			return __($header[$id]);
		}
		else{
			return '';
		}
	}
	
	public function general() {
		$header = __('Reports - Dashboard');
		$this->Navigation->addCrumb($header);
		$data = array(
			array('name' => 'Quality Assurance',  'model' => NULL,'format' => array('HTML' => 'InstitutionQA'), 'params' => array('HTML' => array(1))),
		);
		
		foreach($data as $i => $obj) {
			$data[$i]['formats'] = $obj['format'];
		}
		
		$this->set(compact('data', 'header'));
    }
	
	public function dashboards( $type) {
		if($type == "HTML"){
			return $this->redirect(array('controller'=> 'Dashboards', 'action' => 'InstitutionQA', 'plugin' => 'Dashboards'));
		}
	}
	
	
    public function overview() {
		$id = empty($this->params['pass'][0])? 0: $this->params['pass'][0]; //Report ID
		
		if($this->request->is('post')){
			$temp_geo_id = $this->request->data['Dashboards']['geo_level_id'];
			$temp_area_id = $this->request->data['Dashboards']['area_level_id'];
			$temp_year_id = $this->request->data['Dashboards']['year_id'];
			return $this->redirect(array('controller' => 'Dashboards', 'action' => 'overview',$id,$temp_geo_id,$temp_area_id,$temp_year_id));
		}
		
		$countryData = $this->QADashboard->getCountry();
		$countryId = $countryData['DIArea']['Area_NId'];
		//$countryName = $countryData['DIArea']['Area_Name'];
		
		$this->Session->write('Dashboard.Overview.CountryId', $countryId);
		//$this->Session->write('Dashboard.Overview.CountryName', $countryName);
		
		$geoLvlId = empty($this->params['pass'][1])? 0: $this->params['pass'][1]; //Country ID/Geo Level Id
		$areaId = empty($this->params['pass'][2])? 0: $this->params['pass'][2]; //Area Id 
		$yearId = empty($this->params['pass'][3])? 0: $this->params['pass'][3]; //year Id 
		
		$Report = ClassRegistry::init('Report');
		$rData = $Report->findById($id);
		
		if(!empty($rData)){
			//redirect
		}
		$crumbTitle = $rData['Report']['name'];
		$header = $this->getHeader($id);
		
		$this->Navigation->addCrumb($crumbTitle);
		$geoLvlOptions = $this->QADashboard->getAreaLevel();
		$geoLvlId = (empty($geoLvlId))? key($geoLvlOptions): $geoLvlId;
		
		$areaLvlOptions = $this->QADashboard->getAreasByLevel($geoLvlId);
		$selectedAreaId = (empty($areaId))? key($areaLvlOptions): $areaId;
		
		$yearsOptions = $this->QADashboard->getYears();
		$yearId = (empty($yearId))? key($yearsOptions): $yearId;

		$tableTitle = $areaLvlOptions[$selectedAreaId];
		$tableTitle .= " ".__('Year')." ".$yearsOptions[$yearId];	
		$QATableData = $this->setupQATableData($selectedAreaId,$yearId);
		
		$displayChartData = array(
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'ATAspectJSON', $selectedAreaId, $yearId)),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'TrendLineJSON',$selectedAreaId, $yearId, $yearsOptions[$yearId])),
		//	'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'AdminBreakdownJSON', $selectedAreaId, $yearId)),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'TechBreakdownJSON', $selectedAreaId, $yearId)),
		//	'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'FDBothBreakdownJSON', $selectedAreaId, $yearId)),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'FDTechAdminBreakdownJSON', $selectedAreaId, $yearId)),
		//	'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'AppointmentJSON', $selectedAreaId, $yearId)),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'OwnershipJSON', $selectedAreaId, $yearId)),
		//	'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'LocalityJSON', $selectedAreaId, $yearId)),
		//	'break',
		);
		
		
		$this->set(compact('header', 'geoLvlId', 'areaId', /*'FDId',*/'yearId', 'geoLvlOptions', 'areaLvlOptions', 'FDLvlOptions', 'yearsOptions', /*'totalKGInfo',*/ 'displayChartData', 'QATableData', 'tableTitle'));
		
    }
	
	public function dashboardsAjaxGetArea($firstBlank = false){
		$this->autoRender = false;
		if($this->request->is('ajax')){
			$levelId = $this->request->query['levelId'];
			$prependBlank = !empty($this->request->query['prependBlank'])? $this->request->query['prependBlank']:false;
			$data = $this->QADashboard->getAreasByLevel($levelId);
			
			$listStr = '';
			if($prependBlank !== false){
				echo '<option value="0">All</option>';
			}
			foreach($data as $key => $item){
				echo '<option value="'.$key.'">'.$item.'</option>';
			}
		}
	}
	
	public function ATAspectJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		
		$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		//$countryName = $this->Session->read('Dashboard.Overview.CountryName');
		
		$areaRawData = $this->QADashboard->getAreaById(array($countryId, $selectedAreaId));
		$timePeriodRawData = $this->QADashboard->getYears('all', array('id'=> $yearId));
		
		$_options['indicatorGId'] = $this->QADashboard->indicators['QA_AdminTechBoth_Score'];
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));
		
		$this->HighCharts->plotBy = 'indicator';
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Administrative and Technical Aspects'));
		//$data = array_merge($data, $this->HighCharts->customGenerateCategory('column'));
		
		$data = array_merge($data, $this->HighCharts->customGetGenericChartData('column',$rawData));
		
		return  json_encode($data, JSON_NUMERIC_CHECK);
	}
	
	public function TrendLineJSON($selectedAreaId, $yearId, $year){
		$this->autoRender = false;
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Trends'));
		$areaRawData = $this->QADashboard->getAreaById(array($selectedAreaId));
		$timePeriodRawData = $this->QADashboard->getYearRange($yearId,10);
		
		$_options['indicatorGId'] = $this->QADashboard->indicators['QA_AdminTechBoth_Score'];
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));

	//	$this->HighCharts->plotBy = 'indicator';
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Administrative and Technical Aspects', 'chartType' => 'line'));
	//	$data = array_merge($data, $this->HighCharts->customGenerateCategory('line'));
		$data = array_merge($data, $this->HighCharts->customGetLineChartData($rawData));
		
		return  json_encode($data, JSON_NUMERIC_CHECK);
	}
	
	public function AdminBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		$this->autoRender = false;
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		//$countryName = $this->Session->read('Dashboard.Overview.CountryName');
		
		$areaRawData = $this->QADashboard->getAreaById(array($countryId, $selectedAreaId));
		$timePeriodRawData = $this->QADashboard->getYears('all', array('id'=> $yearId));
		
		$_options['indicatorGId'] = $this->QADashboard->indicators['QA_AdminBreakdown_Score'];
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));
	
		
		$this->HighCharts->plotBy = 'indicator';
		$this->HighCharts->rotateLabel = true;
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Administrative and Technical Aspects'));
	//	$data = array_merge($data, $this->HighCharts->customGenerateCategory('column'));
		
		$data = array_merge($data, $this->HighCharts->customGetGenericChartData('column',$rawData));
		
		return  json_encode($data, JSON_NUMERIC_CHECK);
	}
	
	public function TechBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		//$countryName = $this->Session->read('Dashboard.Overview.CountryName');
		
		$areaRawData = $this->QADashboard->getAreaById(array($countryId, $selectedAreaId));
		$timePeriodRawData = $this->QADashboard->getYears('all', array('id'=> $yearId));
		
		$_options['indicatorGId'] = $this->QADashboard->indicators['QA_TechBreakdown_Score'];
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));
	
		
		$this->HighCharts->plotBy = 'indicator';
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Administrative and Technical Aspects'));
	//	$data = array_merge($data, $this->HighCharts->customGenerateCategory('column'));
		
		$data = array_merge($data, $this->HighCharts->customGetGenericChartData('column',$rawData));
		
		return  json_encode($data, JSON_NUMERIC_CHECK);
	}
	
	public function FDBothBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		//$countryName = $this->Session->read('Dashboard.Overview.CountryName');
		
		$areaRawData = $this->QADashboard->getAreaChildLevel($selectedAreaId, false);//$this->QADashboard->getAreaById(array($countryId, $selectedAreaId));
		//$childAreaOptions = $this->QADashboard->getAreaChildLevel($selectedAreaId, false);
	//	pr($areaRawData);
		//pr($childAreaOptions);
		$timePeriodRawData = $this->QADashboard->getYears('all', array('id'=> $yearId));
		
		$_options['indicatorGId'] = $this->QADashboard->indicators['QA_AdminTechBoth_Score']['Both'];
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));
	
		
		$this->HighCharts->plotBy = 'indicator';
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Distribution of Both Aspects'));
	//	$data = array_merge($data, $this->HighCharts->customGenerateCategory('column'));
		
		$data = array_merge($data, $this->HighCharts->customGetGenericChartData('column',$rawData));
		
		return  json_encode($data, JSON_NUMERIC_CHECK);
		
	}
	
	
	public function FDTechAdminBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$title = "Scatterplot of Administrative and Technical and Aspects";
		$areaRawData =  $this->QADashboard->getAllAreaChildByLevel($selectedAreaId, 5, false);
		
		$timePeriodRawData = $this->QADashboard->getYears('all', array('id'=> $yearId));
		
		$_options['indicatorGId'] = array($this->QADashboard->indicators['QA_AdminTechBoth_Score']['Admin'],$this->QADashboard->indicators['QA_AdminTechBoth_Score']['Tech']);
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));
		
		$this->HighCharts->plotBy = 'indicator';
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Scatterplot of Administrative and Technical and Aspects', 'chartType' => 'scatter'));
		$data = array_merge($data, $this->HighCharts->setupCustomTextChartCategory());
		$data = array_merge($data, $this->HighCharts->customGetScatterChartData($rawData));
		
		return  json_encode($data, JSON_NUMERIC_CHECK);
	}
	
	public function AppointmentJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		$countryName = $this->Session->read('Dashboard.Overview.CountryName');
		
		$areaRawData = $this->QADashboard->getAreaById(array( $selectedAreaId));
		$timePeriodRawData = $this->QADashboard->getYears('all', array('id'=> $yearId));
		
		$_options['indicatorGId'] = $this->QADashboard->indicators['QA_AdminTechBoth_Score'];
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Permanent'],$this->QADashboard->indicators['SubgrpVal']['Contract']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));
	//pr($rawData);
		
	//	$this->HighCharts->plotBy = 'indicator';
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Appointment'));
	//	$data = array_merge($data, $this->HighCharts->customGenerateCategory('column'));
		
		$data = array_merge($data, $this->HighCharts->customGetGenericChartData('column',$rawData));
		
		return  json_encode($data, JSON_NUMERIC_CHECK);
		
	}

	public function OwnershipJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		
		$areaRawData = $this->QADashboard->getAreaById(array( $selectedAreaId));
		$timePeriodRawData = $this->QADashboard->getYears('all', array('id'=> $yearId));
		
		$_options['indicatorGId'] = $this->QADashboard->indicators['QA_AdminTechBoth_Score'];
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Owned'],$this->QADashboard->indicators['SubgrpVal']['Rented'],$this->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));
	//pr($rawData);
		
	//	$this->HighCharts->plotBy = 'dimension';
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Ownership'));
	//	$data = array_merge($data, $this->HighCharts->customGenerateCategory('column'));
		
		$data = array_merge($data, $this->HighCharts->customGetGenericChartData('column',$rawData));
		
		return  json_encode($data, JSON_NUMERIC_CHECK);
		
	}
	
	public function LocalityJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		
		$areaRawData = $this->QADashboard->getAreaById(array( $selectedAreaId));
		$timePeriodRawData = $this->QADashboard->getYears('all', array('id'=> $yearId));
		
		$_options['indicatorGId'] = $this->QADashboard->indicators['QA_AdminTechBoth_Score'];
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Urban'],$this->QADashboard->indicators['SubgrpVal']['Rural'],$this->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));
	//pr($rawData);
		
	//	$this->HighCharts->plotBy = 'dimension';
		$data = $this->HighCharts->customGenerateHeader(array('caption' => 'Locality'));
	//	$data = array_merge($data, $this->HighCharts->customGenerateCategory('column'));
		
		$data = array_merge($data, $this->HighCharts->customGetGenericChartData('column',$rawData));
		
		return  json_encode($data, JSON_NUMERIC_CHECK);
		
	}
	//Table Setup
	
	public function setupQATableData($areaId,$yearId){
		$areaRawData = $this->QADashboard->getAreaChildLevel($areaId, false);
		if(empty($areaRawData)){
			$areaRawData = $this->QADashboard->getAreaById(array($areaId));
		}
		//
		$timePeriodRawData = $this->QADashboard->getYears('all', array('id'=> $yearId));
		
		$_options['indicatorGId'] = $this->QADashboard->indicators['QA_AdminTechBoth_Score'];
		$_options['unitGId'] = array($this->QADashboard->indicators['Unit']['Percent']/*,$this->QADashboard->indicators['Unit']['Number']*/);
		$_options['subgroupValGId'] = array($this->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $this->QADashboard->getIUSByIndividualGId($_options);
		
		$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
		
		$timeperiodIds = $this->HighCharts->getTimeperiodIds();
		$areaIds = $this->HighCharts->getAreaIds();
		$sourceID = $this->QADashboard->getLatestSourceID($this->HighCharts->selectedIUS,$timeperiodIds);
		
		$rawData = $this->QADashboard->getDashboardRawData(array('IUS' => $this->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));
		
		$tableHeaders = array(__('Name'), __('Administrative')." (%)", __('Technical')." (%)", __('Both')." (%)");

		$tableData = array();
		$structureTemplate = array();
		foreach($areaRawData as $aKey => $aObj){
			foreach($this->HighCharts->selectedIndicator as $indObj){
				$uniqId = $aObj['DIArea']['Area_Nid'];
				$structureTemplate[$uniqId]['name'] =  sprintf('%s - %s', $aObj['DIArea']['Area_ID'],$aObj['DIArea']['Area_Name']);
				$structureTemplate[$uniqId][$indObj] = 0;
			}
		}
		
		$filterData = array();
		foreach($rawData as $obj){
			$uniqId = $obj['DIArea']['Area_NId'];
			$filterData[$uniqId][$obj['Indicator']['Indicator_Name']] = $obj['DIData']['Data_Value'];
		}
		
		foreach($structureTemplate as $key => $obj){
			foreach ($obj as $vKey => $vobj){
				
				if(!empty($filterData[$key][$vKey])){
					$tableData[$key][$vKey] = $filterData[$key][$vKey];
				}
				else{
					$tableData[$key][$vKey] = $structureTemplate[$key][$vKey];
				}
			}
		}
		unset($filterData);
		unset($structureTemplate);
		$tableData = array_values($tableData);
		
		return array('tableHeaders' => $tableHeaders, 'tableData' => $tableData);
	}
	
	public function addReportDate($csv_file){
        $footer = array("Report Generated: " . date("Y-m-d H:i:s"));
        fputcsv($csv_file, array(), ',', '"');
        fputcsv($csv_file, $footer, ',', '"');
    }
	
	public function genCSV($areaId, $yearId) {
        $this->autoRender = false;
		$data = $this->setupQATableData($areaId, $yearId);
		
		$year = $this->QADashboard->getYear($yearId);
		$areaName = $this->QADashboard->getAreaName($areaId);
		$fileName = 'QA_'.$year."_".$areaName."_". date("Y.m.d");
		$downloadedFile = $fileName . '.csv';
		
		ini_set('max_execution_time', 600);
		
		$csv_file = fopen('php://output', 'w');
        header('Content-type: application/csv');
        header('Content-Disposition: attachment; filename="' . $downloadedFile . '"');
		
		fputcsv($csv_file, $data['tableHeaders'], ',', '"');
		
		foreach ($data['tableData'] as $dataRow) {
			$row = array();
			foreach ($dataRow as $col) {
				$row[] = $col;
			}
			fputcsv($csv_file, $row, ',', '"');
		}
		
		$this->addReportDate($csv_file);
        fclose($csv_file);
    }
}
