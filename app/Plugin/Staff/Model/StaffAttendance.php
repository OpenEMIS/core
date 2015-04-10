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

class StaffAttendance extends StaffAppModel {
	public $useTable = 'staff_attendances';
	
	public $actsAs = array(
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
                'InstitutionSite' => array(
                    'name' => 'Institution'
                ),
                'Staff' => array(
                    'openemis_no' => 'OpenEMIS ID',
                    'first_name' => 'First Name',
                    'middle_name' => 'Middle Name',
                    'third_name' => 'Third Name',
                    'last_name' => 'Last Name',
                    'preferred_name' => 'Preferred Name'
                ),
                'AcademicPeriod' => array(
                    'name' => 'Academic Period',
                    'school_days' => 'School Days'
                ),
                'StaffAttendance' => array(
                    'total_no_attend' => 'Total Days Attended',
                    'total_no_absence' => 'Total Days Absent',
                    'total' => 'Total'
                )
            ),
            'fileName' => 'Report_Staff_Attendance'
		)
	);
	
	public function getAttendanceData($id,$academicPeriodId,$institutionSiteId=null) {
                if(empty($institutionSiteId)){
                    $list = $this->find('all',array(
				'conditions'=>array('StaffAttendance.staff_id' => $id, 'StaffAttendance.academic_period_id' => $academicPeriodId)));
                }else{
                    $institutionSiteId = intval($institutionSiteId);
                    $list = $this->find('all',array(
				'conditions'=>array('StaffAttendance.staff_id' => $id, 'StaffAttendance.academic_period_id' => $academicPeriodId, 'StaffAttendance.institution_site_id' => $institutionSiteId)));
                }

		return $list;
	}

    public function findID($id,$academicPeriodId) {
        $list = $this->find('all',array(
            'conditions'=>array('StaffAttendance.staff_id' => $id, 'StaffAttendance.academic_period_id' => $academicPeriodId)));
        $myid='';
        if(count($list)>0){
            $myid = $list[0]['StaffAttendance']['id'];
        }
        return $myid;
    }
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->getCSVHeader($this->reportMapping[$index]['fields']);
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = $this->getCSVFields($this->reportMapping[$index]['fields']);
			$options['order'] = array('SecurityUser.openemis_no', 'AcademicPeriod.name');
			$options['conditions'] = array('StaffAttendance.institution_site_id' => $institutionSiteId);

			$options['joins'] = array(
                    array(
                        'table' => 'institution_sites',
                        'alias' => 'InstitutionSite',
                        'conditions' => array(
                            'StaffAttendance.institution_site_id = InstitutionSite.id'
                        )
                    ),
                    array(
                        'table' => 'staff',
                        'alias' => 'Staff',
                        'conditions' => array('StaffAttendance.staff_id = Staff.id')
                    ),
                    array(
                        'table' => 'academic_periods',
                        'alias' => 'AcademicPeriod',
                        'conditions' => array('StaffAttendance.academic_period_id = AcademicPeriod.id')
                    ),
                );
			
			$this->virtualFields = array(
                    'total' => 'StaffAttendance.total_no_attend + StaffAttendance.total_no_absence'
                );

			$data = $this->find('all', $options);

			return $data;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}

}
