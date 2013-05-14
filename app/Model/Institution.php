<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		
		
	   if($conditions['SearchKey'] != ''){
				$cond = array( 'OR' => array(
						'Institution.name LIKE' => "%".$conditions['SearchKey']."%",
						'Institution.code LIKE' =>"%".$conditions['SearchKey']."%",
						'InstitutionHistory.name LIKE' => "%".$conditions['SearchKey']."%",
						'InstitutionHistory.code LIKE' =>"%".$conditions['SearchKey']."%"
				));
				//$cond = array('OR'=> array('InstitutionSite.id'=>null,array('AND'=>array($cond,array('Institution.id' => $conditions['ids'])))));//$conditions['ids']


				//if string is in the list of allowable institution or in a no site
                $innerCond = array();
                if(array_key_exists('ids', $conditions)){
                    $innerCond =array(
                                'OR'=> array(
                                    'InstitutionSite.id'=>null,
                                    array('Institution.id' => $conditions['ids'])
                                )
                            );
                }
				$cond = array(
							array( 
								'AND'=> array(
									$cond,
									$innerCond
								 )
							)
						);//$conditions['ids']

				$this->unbindModel(array('hasMany' => array('InstitutionSite')));

				$data = $this->find('all',
								array(
									'fields' => array(
										'Institution.*',
										'Area.*',
										'InstitutionStatus.*',
										'InstitutionProvider.*',
										'InstitutionSector.*',
										'InstitutionHistory.*')
									,'joins' => array(
										array(
											'table' => 'institution_history',
											'alias' => 'InstitutionHistory',
											'type' => 'LEFT',
											'conditions' => array( 'InstitutionHistory.institution_id = Institution.id' )
										),
										array(
												'table' => 'institution_sites',
												'alias' => 'InstitutionSite',
												'type' => 'LEFT',
												'conditions' => array(
														'InstitutionSite.institution_id = Institution.id'
												)
										)
									),
									'conditions'=>$cond,
									'limit' => $limit,
									'offset' => (($page-1)*$limit),
									'group' => 'Institution.id',
									'order'=>$order)
						);

				$this->sqlPaginateCount = $this->find('count',
								array(
                                    'fields' => array('DISTINCT Institution.id'),
                                    'joins' => array(
											array(
												'table' => 'institution_history',
												'alias' => 'InstitutionHistory',
												'type' => 'LEFT',
												'conditions' => array( 'InstitutionHistory.institution_id = Institution.id' )
											),
											array(
													'table' => 'institution_sites',
													'alias' => 'InstitutionSite',
													'type' => 'LEFT',
													'conditions' => array(
															'InstitutionSite.institution_id = Institution.id'
													)
											)
										),'conditions'=>$cond
										 ,'group' => 'Institution.id')
				);
           }else{
                $cond = array();
                if(array_key_exists('ids', $conditions)){
                    $cond = array('OR'=> array('InstitutionSite.id'=>null,array('Institution.id' => $conditions['ids']))	);//$conditions['ids']
                }
				//pr($cond);
				$data = $this->find('all',
						array( 
							'limit' => $limit,
							'offset' => (($page-1)*$limit),
							'order'=>$order,
							'joins' => array(
								array(
									'table' => 'institution_sites',
									'alias' => 'InstitutionSite',
									'type' => 'LEFT',
									'conditions' => array( 'InstitutionSite.institution_id = Institution.id' )
								)
							),
							'conditions'=>$cond,
							'group' => 'Institution.id')
				);
				
				$this->sqlPaginateCount = $this->find('count',
						array(
							'joins' => array(
								array(
									'table' => 'institution_sites',
									'alias' => 'InstitutionSite',
									'type' => 'LEFT',
									'conditions' => array( 'InstitutionSite.institution_id = Institution.id')
								)
							)
							,'conditions'=>$cond
							,'group' => 'Institution.id')
				);

           }
           return $data;
	} 
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		return $this->sqlPaginateCount;
	}
}
