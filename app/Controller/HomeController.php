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

App::uses('AppController', 'Controller');

class HomeController extends AppController {
	private $debug = false;
	public $helpers = array('Number');
	private $tableCounts = array(
		'Added' => array(
			// Model => db table
			'InstitutionSite' => 'institution_sites',
			'Student' => 'students',
			'Staff' => 'staff'
		),
		'Edited' => array(
			'InstitutionSiteHistory' => 'institution_site_history',
			'StudentHistory' => 'student_history',
			'StaffHistory' => 'staff_history'
		)
	);
	public $uses = array(
		'ConfigItem',
		'ConfigAttachment',
		'InstitutionSite',
		'Students.Student',
		'Staff.Staff',
		'InstitutionSiteHistory',
		'Students.StudentHistory',
		'Staff.StaffHistory',
		'InstitutionSiteStudent',
		'InstitutionSiteStaff',
		'AcademicPeriod',
		'InstitutionSiteSectionStudent'
	);
	
	private function logtimer($str=''){
		if($this->debug == true)
		echo $str." ==> ".date("H:i:s")."<br>\n";
	}
	
	public function index() {
		$this->redirect(array('action' => 'dashboard'));
		$this->logtimer('Start');
		$this->logtimer('Start Attachment');
		$image = array();
		$image = $this->ConfigAttachment->find('first', array('fields' => array('id','file_name'), 'conditions' => array('ConfigAttachment.active' => 1, 'ConfigAttachment.type' => 'dashboard')));

		if($image && sizeof($image['ConfigAttachment']) > 0){
			$image = array_merge($image['ConfigAttachment']);
			$image['width'] = $this->ConfigItem->getValue('dashboard_img_width');
			$image['height'] = $this->ConfigItem->getValue('dashboard_img_height');

			$imageData = $this->ConfigAttachment->getResolution($image['file_name']);
			$image['original_width'] = $imageData['width'];
			$image['original_height'] = $imageData['height'];

			$image = array_merge($image, $this->ConfigAttachment->getCoordinates($image['file_name']));
			$this->set('image', $image);
			
		}
		$this->logtimer('End Attachment');
		$this->logtimer('Start Notice');
		$this->set('message', $this->ConfigItem->getNotice());
		$this->logtimer('End Notice');
		$this->logtimer('Start Adaptation');
		$this->set('adaptation', $this->ConfigItem->getAdaptation());
		$this->logtimer('End Adaptation');
	}

	public function dashboard() {
		foreach($this->tableCounts['Added'] as $key => $val){
			$rec = $this->{$key}->query('SELECT count(*) as count FROM '.$val.';');
			$total[$key] = (isset($rec[0][0]['count']))?$rec[0][0]['count']:'0';
		}
		$this->set('tableCounts', $total);
		$this->set('SeparateThousandsFormat', array(
			'before' => '',
			'places' => 0,
			'thousands' => ',',
		));

		$highChartDatas = array();
		$highChartDatas[] = $this->InstitutionSiteStudent->getHighChart('number_of_students_by_year');
		$highChartDatas[] = $this->InstitutionSiteSectionStudent->getHighChart('number_of_students_by_grade');
		$highChartDatas[] = $this->InstitutionSiteStaff->getHighChart('number_of_staff_by_position');

		$this->set('highChartDatas', $highChartDatas);
		/*
		//Students By Year
		$this->InstitutionSiteStudent->formatResult = true;
		$periodResult = $this->InstitutionSiteStudent->find('first', array(
			'fields' => array(
				'MIN(InstitutionSiteStudent.start_year) as min_year', 'MAX(InstitutionSiteStudent.end_year) as max_year'
			)
		));
		$minYear = $periodResult['min_year'];
		$maxYear = $periodResult['max_year'];

		$years = array();
		$data = array();

		for ($currentYear = $minYear; $currentYear <= $maxYear; $currentYear++) {
			$years[$currentYear] = $currentYear;

			$studentsByYearConditions = array('Student.gender IS NOT NULL');
			$studentsByYearConditions['OR'] = array(
				array(
					'InstitutionSiteStudent.end_year IS NOT NULL',
					'InstitutionSiteStudent.start_year <= "' . $currentYear . '"',
					'InstitutionSiteStudent.end_year >= "' . $currentYear . '"'
				)
			);

			$this->InstitutionSiteStudent->contain('Student');
			$studentsByYear = $this->InstitutionSiteStudent->find('all', array(
				'fields' => array(
					'Student.gender', 'COUNT(InstitutionSiteStudent.id) AS total'
				),
				'conditions' => $studentsByYearConditions,
				'group' => array(
					'Student.gender'
				)
			));

			if (!array_key_exists($currentYear, $data)) {
				$data[$currentYear] = array('M' => 0, 'F' => 0);
			}
			foreach ($studentsByYear as $key => $studentByYear) {
				$yearGender = isset($studentByYear['Student']['gender']) ? $studentByYear['Student']['gender'] : null;
				$yearTotal = isset($studentByYear[0]['total']) ? $studentByYear[0]['total'] : 0;

				$data[$currentYear][$yearGender] = $yearTotal;
			}
		}

		$categories = array();
		$series = array(
			array('name' => __('Male'), 'data' => array()),
			array('name' => __('Female'), 'data' => array())
		);

		foreach ($data as $year => $genderTotals) {
			if (!in_array($year, $categories)) {
				$categories[] = $year;
			}

			foreach ($genderTotals as $gender => $total) {
				if ($gender == 'M') {
					$series[0]['data'][] = $total;
				} else {
					$series[1]['data'][] = $total;
				}
			}
		}

		$highChartData = array();
		$highChartData['chart']['type'] = 'column';
		$highChartData['chart']['borderWidth'] = 1;
		$highChartData['title']['text'] = __('Number of Students By Year');
		$highChartData['xAxis']['title']['text'] = __('Years');
		$highChartData['yAxis']['title']['text'] = __('Total Students');
		$highChartData['xAxis']['categories'] = $categories;
		$highChartData['series'] = $series;
		
		$json_highChartData = json_encode($highChartData, JSON_NUMERIC_CHECK);
		$highChartDatas[] = $json_highChartData;
		*/

		/*
		//Students By Grade for current year
		$currentYearId = $this->AcademicPeriod->getCurrent();
		$currentYear = $this->AcademicPeriod->field('name', array('AcademicPeriod.id' => $currentYearId));

		//$this->InstitutionSiteSectionStudent->formatResult = true;
		$studentByGrades = $this->InstitutionSiteSectionStudent->find('all', array(
			'fields' => array(
				'EducationGrade.id', 'EducationGrade.name', 'Student.gender', 'COUNT(InstitutionSiteSectionStudent.id) AS total'
			),
			'conditions' => array(
				'InstitutionSiteSection.academic_period_id' => $currentYearId
			),
			'group' => array(
				'EducationGrade.id', 'Student.gender'
			),
			'order' => array(
				'EducationGrade.order'
			)
		));

		$grades = array();
		$data = array();
		foreach ($studentByGrades as $key => $studentByGrade) {
			$gradeId = $studentByGrade['EducationGrade']['id'];
			$gradeName = $studentByGrade['EducationGrade']['name'];
			$gradeGender = $studentByGrade['Student']['gender'];
			$gradeTotal = $studentByGrade[0]['total'];

			$grades[$gradeId] = $gradeName;
			if (!array_key_exists($gradeId, $data)) {
				$data[$gradeId] = array('M' => 0, 'F' => 0);
			}
			$data[$gradeId][$gradeGender] = $gradeTotal;
		}

		$categories = array();
		$series = array(
			array('name' => __('Male'), 'data' => array()),
			array('name' => __('Female'), 'data' => array())
		);

		foreach ($data as $grade => $genderTotals) {
			if (!in_array($grade, $categories)) {
				$categories[] = $grades[$grade];
			}

			foreach ($genderTotals as $gender => $total) {
				if ($gender == 'M') {
					$series[0]['data'][] = $total;
				} else {
					$series[1]['data'][] = $total;
				}
			}
		}

		$highChartData = array();
		$highChartData['chart']['type'] = 'column';
		$highChartData['chart']['borderWidth'] = 1;
		$highChartData['title']['text'] = __('Number of Students By Grade for Year ') . $currentYear;
		$highChartData['xAxis']['title']['text'] = __('Education Grades');
		$highChartData['yAxis']['title']['text'] = __('Total Students');
		$highChartData['xAxis']['categories'] = $categories;
		$highChartData['series'] = $series;
		
		$json_highChartData = json_encode($highChartData, JSON_NUMERIC_CHECK);
		$highChartDatas[] = $json_highChartData;
		$highChartDatas[] = $json_highChartData;

		$this->set('highChartDatas', $highChartDatas);
		*/
	}
	
	public function support() {
		$this->bodyTitle = 'About';
		$title = 'Contact';
		$this->Navigation->addCrumb('About', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb($title);
		$support = $this->ConfigItem->getSupport();
		$this->set('supportInformation', $support);
		$this->set('subTitle', $title);
		$this->render('Help/'.$this->action);
	}
	
	public function systemInfo() {
		$this->bodyTitle = 'About';
		$subTitle = 'System Information';
		$this->Navigation->addCrumb('About', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb($subTitle);
		
		$dbo = ConnectionManager::getDataSource('default');
		$temp = explode('/', $dbo->config['datasource']);
		$dbStore = end($temp);
		
		$dbVersion = $dbo->getVersion();
		$this->set(compact('dbStore', 'dbVersion', 'subTitle'));
		$this->render('Help/system_info');
	}
	
	public function license() {
		$this->bodyTitle = 'About';
		$this->Navigation->addCrumb('About', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb('License');
		$this->render('Help/'.$this->action);
	}

	public function partners() {
		$this->bodyTitle = 'About';
		$title = 'Partners';
		$this->Navigation->addCrumb('About', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb($title);
		$images = $this->ConfigAttachment->find('all', array('fields' => array('id','file_name','name'), 'conditions' => array('ConfigAttachment.active' => 1, 'ConfigAttachment.type' => 'partner'), 'order'=>array('order')));

		$imageData = array();
		if(!empty($images)){
			$i = 0;
			foreach($images as $image){
				$imageData[$i] = array_merge($image['ConfigAttachment']);
				$i++;
			}
		}
		$this->set('images', $imageData);
		$this->set('subTitle', $title);
		$this->render('Help/'.$this->action);
	}
		
	public function getLatestStatistics(){
		$this->autoLayout = false;
		foreach($this->tableCounts['Added'] as $key => $val){
			$rec = $this->{$key}->query('SELECT count(*) as count FROM '.$val.';');
			$total[$key] = (isset($rec[0][0]['count']))?$rec[0][0]['count']:'0';
		}
		$this->set('tableCounts', $total);
		$this->set('SeparateThousandsFormat', array(
			'before' => '',
			'places' => 0,
			'thousands' => ',',
		));
	}
	
	public function getLatestActivities(){
		$this->autoLayout = false;
		$query = '';
			   
		$dbo = ConnectionManager::getDataSource('default');//$this->Institution->getDataSource();
		// $dbo = $this->getDataSource();
		
		$limit = 7;
		$data = array();
		foreach ($this->tableCounts as $key => $element) {
			foreach($element as $Model => $tablename){
				$this->logtimer('Start '.$Model);
				$sql = 'SELECT * FROM '.$tablename.' t LEFT JOIN security_users su ON (su.id = t.created_user_id) ORDER BY t.id DESC LIMIT '.$limit;
				if($this->debug) echo "<br><br>".$sql;
				$recs = $this->{$Model}->query($sql);
				$data[$Model] = $recs;
				$this->logtimer('End '.$Model);
			}
			
		}
		$activities = array();
		
		foreach($data as $tableName => &$arrVal){
			$action = (isset($this->tableCounts['Added'][$tableName]))?'Added':'Edited';
			
			foreach($arrVal as $krec => &$vrec ){
				if($action == 'Edited'){
					$parentTable = str_replace('History','',$tableName);
					$foreignKey = strtolower(Inflector::underscore($parentTable)).'_id';
					$rec = $this->{$parentTable}->find('first',array('conditions'=>array( $parentTable.'.id' => $vrec['t'][$foreignKey])));
					if(!$rec) $action = 'Deleted';
				}
				$vrec['t']['user_first_name'] = $vrec['su']['first_name'];
				$vrec['t']['user_last_name'] = $vrec['su']['last_name'];
				$vrec['t']['action'] = $action;
				$tableName = str_ireplace('history','', $tableName);
				$vrec['t']['module'] = ucfirst(Inflector::underscore($tableName));
				$vrec['t']['module'] = ( $vrec['t']['module'] == 'Institution_site')?'Institution Site':$vrec['t']['module'];
				$vrec['t']['name'] = (isset($vrec['t']['name']))?$vrec['t']['name']:$vrec['t']['first_name'].' '.$vrec['t']['last_name'];
				$activities[strtotime($vrec['t']['created'])] = $vrec['t'];
			}
		}
		krsort($activities);
		$activities = array_slice($activities, 0, $limit);
		$this->logtimer('END');
		$this->logtimer('Start lastest Activities');
		$this->set('latestActivities', $activities);
		$this->logtimer('End lastest Activities');
	}

	private function checkActivityDeleteStatus($obj) {
		$table = $obj['table'];
		if($obj['parent_table_id']){
			$parentTable = str_ireplace('history', '', $table);
			$numOfRecords = $this->{$parentTable}->find('count', array(
				'conditions' => array("{$parentTable}.id" => $obj['parent_table_id'])
			));
			if($numOfRecords < 1){
				return true;
			}
		}
		return false;
	}
}
