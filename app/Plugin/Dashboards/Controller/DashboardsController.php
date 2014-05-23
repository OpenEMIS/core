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
	public $institutionSiteId;
	public $uses = array();
    public $helpers = array('Js' => array('Jquery'));
    public $components = array('UserSession', 'Dashboards.QADashboard' );
    public $modules = array(
        'InstitutionQA' => 'Dashboards.DashboardInstitutionQA',
    );

	public function beforeFilter() {
		parent::beforeFilter();
		//pr($this->bodyTitle);
		//$this->bodyTitle = NULL;
		
		$this->set('modelName', 'Dashboards');
		
		
		if($this->action == 'dashboardReport'){
			$this->bodyTitle = 'Reports';
			$this->Navigation->addCrumb('Reports', array('controller' => 'Reports', 'action' => 'index', 'plugin' => 'Reports'));
		}
		else if($this->action == 'overview'){
			$this->Navigation->addCrumb('Reports', array('controller' => 'Reports', 'action' => 'index', 'plugin' => 'Reports'));
			$this->Navigation->addCrumb('Dashboards', array('controller' => 'Dashboards', 'action' => 'dashboardReport', 'plugin' => 'Dashboards'));
		}
		else if($this->action == 'InstitutionQA'){
			if ($this->Session->check('InstitutionId')) {
				$institutionId = $this->Session->read('InstitutionId');
                $Institution = ClassRegistry::init('Institution');
                $institutionName = $Institution->field('name', array('Institution.id' => $institutionId));
                $this->Navigation->addCrumb('Institutions', array('controller' => 'Institutions', 'action' => 'index', 'plugin' => false));
                $this->Navigation->addCrumb($institutionName, array('controller' => 'Institutions', 'action' => 'view', 'plugin' => false));
				
				if ($this->Session->check('InstitutionSiteId')) {
					$this->institutionSiteId = $this->Session->read('InstitutionSiteId');
					$InstitutionSite = ClassRegistry::init('InstitutionSite');
					$institutionSiteName = $InstitutionSite->field('name', array('InstitutionSite.id' => $this->institutionSiteId));
					$this->Navigation->addCrumb($institutionSiteName, array('controller' => 'InstitutionSites', 'action' => 'view', 'plugin' => false));
					//$this->Navigation->addCrumb('Quality', array('controller' => 'Quality', 'action' => 'qualityRubric', 'plugin'=> 'Quality'));

				} else {
					return $this->redirect(array('controller' => 'Institutions', 'action' => 'listSites','plugin' => false));
				}
			}
			else {
                  return $this->redirect(array('controller' => 'Institutions', 'action' => 'index', 'plugin' => false));
            }
		}
		
	}

	public function dashboardReport(){
		$this->Navigation->addCrumb('Dashboards');
		
		$this->set('enabled',true);
		$reportType = 'dashboard';
		$Report = ClassRegistry::init('Report');
        $data = $Report->find('all',array('conditions'=>array('Report.visible' => 1, 'category'=>$reportType.' Reports'), 'order' => array('Report.order')));
  
        $checkFileExist = array();
        $tmp = array();
        
        //arrange and sort according to grounp
        foreach($data as $k => $val){
            //$pathFile = ROOT.DS.'app'.DS.'Plugin'.DS.'Reports'.DS.'webroot'.DS.'results'.DS.str_replace(' ','_',$val['Report']['category']).DS.$val['Report']['module'].DS.str_replace(' ','_',$val['Report']['name']).'.'.$val['Report']['file_type'];
            $module = $val['Report']['module'];
            $category = $val['Report']['category'];
            $name = $val['Report']['name'];
            $val['Report']['file_type'] = ($val['Report']['file_type']=='ind'?'csv':$val['Report']['file_type']);
            $tmp[$reportType.' Reports'][$module][$name] =  $val['Report']; 
        }

        $msg = (isset($_GET['processing']))?'processing':'';
        $this->set('msg',$msg);
        $this->set('data',$tmp);
        $this->set('controllerName', $this->controller);
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
	
	
    public function overview() {
		$id = empty($this->params['pass'][0])? 0: $this->params['pass'][0]; //Report ID
		
		if($this->request->is('post')){
			$temp_geo_id = $this->request->data['Dashboards']['geo_level_id'];
			$temp_area_id = $this->request->data['Dashboards']['area_level_id'];
			//$temp_fd_id = $this->request->data['Dashboards']['fd_level_id'];
			$temp_year_id = $this->request->data['Dashboards']['year_id'];
			return $this->redirect(array('controller' => 'Dashboards', 'action' => 'overview',$id,$temp_geo_id,$temp_area_id,/*$temp_fd_id,*/$temp_year_id));
		}
		
		$countryId = empty($this->params['pass'][1])? 0: $this->params['pass'][1]; //Country ID/Geo Level Id
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
		$geoLvlOptions = $this->QADashboard->getAreaChildLevel(-1);
		$countryId = (empty($countryId))? key($geoLvlOptions): $countryId;
		$areaLvlOptions = $this->QADashboard->getAreaChildLevel($countryId);
		$areaId = (empty($areaId))? key($areaLvlOptions): $areaId;
		$FDId = 0;
		/*$FDLvlOptions = $this->QADashboard->getAreaChildLevel($areaId);
		$FDLvlOptions[0] = 'ALL';
		ksort($FDLvlOptions);
		$FDId = (empty($FDId))? key($FDLvlOptions): $FDId;*/
		
		$yearsOptions = $this->QADashboard->getYears();
		$yearId = (empty($yearId))? key($yearsOptions): $yearId;
		
		$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		
		$tableTitle = (empty($FDId)?$areaLvlOptions[$selectedAreaId]:$FDLvlOptions[$selectedAreaId]);
		$tableTitle .= " ".__('Year')." ".$yearsOptions[$yearId];	
		$QATableData = $this->setupQATableData($areaId,$yearId);
		
		//setup chart data
		$displayChartData = array(
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'ATAspectJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'TrendLineJSON',$selectedAreaId, $yearId, $yearsOptions[$yearId]), 'swfUrl' => 'MSLine.swf'),
			'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'AdminBreakdownJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'TechBreakdownJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'FDBothBreakdownJSON', $selectedAreaId, $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'FDTechAdminBreakdownJSON', $selectedAreaId, $yearId), 'swfUrl' => 'Scatter.swf'),
			'break',
		);
		
		$this->set(compact('header', 'countryId', 'areaId', 'FDId','yearId', 'geoLvlOptions', 'areaLvlOptions', 'FDLvlOptions', 'yearsOptions', /*'totalKGInfo',*/ 'displayChartData', 'QATableData', 'tableTitle'));
		
    }
	
	public function dashboardsAjaxGetArea($firstBlank = false){
		$this->autoRender = false;
		if($this->request->is('ajax')){
			$countryId = $this->request->query['countryId'];
			$prependBlank = !empty($this->request->query['prependBlank'])? $this->request->query['prependBlank']:false;
			$data = $this->QADashboard->getAreaChildLevel($countryId);
			
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
		
	//	$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		$data = $this->QADashboard->setupChartInfo("Administrative and Technical Aspects");
		$catData = array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$data = array_merge($data, $this->QADashboard->setupChartCategory($catData));
		
		$tempAreaData = $this->QADashboard->getSummaryJorData($selectedAreaId,$yearId);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($areaName, '9ACCF6',  $catData, $tempAreaData);
		
		$tempNationalData = $this->QADashboard->getSummaryJorData(1,$yearId);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset('National Average', '82CF27',  $catData, $tempNationalData);
		
		return json_encode($data);
	}
	
	public function TrendLineJSON($selectedAreaId, $yearId, $year){
		$this->autoRender = false;
		
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		
		$data = $this->QADashboard->setupLineChartInfo("Trends");
		$yearOptions = $this->QADashboard->getYears(10,$year);
		
		$data = array_merge($data, $this->QADashboard->setupChartCategory($yearOptions));
	
		$tempAreaData = $this->QADashboard->getSummaryTrendJorData($selectedAreaId,$yearOptions);
		$data['dataset'][] = $this->QADashboard->setupLineChartDataset('Admin', array('color' => '9ACCF6', 'anchorSides' =>3),  $yearOptions, $tempAreaData,array('compareKey' => 'TimePeriod_NId', 'filterDataBy' => array('key' => 'IUSNId', 'value' => 8)));
		$data['dataset'][] = $this->QADashboard->setupLineChartDataset('Tech', array('color' => '82CF27', 'anchorSides' =>4),  $yearOptions, $tempAreaData,array('compareKey' => 'TimePeriod_NId', 'filterDataBy' => array('key' => 'IUSNId', 'value' => 15)));
		$data['dataset'][] = $this->QADashboard->setupLineChartDataset('Both', array('color' => 'CF5227', 'anchorSides' =>20),  $yearOptions, $tempAreaData,array('compareKey' => 'TimePeriod_NId', 'filterDataBy' => array('key' => 'IUSNId', 'value' => 18)));
		/*
		$data['categories']['category'][]['label'] ='2011';
		$data['categories']['category'][]['label'] ='2012';
		$data['categories']['category'][]['label'] ='2013';
				
		$data['dataset'][0]['seriesname'] = "Admin";
		$data['dataset'][0]['color'] = "9ACCF6";
		$data['dataset'][0]['alpha'] = "90";
		$data['dataset'][0]['showvalues'] = "0";
		$data['dataset'][0]['data'][] = array("value"=> 30);
		$data['dataset'][0]['data'][] = array("value"=> 90);
		$data['dataset'][0]['data'][] = array("value"=> 39);
		
		$data['dataset'][1]['seriesname'] = "Tech";
		$data['dataset'][1]['color'] = "82CF27";
		$data['dataset'][1]['alpha'] = "90";
		$data['dataset'][1]['showvalues'] = "0";
		$data['dataset'][1]['data'][] = array("value"=> 80);
		$data['dataset'][1]['data'][] = array("value"=> 44);
		$data['dataset'][1]['data'][] = array("value"=> 63);
		*/
		return json_encode($data);
	}
	
	public function AdminBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		$data = $this->QADashboard->setupChartInfo("Administrative Domains");
		$catData = array(
			1 => 'Management and leadership',
			2 => 'Health, nutrition and protection',
			3 => 'Physical environment',
			4 => 'Teacher',
			5 => 'Evaluation',
			6 => 'KG relationship with parents and local community',
			7 => 'Children with challenges and disability',
				);
		$data = array_merge($data, $this->QADashboard->setupChartCategory($catData));
		
		$tempAreaData = $this->QADashboard->getSummaryAdminBreakdownJorData($selectedAreaId,$yearId);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($areaName, '9ACCF6',  $catData, $tempAreaData);
		
		$tempNationalData = $this->QADashboard->getSummaryAdminBreakdownJorData(1,$yearId);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset('National Average', '82CF27',  $catData, $tempNationalData);
		
		return json_encode($data);
	}
	
	public function TechBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		$data = $this->QADashboard->setupChartInfo("Technical Domains");
		$catData = array(
			11 => 'Planning',
			12 => 'Implementation',
			13 => 'Evaluation',
			14 => 'Professionalism',
				);
		$data = array_merge($data, $this->QADashboard->setupChartCategory($catData));
		
		$tempAreaData = $this->QADashboard->getSummaryTechBreakdownJorData($selectedAreaId,$yearId);
		$data['dataset'][] = $this->QADashboard->setupChartDataset($areaName, '9ACCF6',  $catData, $tempAreaData);
		
		$tempNationalData = $this->QADashboard->getSummaryTechBreakdownJorData(1,$yearId);
		$data['dataset'][] =  $this->QADashboard->setupChartDataset('National Average', '82CF27',  $catData, $tempNationalData);
		
		return json_encode($data);
	}
	
	public function FDBothBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		
		$data = $this->QADashboard->setupChartInfo("Distribution of Both Aspects");
		$catData = $this->QADashboard->getAreaChildLevel($selectedAreaId);
		$areaName = $this->QADashboard->getAreaName($selectedAreaId);
		
		$data = array_merge($data, $this->QADashboard->setupChartCategory($catData));
		$tempAreaData = $this->QADashboard->getSummaryBothFDBreakdownJorData($selectedAreaId,$yearId);
		
		$data['dataset'][] = $this->QADashboard->setupChartDataset($areaName, '9ACCF6',  $catData, $tempAreaData, 'Area_NId');
		return json_encode($data);
	}
	
	
	public function FDTechAdminBreakdownJSON($selectedAreaId, $yearId){
		$this->autoRender = false;
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		
		$title = "Scatterplot of Administrative and Technical and Aspects";
		$xaxisName = 'Administrative Aspects';
		$yaxisName = 'Technical Aspects';
		$data = $this->QADashboard->setupScatterChartInfo($title, $xaxisName, $yaxisName);
		
		$catData = $this->QADashboard->getAreaChildLevel($selectedAreaId);
		
		$tempAreaData = $this->QADashboard->getSummaryTechAdminFDBreakdownJorData($selectedAreaId,$yearId);
		$data['dataset'][] = $this->QADashboard->setupScatterChartDataset($tempAreaData,$catData);
		
		return  json_encode($data);
	}
	
	
	public function setupQATableData($areaId,$yearId){
		$areaBreakdownOptions = $this->QADashboard->getAreaChildLevel($areaId);
		$FDAreaScoreData = $this->QADashboard->getSummaryAllFDBreakdownJorData($areaId,$yearId);
		
		$tableHeaders = array(__('Name'), __('Administrative'), __('Technical'), __('Both'));
		$tableData = array();

		$catData = array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		
		foreach ($areaBreakdownOptions as $keyArea => $area) {
			
			$row = array();
			$row[] = $area;
			$scoreFound = false;
			foreach ($FDAreaScoreData as $score) {
				
				if($keyArea == $score['JORData']['Area_NId']){
					foreach($catData as $keyCat => $obj){
						
						if($keyCat == $score['JORData']['IUSNId']){
							$row[] = $score['JORData']['Data_Value'];
							$scoreFound = true;
							break;
						}
					}
				}
			}
			if(!$scoreFound){
				for($i = count($row); $i <= count($catData); $i++){
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
