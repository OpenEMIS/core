<?php
App::uses('AppHelper', 'View/Helper');
App::uses('DateTimeComponent', 'Controller/Component');
App::uses('String', 'Utility');

class UtilityHelper extends AppHelper {
	
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
		$utility->unshiftArray($year, array('' => 'Year'));
		$config = array_merge(array('options' => $year), $config);
		$yearSelect = $form->input($id, $config);
		return $yearSelect;
	}
	
	public function getDatePicker($form, $id, $settings=array()) {
		$wrapper = '<div class="datepicker">%s</div>%s';
		$_settings = array(
			'order' => 'dmy',
			'desc' => true,
			'glue' => "\n<span>-</span>\n",
			'yearRange' => array()
		);
		$_settings = array_merge($_settings, $settings);
		
		$utility = new UtilityComponent(new ComponentCollection);
		
		$day = DateTimeComponent::generateDay();
		$month = DateTimeComponent::generateMonth();
		$year = DateTimeComponent::generateYear($_settings['yearRange']);
		
		if(isset($settings['desc'])) {
			krsort($year);
		}
		
		$defaultDay = 0;
		$defaultMonth = 0;
		$defaultYear = 0;
		
		$utility->unshiftArray($day, array('0' => __('Day')));
		$utility->unshiftArray($month, array('0' => __('Month')));
		$utility->unshiftArray($year, array('0' => __('Year')));
		
		$dateOptions = array('class' => 'datepicker_date', 'type' => 'text', 'div' => false, 'label' => false);
		if(isset($_settings['name'])) {
			$dateOptions['name'] = $_settings['name'];
		}
		if(isset($_settings['value'])) {
			$dateOptions['value'] = $_settings['value'];
			$date = explode(' ', $dateOptions['value']);
			$dateParams = explode('-', $date[0]);
			list($defaultYear, $defaultMonth, $defaultDay) = $dateParams;
		}
		$dateHidden = $form->input($id, $dateOptions);
		
		$selectOpts = array(
			'name' => '',
			'type' => 'select',
			'autocomplete' => 'off',
			'div' => false,
			'label' => false
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
		
		$select = sprintf($wrapper, implode($_settings['glue'], $dateSelect), $dateHidden);
		return $select;
	}
	
	/**
	 * Formatting input date based on Config Setting on view
	 * @param  string $date   [input date]
	 * @param  string $format [leave null to get from config setting]
	 * @return string         [formatted date]
	 */
	public function formatDate($date, $format=null) {
		if (is_null($format)) {
			$format = DateTimeComponent::getConfigDateFormat();
		}
		if($date == '0000-00-00' || $date == ''){ 
			echo "";
		}else{
			$date = new DateTime($date);
			echo $date->format($format);
		}
	}

	public function formatGender($value) {
		return ($value == 'F') ? __('Female') : __('Male');
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
			'onclick' => 'jsTable.doRemove(this);'
		);
		
		if(isset($options['class'])) {
			$_options['class'] = $_options['class'] . ' ' . $options['class'];
			unset($options['class']);
		}
		if(isset($options['onclick'])) {
			$_options['onclick'] = $_options['onclick'] . $options['onclick'];
			unset($options['onclick']);
		}
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
	
	public function getAddRow($caption) {
		return sprintf('<div class="row"><a class="void icon_plus">%s %s</a></div>', __('Add'), __($caption));
	}
	
	public function checkOrCrossMarker($flag) {
		return $flag ? '<span class="green">&#10003;</span>' : '<span class="red">&#10008;</span>';
	}
	
	public function getStatus($status) {
		return $status ? '<span class="green">'.__('Active').'</span>' : __('Inactive');
	}
	
	// for permissions	
	public function getPermissionInput($form, $fieldName, $type, $value) {
		$options = array(
			'id' => $type,
			'name' => sprintf($fieldName, $type),
			'type' => 'checkbox',
			'value' => 1,
			'autocomplete' => 'off',
			//'onchange' => 'jsList.activate(this)',
			'before' => '<div class="table_cell center">',
			'after' => '</div>'
		);
		
		if(is_null($value)) {
			$options['disabled'] = 'disabled';
		} else {
			if($value == 1) {
				$options['checked'] = 'checked';
			}
		}
		
		$input = $form->input($type, $options);
		return $input;
	}
	// end permissions
}