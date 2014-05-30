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
// App::uses('String', 'Utility');

class HomeController extends AppController {
    private $debug = false;
	public $helpers = array('Number');
	private $tableCounts = array(
		'Added' => array(
			// Model => db table
			'Institution' => 'institutions',
			'InstitutionSite' => 'institution_sites',
			'Student' => 'students',
			'Teacher' => 'teachers',
			'Staff' => 'staff'
		),
		'Edited' => array(
			'InstitutionHistory' => 'institution_history',
			'InstitutionSiteHistory' => 'institution_site_history',
			'StudentHistory' => 'student_history',
			'TeacherHistory' => 'teacher_history',
			'StaffHistory' => 'staff_history'
		)
	);
	public $uses = array(
		'ConfigItem',
		'ConfigAttachment',
		'Institution',
		'InstitutionSite',
		'Students.Student',
		'Teachers.Teacher',
		'Staff.Staff',
		'InstitutionHistory',
		'InstitutionSiteHistory',
		'Students.StudentHistory',
		'Teachers.TeacherHistory',
		'Staff.StaffHistory',
		'SecurityUser',
		'SecurityRoleFunction',
		'SecurityGroupUser'
	);
	private function logtimer($str=''){
			if($this->debug == true)
			echo $str." ==> ".date("H:i:s")."<br>\n";
	}
	public function index() {
		$this->logtimer('Start');
		$this->logtimer('Start Attachment');
		$image = array();
		$image = $this->ConfigAttachment->find('first', array('fields' => array('id','file_name'), 'conditions' => array('ConfigAttachment.active' => 1, 'ConfigAttachment.type' => 'dashboard')));

		if(sizeof($image['ConfigAttachment']) > 0){
			$image = array_merge($image['ConfigAttachment']);
			$image['width'] = $this->ConfigItem->getValue('dashboard_img_width');
			$image['height'] = $this->ConfigItem->getValue('dashboard_img_height');
			$image = array_merge($image, $this->ConfigAttachment->getCoordinates($image['file_name']));
			$this->set('image', $image/*$this->ConfigItem->getDashboardMastHead()*/);
			
		}
		$this->logtimer('End Attachment');
		$this->logtimer('Start Notice');
		$this->set('message', $this->ConfigItem->getNotice());
		$this->logtimer('End Notice');
		$this->logtimer('Start Adaptation');
		$this->set('adaptation', $this->ConfigItem->getAdaptation());
		$this->logtimer('End Adaptation');
		
	}
	
	public function details() {
		$this->bodyTitle = 'Account';
		$this->Navigation->addCrumb('Account', array('controller' => 'Home', 'action' => 'details'));
		$this->Navigation->addCrumb('My Details');
		$header = __('My Details');
		$userId = $this->Auth->user('id');
		$this->SecurityUser->id = $userId;
		$obj = $this->SecurityUser->read();

		$obj['groups'] = $this->SecurityGroupUser->getGroupsByUserId($userId);
		/*
		$roleIds = $this->SecurityUserRole->find('list', array(
			'fields' => array('SecurityUserRole.security_role_id'),
			'conditions' => array('SecurityUserRole.security_user_id' => $userId)
		));
		$obj['SecurityUser']['roles'] = $this->SecurityRoleFunction->getModules($roleIds);
		*/
		$this->set(compact('obj','header'));
	}
	public function detailsEdit() {
		$this->bodyTitle = 'Account';
		$this->Navigation->addCrumb('Account', array('controller' => 'Home', 'action' => 'details'));
		$this->Navigation->addCrumb('Edit My Details');
		$header = __('Edit My Details');
		$userId = $this->Auth->user('id');
		$this->SecurityUser->formatResult = true;
		$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.id' => $userId)));
		
		$data['groups'] = $this->SecurityGroupUser->getGroupsByUserId($userId);
		if($this->request->is('post') || $this->request->is('put')) {
			$postData = $this->data['SecurityUser'];
			if($this->SecurityUser->doValidate($postData)) {
				$name = $postData['first_name'] . ' ' . $postData['last_name'];
				$this->Utility->alert($name . ' '.__('has been updated successfully.'));
				$this->Session->write('Auth.User', array_merge($this->Auth->user(), $postData));
				$this->redirect(array('action' => 'details'));
			} else {
				$data = array_merge($data, $postData);
			}
		}
		$this->set(compact('data', 'header'));
		$this->set('statusOptions', $this->SecurityUser->status);
	}
	
	public function password() {
		$this->bodyTitle = 'Account';
		$this->Navigation->addCrumb('Account', array('controller' => 'Home', 'action' => 'details'));
		$this->Navigation->addCrumb('Change Password');
		$header = __('Change Password');
		$allowChangePassword = (bool) $this->ConfigItem->getValue('change_password');
		
		if(!$allowChangePassword) {
			$this->Utility->alert($this->Utility->getMessage('SECURITY_NO_ACCESS'), array('type' => 'warn'));
		}
		$this->set('allowChangePassword', $allowChangePassword);
		
		if($this->request->is('post')) {
			$data = $this->data;
			$data['SecurityUser']['id'] = $this->Auth->user('id');
			$status = array('status' => 'ok', 'msg' => __('Password has been changed.'));
			$error = $this->validateChangePassword($data['SecurityUser']['oldPassword'], $data['SecurityUser']['newPassword'], $data['SecurityUser']['retypePassword']);
			if(!empty($error)){
				$status = array('status' => 'error', 'msg' => __($error));
				//return array('statuts' => 0, 'msg' => $error);
			}else{
				$oldPasswordHash = $this->Auth->password($data['SecurityUser']['oldPassword'], null, true);
				$newPasswordHash = $this->Auth->password($data['SecurityUser']['newPassword'], null, true);
				unset($data['SecurityUser']['oldPassword']);
				unset($data['SecurityUser']['retypePassword']);
				$data['SecurityUser']['password'] = $data['SecurityUser']['newPassword'];
				unset($data['SecurityUser']['newPassword']);
				
				if(!$this->SecurityUser->save($data)){
					$status = array('status' => 'error', 'msg' => __('Please try again later.'));
				} else {
					$username = $this->Auth->user('username');
					$this->log('[' . $username . '] Changing password from ' . $oldPasswordHash . ' to ' . $newPasswordHash, 'security');
					$status = array('status' => 'ok', 'msg' => __('Password has been changed.'));
				}
			}
			//Changed by Adrian
			//$this->Utility->alert($status['status'], $status['msg']);
			$this->Utility->alert($status['msg'],array('type'=>$status['status']));
		}
		
		$this->set(compact('header'));
	}

	private function validateChangePassword($currentPassword, $newPassword, $retypePassword) {
		$error = '';
		$this->SecurityUser->id = $this->Auth->user('id');
		$user = $this->SecurityUser->read();
			if(empty($currentPassword)){
				$error = __('Please enter your current password.');
			}elseif(strcmp(trim($user['SecurityUser']['password']), trim($this->Auth->password($currentPassword))) != 0){
				$error = __('Current password does not match.');
			}
			// pr(preg_match('/^[A-Za-z0-9_]+$/',$newPassword));
			if(empty($error)){
				if(strlen($newPassword) < 1) {
					$error = __('New password required.');
				}else if(strlen($newPassword) < 6) {
					$error = __('Please enter a min of 6 alpha numeric characters.');
				}else if(preg_match('/^[A-Za-z0-9_]+$/',$newPassword) == 0 || preg_match('/^[A-Za-z0-9_]+$/',$newPassword) ==  false) {
					$error = __('Please enter alpha numeric characters.');
				}else if((strlen($newPassword) != strlen($retypePassword)) || $newPassword != $retypePassword){
					$error = __('Passwords do not match.');
				}
			}
		// pr($error);
		return $error;
	}
	
	public function support() {
		$this->bodyTitle = 'Support';
		$title = 'Contact';
		$this->Navigation->addCrumb('Help', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb($title);
		$support = $this->ConfigItem->getSupport();
		$this->set('supportInformation', $support);
		$this->set('subTitle', $title);
		$this->render('Help/'.$this->action);
	}
	
	public function systemInfo() {
		$this->bodyTitle = 'Support';
		$subTitle = 'System Information';
		$this->Navigation->addCrumb('Help', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb($subTitle);
		
		$dbo = ConnectionManager::getDataSource('default');
		$temp = explode('/', $dbo->config['datasource']);
		$dbStore = end($temp);
		
		$dbVersion = $dbo->getVersion();
		$this->set(compact('dbStore', 'dbVersion', 'subTitle'));
		$this->render('Help/system_info');
	}
	
	public function license() {
		$this->bodyTitle = 'Support';
		$this->Navigation->addCrumb('Help', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb('License');
		$this->render('Help/'.$this->action);
	}

	public function partners() {
		$this->bodyTitle = 'Support';
		$title = 'Partners';
		$this->Navigation->addCrumb('Help', array('controller' => 'Home', 'action' => 'support'));
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

	private function getInstitutionByInstitutionSite( $record=NULL){
		$data = '';
		if(is_null($record)){
			return '';
		}
		$rawData = $this->{$record['table']}->find('all',
			array(
				// 'fields' => array('name'),
				'limit' => 1,
				'conditions' => array($record['table'].'.id' => $record['id'])
			)
		);
		if(count($rawData)<1){
			return '';
		}
		return $rawData[0]['Institution']['name'];
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
                    $action =  (isset($this->tableCounts['Added'][$tableName]))?'Added':'Edited';
                    
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

