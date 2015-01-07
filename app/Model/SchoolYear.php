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

class SchoolYear extends AppModel {
	public $actsAs = array('FieldOption', 'DatePicker' => array('start_date', 'end_date'));
	public function beforeSave($options = array()) {
		$attr = array('start_date' => 'start_year', 'end_date' => 'end_year');
		foreach($attr as $date => $year) {
			if(isset($this->data[$this->alias][$date])) {
				$dateValue = $this->data[$this->alias][$date];
				$this->data[$this->alias][$year] = date('Y', strtotime($dateValue));
			}
		}
		parent::beforeSave($options);
		return true;
	}

	public function getOptionFields() {
		parent::getOptionFields();

		$this->fields['start_year']['type'] = 'hidden';
		$this->fields['end_year']['type'] = 'hidden';

		$this->fields['current']['labelKey'] = 'FieldOption';
		$this->fields['current']['type'] = 'select';
		$this->fields['current']['default'] = 1;
		$this->fields['current']['options'] = array(1 => __('Yes'), 0 => __('No'));

		$this->fields['available']['labelKey'] = 'FieldOption';
		$this->fields['available']['type'] = 'select';
		$this->fields['available']['default'] = 1;
		$this->fields['available']['options'] = array(1 => __('Yes'), 0 => __('No'));

		return $this->fields;
	}
	
	public function getAvailableYears($list = true, $order='ASC') {
		if($list) {
			$result = $this->find('list', array(
				'fields' => array('SchoolYear.id', 'SchoolYear.name'),
				'conditions' => array('SchoolYear.visible' => 1),
				'order' => array('SchoolYear.name ' . $order)
			));
		} else {
			$result = $this->find('all', array(
				'conditions' => array('SchoolYear.visible' => 1),
				'order' => array('SchoolYear.name ' . $order)
			));
		}
		return $result;
	}
	
	public function getYearList($type='name', $order='DESC') {
		$value = 'SchoolYear.' . $type;
		$result = $this->find('list', array(
			'fields' => array('SchoolYear.id', $value),
			'order' => array($value . ' ' . $order)
		));
		return $result;
	}
	
	public function institutionProgrammeYearList($institutionSiteId) {
		$result = $this->find('list', array(
			'fields' => array('SchoolYear.id', 'SchoolYear.name'),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.school_year_id = SchoolYear.id',
						'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId
					)
				)
			),
			'conditions' => array(
				'SchoolYear.visible' => 1
			),
			'order' => array('SchoolYear.name DESC')
		));
		return $result;
	}
	
	public function institutionClassYearList($institutionSiteId) {
		$result = $this->find('list', array(
			'fields' => array('SchoolYear.id', 'SchoolYear.name'),
			'joins' => array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClass.school_year_id = SchoolYear.id',
						'InstitutionSiteClass.institution_site_id' => $institutionSiteId
					)
				)
			),
			'conditions' => array(
				'SchoolYear.visible' => 1
			),
			'order' => array('SchoolYear.name DESC')
		));
		return $result;
	}
	
	public function getYearListValues($type='name', $order='DESC') {
		$value = 'SchoolYear.' . $type;
		$result = $this->find('list', array(
			'fields' => array($value, $value),
			'order' => array($value . ' ' . $order)
		));
		return $result;
	}
	
	public function getYearListForVerification($institutionSiteId, $validate=true) {
		$CensusVerification = ClassRegistry::init('CensusVerification');
		$yearIds = $CensusVerification->find('list', array(
			'fields' => array('CensusVerification.school_year_id'),
			'joins' => array(
				array(
					'table' => 'census_verifications',
					'alias' => 'CensusVerification2',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusVerification2.school_year_id = CensusVerification.school_year_id',
						'CensusVerification2.institution_site_id = CensusVerification.institution_site_id',
						'CensusVerification2.created > CensusVerification.created'
					)
				)
			),
			'conditions' => array(
				'CensusVerification.status' => 1,
				'CensusVerification.institution_site_id' => $institutionSiteId,
				'CensusVerification2.id IS NULL'
			)
		));
		
		$conditions = array();
		if($validate) {
			$conditions['id NOT'] = array_values($yearIds);
		} else {
			$conditions['id'] = array_values($yearIds);
		}
		$data = $this->find('list', array(
			'fields' => array('SchoolYear.id', 'SchoolYear.name'),
			'conditions' => $conditions,
			'order' => 'SchoolYear.name'
		));
		return $data;
	}
	
	public function findOptions($options=array()) {
		$options['order'] = array('SchoolYear.name DESC');
		$list = parent::findOptions($options);
		return $list;
	}

	/**
	 * get school year id based on the given year
	 * @return int 	school year id
	 */
	public function getSchoolYearId($year) {
		$data = $this->findByName($year);	
		return $data['SchoolYear']['id'];
	}
	
	public function getYearRange() {
		$data = $this->find('list', array(
			'fields' => array('SchoolYear.id', 'SchoolYear.start_year'),
			'order' => array('SchoolYear.start_year')
		));
		return $data;
	}
	
	public function getSchoolYearById($yearId) {
		$data = $this->findById($yearId);	
		return $data['SchoolYear']['name'];
	}
	
	public function getSchoolYearObjectById($yearId) {
		$data = $this->findById($yearId);	
		return $data['SchoolYear'];
	}
	
	public function getSchoolYearIdByDate($date) {
		if(empty($date)){
			return '';
		}
		
		$result = $this->find('first', array(
			'fields' => array('SchoolYear.id'),
			'conditions' => array(
				'SchoolYear.visible' => 1,
				'SchoolYear.start_date <=' => $date,
				'SchoolYear.end_date >=' => $date
			)
		));
		
		if(!empty($result['SchoolYear']['id'])){
			return $result['SchoolYear']['id'];
		}else{
			return '';
		}
	}

	public function getCurrentSchoolYear() {
		$currentDate = date('Y-m-d', time());
		$data = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'start_date <= ' => $currentDate,
				'end_date >= ' => $currentDate
			)
		));
		
		if(!empty($data)){
			return $data['SchoolYear'];
		}else{
			return NULL;
		}
	}

	public function getCurrentSchoolYearId() {
		$result = $this->find('first', array(
			'fields' => array('SchoolYear.id'),
			'conditions' => array(
				'SchoolYear.visible = 1',
				'SchoolYear.current = 1'
			)
		));
		
		if(!empty($result['SchoolYear']['id'])){
			return $result['SchoolYear']['id'];
		}else{
			return '';
		}
	}
}
