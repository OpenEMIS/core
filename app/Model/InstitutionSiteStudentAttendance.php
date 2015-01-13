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
		'Students.Student',
		'InstitutionSiteSection',
		'StudentAbsenceReason' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'student_absence_reason_id'
		)
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
		$order = array('InstitutionSitePosition.position_no');
		return $order;
	}
	/* End Excel Behaviour */

	public function excel($periodId) {
		$this->selectedPeriod = $periodId;
		parent::excel();
	}

	public function generateSheet($writer) {
		$SchoolYear = ClassRegistry::init('SchoolYear');
		$period = $SchoolYear->findById($this->selectedPeriod);
		$startDate = $period['SchoolYear']['start_date'];
		$endDate = $period['SchoolYear']['end_date'];

		$months = $this->generateMonthsByDates($startDate, $endDate);
		//pr($months);
		$footer = $this->excelGetFooter();
		
		foreach($months as $month){
			$monthInString = $month['month']['inString'];
			$monthInNumber = $month['month']['inNumber'];
			$year = $month['year'];
			
			$days = $this->generateDaysOfMonth($year, $monthInNumber, $startDate, $endDate);
			//pr($days);
			
			$header = array(
				__('Section'),
				__('Student')
			);
			$header = array_merge($header, $days);
			//pr($header);
			$writer->writeSheetRow($monthInString, $header);
			
			$data = $this->getData($days);
			//pr($data);die;
			
			foreach ($data as $row) {
				$writer->writeSheetRow($monthInString, $row);
			}
			
			$writer->writeSheetRow($monthInString, array(''));
			$writer->writeSheetRow($monthInString, $footer);
		}
	}
	
	public function getData($days) {
		if(count($days) == 0){
			return null;
		}else{
			$monthStartDay = $days[0];
			//pr($monthStartDay);
			$monthEndDay = $days[count($days) - 1];
			//pr($monthEndDay);
		}
		//pr($days);die;
		
		$InstitutionSiteStudentAbsence = ClassRegistry::init('InstitutionSiteStudentAbsence');
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$InstitutionSiteSection = ClassRegistry::init('InstitutionSiteSection');
		$yearId = $this->selectedPeriod;
		$sections = $InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $yearId);
		
		$InstitutionSiteSectionStudent = ClassRegistry::init('InstitutionSiteSectionStudent');
		$data = array();
		foreach($sections as $sectionId => $sectionName){
			$studentList = $InstitutionSiteSectionStudent->getSectionSutdents($sectionId, $monthStartDay, $monthEndDay);
			//pr($studentList);die;
			$absenceData = $InstitutionSiteStudentAbsence->getAbsenceData($institutionSiteId, $yearId, $sectionId, $monthStartDay, $monthEndDay);
			
			$absenceCheckList = array();
			foreach($absenceData AS $absenceUnit){
				$absenceStudent = $absenceUnit['Student'];
				$studentId = $absenceStudent['id'];
				$absenceRecord = $absenceUnit['InstitutionSiteStudentAbsence'];
				$indexAbsenceDate = date('Y-m-d', strtotime($absenceRecord['first_date_absent']));

				$absenceCheckList[$studentId][$indexAbsenceDate] = $absenceUnit;

				if(!empty($absenceRecord['last_date_absent']) && $absenceRecord['last_date_absent'] > $absenceRecord['first_date_absent']){
					$tempStartDate = date("Y-m-d", strtotime($absenceRecord['first_date_absent']));
					$formatedLastDate = date("Y-m-d", strtotime($absenceRecord['last_date_absent']));
					
					while($tempStartDate <= $formatedLastDate){
						$stampTempDate = strtotime($tempStartDate);
						$tempIndex = date('Y-m-d', $stampTempDate);

						$absenceCheckList[$studentId][$tempIndex] = $absenceUnit;

						$stampTempDateNew = strtotime('+1 day', $stampTempDate);
						$tempStartDate = date("Y-m-d", $stampTempDateNew);
					}
				}
			}
			//pr($absenceCheckList);die;
			
			foreach ($studentList as $student){
				$studentObj = $student['Student'];
				$studentId = $studentObj['id'];
				$studentName = sprintf('%s %s %s', $studentObj['first_name'], $studentObj['middle_name'], $studentObj['last_name']);
				
				$row = array();
				$row[] = $sectionName;
				$row[] = $studentName;
				
				foreach ($days as $index){
					if (isset($absenceCheckList[$studentId][$index])) {
						$absenceObj = $absenceCheckList[$studentId][$index]['InstitutionSiteStudentAbsence'];
						if ($absenceObj['full_day_absent'] !== 'Yes') {
							$startTimeAbsent = $absenceObj['start_time_absent'];
							$endTimeAbsent = $absenceObj['end_time_absent'];
							$timeStr = sprintf(__('Absent') . ' - ' . $absenceObj['absence_type']. ' (%s - %s)' , $startTimeAbsent, $endTimeAbsent);
							$row[] = $timeStr;
						}else{
							$row[] = sprintf('%s %s %s', __('Absent'), __('Full'), __('Day'));
						}
					}else{
						$row[] = __('Attended');
					}
				}
				
				$data[] = $row;
			}
		}
		//pr($data);die;
		return $data;
	}
	
	public function generateMonthsByDates($startDate, $endDate) {
		$result = array();
		$stampStartDay = strtotime($startDate);
		$stampEndDay = strtotime($endDate);
		$stampToday = strtotime(date('Y-m-d'));
		
		$stampFirstDayOfMonth = strtotime('01-' . date('m', $stampStartDay) . '-' . date('Y', $stampStartDay));
		while($stampFirstDayOfMonth <= $stampEndDay && $stampFirstDayOfMonth <= $stampToday){
			$monthString = date('F', $stampFirstDayOfMonth);
			$monthNumber = date('m', $stampFirstDayOfMonth);
			$year = date('Y', $stampFirstDayOfMonth);
			
			$result[] = array(
				'month' => array('inNumber' => $monthNumber, 'inString' => $monthString),
				'year' => $year
			);
			
			$stampFirstDayOfMonth = strtotime('+1 month', $stampFirstDayOfMonth);
		}
		
		return $result;
	}
	
	public function generateDaysOfMonth($year, $month, $startDate, $endDate){
		$days = array();
		$stampStartDay = strtotime($startDate);
		//pr($startDate);
		$stampEndDay = strtotime($endDate);
		//pr($endDate);
		$stampToday = strtotime(date('Y-m-d'));
		
		$stampFirstDayOfMonth = strtotime($year . '-' . $month . '-01');
		//pr($year . '-' . $month . '-01');
		$stampFirstDayNextMonth = strtotime('+1 month', $stampFirstDayOfMonth);
		//pr(date('Y-m-d', $stampFirstDayNextMonth));
		
		
		if($stampFirstDayOfMonth <= $stampStartDay){
			$tempStamp = $stampStartDay;
		}else{
			$tempStamp = $stampFirstDayOfMonth;
		}
		//pr(date('Y-m-d', $tempStamp));
		
		while($tempStamp <= $stampEndDay && $tempStamp < $stampFirstDayNextMonth && $tempStamp < $stampToday){
			$days[] = date('Y-m-d', $tempStamp);
			
			$tempStamp = strtotime('+1 day', $tempStamp);
		}

		return $days;
	}
	
}
