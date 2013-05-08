<?php
App::uses('AppController', 'Controller');
// App::uses('String', 'Utility');

class HomeController extends AppController {

	public $helpers = array('Number');

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
		'SecurityUserRole',
		'SecurityRoleFunction'
	);

	public function index() {

		$totalInstitutions = $this->Institution->find('count');
		$totalInstitutionSites = $this->InstitutionSite->find('count');

		try {
			$totalStudent = $this->Student->find('count');
		} catch (MissingTableException $e) {
			$totalStudent = 0;
		}
		
		try {
			$totalTeacher = $this->Teacher->find('count');
		} catch (MissingTableException $e) {
			$totalTeacher = 0;
		}

		try {
			$totalStaff = 	$this->Staff->find('count');
		} catch (MissingTableException $e) {
			$totalStaff = 0;
		}
		$image = array();
		$image = $this->ConfigAttachment->find('first', array('fields' => array('id','file_name'), 'conditions' => array('ConfigAttachment.active' => 1, 'ConfigAttachment.type' => 'dashboard')));

		if(sizeof($image['ConfigAttachment']) > 0){
			$image = array_merge($image['ConfigAttachment']);
			$image['width'] = $this->ConfigItem->getValue('dashboard_img_width');
			$image['height'] = $this->ConfigItem->getValue('dashboard_img_height');
			$image = array_merge($image, $this->ConfigAttachment->getCoordinates($image['file_name']));
			$this->set('image', $image/*$this->ConfigItem->getDashboardMastHead()*/);
			
		}

		$this->set('institutions', $totalInstitutions);
		$this->set('institutionSites', $totalInstitutionSites);
		$this->set('students', $totalStudent);
		$this->set('teachers', $totalTeacher);
		$this->set('staffs', $totalStaff);
		$this->set('latestActivities', $this->getLatestActivities());
		$this->set('message', $this->ConfigItem->getNotice());
		$this->set('adaptation', $this->ConfigItem->getAdaptation());
		$this->set('SeparateThousandsFormat', array(
			'before' => '',
			'places' => 0,
		    'thousands' => ',',
		));
	}
	
	public function details() {
		$this->bodyTitle = 'Account';
		$this->Navigation->addCrumb('Account', array('controller' => 'Home', 'action' => 'details'));
		$this->Navigation->addCrumb('My Details');
		$userId = $this->Auth->user('id');
		$this->SecurityUser->id = $userId;
		$obj = $this->SecurityUser->read();
		$roleIds = $this->SecurityUserRole->find('list', array(
			'fields' => array('SecurityUserRole.security_role_id'),
			'conditions' => array('SecurityUserRole.security_user_id' => $userId)
		));
		$obj['SecurityUser']['roles'] = $this->SecurityRoleFunction->getModules($roleIds);
		$this->set('obj', $obj['SecurityUser']);
	}
	public function detailsEdit() {
		$this->bodyTitle = 'Account';
		$this->Navigation->addCrumb('Account', array('controller' => 'Home', 'action' => 'details'));
		$this->Navigation->addCrumb('Edit My Details');
		$userId = $this->Auth->user('id');
		$this->SecurityUser->formatResult = true;
		$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.id' => $userId)));
		
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
		$this->set('data', $data);
		$this->set('statusOptions', $this->SecurityUser->status);
	}
	
	public function password() {
		$this->bodyTitle = 'Account';
		$this->Navigation->addCrumb('Account', array('controller' => 'Home', 'action' => 'details'));
		$this->Navigation->addCrumb('Change Password');
		
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
		$this->bodyTitle = 'Help';
		$title = 'Support';
		$this->Navigation->addCrumb('Help', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb($title);
		$support = $this->ConfigItem->getSupport();
		$this->set('supportInformation', $support);
		$this->set('subTitle', $title);
		$this->render('Help/'.$this->action);
	}
	
	public function systemInfo() {
		$this->bodyTitle = 'Help';
		$title = 'System Information';
		$this->Navigation->addCrumb('Help', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb($title);
		
		$dbo = ConnectionManager::getDataSource('default');
		$db_store = end(explode('/', $dbo->config['datasource']));
		$db_version = $dbo->getVersion();
		$this->set('db_store', $db_store);
		$this->set('db_version', $db_version);
		$this->set('subTitle', $title);
		$this->render('Help/system_info');
	}
	
	public function license() {
		$this->bodyTitle = 'Help';
		$this->Navigation->addCrumb('Help', array('controller' => 'Home', 'action' => 'support'));
		$this->Navigation->addCrumb('License');
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

	private function getLatestActivities(){
		$query = '';
		$tables = array(
			'Added' => array(
				// Model => db table
				'Institution' => 'institution',
				'InstitutionSite' => 'institution_site',
				'Student' => 'student',
				'Teacher' => 'teacher',
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

		$dbo = ConnectionManager::getDataSource('default');//$this->Institution->getDataSource();
		// $dbo = $this->getDataSource();
		
		$limit = 7;

		foreach ($tables as $key => $element) {
			foreach($element as $innerKey => $innerElement){
				// build fields
				$fields = array();
				$fieldsFormat = "`{$innerKey}`.`id`, 
								%s
								`{$innerKey}`.`first_name` AS `name`,
								`{$innerKey}`.`last_name`,
								'{$innerKey}' AS `table`,
								'{$key}' as `action`,
								`{$innerKey}`.`created`, 
								`SecurityUser`.`first_name` AS `user_first_name`, 
								`SecurityUser`.`last_name` AS `user_last_name` "
							;
				if(stristr($innerKey, 'Institution')){
					$fieldsFormat = "`{$innerKey}`.`id`, 
						%s
						`{$innerKey}`.`name`, 
						NULL as `last_name`,
						'{$innerKey}' AS `table`,
						'{$key}' as `action`,
						`{$innerKey}`.`created`, 
						`SecurityUser`.`first_name` AS `user_first_name`, 
						`SecurityUser`.`last_name` AS `user_last_name` ";

				}
				if(stristr($innerKey, 'history')){
					$table = $dbo->fullTableName($this->{$innerKey}, false, false);
					$table = str_ireplace('_history', '', $table);
					$fields[] = sprintf($fieldsFormat, "`{$innerKey}`.`".$table."_id` AS `parent_table_id`,");

				}else{
					$fields[] = sprintf($fieldsFormat, 'NULL AS `parent_table_id`,');
					
				}
				$params = array(
						'fields' => $fields,
		               'table' => $dbo->fullTableName($this->{$innerKey}),
		               'alias' => "{$innerKey}",
		               'limit' => $limit,
		               'offset' => 0,
		               'joins' => array("LEFT JOIN `{$dbo->config['database']}`.`security_users` AS `SecurityUser` ON (`{$innerKey}`.`created_user_id` = `SecurityUser`.`id`)"),
		               'conditions' => null,
		               'order' => "created DESC",
		               'group' => null
		           );

				$params['recursive'] = 1;

				// build sub-query
		       	$subQuery = $dbo->buildStatement(
		           $params,
		           $this->{$innerKey}
		       );
		        $query .= '('.$subQuery.')';
		        $actions = array_keys($tables);
		        $models = array_keys($element);
		        // glue sub-queries
		        if(!(end($actions) == $key && end($models) == $innerKey)){
			        $query .= ' UNION ';
		        }
			}
		}
		// order query
        $query .= ' ORDER BY `created` DESC ';
        // pr($query);

       $rawData = $dbo->query($query);
       $topActivitiesRawData = array_slice($rawData, 0, $limit);
       $formatedData = array();
		foreach($topActivitiesRawData as $element){
			$matches =array();
			preg_match_all('/((?:^|[A-Z])[a-z]+)/',$element[0]['table'],$matches);
			if(stristr($element[0]['action'], 'Added') && stristr($element[0]['table'], 'InstitutionSite')){
				$element[0]['institution'] = $this->getInstitutionByInstitutionSite($element[0]);

			}elseif(stristr($element[0]['action'], 'Edited') && stristr($element[0]['table'], 'InstitutionSiteHistory')){
				$element[0]['institution'] = $this->getInstitutionByInstitutionSite($element[0]);

			}
			$element[0]['module'] = ''; 
			$element[0]['module'] = implode(' ', $matches[1]);; 
			$element[0]['module'] = trim(str_ireplace('History', '', $element[0]['module'])); 

			$formatedData = array_merge($formatedData, $element);
		}

		foreach ($formatedData as $key => $value) {
			$isDelete = $this->checkActivityDeleteStatus($formatedData[$key]);
			if($isDelete){  $formatedData[$key]['action'] = 'Deleted'; }
		}
       return $formatedData;
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
