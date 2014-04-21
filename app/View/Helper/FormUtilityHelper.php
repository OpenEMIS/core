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

class FormUtilityHelper extends AppHelper {
	public $helpers = array('Html', 'Form', 'Label');
	
	public function getFormOptions($url=array(), $type='') {
		if(!isset($url['controller'])) {
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
	
	public function getFormButtons($option = NULL) {
		$cancelURL = $option['cancelURL'];
		echo '<div class="form-group">';
		echo '<div class="col-md-offset-4">';
		echo $this->Form->submit($this->Label->get('general.save'), array('class' => 'btn_save btn_right', 'div' => false));
		echo $this->Html->link($this->Label->get('general.cancel'), $cancelURL, array('class' => 'btn_cancel btn_left'));
		echo '</div>';
		echo '</div>';
	}
	
	public function datepicker($field, $options=array()) {
		$dateFormat = 'dd-mm-yyyy';
		$icon = '<span class="input-group-addon"><i class="fa fa-calendar"></i></span></div>';
		$_options = array(
			'id' => 'date',
			'data-date' => date('d-m-Y'),
			'data-date-format' => $dateFormat,
			'data-date-autoclose' => 'true',
			'label' => false
		);
		if(!empty($options)) {
			$_options = array_merge($_options, $options);
		}
		$label = $_options['label'];
		unset($_options['label']);
		$wrapper = $this->Html->div('input-group date', null, $_options);
		$defaults = $this->Form->inputDefaults();
		$inputOptions = array(
			'id' => $_options['id'],
			'type' => 'text',
			'between' => $defaults['between'] . $wrapper,
			'after' => $icon . $defaults['after'],
			'value' => $_options['data-date']
		);
		if($label !== false) {
			$inputOptions['label'] = array('text' => $label, 'class' => $defaults['label']['class']);
		}
		$html = $this->Form->input($field, $inputOptions);
		
		if(!is_null($this->_View->get('datepicker'))) {
			$datepickers = $this->_View->get('datepicker');
			$datepickers[] = $_options['id'];
			$this->_View->set('datepicker', $datepickers);
		} else {
			$this->_View->set('datepicker', array($_options['id']));
		}
		return $html;
	}
        
        public function getFormWizardButtons($option = NULL) {
            if (!$option['WizardMode']) {
                echo $this->getFormButtons(array('cancelURL' => $option['cancelURL']));
            } else {
                echo '<div class="add_more_controls">' . $this->Form->submit($this->Label->get('wizard.addmore'), array('div' => false, 'name' => 'submit', 'class' => "btn_save btn_right")) . '</div>';

                echo $this->Form->submit($this->Label->get('wizard.previous'), array('div' => false, 'name' => 'submit', 'class' => "btn_save btn_right"));
                if (!$option['WizardEnd']) {
                    echo $this->Form->submit($this->Label->get('wizard.next'), array('div' => false, 'name' => 'submit', 'name' => 'submit', 'class' => "btn_save btn_right"));
                } else {
                    echo $this->Form->submit($this->Label->get('wizard.finish'), array('div' => false, 'name' => 'submit', 'name' => 'submit', 'class' => "btn_save btn_right"));
                }
                if ($option['WizardMandatory'] != '1' && !$option['WizardEnd']) {
                    echo $this->Form->submit($this->Label->get('wizard.skip'), array('div' => false, 'name' => 'submit', 'class' => "btn_cancel btn_cancel_button btn_left"));
                }
            }
        }
}