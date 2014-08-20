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

class VisualizerController extends VisualizerAppController {

	public $uses = Array('Visualizer.VisualizerArea', 'Visualizer.VisualizerData');
	public $components = array('Paginator'/* , 'FusionCharts.FusionCharts' */, 'HighCharts.HighCharts');
	public $helpers = array('Visualizer.Visualizer');
	public $nextPg = '';
	public $prevPg = '';

	//public $rootURL = '';

	public function beforeFilter() {
		parent::beforeFilter();
		$this->Navigation->addCrumb('Visualizer', array('controller' => $this->name, 'action' => 'index'));
		$tabs = array();

		$protocol = ($_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://');
		$host = $_SERVER['HTTP_HOST'];

		$rootURL = $protocol . $host . $this->webroot;

		$currentPg = $this->action; //empty($this->params['pass'][0]) ? 'indicator' : $this->params['pass'][0];
		//	$this->Session->delete('visualizer.wizard');
		if ($this->action != 'visualization') {
			if ($this->Session->check('visualizer.sort')) {
				if ($this->Session->read('visualizer.sort.action') != $this->action) {
					$this->Session->delete('visualizer.sort');
				}
			}

			if (!$this->Session->check('visualizer.wizard')) {
				$tabs['indicator'] = array('name' => 'Indicator', 'state' => 'active', 'url' => $rootURL . 'Visualizer/indicator', 'showStep' => true);
				$tabs['unit'] = array('name' => 'Unit', 'url' => $rootURL . 'Visualizer/unit', 'showStep' => true);
				$tabs['dimension'] = array('name' => 'Dimension', 'url' => $rootURL . 'Visualizer/dimension', 'showStep' => true);
				$tabs['area'] = array('name' => 'Area', 'url' => $rootURL . 'Visualizer/area', 'showStep' => true);
				$tabs['time'] = array('name' => 'Time', 'url' => $rootURL . 'Visualizer/time', 'showStep' => true);
				$tabs['source'] = array('name' => 'Source', 'url' => $rootURL . 'Visualizer/source', 'showStep' => true);
				$tabs['review'] = array('name' => 'Review', 'url' => $rootURL . 'Visualizer/review', 'showStep' => true);
			} else {
				$tabs = $this->Session->read('visualizer.wizard');
				if (array_key_exists($currentPg, $tabs)) {
					foreach ($tabs as $key => $singleTabObj) {
						if (isset($singleTabObj['state']) && $singleTabObj['state'] == 'active' && $key != $currentPg) {
							$singleTabObj['state'] = 'enabled';
							$tabs[$key] = $singleTabObj;
						}
					}
					$tabs[$currentPg]['state'] = 'active';
				}
			}
			$this->Session->write('visualizer.wizard', $tabs);

			$nextPg = $this->get_next($tabs, $currentPg);
			$prevPg = $this->get_prev($tabs, $currentPg);

			$this->nextPg = $nextPg;
			$this->prevPg = $prevPg;

			$this->set(compact('tabs', 'nextPg', 'prevPg'));
		} else {// visualization
			$id = '';
			if (!empty($this->params['pass'][1])) {
				$id = $this->params['pass'][1];

				if (!$this->Session->check('visualizer.visualization.' . $id)) {
					$this->Session->write('visualizer.visualization.' . $id, $this->Session->read('visualizer.selectedOptions'));
				}
			}

			$tabs['table'] = array('name' => 'Table', 'url' => $rootURL . 'Visualizer/visualization/table/' . $id);
			$tabs['column'] = array('name' => 'Column', 'url' => $rootURL . 'Visualizer/visualization/column/' . $id);
			$tabs['column-stack'] = array('name' => 'Stacked Column', 'url' => $rootURL . 'Visualizer/visualization/column-stack/' . $id);
			$tabs['bar'] = array('name' => 'Bar', 'url' => $rootURL . 'Visualizer/visualization/bar/' . $id);
			$tabs['bar-stack'] = array('name' => 'Stacked Bar', 'url' => $rootURL . 'Visualizer/visualization/bar-stack/' . $id);
			$tabs['line'] = array('name' => 'Line', 'url' => $rootURL . 'Visualizer/visualization/line/' . $id);
			$tabs['area'] = array('name' => 'Area', 'url' => $rootURL . 'Visualizer/visualization/area/' . $id);
			$tabs['pie'] = array('name' => 'Pie', 'url' => $rootURL . 'Visualizer/visualization/pie/' . $id);
			$tabs['scatter'] = array('name' => 'Scatter', 'url' => $rootURL . 'Visualizer/visualization/scatter/' . $id);
			$tabs['map'] = array('name' => 'Map', 'url' => $rootURL . 'Visualizer/visualization/map/' . $id);

			if (!empty($this->params['pass'][0])) {
				$selectedTab = $this->params['pass'][0];
				if (array_key_exists($selectedTab, $tabs)) {
					foreach ($tabs as $key => $singleTabObj) {
						$singleTabObj['state'] = ($key != $selectedTab) ? '' : 'active';
						$tabs[$key] = $singleTabObj;
					}
				} else {
					return $this->redirect(array('action' => 'visualization', 'table', $id, 'plugin' => 'Visualizer'));
				}
			}
			$this->Session->write('visualizer.visualizationTab', $tabs);
			$this->set(compact('tabs'));
		}
	}

	private function get_next($array, $key) {
		reset($array);
		$currentKey = key($array);
		while ($currentKey !== null && $currentKey != $key) {
			next($array);
			$currentKey = key($array);
			next($array);
			$nexKey = key($array);
			prev($array);
		}
		if (empty($nexKey) && $currentKey == $key) {
			next($array);
			$nexKey = key($array);
		}

		return empty($nexKey) ? '' : $nexKey; //next($array);
	}

	private function get_prev($array, $key) {
		reset($array);
		$currentKey = key($array);

		while ($currentKey !== null && $currentKey != $key) {
			next($array);
			$currentKey = key($array);

			if ($currentKey == $key) {
				prev($array);
				$prevKey = key($array);
				break;
			}
		}
		return empty($prevKey) ? '' : $prevKey; //next($array);
	}

	private function getIdArrayToStr($array, $field) {
		$idArr = array();
		foreach ($array as $obj) {
			foreach ($obj as $key => $item) {
				//pr($item);
				if (array_key_exists($field, $item)) {
					$idArr[] = $item[$field];
				}
			}
		}

		return $idArr; //implode(',', $idArr);
	}

	/*
	 * Populating tables format
	 */

	private function processIndicatorRawData($data, &$selectedId) {
		$tableRowData = array();
		$selectedIndicatorId = $selectedId;
		foreach ($data as $obj) {
			$selectedIndicatorId = (empty($selectedIndicatorId)) ? $obj['Indicator']['Indicator_NId'] : $selectedIndicatorId;
			$row = array();
			$row['id'] = $obj['Indicator']['Indicator_NId'];
			$row['name'] = $obj['Indicator']['Indicator_Name'];
			$row['desc'] = $obj['Indicator']['Indicator_Info'];
			$row['checked'] = ($selectedIndicatorId == $obj['Indicator']['Indicator_NId'] ) ? true : false;

			$tableRowData[] = $row;
		}

		$selectedId = $selectedIndicatorId;

		return $tableRowData;
	}

	private function processUnitRawData($data, &$selectedId) {
		$tableRowData = array();
		$selectedUnitId = $selectedId;
		foreach ($data as $obj) {
			$selectedUnitId = (empty($selectedUnitId)) ? $obj['Unit']['Unit_NId'] : $selectedUnitId;
			$row = array();
			$row['id'] = $obj['Unit']['Unit_NId'];
			$row['unit'] = $obj['Unit']['Unit_Name'];
			$row['indicator'] = $obj['Indicator']['Indicator_Name'];
			$row['checked'] = ($selectedUnitId == $obj['Unit']['Unit_NId'] ) ? true : false;
			$tableRowData[] = $row;
		}
		$selectedId = $selectedUnitId;

		return $tableRowData;
	}

	private function processDimensionRawData($data) {
		$tableRowData = array();
		foreach ($data as $obj) {
			$row = array();
			$row['IUSId'] = $obj['IndicatorUnitSubgroup']['IUSNId'];
			$row['subgroupVal'] = $obj['SubgroupVal']['Subgroup_Val'];
			$row['indicator'] = $obj['Indicator']['Indicator_Name'];
			$row['unit'] = $obj['Unit']['Unit_Name'];
			$tableRowData[] = $row;
		}

		return $tableRowData;
	}

	private function processAreaRawData($data, $columnsHeaderData) {
		$tableRowData = array();
		if (!empty($data)) {
			foreach ($data as $rowData) {
				$row = array();
				foreach ($rowData as $obj) {
					//	pr('level '.$obj['DIArea']['Area_Level']. ' -- '.$obj['DIArea']['Area_Name']);
					$row['Area_NId'] = $obj['DIArea']['Area_NId'];
					$row['Area_ID'] = $obj['DIArea']['Area_ID'];
					$row['level_' . $obj['DIArea']['Area_Level'] . '_name'] = /* sprintf('%s - %s',$obj['DIArea']['Area_ID'], */$obj['DIArea']['Area_Name']/* ) */;
				}

				for ($i = 1; $i < count($columnsHeaderData); $i++) {
					$arrName = 'level_' . ($i + 1) . '_name';
					if (!array_key_exists($arrName, $row)) {
						$row['level_' . ($i + 1) . '_name'] = '';
					}
				}
				$tableRowData[] = $row;
			}
		}

		return $tableRowData;
	}

	private function processTimeRawData($data) {
		$tableRowData = array();
		foreach ($data as $obj) {
			$row = array();
			$row['TimePeriod_NId'] = $obj['TimePeriod']['TimePeriod_NId'];
			$row['TimePeriod'] = $obj['TimePeriod']['TimePeriod'];
			$tableRowData[] = $row;
		}

		return $tableRowData;
	}

	private function processSourceRawData($data) {
		$tableRowData = array();
		foreach ($data as $obj) {
			$row = array();
			$row['IC_NId'] = $obj['IndicatorClassification']['IC_NId'];
			$row['IC_Name'] = $obj['IndicatorClassification']['IC_Name'];
			$tableRowData[] = $row;
		}

		return $tableRowData;
	}

	// ===================== End Populating Tables Format =======================
	public function ajaxSortData() {
		$this->autoRender = false;
		if ($this->request->is('ajax')) {
			$action = $this->params['pass'][0];
			$col = $this->request->data['col'];
			$direction = $this->request->data['direction'];

			$this->Session->write('visualizer.sort.action', $action);
			$this->Session->write('visualizer.sort.col', $col);
			$this->Session->write('visualizer.sort.direction', $direction);
		}
	}

	public function ajaxUpdateUserCBSelection() {
		$this->autoRender = false;
		if ($this->request->is('ajax')) {
			$type = $this->request->data['sectionType'];
			$value = $this->request->data['value'];
			$checked = $this->request->data['checked'];
			//	$this->resetTabsAfter($type);
			$sessionName = 'visualizer.selectedOptions.' . $type . '.' . $value;
			if ($checked == 'unchecked') {
				//if ($this->Session->check($sessionName)) {
				//$key = array_search('green', $array);
				$this->Session->delete($sessionName);
			} else {
				$this->Session->write($sessionName, $value);
			}
		}
	}

	public function ajaxUpdateUserRBSelection() {
		$this->autoRender = false;
		if ($this->request->is('ajax')) {
			$type = $this->request->data['sectionType'];
			$value = $this->request->data['value'];

			$this->resetTabsAfter($type);
			$this->Session->write('visualizer.selectedOptions.' . $type, $value);
		}
	}

	public function index() {
		return $this->redirect(array('action' => 'indicator'));
	}

	public function resetTabsAfter($selecetedTab) {
		$tabs = $this->Session->read('visualizer.wizard');
		$starttoReset = false;

		switch ($selecetedTab) {
			case 'IUS':
				$selecetedTab = 'dimension';
				break;
		}

		foreach ($tabs as $key => $singleTabObj) {
			if ($starttoReset) {
				switch ($key) {
					case 'time':
						$optKey = 'timeperiod';
						break;
					case 'dimension':
						$optKey = 'IUS';
						break;
					default :
						$optKey = $key;
				}

				$this->Session->delete('visualizer.selectedOptions.' . $optKey);
				unset($singleTabObj['state']);
				$tabs[$key] = $singleTabObj;
			}
			if ($key == $selecetedTab) {
				$starttoReset = true;
			}
		}
		$this->Session->write('visualizer.wizard', $tabs);
	}

	public function reset($redirect = true) {
		$this->autoRender = false;
		$this->Session->delete('visualizer');
		return $this->redirect(array('action' => 'indicator'));
	}

	public function indicator() {
		$this->Navigation->addCrumb('Indicator');
		$header = __('Visualizer') . ' - ' . __('Indicator');

		if ($this->request->is(array('post', 'put'))) {
			if (!empty($this->request->data['indicator']['id'])) {
				$this->Session->write('visualizer.selectedOptions.indicator', $this->request->data['indicator']['id']);

				if (count($this->Session->read('visualizer.selectedOptions.indicator')) > 0) {
					$this->Session->delete('visualizer.sort');
					return $this->redirect(array('action' => $this->nextPg, 'plugin' => 'Visualizer'));
				}
			}
			$displayError = true;
			$this->Message->alert('visualizer.failed.minSelection');
			unset($this->request->data['indicator']['search']);
		}

		$selectedIndicatorId = $this->Session->read('visualizer.selectedOptions.indicator');

		$sortType = 'ASC';
		$sortCol = 'Indicator_Name';
		$sortDirection = 'up';
		$order = $this->configSortData($sortDirection, $sortType, $sortCol);

		$di6Indicator = ClassRegistry::init('Visualizer.VisualizerIndicator');
		$data = $di6Indicator->find('all', array('fields' => array('Indicator_NId', 'Indicator_Name', 'Indicator_Info'), 'order' => $order));
		$tableRowData = $this->processIndicatorRawData($data, $selectedIndicatorId);
		if (empty($tableRowData) && empty($displayError)) {
			$this->Message->alert('general.noData');
		}
		$this->set(compact('header', 'tableRowData', 'selectedIndicatorId', 'sortCol', 'sortDirection'));
	}

	public function unit() {
		$this->Navigation->addCrumb('Unit');
		$header = __('Visualizer') . ' - ' . __('Unit');

		if (!$this->Session->check('visualizer.selectedOptions')) {
			return $this->redirect(array('action' => 'indicator', 'plugin' => 'Visualizer'));
		}

		if ($this->request->is(array('post', 'put'))) {
			if (!empty($this->request->data['unit']['id'])) {
				$this->Session->write('visualizer.selectedOptions.unit', $this->request->data['unit']['id']);

				if (count($this->Session->read('visualizer.selectedOptions.unit')) > 0) {
					$this->Session->delete('visualizer.sort');
					return $this->redirect(array('action' => $this->nextPg, 'plugin' => 'Visualizer'));
				}
			}
			$displayError = true;
			$this->Message->alert('visualizer.failed.minSelection');
			unset($this->request->data['unit']['search']);
		}

		$selectedIndicatorId = $this->Session->read('visualizer.selectedOptions.indicator');
		$selectedUnitIds = $this->Session->read('visualizer.selectedOptions.unit');

		$sortType = 'ASC';
		$sortCol = 'Unit.Unit_Name';
		$sortDirection = 'up';
		$order = $this->configSortData($sortDirection, $sortType, $sortCol);

		$di6IndicatorUnitSubgroup = ClassRegistry::init('Visualizer.VisualizerIndicatorUnitSubgroup');
		$data = $di6IndicatorUnitSubgroup->getUnits($selectedIndicatorId, $order);

		$tableRowData = $this->processUnitRawData($data, $selectedUnitIds);

		if (empty($tableRowData) && empty($displayError)) {
			$this->Message->alert('general.noData');
		}
		$this->set(compact('header', 'tableRowData', 'selectedUnitIds', 'sortCol', 'sortDirection'));
	}

	public function dimension() {
		$this->Navigation->addCrumb('Dimension');
		$header = __('Visualizer') . ' - ' . __('Dimension');
		$selectType = 'checkbox';

		if (!$this->Session->check('visualizer.selectedOptions')) {
			return $this->redirect(array('action' => 'indicator', 'plugin' => 'Visualizer'));
		}

		if ($this->request->is(array('post', 'put'))) {
			if (count($this->Session->read('visualizer.selectedOptions.IUS')) > 0) {
				$this->Session->delete('visualizer.sort');
				return $this->redirect(array('action' => $this->nextPg, 'plugin' => 'Visualizer'));
			}
			$displayError = true;
			$this->Message->alert('visualizer.failed.minSelection');
			unset($this->request->data['dimension']['search']);
		}

		$selectedIndicatorId = $this->Session->read('visualizer.selectedOptions.indicator');
		$selectedUnitIds = $this->Session->read('visualizer.selectedOptions.unit');
		$selectedDimensionIds = $this->Session->read('visualizer.selectedOptions.IUS');

		$sortType = 'ASC';
		$sortCol = 'SubgroupVal.Subgroup_Val';
		$sortDirection = 'up';
		$order = $this->configSortData($sortDirection, $sortType, $sortCol);

		$di6IndicatorUnitSubgroup = ClassRegistry::init('Visualizer.VisualizerIndicatorUnitSubgroup');
		$data = $di6IndicatorUnitSubgroup->getDimensions(array('indicators' => $selectedIndicatorId, 'units' => $selectedUnitIds), $order);

		$tableRowData = $this->processDimensionRawData($data);
		if (empty($tableRowData) && empty($displayError)) {
			$this->Message->alert('general.noData');
		}
		$this->set(compact('header', 'tableRowData', 'selectedDimensionIds', 'sortCol', 'sortDirection'));
	}

	public function area() {
		//$this->autoRender = false;
		$this->Navigation->addCrumb('Area');
		$header = __('Visualizer') . ' - ' . __('Area');

		if (!$this->Session->check('visualizer.selectedOptions')) {
			return $this->redirect(array('action' => 'indicator', 'plugin' => 'Visualizer'));
		}

		if ($this->request->is(array('post', 'put'))) {
			if (count($this->Session->read('visualizer.selectedOptions.area')) > 0) {
				return $this->redirect(array('action' => $this->nextPg, 'plugin' => 'Visualizer'));
			}
			$displayError = true;
			$this->Message->alert('visualizer.failed.minSelection');
			unset($this->request->data['area']['search']);
		}

		$selectedAreaIds = $this->Session->read('visualizer.selectedOptions.area');

		$di6AreaLevel = ClassRegistry::init('Visualizer.VisualizerAreaLevel');
		$areaLevelOptions = $di6AreaLevel->getAreaLevelList();
		$tableHeaders = $areaLevelOptions; //$di6AreaLevel->getAreaLevelList();

		$options['order'] = array('DIArea.Area_ID' => 'asc');

		$selectedAreaLevel = '';
		if (!empty($this->params['pass'])) {
			if (array_key_exists($this->params['pass'][0], $areaLevelOptions)) {
				$options['conditions']['DIArea.Area_Level'] = $this->params['pass'][0];
				$selectedAreaLevel = $this->params['pass'][0];
				$tableHeaders = $di6AreaLevel->getAreaLevelUpto($selectedAreaLevel); //$di6AreaLevel->getAreaLevelList();
			}
		}
		array_unshift($tableHeaders, __('Area ID'));

		if ($this->Session->check('visualizer.selectedOptions.area')) {
			$searchStr = $this->Session->read('visualizer.areaSearch.str');
			$this->request->data['area']['search'] = $searchStr;
			$options['conditions']['OR'] = array('DIArea.Area_Name LIKE' => '%' . $searchStr . '%', 'DIArea.Area_ID LIKE' => '%' . $searchStr . '%');
		}

		$this->Paginator->settings = array_merge(array('limit' => 20), $options);

		$data = $this->Paginator->paginate('VisualizerArea');
		$fullPathData = $this->VisualizerArea->getAreaTreaFullPath($data);
		$tableRowData = $this->processAreaRawData($fullPathData, $areaLevelOptions);
		if (empty($tableRowData) && empty($displayError)) {
			$this->Message->alert('general.noData');
		}
		$this->set(compact('header', 'tableHeaders', 'tableRowData', 'areaLevelOptions', 'selectedAreaLevel', 'selectedAreaIds'));
	}

	public function ajaxResetSearch() {
		$this->autoRender = false;
		if ($this->request->is('ajax')) {
			$searchStr = $this->request->data['searchStr'];
			$this->Session->write('visualizer.areaSearch.str', $searchStr);
		}
	}

	public function ajaxAreaSearch() {
		$this->autoRender = false;
		if ($this->request->is('ajax')) {
			$searchStr = $this->request->data['searchStr'];
			$selectedAreaLevel = $this->request->data['areaLvl'];

			$this->Session->write('visualizer.areaSearch.str', $searchStr);
			$selectedAreaIds = $this->Session->read('visualizer.selectedOptions.area');

			$di6AreaLevel = ClassRegistry::init('Visualizer.VisualizerAreaLevel');
			$areaLevelOptions = $di6AreaLevel->getAreaLevelList();
			$options['order'] = array('DIArea.Area_ID' => 'asc');

			if (!empty($selectedAreaLevel)) {
				$options['conditions']['DIArea.Area_Level'] = $selectedAreaLevel;
				$areaLevelOptions = $di6AreaLevel->getAreaLevelUpto($selectedAreaLevel);
			}
			if (!empty($searchStr)) {
				$options['conditions']['OR'] = array('DIArea.Area_Name LIKE' => '%' . $searchStr . '%', 'DIArea.Area_ID LIKE' => '%' . $searchStr . '%');
				//$options['conditions']['DIArea.Area_Name LIKE'] = '%' . $searchStr . '%';
			}

			$this->Paginator->settings = array_merge(array('limit' => 20), $options);


			$data = $this->Paginator->paginate('VisualizerArea');
			$fullPathData = $this->VisualizerArea->getAreaTreaFullPath($data);
			$tableRowData = $this->processAreaRawData($fullPathData, $areaLevelOptions);
		}
		$this->set(compact('tableRowData', 'areaLevelOptions', 'selectedAreaIds'));
		$this->render('ajax_table_row');
	}

	public function time() {
		$this->Navigation->addCrumb('Time');
		$header = __('Visualizer') . ' - ' . __('Time');

		if (!$this->Session->check('visualizer.selectedOptions')) {
			return $this->redirect(array('action' => 'indicator', 'plugin' => 'Visualizer'));
		}

		if ($this->request->is(array('post', 'put'))) {
			if (count($this->Session->read('visualizer.selectedOptions.timeperiod')) > 0) {
				$this->Session->delete('visualizer.sort');
				return $this->redirect(array('action' => $this->nextPg, 'plugin' => 'Visualizer'));
			}
			$displayError = true;
			$this->Message->alert('visualizer.failed.minSelection');
			unset($this->request->data['time']['search']);
		}

		$selectedDimensionIds = $this->Session->read('visualizer.selectedOptions.IUS');
		$selectedTimeperiodIds = $this->Session->read('visualizer.selectedOptions.timeperiod');
		$selectedAreaIds = $this->Session->read('visualizer.selectedOptions.area');

		$sortType = 'DESC';
		$sortCol = 'TimePeriod.TimePeriod';
		$sortDirection = 'down';
		$order = $this->configSortData($sortDirection, $sortType, $sortCol);

		$di6Data = ClassRegistry::init('Visualizer.VisualizerData');
		$data = $di6Data->getTimePeriodList($selectedDimensionIds, $selectedAreaIds, $order);
		$tableRowData = $this->processTimeRawData($data);
		if (empty($tableRowData) && empty($displayError)) {
			$this->Message->alert('general.noData');
		}
		$this->set(compact('header', 'tableRowData', 'selectedTimeperiodIds', 'sortCol', 'sortDirection'));
	}

	public function source() {
		$this->Navigation->addCrumb('Source');
		$header = __('Visualizer') . ' - ' . __('Source');

		if (!$this->Session->check('visualizer.selectedOptions')) {
			return $this->redirect(array('action' => 'indicator', 'plugin' => 'Visualizer'));
		}

		if ($this->request->is(array('post', 'put'))) {
			if (count($this->Session->read('visualizer.selectedOptions.source')) > 0) {
				$this->Session->delete('visualizer.sort');
				return $this->redirect(array('action' => $this->nextPg, 'plugin' => 'Visualizer'));
			}
			$displayError = true;
			$this->Message->alert('visualizer.failed.minSelection');
			unset($this->request->data['source']['search']);
		}

		$selectedDimensionIds = $this->Session->read('visualizer.selectedOptions.IUS');
		$selectedTimeperiodIds = $this->Session->read('visualizer.selectedOptions.timeperiod');
		$selectedSourceIds = $this->Session->read('visualizer.selectedOptions.source');

		$sortType = 'DESC';
		$sortCol = 'IndicatorClassification.IC_Name';
		$sortDirection = 'down';
		$order = $this->configSortData($sortDirection, $sortType, $sortCol);

		$di6IndicatorClassification = ClassRegistry::init('Visualizer.VisualizerIndicatorClassification');
		$data = $di6IndicatorClassification->getSource($selectedDimensionIds, $selectedTimeperiodIds, $order);

		$tableRowData = $this->processSourceRawData($data);
		if (empty($tableRowData) && empty($displayError)) {
			$this->Message->alert('general.noData');
		}
		$this->set(compact('header', 'tableRowData', 'selectedSourceIds', 'sortCol', 'sortDirection'));
	}

	public function review() {
		$this->Navigation->addCrumb('Review');
		$header = __('Visualizer') . ' - ' . __('Review');

		if (!$this->Session->check('visualizer.selectedOptions')) {
			return $this->redirect(array('action' => 'indicator', 'plugin' => 'Visualizer'));
		}

		$showVisualizeBtn = true;

		$reviewData = array();
		$selectedIndicatorId = $this->Session->read('visualizer.selectedOptions.indicator');
		$selectedUnitIds = $this->Session->read('visualizer.selectedOptions.unit');
		$selectedDimensionIds = $this->Session->read('visualizer.selectedOptions.IUS');
		$selectedAreaIds = $this->Session->read('visualizer.selectedOptions.area');
		$selectedTimeperiodIds = $this->Session->read('visualizer.selectedOptions.timeperiod');
		$selectedSourceIds = $this->Session->read('visualizer.selectedOptions.source');

		$di6IndicatorUnitSubgroup = ClassRegistry::init('Visualizer.VisualizerIndicatorUnitSubgroup');
		$indicatorRawData = $di6IndicatorUnitSubgroup->getDimensions(array('IUS' => $selectedDimensionIds));

		$reviewData['indicator'] = $this->processDimensionRawData($indicatorRawData);

		$areaRawData = $this->VisualizerArea->find('all', array('conditions' => array('DIArea.Area_NId' => $selectedAreaIds), 'order' => array('DIArea.lft asc')));
		$di6AreaLevel = ClassRegistry::init('Visualizer.VisualizerAreaLevel');
		//$tableHeaders =  $di6AreaLevel->getAreaLevelList();
		$fullPathData = $this->VisualizerArea->getAreaTreaFullPath($areaRawData);

		$lastRow = end($fullPathData);
		$lastItem = end($lastRow);
		$areaLevelOptions = $di6AreaLevel->getAreaLevelUpto($lastItem['DIArea']['Area_Level']);

		$reviewData['area'] = $this->processAreaRawData($fullPathData, $areaLevelOptions);

		$di6TimePeriod = ClassRegistry::init('Visualizer.VisualizerTimePeriod');
		$timePeriodRawData = $di6TimePeriod->find('all', array('conditions' => array('TimePeriod.TimePeriod_NId' => $selectedTimeperiodIds)));
		$reviewData['timeperiod'] = $this->processTimeRawData($timePeriodRawData);

		$di6IndicatorClassification = ClassRegistry::init('Visualizer.VisualizerIndicatorClassification');
		$sourceRawData = $di6IndicatorClassification->find('all', array('conditions' => array('IndicatorClassification.IC_NId' => $selectedSourceIds)));
		$reviewData['source'] = $this->processSourceRawData($sourceRawData);

		$this->set(compact('header', 'reviewData', 'areaLevelOptions', 'showVisualizeBtn'));
	}

	public function visualization() {
		$this->Navigation->addCrumb('Visualization');

		$visualType = $this->params['pass'][0];
		$id = $this->params['pass'][1];
		
		$tabs = $this->Session->read('visualizer.visualizationTab');
		$header = __('Visualizer') . ' - ' . __(ucfirst(strtolower($tabs[$visualType]['name'])));

		$showVisualization = true;
		if ($visualType == 'table') {
			$selectedOptions = $this->VisualizerData->getQueryOptionsSetup($this->Session->read('visualizer.visualization.' . $id), 'LEFT');

			$sortType = 'DESC';
			$sortCol = 'TimePeriod.TimePeriod';
			$sortDirection = 'down';
			$order = $this->configSortData($sortDirection, $sortType, $sortCol);

			$selectedOptions['order'] = $order;
			$this->Paginator->settings = $selectedOptions; //array_merge(array('limit' => 20), $selectedOptions);

			$selectedTimeperiodIds = $this->Session->read('visualizer.visualization.' . $id . '.timeperiod');
			$di6TimePeriod = ClassRegistry::init('Visualizer.VisualizerTimePeriod');
			$timePeriodRawData = $di6TimePeriod->find('all', array('conditions' => array('TimePeriod.TimePeriod_NId' => $selectedTimeperiodIds), 'order' => 'TimePeriod.TimePeriod DESC'));

			$yearCaption = $this->HighCharts->getYearSubcaption($timePeriodRawData);

			$data = $this->Paginator->paginate('VisualizerData');
			if (empty($data)) {
				$this->Message->alert('general.noData');
			}
			$this->set(compact('sortCol', 'sortDirection', 'yearCaption'));
		} else {
			$selectedDimensionIds = $this->Session->read('visualizer.visualization.' . $id . '.IUS');

			if (count($selectedDimensionIds) != 2 && $visualType == 'scatter') {
				$showVisualization = false;
				$this->Message->alert('visualizer.setting.minScatterDimension');
			}
			/* else{
			  $setupChartOption = array('url' => array('controller' => 'Visualizer', 'action' => 'VisualizeFusionChart',$visualType, $id), 'width'=> 950, 'height' => 713);
			  $displayChartData = $this->FusionCharts->getDisplayType($visualType, $setupChartOption);//array('chartURLdata' => array('controller' => 'Visualizer', 'action' => 'VisualizeBarFusionChart', $id),'swfUrl' => 'ScrollColumn2D.swf', 'tWidth' => '940', 'tHeight' => '705');

			  $this->set('displayChartData',$displayChartData);
			  } */
		}

		$this->set(compact('header', 'data', 'visualType', 'showVisualization', 'id'));
	}

	/*
	  public function VisualizeFusionChart($visualType, $id){
	  $this->autoRender = false;
	  $selectedOptions = $this->VisualizerData->getQueryOptionsSetup($this->Session->read('visualizer.visualization.'.$id));
	  $rawData = $this->VisualizerData->find('all', $selectedOptions);
	  //Retrive selected info
	  $selectedIndicatorId = $this->Session->read('visualizer.visualization.'.$id.'.indicator');
	  $selectedUnitIds = $this->Session->read('visualizer.visualization.'.$id.'.unit');
	  $selectedDimensionIds = $this->Session->read('visualizer.visualization.'.$id.'.IUS');
	  $selectedAreaIds = $this->Session->read('visualizer.visualization.'.$id.'.area');
	  $selectedTimeperiodIds = $this->Session->read('visualizer.visualization.'.$id.'.timeperiod');
	  $selectedSourceIds = $this->Session->read('visualizer.visualization.'.$id.'.source');

	  $di6IndicatorUnitSubgroup = ClassRegistry::init('Visualizer.IndicatorUnitSubgroup');
	  $IUSRawData = $di6IndicatorUnitSubgroup->getDimensions(array('IUS' => $selectedDimensionIds));

	  $areaRawData = $this->VisualizerArea->find('all', array('fields' => array('DIArea.Area_NId', 'DIArea.Area_ID', 'DIArea.Area_Name'),  'conditions' => array('DIArea.Area_NId' => $selectedAreaIds), 'order' => array('DIArea.lft asc')));

	  $di6TimePeriod = ClassRegistry::init('Visualizer.TimePeriod');
	  $timePeriodRawData = $di6TimePeriod->find('all', array('conditions' => array('TimePeriod.TimePeriod_NId' => $selectedTimeperiodIds)));

	  $this->FusionCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);

	  switch(strtolower($visualType)){
	  case 'area':
	  case 'line':
	  $fusionFormatData = $this->FusionCharts->getLineChartData($rawData);
	  break;
	  case 'pie':
	  $fusionFormatData = $this->FusionCharts->getPieChartData($rawData);
	  break;
	  case 'scatter':
	  $fusionFormatData = $this->FusionCharts->getScatterChartData($rawData);
	  break;
	  case 'bar':
	  case 'stackbar':
	  case 'column':
	  case 'stackcolumn':
	  default:
	  $fusionFormatData = $this->FusionCharts->getBarChartData($rawData);
	  break;
	  }

	  return $fusionFormatData;
	  }
	 */

	public function VisualizeHighChart($visualType, $id) {
		$this->autoRender = false;

		$selectedIndicatorId = $this->Session->read('visualizer.visualization.' . $id . '.indicator');
		$selectedUnitIds = $this->Session->read('visualizer.visualization.' . $id . '.unit');
		$selectedDimensionIds = $this->Session->read('visualizer.visualization.' . $id . '.IUS');
		$selectedAreaIds = $this->Session->read('visualizer.visualization.' . $id . '.area');
		$selectedTimeperiodIds = $this->Session->read('visualizer.visualization.' . $id . '.timeperiod');
		$selectedSourceIds = $this->Session->read('visualizer.visualization.' . $id . '.source');

		$di6IndicatorUnitSubgroup = ClassRegistry::init('Visualizer.VisualizerIndicatorUnitSubgroup');
		$IUSRawData = $di6IndicatorUnitSubgroup->getDimensions(array('IUS' => $selectedDimensionIds));

		$selectedOptions = $this->VisualizerData->getQueryOptionsSetup($this->Session->read('visualizer.visualization.' . $id));
		$rawData = $this->VisualizerData->find('all', $selectedOptions);

		if (!empty($rawData)) {
			if ($visualType == 'map') {
				$countryData = $this->VisualizerArea->getCountry();

				$di6TimePeriod = ClassRegistry::init('Visualizer.VisualizerTimePeriod');
				$timePeriodRawData = $di6TimePeriod->find('all', array('conditions' => array('TimePeriod.TimePeriod_NId' => $selectedTimeperiodIds), 'order' => 'TimePeriod.TimePeriod DESC', 'limit' => 1));

				$areaRawData = $this->VisualizerData->getAreaLevel($selectedDimensionIds, $selectedAreaIds, $timePeriodRawData[0]['TimePeriod']['TimePeriod_NId']);

				$di6AreaLevel = ClassRegistry::init('Visualizer.VisualizerAreaLevel');
				$areaDBLastLevel = $di6AreaLevel->getAllAreaLevel();

				$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);
				$this->HighCharts->initMapAreaInfo($areaRawData, $areaDBLastLevel, $countryData);
				$jsonData = $this->HighCharts->getMapData($rawData);
			} else {

				$areaRawData = $this->VisualizerArea->find('all', array('fields' => array('DIArea.Area_NId', 'DIArea.Area_ID', 'DIArea.Area_Name', 'DIArea.Area_Level'), 'conditions' => array('DIArea.Area_NId' => $selectedAreaIds), 'order' => array('DIArea.lft asc')));

				$di6TimePeriod = ClassRegistry::init('Visualizer.VisualizerTimePeriod');
				$timePeriodRawData = $di6TimePeriod->find('all', array('conditions' => array('TimePeriod.TimePeriod_NId' => $selectedTimeperiodIds), 'order' => 'TimePeriod.TimePeriod DESC'));

				$this->HighCharts->initVariables($IUSRawData, $areaRawData, $timePeriodRawData);

				$jsonData = $this->HighCharts->getChartData($visualType, $rawData);
			}
		} else {
			$errorMsg = '<div class="alert alert_view alert_info" title="Click to dismiss">
				<div class="alert_icon"></div>
				<div class="alert_content">There are no records.</div>
				</div>';
			$jsonData = json_encode(array('errorMsg' => $errorMsg));
			//$jsonData = json_encode(array('error' =>$this->Message->alert('general.noData')));
			//	pr($this->Message->alert('general.noData'));
		}
		return $jsonData;
	}

	public function loadJsonMap($code, $level) {
		$this->autoRender = false;
		if ($this->request->is('ajax')) {
			$mapData = ClassRegistry::init('HighCharts.HighChartsMap')->getJsonMap(array('code' => $code, 'level' => $level));
			return $mapData;
		}
	}

	public function genCSV($id) {
		$this->autoRender = false;
		$selectedOptions = $this->VisualizerData->getCSVOptionsSetup($this->Session->read('visualizer.visualization.' . $id));
		$data = $this->VisualizerData->find('all', $selectedOptions);
		$tableHeaders = array(__('Time Period'), __('Area ID'), __('Area Name'), __('Indicator'), __('Data Value'), __('Unit'), __('Dimension'), __('Source'));
		$fileName = 'Visualization_' . date("Y.m.d");
		$downloadedFile = $fileName . '.csv';

		ini_set('max_execution_time', 600);

		$csv_file = fopen('php://output', 'w');
		header('Content-type: application/csv');
		header('Content-Disposition: attachment; filename="' . $downloadedFile . '"');

		fputcsv($csv_file, $tableHeaders, ',', '"');

		foreach ($data as $dataRow) {
			$row = array();
			foreach ($dataRow as $col) {
				foreach ($col as $colValue) {
					$row[] = $colValue;
				}
			}
			fputcsv($csv_file, $row, ',', '"');
		}

		$this->addReportDate($csv_file);
		fclose($csv_file);
	}

	private function addReportDate($csv_file) {
		$footer = array(__("Report Generated") . ": " . date("Y-m-d H:i:s"));
		fputcsv($csv_file, array(), ',', '"');
		fputcsv($csv_file, $footer, ',', '"');
	}

	private function configSortData(&$sortDirection, &$sortType, &$sortCol) {
		if ($this->Session->check('visualizer.sort')) {
			$sortDirection = $this->Session->read('visualizer.sort.direction');
			$sortType = ( $sortDirection == 'up') ? 'ASC' : 'DESC';
			$sortCol = $this->Session->read('visualizer.sort.col');
		}
		return sprintf('%s %s', $sortCol, $sortType);
	}

}
