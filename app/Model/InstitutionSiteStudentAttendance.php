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

App::uses('AppModel', 'Model');

class InstitutionSiteStudentAttendance extends AppModel {
	public $useTable = 'institution_site_student_absences';
	public $selectedPeriod;
	public $selectedSection;

	public $belongsTo = array(
		'InstitutionSiteSection'
	);

	public $actsAs = array(
		'ControllerAction2',
		'Excel' => array(
			'header' => array('Student' => array('identification_no', 'first_name', 'last_name'))
		)
	);

	/* Excel Behaviour */
	public function excelGetConditions() {
		$id = CakeSession::read('InstitutionSite.id');
		$conditions = array('InstitutionSiteSection.institution_site_id' => $id);
		return $conditions;
	}
	public function excelGetFieldLookup() {
		$alias = $this->alias;
		$lookup = array(
			"$alias.status" => array(0 => 'Inactive', 1 => 'Active'),
			"$alias.type" => array(0 => 'Non-Teaching', 1 => 'Teaching')
		);
		return $lookup;
	}
	public function excelGetOrder() {
		$order = array('SchoolYear.order', 'InstitutionSitePosition.position_no');
		return $order;
	}
	/* End Excel Behaviour */

	public function excel($periodId, $sectionId) {
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$period = $SchoolYear->findById($periodId);
		$startDate = $period['SchoolYear']['start_date'];
		$endDate = $period['SchoolYear']['end_date'];

		$Student = $this->InstitutionSiteSection->InstitutionSiteSectionStudent;
		$Student->contain('Student');
		pr($Student->findAllByInstitutionSiteSectionId($sectionId, array(), array('Student.first_name')));
		die;
	}

	public function generateSheet($writer) {
		$header = $this->excelGetHeader();
		$footer = $this->excelGetFooter();

		$sheet = 'Sheet1';
		$writer->writeSheetRow($sheet, array_values($header));
		foreach ($data as $row) {
			$sheetRow = array();
			foreach ($header as $key => $label) {
				$value = $this->getValue($row, $key);
				$sheetRow[] = $value;
			}
			$writer->writeSheetRow($sheet, $sheetRow);
		}
		$writer->writeSheetRow($sheet, array(''));
		$writer->writeSheetRow($sheet, $footer);
	}
}
