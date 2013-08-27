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
	
	private function getQuery($model, $joins, $conditions=null) {
		$class = $model->alias;
		$dbo = $model->getDataSource();
		$query = $dbo->buildStatement(array(
			//'fields' => array('NULL', $class.'.id', $class.'.first_name', $class.'.last_name', $class.'.gender', $class.'.date_of_birth', $class.'.address_area_id'),
			'fields' => array($class.'.id'),
			'table' => $dbo->fullTableName($model),
			'alias' => $class,
			'limit' => null,
			'offset' => null,
			'joins' => $joins,
			'conditions' => $conditions,
			'group' => null,
			'order' => null
		), $model);
		//return '('.$query.')';
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
	public function getQueryWithoutSites(Model $model, $params) {
		$class = $model->alias;
		$joins = array();
		// if the creator of the students does not have a group anymore
		$noGroup = 'NOT EXISTS (SELECT 1 FROM security_group_users AS CreatorGroup WHERE CreatorGroup.security_user_id = ' . $class . '.created_user_id)';
		// or the creator and the user is in the same group
		$sameGroup = 'EXISTS (
			SELECT 1 FROM security_group_users AS CreatorGroup
			JOIN security_group_users AS UserGroup
				ON UserGroup.security_group_id = CreatorGroup.security_group_id
				AND UserGroup.security_user_id = ' . $params['userId'] . '
			WHERE CreatorGroup.security_user_id = ' . $class . '.created_user_id
		)';
		$conditions = array(
			'NOT EXISTS (' . $this->getQueryNotExists($model) . ')',
			'AND' => array('OR' => array($noGroup, $sameGroup))
		);
		return $this->getQuery($model, $joins, $conditions);
	}
	
	// To get the query for fetching those records that are linked to the areas configured
	// in SecurityGroupArea and all the child areas
	public function getQueryFromSecurityAreas(Model $model, $params) {
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
		return $this->getQuery($model, $joins);
	}
	
	// To get the query for fetching those records that are linked to the sites configured
	// in SecurityGroupInstitutionSite
	public function getQueryFromSecuritySites(Model $model, $params) {
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
		return $this->getQuery($model, $joins);
	}
	
	public function getQueryFromAccess(Model $model, $params) {
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
		return $this->getQuery($model, $joins);
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
				$class . '.first_name LIKE' => $search,
				$class . '.last_name LIKE' => $search,
				$class . '.code LIKE' => $search,
				$class . 'History.first_name LIKE' => $search,
				$class . 'History.last_name LIKE' => $search,
				$class . 'History.code LIKE' => $search
			);
		}
		return $conditions;
	}
	
	public function paginateQuery(Model $model, $conditions, $fields=null, $order=null, $limit=null, $page = 1) {
		$class = $model->alias;
		$dbo = $model->getDataSource();
		$queries = array(
			$this->getQueryWithoutSites($model, $conditions),
			$this->getQueryFromSecurityAreas($model, $conditions),
			$this->getQueryFromSecuritySites($model, $conditions),
			$this->getQueryFromAccess($model, $conditions)
		);
		
		
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
			'offset' => (($page-1)*$limit),
			'joins' => $this->paginateJoins($model, $joins, $conditions),
			'conditions' => $this->paginateConditions($model, $conditions),
			'group' => !is_null($fields) ? array($class . '.id') : null,
			'order' => $order
		), $model);
		$data = $dbo->fetchAll($query);
		
		/*
		$data = array();
		if(!is_null($fields)) {
			$this->searchStart($model, $conditions['userId']);
			foreach($queries as $query) {
				$this->insert($model, $conditions['userId'], $query);
			}
		}
		*/
		return $data;
	}
	
	public function getPaginate(Model $model, $conditions, $fields, $order, $limit, $page, $recursive, $extra) {
		$isSuperAdmin = $conditions['isSuperAdmin'];
		$class = $model->alias;
		$fields = array(
			$class.'.id', $class.'.identification_no',
			$class.'.first_name', $class.'.last_name',
			$class.'.gender', $class.'.date_of_birth'
		);
		if(strlen($conditions['SearchKey']) != 0) {
			$fields[] = $class.'History.identification_no AS history_identification_no';
			$fields[] = $class.'History.first_name AS history_first_name';
			$fields[] = $class.'History.last_name AS history_last_name';
		}
		
		$joins = array();
		$data = array();
		// if super admin
		if($isSuperAdmin) {
			$data = $model->find('all', array(
				'recursive' => -1,
				'fields' => $fields,
				'joins' => $this->paginateJoins($model, $joins, $conditions),
				'conditions' => $this->paginateConditions($model, $conditions),
				'limit' => $limit,
				'offset' => (($page-1)*$limit),
				'group' => array($class.'.id'),
				'order' => $order
			));
		} else {
			$data = $this->paginateQuery($model, $conditions, $fields, $order, $limit, $page);
		}
		return $data;
	}
	
	public function getPaginateCount(Model $model, $conditions, $recursive, $extra) {
		$isSuperAdmin = $conditions['isSuperAdmin'];
		$joins = array();
		$count = 0;
		
		if($isSuperAdmin) {
			$count = $model->find('count', array(
				'recursive' => -1,
				'joins' => $this->paginateJoins($model, $joins, $conditions),
				'conditions' => $this->paginateConditions($model, $conditions)
			));
		} else {
			$data = $this->paginateQuery($model, $conditions);
			$count = isset($data[0][0]['COUNT']) ? $data[0][0]['COUNT'] : 0;
		}
		return $count;
	}
	
	public function searchStart(Model $model, $userId) {
		$table = 'students_search_' . $userId;
		$drop = "DROP TABLE IF EXISTS " . $table;
		$create = "
			CREATE TABLE IF NOT EXISTS " . $table . " (
			  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  table_id int(11) NOT NULL,
			  first_name varchar(80),
			  last_name varchar(80),
			  gender char(1) NOT NULL,
			  date_of_birth date NOT NULL,
			  address_area_id int(11),
			  KEY table_id (table_id),
			  KEY first_name (first_name),
			  KEY last_name (last_name),
			  KEY address_area_id (address_area_id),
			  KEY first_last_name (first_name, last_name)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8
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
