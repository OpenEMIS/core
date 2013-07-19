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

class Institution extends AppModel {
	public $belongsTo = array(
		'Area',
		'InstitutionStatus',
		'InstitutionProvider',
		'InstitutionSector'
	);
	
	public $hasMany = array('InstitutionSite');
	public $actsAs = array(
		'TrackHistory',
		'CascadeDelete' => array(
			'cascade' => array(
				'InstitutionAttachment',
				'InstitutionCustomValue',
				'InstitutionSite'
			)
		)
	);
        
	public $sqlPaginateCount;
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name'
			)
		),
		'code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Code'
			),
			'ruleUnique' => array(
        		'rule' => 'isUnique',
        		'required' => true,
        		'message' => 'Please enter a unique Code'
		    )
		),
		'address' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Address'
			)
		),
		'postal_code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Postal Code'
			)
		),
		'institution_provider_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Provider'
			)
		),
		'institution_status_id' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', '>', 0),
				'required' => true,
				'message' => 'Please select a Status'
			)
		),
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		),
		'date_opened' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select the Date Opened'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select the Date Opened'
			)
		)
	);
	
	public function getLookupVariables() {
		$lookup = array(
			'Provider' => array('model' => 'InstitutionProvider'),
			'Sector' => array('model' => 'InstitutionSector'),
			'Status' => array('model' => 'InstitutionStatus')
		);
		return $lookup;
	}
	
	public function getInstitutionsWithoutSites() {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('Institution.*'),
			'joins' => array(
				array(
					'table' => 'institution_sites',
					'alias' => 'InstitutionSite',
					'type' => 'LEFT',
					'conditions' => array('InstitutionSite.institution_id = Institution.id')
				)
			),
			'group' => array('Institution.id HAVING COUNT(InstitutionSite.id) = 0')
		));
		return $data;
	}
	
	public function paginateJoins(&$conditions) {
		$joins = array();
		if(strlen($conditions['SearchKey']) != 0) {
			$joins[] = array(
				'table' => 'institution_history',
				'alias' => 'InstitutionHistory',
				'type' => 'LEFT',
				'conditions' => array('InstitutionHistory.institution_id = Institution.id')
			);
		}
		return $joins;
	}
	
	public function paginateConditions($conditions) {
		if(strlen($conditions['SearchKey']) != 0) {
			$search = "%".$conditions['SearchKey']."%";
			$conditions['OR'] = array(
				'Institution.name LIKE' => $search,
				'Institution.code LIKE' => $search,
				'InstitutionHistory.name LIKE' => $search,
				'InstitutionHistory.code LIKE' => $search
			);
		}
		unset($conditions['SearchKey']);
		return $conditions;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$fields = array(
			'Institution.*',
			'Area.name',
			'InstitutionStatus.name',
			'InstitutionProvider.name',
			'InstitutionSector.name'
		);
		
		if(strlen($conditions['SearchKey']) != 0) {
			$fields[] = 'InstitutionHistory.*';
		}
		
		$this->unbindModel(array('hasMany' => array('InstitutionSite')));
		$data = $this->find('all', array(
			'fields' => $fields,
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'group' => 'Institution.id',
			'order' => $order
		));
		return $data;
	}
	
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$count = $this->find('count', array(
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $this->paginateConditions($conditions),
			'group' => 'Institution.id'
		));
		return $count;
	}
}
