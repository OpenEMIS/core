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

class VisualizerHelper extends AppHelper {

	public $helpers = array('Html', 'Form', 'Label');

	public function getTableHeader($colArr, $sortCol, $sortDirection) {
		$tableHeaders = array();
		foreach ($colArr as $obj) {
			if (empty($obj['col'])) {
				$tableHeaders[] = __($obj['name']);
			} else {
				$headerColOrderOptions['class'] = 'icon_sort_up';
				if ($obj['col'] == $sortCol) {
					$headerColOrderOptions['class'] = 'icon_sort_' . $sortDirection;
				}
				$headerColOrderOptions['col'] = $obj['col'];

				$headerColOrderOptions['onclick'] = 'Visualizer.sortData(this)';
				$tableHeaders[] = __($obj['name']) . $this->Html->tag('span', NULL, $headerColOrderOptions);
			}
		}
		
		return $tableHeaders;
	}

	
	public function getTabIcon($tab){
		$icon = '';
		switch($tab){
			case 'indicator':
				$icon = 'fa-hand-o-up';
				break;
			case 'area':
				$icon = 'fa-map-marker';
				break;
			case 'time':
				$icon = 'fa-clock-o';
				break;
			case 'source':
				$icon = 'fa-book';
				break;
			case 'unit':
				$icon = 'fa-arrows-h';
				break;
			case 'dimension':
				$icon = 'fa-cube';
				break;
			case 'review':
				$icon = 'fa-check';
				break;
		}
		
		return $icon;
	}
	
	public function getVisualizarionTabIcon($tab){
		$icon = '';
		switch($tab){
			case 'table':
				$icon = 'fa fa-table';
				break;
			case 'column':
			case 'column-stack':
				$icon = 'icon-bars';
				break;
			case 'bar':
			case 'bar-stack':
				$icon = 'icon-bars rotate-left';
				break;
			case 'line':
				$icon = 'icon-stats';
				break;
			case 'area':
				$icon = 'icon-stats-3';
				break;
			case 'pie':
				$icon = 'icon-pie';
				break;
			case 'scatter':
				$icon = 'icon-stats';
				break;
			case 'map':
				$icon = 'icon-compass';
				break;
		}
		
		return $icon;
	}
}