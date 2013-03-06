<?php
App::uses('AppModel', 'Model');

class SecurityRoleInstitutionSite extends AppModel {
	public $belongsTo = array('SecurityRole', 'InstitutionSite');
	
	public function filterData(&$data) {
		$tmpData = $data;
		foreach($tmpData as $key => $obj) {
			if($obj['institution_site_id']==0) {
				unset($data[$key]);
			}
		}
	}
	
	public function fetchSites($institutionList, $conditions) {
		$this->formatResult = true;
		$list = $this->find('all', array(
			'fields' => array('SecurityRoleInstitutionSite.institution_site_id', 'InstitutionSite.name', 'InstitutionSite.institution_id'),
			'conditions' => $conditions,
			'order' => array('InstitutionSite.name')
		));
		
		$sortList = array();
		foreach($list as $key => &$obj) {
			$name = $institutionList[$obj['institution_id']];
			$obj['institution_name'] = $name;
			$sortList[$name . $key] = $obj;
		}
		ksort($sortList);
		
		return array_values($sortList);
	}
	
	public function findSitesByRoles($roleIds) {
		$institutions = array();
		$list = $this->find('all', array(
			'fields' => array('InstitutionSite.id', 'InstitutionSite.institution_id'),
			'conditions' => array('SecurityRoleInstitutionSite.security_role_id' => $roleIds)
		));
		foreach($list as $obj) {
			$site = $obj['InstitutionSite'];
			$institutionId = $site['institution_id'];
			if(!isset($institutions[$institutionId])) {
				$institutions[$institutionId] = array();
			}
			$institutions[$institutionId][] = $site['id'];
		}
		return $institutions;
	}
        
        public function addInstitutionSitetoRole($arrSettings){
                if($arrSettings['security_role_id'] > 0 && $arrSettings['institution_site_id'] > 0){
                    $records = $this->find('all',array('conditions'=> $arrSettings)); //check if there's an Existing Site to a role
                    if(count($records) == 0){
                       $this->save($arrSettings);
                    }
                }
        }
}
