<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class StaffPosition extends Entity {
	protected $_virtual = ['staff_name', 'start_date_formatted', 'position'];
	
    protected function _getStaffName() {
    	$Users = TableRegistry::get('User.Users');
    	$user = $Users->get($this->security_user_id);
    	return (!empty($user)) ? $user->name : "";
	}

	protected function _getStartDateFormatted(){
        $Users = TableRegistry::get('User.Users');
        return $Users->formatDate($this->start_date);
    } 

    protected function _getPosition(){
    	$InstitutionSiteStaffTable = TableRegistry::get('Institution.InstitutionSiteStaff');
        $staffPosition =  $InstitutionSiteStaffTable
	                      ->find()
	                      ->contain(['Positions.StaffPositionTitles'])
	                      ->where([$InstitutionSiteStaffTable->aliasField('security_user_id') => $this->security_user_id])
	                      ->first();
           
        $data = (!empty($staffPosition['position'])) ? $staffPosition['position']['staff_position_title']['name']: "";     
        return $data;          
    }

}
