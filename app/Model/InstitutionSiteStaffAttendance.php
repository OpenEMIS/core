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

class InstitutionSiteStaffAttendance extends AppModel {
	public $useTable = 'institution_site_staff_absences';
	public $selectedPeriod;

	public $belongsTo = array(
		'Staff.Staff',
		'StaffAbsenceReason' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_absence_reason_id'
		)
	);

	public $actsAs = array(
		'ControllerAction2',
		'Excel'
	);

	public function excel($periodId) {
		$this->selectedPeriod = $periodId;
		parent::excel();
	}

	public function generateSheet($writer) {
		$academicPeriodObj = ClassRegistry::init('AcademicPeriod')->findById($this->selectedPeriod);
		$startDate = date('Y-m-d', strtotime($academicPeriodObj['AcademicPeriod']['start_date']));
		$endDate = date('Y-m-d', strtotime($academicPeriodObj['AcademicPeriod']['end_date']));

		$months = $this->controller->generateMonthsByDates($startDate, $endDate);
		//pr($months);die;
		$footer = $this->excelGetFooter();
		
		foreach($months as $month){
			$monthInString = $month['month']['inString'];
			$monthInNumber = $month['month']['inNumber'];
			$year = $month['year'];
			
			$days = $this->controller->generateDaysOfMonth($year, $monthInNumber, $startDate, $endDate);
			//pr($days);
			$headerDays = array();
			$daysIndex = array();
			foreach($days as $item){
				$headerDays[] = sprintf('%s (%s)', $item['day'], $item['weekDay']);
				$daysIndex[] = $item['date'];
			}
			
			$headerInfo = array(
				__('OpenEMIS ID'),
				__('First Name'),
				__('Last Name')
			);
			$header = array_merge($headerInfo, $headerDays);
			//pr($header);
			$writer->writeSheetRow($monthInString, $header);
			
			$data = $this->getData($daysIndex);
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
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearId = $this->selectedPeriod;
		
		$InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
		$staffList = $InstitutionSiteStaff->getStaffByInstitutionSite($institutionSiteId, $monthStartDay, $monthEndDay);
		//pr($staffList);die;
		$InstitutionSiteStaffAbsence = ClassRegistry::init('InstitutionSiteStaffAbsence');
		$absenceData = $InstitutionSiteStaffAbsence->getAbsenceData($institutionSiteId, $yearId, $monthStartDay, $monthEndDay);
		
		$data = array();
		$absenceCheckList = array();
		foreach($absenceData AS $absenceUnit){
			$absenceStaff = $absenceUnit['Staff'];
			$staffId = $absenceStaff['id'];
			$absenceRecord = $absenceUnit['InstitutionSiteStaffAbsence'];
			$indexAbsenceDate = date('Y-m-d', strtotime($absenceRecord['first_date_absent']));

			$absenceCheckList[$staffId][$indexAbsenceDate] = $absenceUnit;

			if($absenceRecord['full_day_absent'] == 'Yes' && !empty($absenceRecord['last_date_absent']) && $absenceRecord['last_date_absent'] > $absenceRecord['first_date_absent']){
				$tempStartDate = date("Y-m-d", strtotime($absenceRecord['first_date_absent']));
				$formatedLastDate = date("Y-m-d", strtotime($absenceRecord['last_date_absent']));

				while($tempStartDate <= $formatedLastDate){
					$stampTempDate = strtotime($tempStartDate);
					$tempIndex = date('Y-m-d', $stampTempDate);

					$absenceCheckList[$staffId][$tempIndex] = $absenceUnit;

					$stampTempDateNew = strtotime('+1 day', $stampTempDate);
					$tempStartDate = date("Y-m-d", $stampTempDateNew);
				}
			}
		}
		//pr($absenceCheckList);die;

		foreach ($staffList as $staff){
			$staffObj = $staff['Staff'];
			$staffId = $staffObj['id'];
			//$staffName = sprintf('%s %s %s', $staffObj['first_name'], $staffObj['middle_name'], $staffObj['last_name']);

			$row = array();
			$row[] = $staffObj['openemis_no'];
			$row[] = $staffObj['first_name'];
			$row[] = $staffObj['last_name'];

			foreach ($days as $index){
				if (isset($absenceCheckList[$staffId][$index])) {
					$absenceObj = $absenceCheckList[$staffId][$index]['InstitutionSiteStaffAbsence'];
					if ($absenceObj['full_day_absent'] !== 'Yes') {
						$startTimeAbsent = $absenceObj['start_time_absent'];
						$endTimeAbsent = $absenceObj['end_time_absent'];
						$timeStr = sprintf(__('Absent') . ' - ' . $absenceObj['absence_type']. ' (%s - %s)' , $startTimeAbsent, $endTimeAbsent);
						$row[] = $timeStr;
					}else{
						$row[] = sprintf('%s %s %s', __('Absent'), __('Full'), __('Day'));
					}
				}else{
					$row[] = __('');
				}
			}

			$data[] = $row;
		}
			
		//pr($data);die;
		return $data;
	}
	
}
