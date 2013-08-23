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

class SearchBehavior extends ModelBehavior {
	// Generic search function for Students/Teachers/Staff used by InstitutionSiteController
	public function search(Model $model, $search, $params=array()) {
		$class = $model->alias;
		$search = '%' . $search . '%';
		$limit = isset($params['limit']) ? $params['limit'] : false;
		
		$conditions = array(
			'OR' => array(
				$class . '.identification_no LIKE' => $search,
				$class . '.first_name LIKE' => $search,
				$class . '.last_name LIKE' => $search
			)
		);
		$options = array(
			'recursive' => -1,
			'conditions' => $conditions,
			'order' => array($class . '.first_name')
		);
		$count = $model->find('count', $options);
		$data = false;
		if($limit === false || $count < $limit) {
			$options['fields'] = array($class . '.*');
			$data = $model->find('all', $options);
		}
		return $data;
	}
	
	private function getQuery($model, $joins, $conditions, $order, $limit, $page) {
		$class = $model->alias;
		$dbo = $model->getDataSource();
		$query = $dbo->buildStatement(array(
			'fields' => array('NULL', $class.'.id'),
			'table' => $dbo->fullTableName($model),
			'alias' => $class,
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'joins' => $joins,
			'conditions' => $conditions,
			'group' => array($class.'.id'),
			'order' => $order
		), $model);
		return $query;
	}
	
	private function getQueryNotExists($model) {
		$notExists = array(
			'Student' => 'SELECT student_id FROM institution_site_students WHERE student_id = Student.id',
			'Teacher' => 'SELECT teacher_id FROM institution_site_teachers WHERE teacher_id = Teacher.id',
			'Staff' => 'SELECT staff_id FROM institution_site_staff WHERE staff_id = Staff.id'
		);
		return $notExists[$model->alias];
	}
	
	private function getJoins($model) {
		$joins = array();
		$class = $model->alias;
		$obj = '';
		if($class==='Student') { // joins for Students
			$joins[] = array(
				'table' => 'institution_site_students',
				'alias' => 'InstitutionSiteStudent',
				'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
			);
			$joins[] = array(
				'table' => 'institution_site_programmes',
				'alias' => 'InstitutionSiteProgramme',
				'conditions' => array('InstitutionSiteProgramme.id = InstitutionSiteStudent.institution_site_programme_id')
			);
			$obj = 'InstitutionSiteProgramme';
		} else if($class==='Teacher') { // joins for Teachers
			$joins[] = array(
				'table' => 'institution_site_teachers',
				'alias' => 'InstitutionSiteTeacher',
				'conditions' => array('InstitutionSiteTeacher.teacher_id = Teacher.id')
			);
			$obj = 'InstitutionSiteTeacher';
		} else if($class==='Staff') { // joins for Staff
			$joins[] = array(
				'table' => 'institution_site_staff',
				'alias' => 'InstitutionSiteStaff',
				'conditions' => array('InstitutionSiteStaff.staff_id = Staff.id')
			);
			$obj = 'InstitutionSiteStaff';
		}
		$joins[] = array(
			'table' => 'institution_sites',
			'alias' => 'InstitutionSite',
			'conditions' => array('InstitutionSite.id = ' . $obj . '.institution_site_id')
		);
		return $joins;
	}
	
	// To get the query for fetching those records that are not linked to a site
	public function getQueryWithoutSites(Model $model, $params, $order, $limit, $page) {
		$class = $model->alias;
		$joins = array(
			array(
				'table' => 'security_group_users',
				'alias' => 'CreatorGroup',
				'conditions' => array('CreatorGroup.security_user_id = ' . $class . '.created_user_id')
			),
			array(
				'table' => 'security_group_users',
				'alias' => 'UserGroup',
				'conditions' => array(
					'UserGroup.security_group_id = CreatorGroup.security_group_id',
					'UserGroup.security_user_id = ' . $params['userId']
				)
			)
		);
		$conditions = array(
			'NOT EXISTS (' . $this->getQueryNotExists($model) . ')',
			'OR' => array(
				'CreatorGroup.security_group_id IS NULL',
				'AND' => array(
					'CreatorGroup.security_group_id IS NOT NULL',
					'UserGroup.security_group_id IS NOT NULL'
				)
			)
		);
		return $this->getQuery($model, $joins, $conditions, $order, $limit, $page);
	}
	
	// To get the query for fetching those records that are linked to the areas configured
	// in SecurityGroupArea and all the child areas
	public function getQueryFromSecurityAreas(Model $model, $params, $order, $limit, $page) {
		$joins = array(
			array(
				'table' => 'areas',
				'alias' => 'Area',
				'conditions' => array('Area.id = InstitutionSite.area_id')
			),
			array( // to get all child areas including the current parent
				'table' => 'areas',
				'alias' => 'AreaAll',
				'conditions' => array('AreaAll.lft <= Area.lft', 'AreaAll.rght >= Area.rght')
			),
			array(
				'table' => 'security_group_areas',
				'alias' => 'SecurityGroupArea',
				'conditions' => array('SecurityGroupArea.area_id = AreaAll.id')
			),
			array(
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUser',
				'conditions' => array(
					'SecurityGroupUser.security_group_id = SecurityGroupArea.security_group_id',
					'SecurityGroupUser.security_user_id = ' . $params['userId']
				)
			)
		);
		$joins = array_merge($this->getJoins($model), $joins);
		return $this->getQuery($model, $joins, null, $order, $limit, $page);
	}
	
	// To get the query for fetching those records that are linked to the sites configured
	// in SecurityGroupInstitutionSite
	public function getQueryFromSecuritySites(Model $model, $params, $order, $limit, $page) {
		$joins = array(
			array(
				'table' => 'security_group_institution_sites',
				'alias' => 'SecurityGroupInstitutionSite',
				'conditions' => array('SecurityGroupInstitutionSite.institution_site_id = InstitutionSite.id')
			),
			array(
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUser',
				'conditions' => array(
					'SecurityGroupUser.security_group_id = SecurityGroupInstitutionSite.security_group_id',
					'SecurityGroupUser.security_user_id = ' . $params['userId']
				)
			)
		);
		$joins = array_merge($this->getJoins($model), $joins);
		return $this->getQuery($model, $joins, null, $order, $limit, $page);
	}
	
	public function getQueryFromAccess(Model $model, $params, $order, $limit, $page) {
		$class = $model->alias;
		$joins = array(
			array(
				'table' => 'security_user_access',
				'alias' => 'SecurityUserAccess',
				'conditions' => array(
					'SecurityUserAccess.table_id = ' . $class . '.id',
					'SecurityUserAccess.security_user_id = ' . $params['userId'],
					"SecurityUserAccess.table_name = '" . $class . "'"
				)
			)
		);
		return $this->getQuery($model, $joins, null, $order, $limit, $page);
	}
	
	public function paginateJoins(Model $model, $joins, $params) {
		$class = $model->alias;
		$obj = Inflector::singularize(Inflector::tableize($class));
		$table = $obj . '_history';
		$alias = $class . 'History';
		$id = $obj . '_id';
		if(strlen($params['SearchKey']) != 0) {	
			$joins[] = array(
				'table' => $table,
				'alias' => $alias,
				'type' => 'LEFT',
				'conditions' => array(sprintf('%s.%s = %s.id', $alias, $id, $class))
			);
		}
		if(!is_null($params['AdvancedSearch'])) {
			$advanced = $params['AdvancedSearch'];
			if($advanced['area_id'] > 0) { // search by area and all its children
				$joins[] = array(
					'table' => 'areas',
					'alias' => 'Area',
					'conditions' => array('Area.id = ' . $class . '.address_area_id')
				);
				$joins[] = array(
					'table' => 'areas',
					'alias' => 'AreaAll',
					'conditions' => array('AreaAll.lft <= Area.lft', 'AreaAll.rght >= Area.rght', 'AreaAll.id = ' . $advanced['area_id'])
				);
			}
		}
		return $joins;
	}
	
	public function paginateConditions(Model $model, $params) {
		$class = $model->alias;
		$conditions = array();
		if(strlen($params['SearchKey']) != 0) {
			$search = "%".$params['SearchKey']."%";
			$conditions['OR'] = array(
				$class . '.name LIKE' => $search,
				$class . '.code LIKE' => $search,
				$class . 'History.name LIKE' => $search,
				$class . 'History.code LIKE' => $search
			);
		}
		return $conditions;
	}
	
	public function paginateQuery(Model $model, $conditions, $fields=null, $order=null, $limit=null, $page = 1) {
		$class = $model->alias;
		$dbo = $model->getDataSource();
		$queries = array(
			$this->getQueryWithoutSites($model, $conditions, $order, $limit, $page),
			//$this->getQueryFromSecurityAreas($model, $conditions, $order, $limit, $page),
			//$this->getQueryFromSecuritySites($model, $conditions, $order, $limit, $page),
			//$this->getQueryFromAccess($model, $conditions, $order, $limit, $page)
		);
		
		/*
		pr($queries[0]);
		if(!is_null($fields)) {
			$this->searchStart($model, $conditions['userId']);
			$this->insert($model, $conditions['userId'], $queries[0]);
		}
		*/
		
		
		$union = implode(' UNION ', $queries);
		$joins = array(
			array(
				'table' => '(' . $union . ')',
				'alias' => $class . 'Filter',
				'conditions' => array($class . 'Filter.id = ' . $class . '.id')
			)
		);
		$query = $dbo->buildStatement(array(
			'fields' => !is_null($fields) ? $fields : array('COUNT(*) AS COUNT'),
			'table' => $dbo->fullTableName($model),
			'alias' => $class,
			'limit' => $limit,
			'offset' => !is_null($fields) ? (($page-1)*$limit) : null,
			'joins' => $this->paginateJoins($model, $joins, $conditions),
			'conditions' => $this->paginateConditions($model, $conditions),
			'group' => !is_null($fields) ? array($class . '.id') : null,
			'order' => $order
		), $model);
		
		
		if(is_null($fields)) {
			//$this->searchEnd($model, $conditions['userId']);
		}
		//$data = array();
		$data = $dbo->fetchAll($query);
		//pr($data);
		return $data;
	}
	
	public function searchStart(Model $model, $userId) {
		$table = 'students_search_' . $userId;
		$drop = "DROP TABLE IF EXISTS " . $table;
		$create = "
			CREATE TABLE IF NOT EXISTS " . $table . " (
			  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  table_id int(11) NOT NULL
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8
		";
		$model->query($drop);
		$model->query($create);
	}
	
	public function searchEnd(Model $model, $userId) {
		$table = 'students_search_' . $userId;
		$drop = "DROP TABLE IF EXISTS " . $table;
		$model->query($drop);
	}
	
	public function insert(Model $model, $userId, $query) {
		$table = 'students_search_' . $userId;
		$insert = "INSERT INTO " . $table . " " . $query;
		$model->query($insert);
	}
}
