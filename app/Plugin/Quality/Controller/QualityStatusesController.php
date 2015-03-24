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

class QualityStatusesController extends QualityAppController {
	public $uses = array(
		'Quality.QualityStatus',
		'Quality.RubricTemplate',
		'AcademicPeriod'
	);

	public $components = array(
		'ControllerAction' => array('model' => 'Quality.QualityStatus')
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Quality', array('controller' => 'QualityStatuses', 'action' => 'index', 'plugin' => 'Quality'));
		$this->Navigation->addCrumb('Status');
		$this->set('contentHeader', 'Status');
		$this->QualityStatus->fields['rubric_template_id']['hyperlink'] = true;

		$this->QualityStatus->fields['status']['visible'] = false;
		$this->ControllerAction->setFieldOrder('rubric_template_id', 1);
		$this->ControllerAction->setFieldOrder('academic_period_id', 2);

		if ($this->action == 'index' || $this->action == 'view') {
			$this->QualityStatus->fields['rubric_template_id']['dataModel'] = 'RubricTemplate';
			$this->QualityStatus->fields['rubric_template_id']['dataField'] = 'name';

			$this->QualityStatus->fields['academic_period_id']['dataModel'] = 'AcademicPeriod';
			$this->QualityStatus->fields['academic_period_id']['dataField'] = 'name';
		} else if($this->action == 'add' || $this->action == 'edit') {
			$templateOptions = $this->RubricTemplate->find('list');
			$this->QualityStatus->fields['rubric_template_id']['type'] = 'select';
			$this->QualityStatus->fields['rubric_template_id']['options'] = $templateOptions;

			$academicPeriodOptions = $this->AcademicPeriod->getAcademicPeriodList();
			$this->QualityStatus->fields['academic_period_id']['type'] = 'select';
			$this->QualityStatus->fields['academic_period_id']['options'] = $academicPeriodOptions;
		}
	}
}
