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
App::uses('DateTimeComponent', 'Controller/Component');
App::uses('AreaHandlerComponent', 'Controller/Component');
App::uses('Areas', 'Controller/Component');
App::uses('String', 'Utility');

class UtilityHelper extends AppHelper {
	public $alertType = array('error' => 0, 'ok' => 1, 'info' => 2, 'warn' => 3);
	
	public function ellipsis($string, $length = '30') {
		return String::truncate($string, $length, array('ellipsis' => '...', 'exact' => false));
	}
	
    public function highlight($needle, $haystack){
		$ind = stripos($haystack, $needle);
		$len = strlen($needle);
		if($ind !== false){
			return substr($haystack, 0, $ind) . "<span class=\"highlight\">" . substr($haystack, $ind, $len) . "</span>" .
				$this->highlight($needle, substr($haystack, $ind + $len));
		} else return $haystack;
	}
	
	public function getMessage($code) {
		$utility = new UtilityComponent(new ComponentCollection);
		return $utility->getMessage($code);
	}
	
	// For Search Pagination
	public function getPageOptions() {
		return array('escape' => false, 'style' => 'display:none');
	}
	
	public function getPageNumberOptions() {
		return array(
			'modulus' => 5,
			'first' => 2,
			'last' => 2,
			'tag' => 'li', 
			'separator' => '',
			'ellipsis' => '<li><span class="ellipsis">...</span></li>'
		);
	}
	// End Pagination
	
	public function getYearList($form, $id, $config=array()){
		$utility = new UtilityComponent(new ComponentCollection);
		$year = DateTimeComponent::generateYear();
		if(isset($config['desc'])){
			krsort($year);
		}
		$defaultYear = '';
		$config = array_merge(array('options' => $year), $config);
		$yearSelect = $form->input($id, $config);
		return $yearSelect;
	}
	
	public function generateYearList($sort='asc') {
		$utility = new UtilityComponent(new ComponentCollection);
		$list = DateTimeComponent::generateYear();
		if($sort==='desc') {
			krsort($list);
		}
		return $list;
	}

	public function showArea($form,$id,$value,$settings=array()){
        $arrmap = array('Area','AreaLevel');
        $arealevelfk = 'area_level';
        if($id=='area_education_id'){
            $arrmap = array('AreaEducation','AreaEducationLevel');
            $arealevelfk = 'area_education_level';
        }

		$this->AreaHandler = new AreaHandlerComponent(new ComponentCollection);
        if (!is_numeric($value) || !isset($value) || !($this->AreaHandler->checkAreaExist($value,$arrmap)>0)) {
            $value=$this->AreaHandler->getTopArea($arrmap);
        }
		$this->fieldAreaLevels = array_reverse($this->AreaHandler->getAreatoParent($value,$arrmap));
		$this->fieldLevels = $this->AreaHandler->getAreaList($arrmap);

		$ctr = 0;
		
		foreach($this->fieldLevels as $levelid => $levelName){
			$areaVal = array('id'=>'0','name'=>'a');
			foreach($this->fieldAreaLevels as $arealevelid => $arrval){
				if($arrval['level_id'] == $levelid) {
					$areaVal = $arrval;
					continue;
				}
			}
            if($areaVal['name']!='a'){
                echo '<div class="row">
                            <div class="label">'.$levelName.'</div>
                            <div class="value" value="'.$areaVal['id'].'" name="'.$arealevelfk.'_'.$ctr.'" type="select">'.($areaVal['name']=='a'?'':$areaVal['name']).'</div>
                        </div>';
            }
			$ctr++;
		}
	}

    public function showAreaHistory($form,$id,$settings=array(),$orgValue,$arrRec){
        $arrmap = array('Area','AreaLevel');
        $arealevelfk = 'area_level';
        if($id=='area_education_id'){
            $arrmap = array('AreaEducation','AreaEducationLevel');
            $arealevelfk = 'area_education_level';
        }

        $this->AreaHandler = new AreaHandlerComponent(new ComponentCollection);
        $this->fieldLevels = $this->AreaHandler->getAreaList($arrmap);

        $ctr = 0;

        $val = '';
        foreach($this->fieldLevels as $levelid => $levelName){
            if (!is_numeric($orgValue) || !isset($orgValue) || !($this->AreaHandler->checkAreaExist($orgValue, $arrmap)>0)) {
                $orgValue=$this->AreaHandler->getTopArea();
            }
            $this->fieldAreaLevels = array_reverse($this->AreaHandler->getAreatoParent($orgValue, $arrmap));
            foreach($this->fieldAreaLevels as $arealevelid => $arrval){
                if($arrval['level_id'] == $levelid) {
                    $areaVal = $arrval;
                    if(($areaVal['name']=='a'?'':$areaVal['name'])!=''){
                        $val .= ($areaVal['name']=='a'?'':$areaVal['name']).' ('.$levelName.') '.',';
                    }
                    continue;
                }
            }
        }

        echo '<div class="row">
					<div class="label">&nbsp;&nbsp;</div>
					<div class="value" style="width:510px; float: right;"><span>'.str_replace(',',' &rarr; ',rtrim($val,',')).'</span>';
        echo '<div class="table" style="width:510px; float: right;">
							<div class="table_body">';
		$myCompVal = str_replace(',',' &rarr; ',rtrim($val,','));
        foreach($arrRec as $value => $time){
            if($value>0){
                $myVal = '';
                foreach($this->fieldLevels as $levelid => $levelName){
                    if (!is_numeric($value) || !isset($value) ) {$value=0;}
                    $this->fieldAreaLevels = array_reverse($this->AreaHandler->getAreatoParent($value, $arrmap));
                    foreach($this->fieldAreaLevels as $arealevelid => $arrval){
                        if($arrval['level_id'] == $levelid) {
                            $areaVal = $arrval;
                            if(($areaVal['name']=='a'?'':$areaVal['name'])!=''){
                                $myVal .= ($areaVal['name']=='a'?'':$areaVal['name']).' ('.$levelName.') '.',';
                            }
                            continue;
                        }
                    }
                }
				$thisVal = str_replace(',',' &rarr; ',rtrim($myVal,','));
				if($thisVal!=$myCompVal){
					echo '<div class="table_row"><div class="table_cell cell_value">'.$thisVal.'</div>
						  <div class="table_cell cell_datetime">'.$time.'</div>
						  </div>';
					$myCompVal = $thisVal;
				}
            }
        }
        echo '</div></div></div></div>';
    }
	
	public function getAreaPicker($form,$id,$value,$settings=array(), $filter = array()){
        switch($id){
            case 'area_education_id':
                $arrmap = array('AreaEducation','AreaEducationLevel');
                $arealevelfk = 'area_education_level';
                break;

            default:
                $arrmap = array('Area','AreaLevel');
                $arealevelfk = 'area_level';
                break;
        }


		$this->AreaHandler = new AreaHandlerComponent(new ComponentCollection);

		if (!is_numeric($value) || !isset($value) || !($this->AreaHandler->checkAreaExist($value,$arrmap)>0)) {
            $value=$this->AreaHandler->getTopArea($arrmap);
        }

        // Get the filter if needed
        $filterArr = array();
        if(count($filter)>0) {
            foreach($filter as $val){
                foreach($this->AreaHandler->getAreaPaths($val["area_id"]) as $key => $innerArray) {
                    if(!in_array($innerArray['Area']['id'], $filterArr["Area"][$innerArray['Area']['area_level_id']]['id'])){
                        $filterArr["Area"][$innerArray['Area']['area_level_id']]['id'][] = $innerArray['Area']['id'];
                    }
                }
            }
            $filterResult = $filterArr;
            $lowestId = "";
            foreach($filter as $val){
                if($lowestId==""){
                    $lowestId = $val["area_level_id"];
                }
                if($val["area_level_id"]<$lowestId){
                    $lowestId = $val["area_level_id"];
                }
            }
        }else{
            $filterResult = "false";
        }

        if($arrmap[0]=="Area"){
            $_SESSION['filterArr'] = $filterResult;
            $_SESSION['lowestFilter'] = $lowestId;
        }

		$this->fieldAreaLevels = array_reverse($this->AreaHandler->getAreatoParent($value,$arrmap));
		$this->fieldLevels = $this->AreaHandler->getAreaList($arrmap);
        $this->fieldAreadropdowns = $this->AreaHandler->getAllSiteAreaToParent($value,$arrmap);

		$ctr = 0;

		foreach($this->fieldLevels as $levelid => $levelName){
			//pr($this->fieldAreaLevels[$ctr]['id'] . '|');
            $mylevel = $this->AreaHandler->getAreaLevel($this->fieldAreaLevels[$ctr]['id'],$arrmap);
			

            $display = '';
            if($mylevel===''){
                $display = 'display:none;';
            }
            echo '<div class="form-group row" style="'.$display.'">
                    <label class="col-md-3 control-label">'.$mylevel.'</label>
                    '. $form->input($arealevelfk.'_'.$ctr,
                                                        array('class'=>'areapicker default form-control',
                                                        'style'=>'float:left;',
														'div' => false,
														'label' => false,
                                                        'default'=>@$this->fieldAreaLevels[$ctr]['id'],
                                                        'options'=>$this->fieldAreadropdowns[$arealevelfk.'_'.$ctr]['options']));
            if ($ctr==0){
                echo $form->input($id,array('class'=>'areapicker_areaid','div' => false, 'label' => false,'type'=>'text','style'=>'display:none','value' => $value));
            }
            echo		'
                </div>';
			$ctr++;
		}
    }

	public function getDatePicker($form, $id, $settings=array()) {
		$_settings = array(
			'order' => 'dmy',
			'desc' => true,
			'glue' => "\n<span>-</span>\n",
			'yearRange' => array(),
			'yearAdjust' => 0,
			'emptySelect' => false,
			'endDateValidation' => ''
		);
		$_settings = array_merge($_settings, $settings);
		
		$wrapper = '<div class="datepicker" id="%s">%s</div>%s';
		
		$onChange = 'jsDate.updateDay(this);';
		if(strlen($_settings['endDateValidation']) > 0) {
			$wrapper = sprintf('<div class="datepicker" id="%%s" start="#%s" end="#%s">%%s</div>%%s', $id, $_settings['endDateValidation']);
			$onChange = 'jsDate.validateEndDate(this);' . $onChange;
		}
		
		$utility = new UtilityComponent(new ComponentCollection);
		
		$day = DateTimeComponent::generateDay();
		$month = DateTimeComponent::generateMonth();
		$year = DateTimeComponent::generateYear($_settings['yearRange']);
		
		if($_settings['yearAdjust']>0) {
			$yearLast = end($year);
			for($i=0; $i<$_settings['yearAdjust']; $i++) {
				$year[++$yearLast] = $yearLast;
			}
		} else if($_settings['yearAdjust']<0) {
			for($i=0; $i>$_settings['yearAdjust']; $i--) {
				array_pop($year);
			}
		}
		
		if(isset($_settings['desc'])) {
			krsort($year);
		}
		
		$defaultDay = 0;
		$defaultMonth = 0;
		$defaultYear = 0;
		
		if($_settings['emptySelect']) {
			$utility->unshiftArray($day, array('0' => __('Day')));
			$utility->unshiftArray($month, array('0' => __('Month')));
			$utility->unshiftArray($year, array('0' => __('Year')));
		}
		
		$dateOptions = array('class' => 'datepicker_date', 'type' => 'text', 'div' => false, 'label' => false);
		if(isset($_settings['name'])) {
			$dateOptions['name'] = $_settings['name'];
		}
		$dateValue = '';
		if(isset($_settings['value']) && !empty($_settings['value'])) {
			$dateValue = $_settings['value'];
		} else {
			if(!$_settings['emptySelect']) {
				$dateValue = date('Y-m-d');
			} else {
				$dateValue = '0000-00-00';
			}
		}
		$dateOptions['value'] = $dateValue;
		$dateOptions['default'] = $dateValue;
		$date = explode(' ', $dateOptions['value']);
		$dateParams = explode('-', $date[0]);
		list($defaultYear, $defaultMonth, $defaultDay) = $dateParams;
		if(isset($_settings['class'])) {
			$dateOptions['class'] = $dateOptions['class'] . ' ' . $_settings['class'];
		}
		$dateHidden = $form->input($id, $dateOptions);
		
		$selectOpts = array(
			'name' => '',
			'type' => 'select',
			'autocomplete' => 'off',
			'div' => false,
			'label' => false,
			'onchange' => $onChange
		);
		
		$daySelect = $form->input($id.'_day', array_merge($selectOpts, 
			array('class' => 'datepicker_day', 'options' => $day, 'default' => $defaultDay)
		));
			
		$monthSelect =  $form->input($id.'_month', array_merge($selectOpts, 
			array('class' => 'datepicker_month', 'options' => $month, 'default' => $defaultMonth)
		));
			
		$yearSelect =  $form->input($id.'_year', array_merge($selectOpts, 
			array('class' => 'datepicker_year', 'options' => $year, 'default' => $defaultYear)
		));
		
		$order = $_settings['order'];
		$dateSelect = array();
		for($i=0; $i<strlen($order); $i++) {
			if(strcmp($order[$i], 'd')==0) {
				$dateSelect[] = $daySelect;
			} else if(strcmp($order[$i], 'm')==0) {
				$dateSelect[] = $monthSelect;
			} else {
				$dateSelect[] = $yearSelect;
			}
		}
		
		$select = sprintf($wrapper, $id, implode($_settings['glue'], $dateSelect), $dateHidden);
		return $select;
	}
	
	public function getDatePickerNew($form, $id, $settings=array()) {
		$_settings = array(
			'order' => 'dmy',
			'desc' => true,
			'glue' => "\n<span>-</span>\n",
			'yearRange' => array(),
			'yearAdjust' => 0,
			'emptySelect' => false,
			'endDateValidation' => ''
		);
		$_settings = array_merge($_settings, $settings);
		
		$wrapper = '<div class="datepicker" id="%s">%s</div>%s';
		
		$onChange = 'jsDate.updateDay(this);';
		if(strlen($_settings['endDateValidation']) > 0) {
			$wrapper = sprintf('<div class="datepicker" id="%%s" start="#%s" end="#%s">%%s</div>%%s', $id, $_settings['endDateValidation']);
			$onChange = 'jsDate.validateEndDate(this);' . $onChange;
		}
		
		$utility = new UtilityComponent(new ComponentCollection);
		
		$day = DateTimeComponent::generateDay();
		$month = DateTimeComponent::generateMonth();
		$year = DateTimeComponent::generateYear($_settings['yearRange']);
		
		if($_settings['yearAdjust']>0) {
			$yearLast = end($year);
			for($i=0; $i<$_settings['yearAdjust']; $i++) {
				$year[++$yearLast] = $yearLast;
			}
		} else if($_settings['yearAdjust']<0) {
			for($i=0; $i>$_settings['yearAdjust']; $i--) {
				array_pop($year);
			}
		}
		
		if(isset($_settings['desc'])) {
			krsort($year);
		}
		
		$defaultDay = 0;
		$defaultMonth = 0;
		$defaultYear = 0;
		
		if($_settings['emptySelect']) {
			$utility->unshiftArray($day, array('0' => __('Day')));
			$utility->unshiftArray($month, array('0' => __('Month')));
			$utility->unshiftArray($year, array('0' => __('Year')));
		}
		
		$dateOptions = array('class' => 'datepicker_date', 'type' => 'text', 'div' => false, 'label' => false);
		if(isset($_settings['name'])) {
			$dateOptions['name'] = $_settings['name'];
		}
		$dateValue = '';
		if(isset($_settings['value']) && !empty($_settings['value'])) {
			$dateValue = $_settings['value'];
		} else {
			if(!$_settings['emptySelect']) {
				$dateValue = date('Y-m-d');
			} else {
				$dateValue = '0000-00-00';
			}
		}
		$dateOptions['value'] = $dateValue;
		$dateOptions['default'] = $dateValue;
		$date = explode(' ', $dateOptions['value']);
		$dateParams = explode('-', $date[0]);
		list($defaultYear, $defaultMonth, $defaultDay) = $dateParams;
		if(isset($_settings['class'])) {
			$dateOptions['class'] = $dateOptions['class'] . ' ' . $_settings['class'];
		}
		$hiddenOptions = $dateOptions;
		$hiddenOptions['type'] = 'hidden';
		$dateHidden = $form->input($id, $hiddenOptions);
		
		$selectOpts = array(
			'name' => '',
			'type' => 'select',
			'autocomplete' => 'off',
			'div' => false,
			'label' => false,
			'onchange' => $onChange
		);
		
		$daySelect = $form->input($id.'_day', array_merge($selectOpts, 
			array('class' => 'datepicker_day', 'options' => $day, 'default' => $defaultDay)
		));
			
		$monthSelect =  $form->input($id.'_month', array_merge($selectOpts, 
			array('class' => 'datepicker_month', 'options' => $month, 'default' => $defaultMonth)
		));
			
		$yearSelect =  $form->input($id.'_year', array_merge($selectOpts, 
			array('class' => 'datepicker_year', 'options' => $year, 'default' => $defaultYear)
		));
		
		$order = $_settings['order'];
		$dateSelect = array();
		for($i=0; $i<strlen($order); $i++) {
			if(strcmp($order[$i], 'd')==0) {
				$dateSelect[] = $daySelect;
			} else if(strcmp($order[$i], 'm')==0) {
				$dateSelect[] = $monthSelect;
			} else {
				$dateSelect[] = $yearSelect;
			}
		}
		
		$select = sprintf($wrapper, $id, implode($_settings['glue'], $dateSelect), $dateHidden);
		return $select;
	}
	
	/**
	 * Formatting input date based on Config Setting on view
	 * @param  string $date   [input date]
	 * @param  string $format [leave null to get from config setting]
	 * @return string         [formatted date]
	 */
	public function formatDate($date, $format=null, $echo=true) {
		if (is_null($format)) {
			$format = DateTimeComponent::getConfigDateFormat();
		}
		$output = null;
		if($date == '0000-00-00' || $date == ''){ 
			$output = '';
		}else{
			$date = new DateTime($date);
			$output = $date->format($format);
		}
		if($echo) {
			echo $output;
		} else {
			return $output;
		}
	}

	public function formatGender($value) {
		return ($value == 'F') ? __('Female') : __('Male');
	}
	
	public function formatBoolean($value){
		return ($value == 1) ? __('Yes'):__('No');	
	}
	
	public function getListStart() {
		echo '<ul class="quicksand table_view">';
	}
	
	public function getListEnd() {
		echo '</ul>';
	}
	
	public function getListRowStart($dataId, $isVisible) {
		$class = $isVisible ? '' : 'inactive';
		echo sprintf('<li data-id="%s" class="%s">', $dataId, $class);
	}
	
	public function getListRowEnd() {
		echo '</li>';
	}
	
	public function getIdInput($form, $fieldName, $value) {
		return $form->hidden('id', array('name' => sprintf($fieldName, 'id'), 'value' => $value));
	}
	
	public function getOrderInput($form, $fieldName, $value) {
		return $form->hidden('order', array('id' => 'order', 'name' => sprintf($fieldName, 'order'), 'value' => $value));
	}
	
	public function getOrderControls() {
		$html = '
			<div class="cell cell_order">
				<span class="icon_up" onclick="jsList.doSort(this)"></span>
				<span class="icon_down" onclick="jsList.doSort(this)"></span>
			</div>';
		return $html;
	}
	
	public function getDeleteControl($options = array()) {
		$_options = array(
			'class' => 'icon_delete',
			'title' => __('Delete'),
			'onclick' => 'jsTable.doRemove(this);',
			'onDelete' => 'before'
		);
		
		if(isset($options['onDelete'])) {
			$_options['onDelete'] = $options['onDelete'];
		}
		
		if(isset($options['class'])) {
			$_options['class'] = $_options['class'] . ' ' . $options['class'];
			unset($options['class']);
		}
		if(isset($options['onclick'])) {
			if($_options['onDelete'] !== false) {
				if($_options['onDelete']==='after') {
					$_options['onclick'] = $options['onclick'] . ';' . $_options['onclick'];
				} else if($_options['onDelete']==='before') {
					$_options['onclick'] = $_options['onclick'] . $options['onclick'];
				}
			} else {
				$_options['onclick'] = $options['onclick'];
			}
			unset($options['onclick']);
		}
		unset($_options['onDelete']);
		
		$_options = array_merge($_options, $options);
		$html = '<span %s></span>';
		$attr = array();
		foreach($_options as $name => $val) {
			$attr[] = sprintf('%s="%s"', $name, $val);
		}
		return sprintf($html, implode(' ', $attr));
	}
	
	public function getVisibleInput($form, $fieldName, $isVisible, $label=false) {
		$options = array(
			'name' => sprintf($fieldName, 'visible'),
			'type' => 'checkbox',
			'value' => 1,
			'autocomplete' => 'off',
			'onchange' => 'jsList.activate(this)',
			'before' => '<div class="cell cell_visible">',
			'after' => '</div>'
		);
		
		if($isVisible) {
			$options['checked'] = 'checked';
		}
		if($label) {
			$options['label'] = false;
			$options['div'] = false;
		}
		$input = $form->input('visible', $options);
		return $input;
	}
	
	public function getNameInput($form, $fieldName, $value, $editable=true) {
		$options = array(
			'name' => sprintf($fieldName, 'name'),
			'type' => 'text',
			'value' => $value,
			'before' => '<div class="cell cell_name"><div class="input_wrapper">',
			'after' => '</div></div>',
			'maxlength' => '50'
		);
		$text = '<div class="cell cell_name"><span>%s</span></div>';
		$input = $editable ? $form->input('name', $options) : sprintf($text, $value);
		return $input;
	}
	
	public function getTextInput($form, $fieldName, $value, $name, $length, $editable=true) {
		$options = array(
			'name' => sprintf($fieldName, $name),
			'type' => 'text',
			'value' => $value,
			'before' => '<div class="cell cell_'.$name.'"><div class="input_wrapper">',
			'after' => '</div></div>',
			'maxlength' => $length
		);
		$text = '<div class="cell cell_name"><span>%s</span></div>';
		$input = $editable ? $form->input('name', $options) : sprintf($text, $value);
		return $input;
	}
	
	public function getAddRow($caption) {
		return sprintf('<div class="row"><a class="void icon_plus">%s %s</a></div>', __('Add'), __($caption));
	}
	
	public function checkOrCrossMarker($flag) {
		return $flag ? '<span class="green">&#10003;</span>' : '<span class="red">&#10008;</span>';
	}
	
	public function getStatus($status) {
		return $status ? '<span class="green">'.__('Active').'</span>' : __('Inactive');
	}
	
	public function getVisibleOptions() {
		return array(1 => __('Yes'), 0 => __('No'));
	}
	
	public function getVisible($flag) {
		$option = $this->getVisibleOptions();
		return $option[$flag];
	}
	
	// for permissions	
	public function getPermissionInput($form, $fieldName, $type, $value) {
		$options = array(
			'id' => $type,
			'name' => sprintf($fieldName, $type),
			'type' => 'checkbox',
			'value' => 1,
			'autocomplete' => 'off',
			'before' => '<div class="table_cell center">',
			'after' => '</div>'
		);
		
		if(is_null($value)) {
			$options['disabled'] = 'disabled';
		} else {
			if($value == 1) {
				$options['checked'] = 'checked';
			} else if($value == 2) {
				$options['checked'] = 'checked';
				$options['disabled'] = 'disabled';
			}
		}
		
		$input = $form->input($type, $options);
		return $input;
	}
	// end permissions
}