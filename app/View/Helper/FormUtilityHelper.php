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
App::uses('AreaHandlerComponent', 'Controller/Component');

class FormUtilityHelper extends AppHelper {
	public $helpers = array('Html', 'Form', 'Label');
	
	public function getFormOptions($url=array(), $type='') {
		if(!isset($url['controller'])) {
			$url['plugin'] = false;
			$url['controller'] = $this->_View->params['controller'];
		}
		$options = array(
			'url' => $url,
			'class' => 'form-horizontal',
			'novalidate' => true,
			'inputDefaults' => $this->getFormDefaults(),
			'type'=>$type
		);
		return $options;
	}
	
	public function getFormDefaults() {
		$defaults = array(
			'div' => 'form-group',
			'label' => array('class' => 'col-md-3 control-label'),
			'between' => '<div class="col-md-4">',
			'after' => '</div>',
			'class' => 'form-control'
		);
		return $defaults;
	}
	
	public function getFormButtons($options = NULL) {
		$html = '';
		$cancelURL = $options['cancelURL'];
		$center = isset($options['center']) ? $options['center'] : false;
		$html .= '<div class="form-group">';
		$html .= '<div class="col-md-offset-' . ($center ? '5' : '4') . '">';
		if (array_key_exists('reloadBtn', $options) && $options['reloadBtn'] == true) {
			$html .= $this->Form->button('reload', array('id' => 'reload', 'name' => 'submit', 'class' => 'none'));
		}
		$html .= $this->Form->submit($this->Label->get('general.save'), array('name' => 'submit', 'class' => 'btn_save btn_right', 'div' => false));
		$html .= $this->Html->link($this->Label->get('general.cancel'), $cancelURL, array('class' => 'btn_cancel btn_left'));
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}
	
	public function getLabelOptions() {
		$formOptions = $this->getFormDefaults();
		return $formOptions['label'];
	}
	
	public function getWizardButtons($buttons) {
		$html = '<div class="form-group form-buttons">';
		$html .= '<div class="col-md-offset-4">';
		foreach($buttons as $btn) {
			$html .= $this->Form->submit($btn['name'], $btn['options']);
		}
		$html .= '</div>';
		$html .= '</div>';

		$html .= $this->Form->button('reload', array('id' => 'reload', 'name' => 'submit', 'class' => 'none'));

		return $html;
	}
	
	public function getPermissionInput($form, $fieldName, $type, $value) {
		$options = array(
			'id' => $type,
			'name' => sprintf($fieldName, $type),
			'type' => 'checkbox',
			'value' => 1,
			'autocomplete' => 'off',
			'before' => '<td class="center">',
			'after' => '</td>'
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
	
	public function datepicker($field, $options=array()) {
		$dateFormat = 'dd-mm-yyyy';
		$icon = '<span class="input-group-addon"><i class="fa fa-calendar"></i></span></div>';
		$_options = array(
			'id' => 'date',
			'data-date-format' => $dateFormat,
			'data-date-autoclose' => 'true',
			'label' => false,
			'disabled' => false
		);
		$label = isset($options['label']) ? $options['label'] : $_options['label'];
		unset($_options['label']);
		$disabled = isset($options['disabled']) ? $options['disabled'] : $_options['disabled'];
		unset($_options['disabled']);
		$wrapper = $this->Html->div('input-group date', null, $_options);
		if(!empty($options)) {
			$_options = array_merge($_options, $options);
		}
		$defaults = $this->Form->inputDefaults();
		$inputOptions = array(
			'id' => $_options['id'],
			'type' => 'text',
			'between' => $defaults['between'] . $wrapper,
			'after' => $icon . $defaults['after'],
			'value' => (isset($_options['data-date']) && $_options['data-date']!='')?$_options['data-date']:date('d-m-Y')
		);
		$inputOptions = array_merge($_options, $inputOptions);

		if($label !== false) {
			$inputOptions['label'] = array('text' => $label, 'class' => $defaults['label']['class']);
		}
		if($disabled !== false) {
			$inputOptions['disabled'] = $disabled;
		}

		$html = $this->Form->input($field, $inputOptions);
		
		$_datepickerOptions = array();
		$_datepickerOptions['id'] = $_options['id'];
		if(!empty($_options['startDate'])){
			$_datepickerOptions['startDate'] = $_options['startDate'];
		}
		if(!empty($_options['endDate'])){
			$_datepickerOptions['endDate'] = $_options['endDate'];
		}
		if($disabled !== false) {
			$_datepickerOptions['disabled'] = $disabled;
		}
		if (array_key_exists('dateOptions', $_options)) {
			$_datepickerOptions = array_merge($_datepickerOptions, $options['dateOptions']);
		}
		if(!is_null($this->_View->get('datepicker'))) {
			$datepickers = $this->_View->get('datepicker');
			$datepickers[] = $_datepickerOptions;
			$this->_View->set('datepicker', $datepickers);
		} else {
			$this->_View->set('datepicker', array($_datepickerOptions));
		}
		return $html;
	}
	
	public function timepicker($field, $options=array()) {
		$id = isset($options['id']) ? $options['id'] : 'time';
		$wrapper = '<div class="input-group bootstrap-timepicker">';
		$icon = '<span class="input-group-addon"><i class="fa fa-clock-o"></i></span></div>';
		$defaults = $this->Form->inputDefaults();
		if(!empty($options['label'])) {
			$options['label'] = array('text' => $options['label'], 'class' => $defaults['label']['class']);
		}
		$inputOptions = array(
			'id' => $id,
			'type' => 'text',
			'between' => $defaults['between'] . $wrapper,
			'after' => $icon . $defaults['after']
		);

		$inputOptions = array_merge($inputOptions, $options);
		$html = $this->Form->input($field, $inputOptions);
		
		$fields = array('id' => 'id', 'value' => 'defaultTime');
		$_timepickerOptions = array();
		foreach ($fields as $key => $field) {
			if (isset($inputOptions[$key])) {
				$_timepickerOptions[$field] = $inputOptions[$key];
			}
		}
		
		if(!is_null($this->_View->get('timepicker'))) {
			$timepickers = $this->_View->get('timepicker');
			$timepickers[] = $_timepickerOptions;
			$this->_View->set('timepicker', $timepickers);
		} else {
			$this->_View->set('timepicker', array($_timepickerOptions));
		}
		return $html;
	}
	
	public function areapicker($field, $options=array()) {
		$_options = array(
			'id' => 'areapicker',
			'controller' => Inflector::singularize($this->request->controller),
			'model' => 'Area',
			'div' => true
		);

		$inputDefaults = $this->getFormDefaults();
		$inputDefaults['autocomplete'] = 'off';
		$inputDefaults['onchange'] = 'Area.reloadDiv(this)';
		$levelModels = array('Area' => 'AreaLevel', 'AreaAdministrative' => 'AreaAdministrativeLevel');
		$_options = array_merge($_options, $options);

		$controller = $_options['controller'];
		$model = $_options['model'];
		$AreaHandler = new AreaHandlerComponent(new ComponentCollection);
		$value = isset($_options['value']) && $_options['value'] != false ? $_options['value'] : $AreaHandler->getDefaultAreaId($model);
		
		$html = '';
		$worldId = $AreaHandler->{$model}->field($model.'.id', array($model.'.parent_id' => -1));
		$path = !is_null($value) ? $AreaHandler->{$model}->getPath($value) : $AreaHandler->{$model}->findAllById($worldId);
		if(empty($path)) {	//handle situation where got $value but area record deleted
			$path = $AreaHandler->{$model}->findAllById($worldId);
		}
		$inputOptions = $inputDefaults;
		if (!empty($path)) {
			foreach($path as $i => $obj) {
				$options = $AreaHandler->getChildren(array('model'=>$model, 'parentId'=>$obj[$model]['parent_id'], 'dataType'=>'list'));
				if($obj[$model]['parent_id'] > 0 && !($model == 'AreaAdministrative' && $obj[$model]['parent_id'] == $worldId)) {
					$options = array(
						$obj[$model]['parent_id'] => $this->Label->get('Area.select')
					) + $options;
				}
				$foreignKey = Inflector::underscore($levelModels[$model]).'_id';
				$levelName = $AreaHandler->{$levelModels[$model]}->field('name', array('id' => $obj[$model][$foreignKey]));

				if(count($path) != 1) {
					$inputOptions['default'] = $obj[$model]['id'];
					$inputOptions['value'] = $obj[$model]['id'];
				} else {
					$inputOptions['default'] = $worldId;
					$inputOptions['value'] = $worldId;
				}
				$inputOptions['options'] = $options;
				$inputOptions['label']['text'] = $levelName;
				if($model == 'AreaAdministrative' && $obj[$model]['parent_id'] == -1) {	//hide World
					continue;
				}
				$html .= $this->Form->input($i==0 ? $field.'_select' : $levelName, $inputOptions);
			}
		}
		
		$area = end($path);
		$level = $AreaHandler->{$levelModels[$model]}->field('level', array('id' => $area[$model][$foreignKey]));
		$levelOptions = array();
		$levelOptions['limit'] = 1;
		$levelOptions['order'] = $levelModels[$model].'.level';
		if($model == 'Area') {
			$levelOptions['conditions'] = array(
				$levelModels[$model].'.level >' => $level
			);
		} else if($model == 'AreaAdministrative'){
			$parentId = $area[$model]['id'];
			$areaAdministrativeId = $AreaHandler->{$levelModels[$model]}->field(Inflector::underscore($model).'_id', array('id' => $area[$model][Inflector::underscore($levelModels[$model]).'_id']));
			$areaAdministrativeId = $level < 1 ? $parentId : $areaAdministrativeId;	//-1 => World, 0 => Country
			$levelOptions['conditions'] = array(
				$levelModels[$model].'.level >' => $level,
				$levelModels[$model].'.'.Inflector::underscore($model).'_id' => $areaAdministrativeId
			);
		}
		$levels = $AreaHandler->{$levelModels[$model]}->find('list', $levelOptions);

		foreach($levels as $id => $name) {
			$inputOptions = $inputDefaults;
			$inputOptions['options'] = array();
			$inputOptions['disabled'] = 'disabled';
			$html .= $this->Form->input($name, $inputOptions);
		}
		
		$url = 'Areas/ajaxGetAreaOptions/' . $model . '/';
		$urlReload = 'Areas/ajaxReloadAreaDiv/' . $model . '/' . $controller . '/' . $field . '/';
		$html .= $this->Form->hidden($field, array('value' => $value));
		if($_options['div']) {
			$html = $this->Html->div('areapicker', $html, array('id' => $_options['id'], 'value' => $worldId, 'url' => $url, 'urlReload' => $urlReload));
		}

		return $html;
	}
	
	public function areas($value, $model='Area') {
		$levelModels = array('Area' => 'AreaLevel', 'AreaAdministrative' => 'AreaAdministrativeLevel');
		$foreignKey = Inflector::underscore($levelModels[$model]).'_id';
		
		$html = '';
		$row = '<div class="row">%s</div>';
		$labelCol = '<div class="col-md-3">%s</div>';
		$valueCol = '<div class="col-md-6">%s</div>';
		
		$AreaHandler = new AreaHandlerComponent(new ComponentCollection);
		$path = $AreaHandler->{$model}->getPath($value);
		if (!empty($path)) {
			foreach($path as $i => $obj) {
				if($model == 'AreaAdministrative' && $obj[$model]['parent_id'] == -1) {
					continue;
				}
				$levelName = $AreaHandler->{$levelModels[$model]}->field('name', array('id' => $obj[$model][$foreignKey]));
				$html .= sprintf($row, sprintf($labelCol, $levelName) . sprintf($valueCol, $obj[$model]['name']));
			}
		}
		return $html;
	}
	
	public function getFormWizardButtons($option = NULL) {
		$btnOptions = array('div' => false, 'name' => 'submit', 'class' => 'btn_save btn_right');
		echo '<div class="form-group">';
		if (!$option['WizardMode']) {
			echo '<div class="col-md-offset-4">'.$this->getFormButtons(array('cancelURL' => $option['cancelURL'])).'</div>';
		} else {
			if(!isset($option['addMoreBtn']) || $option['addMoreBtn'] == true){
				echo '<div class="add_more_controls">' . $this->Form->submit($this->Label->get('wizard.addmore'), $btnOptions) . '</div><br/>';
			}
			
			echo '<div class="col-md-offset-4">';
			echo $this->Form->submit($this->Label->get('wizard.previous'), $btnOptions);
			if (!$option['WizardEnd']) {
				echo $this->Form->submit($this->Label->get('wizard.next'), $btnOptions);
			} else {
				echo $this->Form->submit($this->Label->get('wizard.finish'), $btnOptions);
			}
			if ($option['WizardMandatory'] != '1' && !$option['WizardEnd']) {
				$btnOptions['class'] = 'btn_cancel btn_cancel_button btn_left';
				echo $this->Form->submit($this->Label->get('wizard.skip'), $btnOptions);
			}
			echo '</div>';
		}
		echo '</div>';
	}
	
	public function getSourceClass($tag) {
		$classes = array(
			0 => 'row_dataentry',
			1 => 'row_external',
			2 => 'row_internal',
			3 => 'row_estimate'
		);
		return $classes[$tag];
	}
	
	public function getCheckbox($options = array()){
		$label = empty($options['label']['text'])? 'checkbox' : __($options['label']['text']);
		$clabelClass = empty($options['label']['class'])? array('col-md-4'):$options['label']['class'];
		$checkboxName = empty($options['checkbox']['name'])? 'chcekbox':$options['checkbox']['name'];
		
		$isCheck = (!empty($options['enabledChecked']) && $options['enabledChecked'])? 'checked' : '';
		$checkBoxSetting = array('class' => 'icheck-input', $isCheck);
		
		if($options['checkbox']['options']){
			$checkBoxSetting = array_merge($checkBoxSetting, $options['checkbox']['options']);
		}
		
		$checkBoxDiv = $this->Html->div('col-md-1', $this->Form->checkbox($checkboxName, $checkBoxSetting));
		$checkBoxDiv = $this->Html->div('col-md-offset-3',$checkBoxDiv.$this->Form->label('clabel', $label, $clabelClass));
		$checkBoxDiv = $this->Html->div('form-group',$checkBoxDiv);
		echo $checkBoxDiv;
	}
	
	public function isFieldVisible($attr, $type) {
		$visible = false;

		if (array_key_exists('visible', $attr)) {
			$visibleField = $attr['visible'];

			if (is_bool($visibleField)) {
				$visible = $visibleField;
			} else if (is_array($visibleField)) {
				if (array_key_exists($type, $visibleField)) {
					$visible = isset($visibleField[$type]) ? $visibleField[$type] : true;
				}
			}
		}
		return $visible;
	}
}