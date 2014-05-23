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

class DashboardInstitutionQA extends DashboardsAppModel {
	public $areaId;
    public $useTable = false;
    public $actsAs = array('ControllerAction');
    

    public function beforeAction($controller, $action) {
		$this->areaId =$controller->institutionSiteId;
        if ($action != 'qualityRubric') {
            // $controller->Navigation->addCrumb('Rubrics', array('controller' => 'Quality', 'action' => 'qualityRubric', 'plugin' => 'Quality'));
        }
    }

   public function InstitutionQA($controller, $params){
		if($controller->request->is('post')){
			$temp_year_id = $controller->request->data['Dashboards']['year_id'];
			return $controller->redirect(array('controller' => 'Dashboards', 'action' => 'InstitutionQA',$temp_year_id));
		}
		
		$controller->Navigation->addCrumb('Report - Dashboard');
		$header = __('Report - Dashboard');
		
		
		$yearsOptions = $controller->QADashboard->getYears();
		$yearId = empty($params['pass'][0])? 0: $params['pass'][0]; //year Id 
		$yearId = (empty($yearId))? key($yearsOptions): $yearId;
		
		$displayChartData = array(
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'InstitutionQA_ATAspectJSON', $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'InstitutionQA_TrendLineJSON', $yearId, $yearsOptions[$yearId]), 'swfUrl' => 'MSLine.swf'),
			'break',
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'InstitutionQA_AdminBreakdownJSON', $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'InstitutionQA_TechBreakdownJSON', $yearId), 'swfUrl' => 'ScrollColumn2D.swf'),
			'break',
		);
		
		$controller->set(compact('header', 'areaId','yearId',  'yearsOptions', /*'totalKGInfo',*/ 'displayChartData'));
   }
   
   public function InstitutionQA_ATAspectJSON($controller, $params){
		$this->render = false;
		
		$parentAreaData = $controller->QADashboard->getAreaParentData($this->areaId);
		
		$yearId = $params['pass'][0];
		
		$areaName = $controller->QADashboard->getAreaName($this->areaId);
		$data = $controller->QADashboard->setupChartInfo("Administrative and Technical Aspects");
		$catData = array(8 => 'Administrative', 15 => 'Technical', 18 => 'Both');
		$data = array_merge($data, $controller->QADashboard->setupChartCategory($catData));
		
		$tempAreaData = $controller->QADashboard->getSummaryJorData($this->areaId,$yearId);
		$data['dataset'][] = $controller->QADashboard->setupChartDataset($areaName, '9ACCF6',  $catData, $tempAreaData);
		
		$tempAreaData = $controller->QADashboard->getSummaryJorData($parentAreaData['JORArea']['Area_NId'],$yearId);
		$data['dataset'][] = $controller->QADashboard->setupChartDataset($areaName, '9A22F6',  $catData, $tempAreaData);
		
		$tempNationalData = $controller->QADashboard->getSummaryJorData(1,$yearId);
		$data['dataset'][] =  $controller->QADashboard->setupChartDataset('National Average', '82CF27',  $catData, $tempNationalData);
		
		return json_encode($data);
	}
	
	public function InstitutionQA_TrendLineJSON($controller, $params){
		$this->render = false;
		
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		$yearId = $params['pass'][0];
		$year = $params['pass'][1];
		$data = $controller->QADashboard->setupLineChartInfo("Trends");
		$yearOptions = $controller->QADashboard->getYears(10,$year);
		
		$data = array_merge($data, $controller->QADashboard->setupChartCategory($yearOptions));
	
		$tempAreaData = $controller->QADashboard->getSummaryTrendJorData($this->areaId,$yearOptions);
		$data['dataset'][] = $controller->QADashboard->setupLineChartDataset('Admin', array('color' => '9ACCF6', 'anchorSides' =>3),  $yearOptions, $tempAreaData,array('compareKey' => 'TimePeriod_NId', 'filterDataBy' => array('key' => 'IUSNId', 'value' => 8)));
		$data['dataset'][] = $controller->QADashboard->setupLineChartDataset('Tech', array('color' => '82CF27', 'anchorSides' =>4),  $yearOptions, $tempAreaData,array('compareKey' => 'TimePeriod_NId', 'filterDataBy' => array('key' => 'IUSNId', 'value' => 15)));
		$data['dataset'][] = $controller->QADashboard->setupLineChartDataset('Both', array('color' => 'CF5227', 'anchorSides' =>20),  $yearOptions, $tempAreaData,array('compareKey' => 'TimePeriod_NId', 'filterDataBy' => array('key' => 'IUSNId', 'value' => 18)));

		return json_encode($data);
	}
	
	public function InstitutionQA_AdminBreakdownJSON($controller, $params){
		$this->render = false;
		$yearId = $params['pass'][0];
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		$parentAreaData = $controller->QADashboard->getAreaParentData($this->areaId);
		$areaName = $controller->QADashboard->getAreaName($this->areaId);
		$data = $controller->QADashboard->setupChartInfo("Administrative Domains");
		$catData = array(
			1 => 'Management and leadership',
			2 => 'Health, nutrition and protection',
			3 => 'Physical environment',
			4 => 'Teacher',
			5 => 'Evaluation',
			6 => 'KG relationship with parents and local community',
			7 => 'Children with challenges and disability',
				);
		$data = array_merge($data, $controller->QADashboard->setupChartCategory($catData));
		
		$tempAreaData = $controller->QADashboard->getSummaryAdminBreakdownJorData($this->areaId,$yearId);
		$data['dataset'][] = $controller->QADashboard->setupChartDataset($areaName, '9ACCF6',  $catData, $tempAreaData);
		
		$tempAreaData = $controller->QADashboard->getSummaryAdminBreakdownJorData($parentAreaData['JORArea']['Area_NId'],$yearId);
		$data['dataset'][] = $controller->QADashboard->setupChartDataset($areaName, '9A22F6',  $catData, $tempAreaData);
		
		$tempNationalData = $controller->QADashboard->getSummaryAdminBreakdownJorData(1,$yearId);
		$data['dataset'][] =  $controller->QADashboard->setupChartDataset('National Average', '82CF27',  $catData, $tempNationalData);
		
		return json_encode($data);
	}
	
	public function InstitutionQA_TechBreakdownJSON($controller, $params){
		$this->render = false;
		//$selectedAreaId = !empty($FDId)?$FDId :$areaId;
		$yearId = $params['pass'][0];
		
		$parentAreaData = $controller->QADashboard->getAreaParentData($this->areaId);
		$areaName = $controller->QADashboard->getAreaName($this->areaId);
		$data = $controller->QADashboard->setupChartInfo("Technical Domains");
		$catData = array(
			11 => 'Planning',
			12 => 'Implementation',
			13 => 'Evaluation',
			14 => 'Professionalism',
				);
		$data = array_merge($data, $controller->QADashboard->setupChartCategory($catData));
		
		$tempAreaData = $controller->QADashboard->getSummaryTechBreakdownJorData($this->areaId,$yearId);
		$data['dataset'][] = $controller->QADashboard->setupChartDataset($areaName, '9ACCF6',  $catData, $tempAreaData);
		
		$tempAreaData = $controller->QADashboard->getSummaryTechBreakdownJorData($parentAreaData['JORArea']['Area_NId'],$yearId);
		$data['dataset'][] = $controller->QADashboard->setupChartDataset($areaName, '9A22F6',  $catData, $tempAreaData);
		
		$tempNationalData = $controller->QADashboard->getSummaryTechBreakdownJorData(1,$yearId);
		$data['dataset'][] =  $controller->QADashboard->setupChartDataset('National Average', '82CF27',  $catData, $tempNationalData);
		
		return json_encode($data);
	}
}
