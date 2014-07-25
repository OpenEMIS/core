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
    public $components = array('UserSession', 'Dashboards.QADashboard' );
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
				$this->Navigation->addCrumb('Reports', array('controller' => 'InstitutionReports', 'action' => 'index', 'plugin' => false));
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
			//$temp_fd_id = $this->request->data['Dashboards']['fd_level_id'];
			$temp_year_id = $this->request->data['Dashboards']['year_id'];
			return $this->redirect(array('controller' => 'Dashboards', 'action' => 'overview',$id,$temp_geo_id,$temp_area_id,/*$temp_fd_id,*/$temp_year_id));
		}
		
		$countryData = $this->QADashboard->getCountry();
		$countryId = $countryData['JORArea']['Area_NId'];
		$countryName = $countryData['JORArea']['Area_Name'];
		
		$this->Session->write('Dashboard.Overview.CountryId', $countryId);
		$this->Session->write('Dashboard.Overview.CountryName', $countryName);
		
		$geoLvlId = empty($this->params['pass'][1])? 0: $this->params['pass'][1]; //Country ID/Geo Level Id
		$areaId = empty($this->params['pass'][2])? 0: $this->params['pass'][2]; //Area Id 
		//$FDId = empty($this->params['pass'][3])? 0: $this->params['pass'][3]; //FD Id 
		$yearId = empty($this->params['pass'][3])? 0: $this->params['pass'][3]; //year Id 
		
		$Report = ClassRegistry::init('Report');
		$rData = $Report->findById($id);
		
		if(!empty($rData)){
			//redirect
		}
		$crumbTitle = $rData['Report']['name'];
		$header = $this->getHeader($id);
		
		$this->Navigation->addCrumb($crumbTitle);
		$geoLvlOptions = $this->QADashboard->getAreaLevel(5);
		$geoLvlId = (empty($geoLvlId))? key($geoLvlOptions): $geoLvlId;
		
		$areaLvlOptions = $this->QADashboard->getAreasByLevel($geoLvlId);
		$areaId = (empty($areaId))? key($areaLvlOptions): $areaId;
		$FDId = 0;
		/*$FDLvlOptions = $this->QADashboard->getAreaChildLevel($areaId);
		$FDLvlOptions[0] = 'ALL';
		ksort($FDLvlOptions);
		$FDId = (empty($FDId))? key($FDLvlOptions): $FDId;*/
		
		$yearsOptions = $this->QADashboard->getYears();
		$yearId = (empty($yearId))? key($yearsOptions): $yearId;
		
		$selectedAreaId = !empty($FDId)?$FDId :$areaId;

		
		//$this->localityJSON($selectedAreaId, $yearId);
		//pr($areaLvlOptions);
		//$tableTitle = (empty($FDId)?$areaLvlOptions[$selectedAreaId]:$FDLvlOptions[$selectedAreaId]);
		$tableTitle = $areaLvlOptions[$selectedAreaId];
		$tableTitle .= " ".__('Year')." ".$yearsOptions[$yearId];	
		$QATableData = $this->setupQATableData($areaId,$yearId);
		
		//setup chart data
		$displayChartData = array(
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'ATAspectJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'TrendLineJSON',$selectedAreaId, $yearId, $yearsOptions[$yearId]), 'swfUrl' => 'MSLine.swf'),
		//	'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'AdminBreakdownJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'TechBreakdownJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
		//	'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'FDBothBreakdownJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'FDTechAdminBreakdownJSON', $selectedAreaId, $yearId), 'swfUrl' => 'Scatter.swf'),
		//	'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'AppointmentJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'OwnershipJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
		//	'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'LocalityJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
		//	'break',
		);
		
		$this->set(compact('header', 'geoLvlId', 'areaId', 'FDId','yearId', 'geoLvlOptions', 'areaLvlOptions', 'FDLvlOptions', 'yearsOptions', /*'totalKGInfo',*/ 'displayChartData', 'QATableData', 'tableTitle'));
		
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
		$countryName = $this->Session->read('Dashboard.Overview.CountryName');
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		$data = $this->QADashboard->setupChartInfo("Administrative and Technical Aspects");
		$indData = $this->QADashboard->getIndicatorByGID($this->QADashboard->indicators['QA_AdminTechBoth_Score']);//array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']));
		
		$data = array_merge($data, $this->QADashboard->setupChartCategory($indData));
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$tempAreaData = $this->QADashboard->getSummaryJorData($setupOptions);
		
		$chartDataSetup = array(
			'caption' => $areaName,
			'color' => 'blue'
		);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempAreaData);
		
		$setupOptions = array(
			'areaIds' => $countryId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$tempNationalData = $this->QADashboard->getSummaryJorData($setupOptions); 
		$chartDataSetup = array(
			'caption' => $countryName,
			'color' => 'green'
		);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempNationalData);
		//pr($data);die;
		return json_encode($data);
	}
	
	public function TrendLineJSON($selectedAreaId, $yearId, $year){
		$this->autoRender = false;
		
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		
		$data = $this->QADashboard->setupLineChartInfo("Trends");
		$yearOptions = $this->QADashboard->getYears(10,$year);
		$data = array_merge($data, $this->QADashboard->setupChartCategory($yearOptions));
	
		$indData = $this->QADashboard->getIndicatorByGID($this->QADashboard->indicators['QA_AdminTechBoth_Score']);
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']));
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'years' => $yearOptions,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$tempAreaData = $this->QADashboard->getSummaryTrendJorData($setupOptions);
		$data['dataset'][] = $this->QADashboard->setupLineChartDataset($tempAreaData, $indData, $unitIndData,$yearOptions );
		
		return json_encode($data);
	}
	
	public function AdminBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		$countryName = $this->Session->read('Dashboard.Overview.CountryName');
		$data = $this->QADashboard->setupChartInfo("Administrative Domains");
		$indData = $this->QADashboard->getIndicatorByGID($this->QADashboard->indicators['QA_AdminBreakdown_Score']);//array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']));

		$data = array_merge($data, $this->QADashboard->setupChartCategory($indData));
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$tempAreaData = $this->QADashboard->getSummaryJorData($setupOptions);
		
		$chartDataSetup = array(
			'caption' => $areaName,
			'color' => 'blue'
		);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempAreaData);
		
		$setupOptions = array(
			'areaIds' => $countryId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$tempNationalData = $this->QADashboard->getSummaryJorData($setupOptions); 
		
		$chartDataSetup = array(
			'caption' => $countryName,
			'color' => 'green'
		);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempNationalData);
		
		return json_encode($data);
	}
	
	public function TechBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		$data = $this->QADashboard->setupChartInfo("Technical Domains");
		$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		$countryName = $this->Session->read('Dashboard.Overview.CountryName');
		
		$indData = $this->QADashboard->getIndicatorByGID($this->QADashboard->indicators['QA_TechBreakdown_Score']);//array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']));

		$data = array_merge($data, $this->QADashboard->setupChartCategory($indData));
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$tempAreaData = $this->QADashboard->getSummaryJorData($setupOptions);
		$chartDataSetup = array(
			'caption' => $areaName,
			'color' => 'blue'
		);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempAreaData);
		
		$setupOptions = array(
			'areaIds' => $countryId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$tempNationalData = $this->QADashboard->getSummaryJorData($setupOptions); 
		$chartDataSetup = array(
			'caption' => $countryName,
			'color' => 'green'
		);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempNationalData);
		
		return json_encode($data);
	}
	
	public function FDBothBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		$data = $this->QADashboard->setupChartInfo("Distribution of Both Aspects");
		$childAreaOptions = $this->QADashboard->getAreaChildLevel($selectedAreaId, false);
		if(empty($childAreaOptions)){
			$childAreaOptions = $this->QADashboard->getAreaById($selectedAreaId, 'list');
		}
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		
		$indData = $this->QADashboard->getIndicatorByGID($this->QADashboard->indicators['QA_AdminTechBoth_Score']['Both']);//array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']));

		$data = array_merge($data, $this->QADashboard->setupChartCategory($childAreaOptions));
		
		$setupOptions = array(
			'areaIds' => $childAreaOptions,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$tempAreaData = $this->QADashboard->getSummaryJorData($setupOptions);//pr($tempAreaData);die;
		
		$chartDataSetup = array(
			'caption' => $areaName,
			'color' => 'blue',
			'compareKey' => 'Area_NId'
		);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($chartDataSetup,  $childAreaOptions, $unitIndData, $tempAreaData);
		
		return json_encode($data);
	}
	
	
	public function FDTechAdminBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		$title = "Scatterplot of Administrative and Technical and Aspects";
		//$xaxisName = 'Administrative Aspects';
		//$yaxisName = 'Technical Aspects';
	/*	$data = '

{
  "chart":{
    "caption":"Portfolio of Investments in Equities",
    "subcaption":"(diameter of bubble indicates quantity of equities held)",
    "xaxisname":"Acquisition Price",
    "yaxisname":"Current Price"
  },
  "dataset":[{
      "data":[{}
      ]
    }
  ]
}

';return  $data;*/
		
		$childAreaOptions = $this->QADashboard->getAllAreaChildByLevel($selectedAreaId, 5, false);
		if(empty($childAreaOptions)){
			$childAreaOptions = $this->QADashboard->getAreaById($selectedAreaId, 'list');
		}
		
		$indData = $this->QADashboard->getIndicatorByGID(array($this->QADashboard->indicators['QA_AdminTechBoth_Score']['Admin'],$this->QADashboard->indicators['QA_AdminTechBoth_Score']['Tech']) );//array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent']));
		
		$data = $this->QADashboard->setupScatterChartInfo($title, $indData);
		$setupOptions = array(
			'areaIds' => $childAreaOptions,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$tempAreaData = $this->QADashboard->getSummaryJorData($setupOptions);
		$data['dataset'][] = $this->QADashboard->setupScatterChartDataset($tempAreaData,$indData,$unitIndData, $childAreaOptions);
		//die;
		return  json_encode($data);
	}
	
	public function AppointmentJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		$data = $this->QADashboard->setupChartInfo("Appointment");
		$indData = $this->QADashboard->getIndicatorByGID($this->QADashboard->indicators['QA_AdminTechBoth_Score']);//array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']));
		
		$data = array_merge($data, $this->QADashboard->setupChartCategory($indData));
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => array($this->QADashboard->indicators['SubgrpVal']['Permanent'])
		);
		$tempAreaData = $this->QADashboard->getSummaryJorData($setupOptions);
		$chartDataSetup = array(
			'caption' => 'Permanent',
			'color' => 'blue'
		);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempAreaData);
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => array($this->QADashboard->indicators['SubgrpVal']['Contract']),
		);
		$tempNationalData = $this->QADashboard->getSummaryJorData($setupOptions); 
		$chartDataSetup = array(
			'caption' => 'Contract',
			'color' => 'purple'
		);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempNationalData);
		
		$chartDataSetup = array(
			'caption' => 'Both',
			'color' => 'green'
		);
		$data['dataset'][] =  $this->QADashboard->sumDataForChart($chartDataSetup, $data['dataset']); 
		//pr($data);
		return  json_encode($data);
	}

	public function OwnershipJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		$data = $this->QADashboard->setupChartInfo("Ownership");
		$indData = $this->QADashboard->getIndicatorByGID($this->QADashboard->indicators['QA_AdminTechBoth_Score']);//array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']));
		
		$data = array_merge($data, $this->QADashboard->setupChartCategory($indData, 'Both'));
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => array($this->QADashboard->indicators['SubgrpVal']['Owned'])
		);
		$tempAreaData = $this->QADashboard->getSummaryJorData($setupOptions);
		$chartDataSetup = array(
			'caption' => __('Owned'),
			'color' => 'blue'
		);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($chartDataSetup, $indData, $unitIndData, $tempAreaData);
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => array($this->QADashboard->indicators['SubgrpVal']['Rented']),
		);
		$tempNationalData = $this->QADashboard->getSummaryJorData($setupOptions); 
		
		$chartDataSetup = array(
			'caption' => __('Rented'),
			'color' => 'purple'
		);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempNationalData);
		
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => array($this->QADashboard->indicators['SubgrpVal']['Total']),
		);
		$tempNationalData = $this->QADashboard->getSummaryJorData($setupOptions); 
		
		$chartDataSetup = array(
			'caption' => 'Both',
			'color' => 'green'
		);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempNationalData);
		//pr($data);
		return  json_encode($data);
	}
	
	public function LocalityJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$countryId = $this->Session->read('Dashboard.Overview.CountryId');
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		$data = $this->QADashboard->setupChartInfo("Locality");
		$indData = $this->QADashboard->getIndicatorByGID($this->QADashboard->indicators['QA_AdminTechBoth_Score']);//array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']));
		
		$data = array_merge($data, $this->QADashboard->setupChartCategory($indData));
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => array($this->QADashboard->indicators['SubgrpVal']['Urban'])
		);
		$tempAreaData = $this->QADashboard->getSummaryJorData($setupOptions);
		$chartDataSetup = array(
			'caption' => __('Urban'),
			'color' => 'blue'
		);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempAreaData);
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => array($this->QADashboard->indicators['SubgrpVal']['Rural']),
		);
		$tempNationalData = $this->QADashboard->getSummaryJorData($setupOptions);
		$chartDataSetup = array(
			'caption' => __('Rural'),
			'color' => 'purple'
		);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset($chartDataSetup,  $indData,$unitIndData, $tempNationalData);
		
		$setupOptions = array(
			'areaIds' => $selectedAreaId,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,//array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']),
			'Subgroup_Val_GId' => array($this->QADashboard->indicators['SubgrpVal']['Total']),
		);
		$tempNationalData = $this->QADashboard->getSummaryJorData($setupOptions); 
		
		$chartDataSetup = array(
			'caption' => 'Both',
			'color' => 'green'
		);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset($chartDataSetup, $indData, $unitIndData, $tempNationalData);
		//pr($data);
		return  json_encode($data);
	}
	//Table Setup
	
	public function setupQATableData($areaId,$yearId){
		$areaBreakdownOptions = $this->QADashboard->getAreaChildLevel($areaId);
		if(empty($areaBreakdownOptions)){
			$areaBreakdownOptions = $this->QADashboard->getAreaById($areaId, 'list');
		}
		$indData = $this->QADashboard->getIndicatorByGID($this->QADashboard->indicators['QA_AdminTechBoth_Score']);//array(8 => 'Administrative', 15 => 'Technical', 18 => __('Both'));
		$unitIndData = $this->QADashboard->getUnitIndicatorByGID(array($this->QADashboard->indicators['Unit']['Percent'],$this->QADashboard->indicators['Unit']['Number']));
		
		$setupOptions = array(
			'areaIds' => $areaBreakdownOptions,
			'TimePeriod_Nid' => $yearId,
			'indicators' => $indData,
			'UnitIds' => $unitIndData,
			'Subgroup_Val_GId' => $this->QADashboard->indicators['SubgrpVal']['Total'],
		);
		$data = $this->QADashboard->getSummaryJorData($setupOptions);
		
		$tableHeaders = array(__('Name'), __('Administrative')." (%)", __('Technical')." (%)", __('Both')." (%)");
		$tableData = array();

		foreach ($areaBreakdownOptions as $keyArea => $area) {
			
			$row = array();
			$row[] = $area;
			$scoreFound = false;
			foreach ($data as $score) {
				if($keyArea == $score['JORData']['Area_NId']){
					foreach($indData as $iKey => $obj){
						
						if($iKey == $score['JORData']['IUSNId']){
							$row[] = $score['JORData']['Data_Value'];
							$scoreFound = true;
							break;
						}
					}
				}
			}
			if(!$scoreFound){
				for($i = count($row); $i <= count($indData); $i++){
					$row[] = '';
				}
			}
			
			$tableData[] = $row;
		}
		
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
