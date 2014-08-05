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
		$this->areaId = $controller->institutionSiteAreaId; //$controller->institutionSiteId;
	}

	public function InstitutionQA($controller, $params) {
		if ($controller->request->is('post')) {
			$temp_year_id = $controller->request->data['Dashboards']['year_id'];
			return $controller->redirect(array('controller' => 'Dashboards', 'action' => 'InstitutionQA', $temp_year_id));
		}

		$controller->Navigation->addCrumb('Report - Dashboard');
		$header = __('Report - Dashboard');

		$countryData = $controller->QADashboard->getCountry();
		$countryId = $countryData['DIArea']['Area_NId'];

		$controller->Session->write('Dashboard.Overview.CountryId', $countryId);

		$yearsOptions = $controller->QADashboard->getYears();
		$yearId = empty($params['pass'][0]) ? 0 : $params['pass'][0]; //year Id 
		$yearId = (empty($yearId)) ? key($yearsOptions) : $yearId;

		$displayChartData = array();
		if (!empty($this->areaId)) {
			$displayChartData = array(
				array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'InstitutionQA_ATAspectJSON', $this->areaId, $yearId)),
				array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'InstitutionQA_TrendLineJSON', $this->areaId, $yearId, $yearsOptions[$yearId])),
				array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'InstitutionQA_AdminBreakdownJSON', $this->areaId, $yearId)),
				array('chartURLdata' => array('controller' => 'Dashboards', 'action' => 'InstitutionQA_TechBreakdownJSON', $this->areaId, $yearId)),
			);
		} else {
			$controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'));
		}

		$controller->set(compact('header', 'areaId', 'yearId', 'yearsOptions', /* 'totalKGInfo', */ 'displayChartData'));
	}

	public function InstitutionQA_ATAspectJSON($controller, $params) {
		$this->render = false;
		$selectedAreaId = $params['pass'][0];
		$yearId = $params['pass'][1];

		$countryId = $controller->Session->read('Dashboard.Overview.CountryId');

		$areaRawData = $controller->QADashboard->getAreaById(array($countryId, $selectedAreaId));
		$timePeriodRawData = $controller->QADashboard->getYears('all', array('id' => $yearId));

		$_options['indicatorGId'] = $controller->QADashboard->indicators['QA_AdminTechBoth_Score'];
		$_options['unitGId'] = array($controller->QADashboard->indicators['Unit']['Percent']/* ,$this->QADashboard->indicators['Unit']['Number'] */);
		$_options['subgroupValGId'] = array($controller->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $controller->QADashboard->getIUSByIndividualGId($_options);

		$controller->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);

		$timeperiodIds = $controller->HighCharts->getTimeperiodIds();
		$areaIds = $controller->HighCharts->getAreaIds();
		$sourceID = $controller->QADashboard->getLatestSourceID($controller->HighCharts->selectedIUS, $timeperiodIds);

		$rawData = $controller->QADashboard->getDashboardRawData(array('IUS' => $controller->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));


		$controller->HighCharts->plotBy = 'indicator';
		$data = $controller->HighCharts->customGenerateHeader(array('caption' => 'Administrative and Technical Aspects'));
		//$data = array_merge($data, $controller->HighCharts->customGenerateCategory('column'));

		$data = array_merge($data, $controller->HighCharts->customGetGenericChartData('column', $rawData));

		return json_encode($data, JSON_NUMERIC_CHECK);
	}

	public function InstitutionQA_TrendLineJSON($controller, $params) {
		$this->render = false;
		$selectedAreaId = $params['pass'][0];
		$yearId = $params['pass'][1];
		$year = $params['pass'][2];

		$data = $controller->HighCharts->customGenerateHeader(array('caption' => 'Trends'));
		$areaRawData = $controller->QADashboard->getAreaById(array($selectedAreaId));
		$timePeriodRawData = $controller->QADashboard->getYearRange($yearId, 10);

		$_options['indicatorGId'] = $controller->QADashboard->indicators['QA_AdminTechBoth_Score'];
		$_options['unitGId'] = array($controller->QADashboard->indicators['Unit']['Percent']/* ,$this->QADashboard->indicators['Unit']['Number'] */);
		$_options['subgroupValGId'] = array($controller->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $controller->QADashboard->getIUSByIndividualGId($_options);

		$controller->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);

		$timeperiodIds = $controller->HighCharts->getTimeperiodIds();
		$areaIds = $controller->HighCharts->getAreaIds();
		$sourceID = $controller->QADashboard->getLatestSourceID($controller->HighCharts->selectedIUS, $timeperiodIds);

		$rawData = $controller->QADashboard->getDashboardRawData(array('IUS' => $controller->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));

		$data = $controller->HighCharts->customGenerateHeader(array('caption' => 'Administrative and Technical Aspects', 'chartType' => 'line'));
		//$data = array_merge($data, $controller->HighCharts->customGenerateCategory('line'));
		$data = array_merge($data, $controller->HighCharts->customGetLineChartData($rawData));

		return json_encode($data, JSON_NUMERIC_CHECK);
	}

	public function InstitutionQA_AdminBreakdownJSON($controller, $params) {
		$this->render = false;
		$selectedAreaId = $params['pass'][0];
		$yearId = $params['pass'][1];
		$countryId = $controller->Session->read('Dashboard.Overview.CountryId');
		//$countryName = $this->Session->read('Dashboard.Overview.CountryName');

		$areaRawData = $controller->QADashboard->getAreaById(array($countryId, $selectedAreaId));
		$timePeriodRawData = $controller->QADashboard->getYears('all', array('id' => $yearId));

		$_options['indicatorGId'] = $controller->QADashboard->indicators['QA_AdminBreakdown_Score'];
		$_options['unitGId'] = array($controller->QADashboard->indicators['Unit']['Percent']/* ,$controller->QADashboard->indicators['Unit']['Number'] */);
		$_options['subgroupValGId'] = array($controller->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $controller->QADashboard->getIUSByIndividualGId($_options);

		$controller->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);

		$timeperiodIds = $controller->HighCharts->getTimeperiodIds();
		$areaIds = $controller->HighCharts->getAreaIds();
		$sourceID = $controller->QADashboard->getLatestSourceID($controller->HighCharts->selectedIUS, $timeperiodIds);

		$rawData = $controller->QADashboard->getDashboardRawData(array('IUS' => $controller->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));

		$controller->HighCharts->plotBy = 'indicator';
		$controller->HighCharts->rotateLabel = true;
		$data = $controller->HighCharts->customGenerateHeader(array('caption' => 'Administrative and Technical Aspects'));
		//$data = array_merge($data, $controller->HighCharts->customGenerateCategory('column'));

		$data = array_merge($data, $controller->HighCharts->customGetGenericChartData('column', $rawData));

		return json_encode($data, JSON_NUMERIC_CHECK);
	}

	public function InstitutionQA_TechBreakdownJSON($controller, $params) {
		$this->render = false;
		$selectedAreaId = $params['pass'][0];
		$yearId = $params['pass'][1];
		$countryId = $controller->Session->read('Dashboard.Overview.CountryId');

		$areaRawData = $controller->QADashboard->getAreaById(array($countryId, $selectedAreaId));
		$timePeriodRawData = $controller->QADashboard->getYears('all', array('id' => $yearId));

		$_options['indicatorGId'] = $controller->QADashboard->indicators['QA_TechBreakdown_Score'];
		$_options['unitGId'] = array($controller->QADashboard->indicators['Unit']['Percent']/* ,$controller->QADashboard->indicators['Unit']['Number'] */);
		$_options['subgroupValGId'] = array($controller->QADashboard->indicators['SubgrpVal']['Total']);

		$IUSRawData = $controller->QADashboard->getIUSByIndividualGId($_options);

		$controller->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);

		$timeperiodIds = $controller->HighCharts->getTimeperiodIds();
		$areaIds = $controller->HighCharts->getAreaIds();
		$sourceID = $controller->QADashboard->getLatestSourceID($controller->HighCharts->selectedIUS, $timeperiodIds);

		$rawData = $controller->QADashboard->getDashboardRawData(array('IUS' => $controller->HighCharts->selectedIUS, 'area' => $areaIds, 'timeperiod' => $timeperiodIds, 'source' => $sourceID));


		$controller->HighCharts->plotBy = 'indicator';
		$data = $controller->HighCharts->customGenerateHeader(array('caption' => 'Administrative and Technical Aspects'));
		//$data = array_merge($data, $controller->HighCharts->customGenerateCategory('column'));

		$data = array_merge($data, $controller->HighCharts->customGetGenericChartData('column', $rawData));

		return json_encode($data, JSON_NUMERIC_CHECK);
	}

}
