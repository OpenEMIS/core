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
	public function getFormOptions($url=array(), $type='') {
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
	
	public function getFormButtons($view, $option = NULL) {
		$cancelURL = $option['cancelURL'];
		echo '<div class="form-group">';
		echo '<div class="col-md-offset-4">';
		echo $view->Form->submit(__('Save'), array('class' => 'btn_save btn_right', 'div' => false));
		echo $view->Html->link(__('Cancel'), $cancelURL, array('class' => 'btn_cancel btn_left'));
		echo '</div>';
		echo '</div>';
	}
}