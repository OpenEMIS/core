<?php
namespace Report\Model\Behavior;

use ArrayObject;
use ZipArchive;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Network\Response;
use Report\Model\Table\ReportProgressTable as Process;
use Cake\I18n\I18n;
use Cake\Network\Session;
use Cake\I18n\Time;
use Cake\FileSystem\File;
use DateTime;

class ReportListBehavior extends Behavior {
	public $ReportProgress;

	public function initialize(array $config) {
		
		$this->ReportProgress = TableRegistry::get('Report.ReportProgress');
		$this->SecurityUsers  = TableRegistry::get('Security.Users');
		
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.add.beforeSave'] = 'addBeforeSave';
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.afterAction'] = 'afterAction';
		$events['Model.excel.onExcelBeforeWrite'] = 'onExcelBeforeWrite';
		$events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
		$events['ExcelTemplates.Model.onExcelTemplateAfterGenerate'] = 'onExcelTemplateAfterGenerate';
		$events['ExcelTemplates.Model.onCsvGenerateComplete'] = 'onCsvGenerateComplete';
		return $events;
	}

	public function afterAction(Event $event, $config) {
		
		if ($this->_table->action == 'index') {
			/*POCOR-6208 starts*/
			if ($this->_table->Auth->user()['super_admin'] == 0) {
				$SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
	        	$SecurityRoles = TableRegistry::get('Security.SecurityRoles');
	        	$securityFunctions = TableRegistry::get('SecurityFunctions');
	        	$SecurityRoleFunctions = TableRegistry::get('Security.SecurityRoleFunctions');
	        	$userRole = $SecurityGroupUsers->find()->select([
	                        	$SecurityGroupUsers->aliasField('security_role_id'),
	                        	$SecurityRoles->aliasField('name')
	                    	])->leftJoin([$SecurityRoles->alias() => $SecurityRoles->table()], [
	                       		'security_role_id = ' . $SecurityRoles->aliasField('id')
	                    	])->where(['security_user_id' => $this->_table->Auth->user()['id']])
	                    	->toArray(); 
	        	if (!empty($userRole )) { 
		            $roles = [];
		            foreach ($userRole as $key => $value) {
		                $roles[] = $value->security_role_id;
		            }
	        	} 
				$permission_check_array = [
					'InstitutionStatistics',
					'InstitutionStandards',
				];
				if ( in_array($this->_table->alias(), $permission_check_array) ) {
	        		$function = $securityFunctions->find()
	        				->select([$securityFunctions->aliasField('id')])
	        				->where([
	        					$securityFunctions->aliasField('module') => 'Institutions',
	        					$securityFunctions->aliasField('_delete') => $this->_table->alias() .'.'.'remove'
	        				])
	        				->first();
	        	} else {
	        		$function = $securityFunctions->find()
	        				->select([$securityFunctions->aliasField('id')])
	        				->where([
	        					$securityFunctions->aliasField('module') => 'Reports',
	        					$securityFunctions->aliasField('_delete') => $this->_table->alias() .'.'.'delete'
	        				])
	        				->first();
	        	}
	        	
	        	if (!empty($function)) {
	        		$functionId = $function->id;
	        		$data = $SecurityRoleFunctions->find()
	        					->select([$SecurityRoleFunctions->aliasField('_delete')])
				                ->where([
				                    $SecurityRoleFunctions->aliasField('security_role_id IN') => $roles,
				                    $SecurityRoleFunctions->aliasField('security_function_id') => $functionId
				                ])->first();
				    if (!empty($data)) {
				    	$check = $data->_delete;	
				    }
	        	}
			}
			$user = $this->_table->Auth->user();
			$this->_table->controller->set('UsersCheck', $user);
			$this->_table->controller->set('AccessCheck', $check);
			/*POCOR-6208 ENDS*/
			$this->_table->controller->set('ControllerAction', $config);
			$this->_table->ControllerAction->renderView('/Reports/index');
		}
	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
		//print_r($this->ReportProgress); die;
		//print_r($this->_table->alias());die;
		$query = $settings['query'];
		$settings['pagination'] = false;
		$fields = $this->_table->ControllerAction->getFields($this->ReportProgress);
		$fields['current_records']['visible'] = false;
		$fields['total_records']['visible'] = false;
		$fields['error_message']['visible'] = false;
		$fields['file_path']['visible'] = false;
		$fields['module']['visible'] = false;
		$fields['params']['visible'] = false;
		$fields['pid']['visible'] = false;
		$fields['created']['visible'] = true;
		$fields['modified']['visible'] = true;

		$this->_table->fields = $fields;

		$this->_table->ControllerAction->setFieldOrder(['name', 'created', 'modified', 'expiry_date', 'status']);

		// To remove expired reports
		$this->ReportProgress->purge();

		// beside super user, report can only be seen by the one who generate it.
		$conditions = [
			$this->ReportProgress->aliasField('module') => $this->_table->alias()
		];
		
		if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
			$userId = $this->_table->Auth->user('id');
			$conditions[$this->ReportProgress->aliasField('created_user_id')] = $userId;
		}
		//POCOR-6621 fetch report listing based on module and current institute
		$session = new Session();
	    $institutionId  = $session->read('Institution.Institutions.id'); 
		if($this->_table->alias() == 'InstitutionStandards'){ // Inside the institution module report listing
			$query = $this->ReportProgress->find('all')
			//START:POCOR-6629
			// ->where(['JSON_EXTRACT(params, "$.current_institution_id")=' . "'".$institutionId."'",'module'=>'InstitutionStandards'])
			->where(['JSON_EXTRACT(params, "$.current_institution_id")=' . $institutionId,'module'=>'InstitutionStandards'])
			//END:POCOR-6629
			->order([
				$this->ReportProgress->aliasField('created') => 'DESC',
				$this->ReportProgress->aliasField('expiry_date') => 'DESC'
			]);				
		}elseif($this->_table->alias() == 'InstitutionStatistics'){ // Inside the institution module report listing
			$query = $this->ReportProgress->find('all')
			//START:POCOR-6629
			// ->where(['JSON_EXTRACT(params, "$.institution_id")=' . "'".$institutionId."'",'module'=>'InstitutionStatistics'])
			->where(['JSON_EXTRACT(params, "$.institution_id")=' . "'".$institutionId."'",'module'=>'InstitutionStatistics'])
			//END:POCOR-6629
			->order([
				$this->ReportProgress->aliasField('created') => 'DESC',
				$this->ReportProgress->aliasField('expiry_date') => 'DESC'
			]);	
		}else{
			// This is for report module listing
			$query = $this->ReportProgress->find()
			->contain('CreatedUser') //association declared on AppTable
			->where($conditions)
			->order([
				$this->ReportProgress->aliasField('created') => 'DESC',
				$this->ReportProgress->aliasField('expiry_date') => 'DESC'
			]);
		}
		//POCOR-6621 End

		return $query;
	}

	public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request) {
		
		
		if($request->data['Staff']['feature'] == 'Report.StaffPhoto' || $request->data['Students']['feature'] == 'Report.StudentsPhoto'){
			$attr['options'] = ['zip' => 'Zip'];

		} else {
			$attr['options'] = ['xlsx' => 'Excel'];
		}

		$attr['select'] = false;
		return $attr;
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		
		$data[$this->_table->alias()]['locale'] = I18n::locale();
		$session = new Session();
		$data[$this->_table->alias()]['user_id'] = $session->read('Auth.User.id');
		$data[$this->_table->alias()]['super_admin'] = $session->read('Auth.User.super_admin');
		$process = function($model, $entity) use ($data) {
			$errors = $entity->errors();
			if (empty($errors)) {
				$this->_generate($data);
				return true;
			} else {
				return false;
			}
		};
		
		return $process;
	}

	public function onExcelGenerate(Event $event, $settings) {
		$requestData = json_decode($settings['process']['params']);
		$locale = $requestData->locale;
		I18n::locale($locale);
	}

	public function onExcelStartSheet(Event $event, ArrayObject $settings, $totalCount) {
		$process = $settings['process'];
		$this->ReportProgress->updateAll(
			['total_records' => $totalCount],
			['id' => $process->id]
		);
	}

	public function onExcelBeforeWrite(Event $event, ArrayObject $settings, $rowProcessed, $percentCount) {
		$process = $settings['process'];
		if (($percentCount > 0 && $rowProcessed % $percentCount == 0) || $percentCount == 0)  {
			$this->ReportProgress->updateAll(
				['current_records' => $rowProcessed],
				['id' => $process->id]
			);
		}
	}

	public function onExcelEndSheet(Event $event, ArrayObject $settings, $totalProcessed) {
		$process = $settings['process'];
		$this->ReportProgress->updateAll(
			['current_records' => $totalProcessed],
			['id' => $process->id]
		);
	}

	public function onExcelGenerateComplete(Event $event, ArrayObject $settings) {
		$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
		$setTime= $ConfigItems->value("time_zone");
		$timeZone= !empty($setTime) ? $setTime : 'UTC'; //POCOR-6732
		date_default_timezone_set($timeZone);
		$currentTimeZone = date("Y-m-d H:i:s");
		$process = $settings['process'];
		$expiryDate = new Time();
		$expiryDate->addDays(5);
		$this->ReportProgress->updateAll(
			['status' => Process::COMPLETED, 'file_path' => $settings['file_path'], 'expiry_date' => $expiryDate, 'modified' => $currentTimeZone],
			['id' => $process->id]
		);
		$settings['purge'] = false; //for report, dont purge after download.
	}

	public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
	{
		$requestData = json_decode($extra['process']['params']);
		$locale = $requestData->locale;
		I18n::locale($locale);
	}

	public function onExcelTemplateAfterGenerate(Event $event, array $params, ArrayObject $extra)
	{
		
		$process = $extra['process'];
		$expiryDate = new Time();
		$expiryDate->addDays(5);
		$this->ReportProgress->updateAll(
			['status' => Process::COMPLETED, 'file_path' => $extra['file_path'], 'expiry_date' => $expiryDate, 'modified' => new Time()],
			['id' => $process->id]
		);
	}

	public function onCsvGenerateComplete(Event $event, ArrayObject $settings)
	{
		$process = $settings['process'];
		$expiryDate = new Time();
		$expiryDate->addDays(5);
		$this->ReportProgress->updateAll(
			['status' => Process::COMPLETED, 'file_path' => $settings['file_path'], 'expiry_date' => $expiryDate, 'modified' => new Time()],
			['id' => $process->id]
		);
	}

	protected function _generate($data) {
		$alias = $this->_table->alias();
		$featureList = $this->_table->fields['feature']['options'];
		$feature = $data[$alias]['feature'];
		$fields = $this->_table->fields;
		$table = TableRegistry::get($feature);

		// Event:
		// $eventKey = 'Model.Report.onGetName';
		// $event = new Event($eventKey, $this, [$data]);
		// $event = $table->eventManager()->dispatch($event);
		// $name = $event->result;
		// End Event

		$filters = [];
		foreach ($fields as $key => $obj) {
			if (in_array($obj['type'], ['select', 'chosenSelect']) && !in_array($key, ['feature', 'format'])) {
				$selectedOption = $data[$alias][$key];

				if (array_key_exists($selectedOption, $obj['options']) && !empty($obj['options'][$selectedOption])) {
					$value = $obj['options'][$selectedOption];

					// used for institution rubrics
					if (is_array($value)) {
						if (array_key_exists('text', $value)) {
							$value = $value['text'];
						} else {
							$value = '';
						}
					}

					if (!empty($value)) {
						$filters[] = __(trim($value));
					}
				}
			}
		}

		//Check if there exists start and end report date filter, if yes, print out the start and end date.
		if(array_key_exists('report_start_date', $data[$alias]) && array_key_exists('report_end_date', $data[$alias]) && $data[$alias]['report_start_date'] != 0 && $data[$alias]['report_end_date'] != 0) {
				
				$reportStartDate = ($this->_table->formatDate(new DateTime($data[$alias]['report_start_date'])));

				$reportEndDate = ($this->_table->formatDate(new DateTime($data[$alias]['report_end_date'])));

				$filters[] = $reportStartDate. __(' to '). $reportEndDate;
		}
		

		$name = $alias;
		$name .= ': '. $featureList[$feature];
			
		if (!empty($filters)) {
			if($feature == 'Report.InstitutionStudents'){
				unset($filters[1]);
				unset($filters[6]);
				$filterStr = implode(' - ', $filters);
				$name .= ' - '.$filterStr;
			}else{
				$filterStr = implode(' - ', $filters);

				$name .= ' - '.$filterStr;
			}

		}
		/*POCOR-6304 starts*/
		$Institutions = TableRegistry::get('Institution.Institutions');
		$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$EducationGrades = TableRegistry::get('Education.EducationGrades');
		if (array_key_exists('institution_id', $data['InstitutionStatistics'])) {
			$institutionId = $data['InstitutionStatistics']['institution_id'];
	        $institutionData = $Institutions->get($institutionId);
	        $academicPeriodData = $AcademicPeriod->get($data['InstitutionStatistics']['academic_period_id']);
	        if($feature == 'Report.InstitutionStudents'){
	        	$name .= ' - '.$filterStr;
	        }else{
				$name = $featureList[$feature] .' - '. $academicPeriodData->name .' - '. $institutionData->code .' - '. $institutionData->name;
			}
		}
		if (array_key_exists('institution_id', $data['InstitutionStandards'])) {
			$institutionId = $data['InstitutionStandards']['institution_id'];
	        $institutionData = $Institutions->get($institutionId);
	        $academicPeriodData = $AcademicPeriod->get($data['InstitutionStandards']['academic_period_id']);
	        if($feature == 'Report.InstitutionStudents'){
	        	$name .= ' - '.$filterStr;
	        }else{
				$name = $featureList[$feature] .' - '. $academicPeriodData->name .' - '. $institutionData->code .' - '. $institutionData->name;
			}
		}
		/*POCOR-6304 ends*/
		/*POCOR-6439 start : shorting report name as it is throwing error before save*/
		if($feature == 'Report.StudentAttendanceSummary') {
			$academicPeriodData = $AcademicPeriod->get($data['Institutions']['academic_period_id']);
			if ($data['Institutions']['institution_id'] > 0) {
				$institutionData = $Institutions->get($data['Institutions']['institution_id']);
				$institutionCode = $institutionData->code;
			} else {
				$institutionCode = 'All Institutions';
			}
	        if ($data['Institutions']['education_grade_id'] > -1) {
	        	$EducationGradesData = $EducationGrades->get($data['Institutions']['education_grade_id']);
	        	$gradeName = $EducationGradesData->name;
	        } else {
	        	$gradeName = 'All Grades';
	        }
	        $reportStartDate = date("Ymd", strtotime($data[$alias]['report_start_date']));
	        $reportEndDate = date("Ymd", strtotime($data[$alias]['report_end_date']));
	        $reportName = $alias .':'. $featureList[$feature] .' - '. $academicPeriodData->name .' - '. $institutionCode .' - '. $gradeName.' - '. $reportStartDate .' - '.$reportEndDate;
	        $name = $reportName;
		}
		/*POCOR-6439 ends*/		

		$params = $data[$alias];
		
		$ReportProgress = TableRegistry::get('Report.ReportProgress');
		$obj = ['name' => $name, 'module' => $alias, 'params' => $params];
		$id = $ReportProgress->addReport($obj);

		if ($id !== false) {
			$ReportProgress->generate($id, $obj['params']['format']);
		}
	}

	public function download($id) {
		$this->_table->controller->autoRender = false;

		$entity = $this->ReportProgress->get($id);
		$path = $entity->file_path;

		$file = new File($path, false);
		if (!empty($path) && $file->exists()) {
			$pathInfo = pathinfo($path);
			$ext = $pathInfo['extension'];

			// set name of report (with filters and translation)
			$filename = $entity->name . ' - ' . date('Ymd') . 'T' . date('His') . '.' . $ext;

			// Syntax will change in v3.4.x
			$response = $this->_table->controller->response;
			$response->file($path, [
				'name' => $filename,
				'download' => true
			]);

			return $response;

		} else {
			$this->ReportProgress->delete($entity);
			$controller = $this->_table->controller->name;
			$table = $this->_table->alias();
			$this->_table->Alert->error('general.noFile', ['reset'=>true]);
			$url = ['controller' => $controller, 'action' => $table, 'index'];
			return $this->_table->controller->redirect($url);
		}
	}

	private function getFile($phpResourceFile) {
        $file = '';
        while (!feof($phpResourceFile)) {
            $file .= fread($phpResourceFile, 8192);
        }
        fclose($phpResourceFile);

        return $file;
	}
	
	public function zipArchievePhoto($id, $module)
    { 
		if($module == 'Students'){
			$where	= ['is_student' =>1, 'photo_content !=' =>''];
		}
		if($module == 'Staff'){
			$where  = ['is_staff' 	=>1, 'photo_content !=' =>''];
		}
		
		$this->_table->controller->autoRender = false;
		$files = $this->SecurityUsers->find()
				->select(['id','openemis_no','photo_name','photo_content'])
				->where($where)
				->toList();
		
        if (!empty($files) ) {

			$path = WWW_ROOT . 'downloads' . DS . lcfirst($module).'-photo' . DS;
			$zipName = $module.'PhotoReport' . '_' . date('Ymd') . 'T' . date('His') . '.zip';
			$filepath = $path . $zipName;
			
			$zip = new ZipArchive;
			$zip->open($filepath, ZipArchive::CREATE);
            foreach ($files as $file) {

				  $target_file = basename($file->photo_name);
                  $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
			      $zip->addFromString($file->openemis_no.'.'.$imageFileType,  $this->getFile($file->photo_content));
            }
            $zip->close();

            header("Pragma: public", true);
            header("Expires: 0"); // set expiration time
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Type: application/force-download");
            header("Content-Type: application/zip");
            header("Content-Length: ".filesize($filepath));
		    header("Content-Disposition: attachment; filename=".$zipName);
		    
            readfile($filepath);
			// delete file after download
			// unlink($filepath);
			 die;

        } else {
			$controller = $this->_table->controller->name;
			$table = $this->_table->alias();
			$this->_table->Alert->error('general.noFile', ['reset'=>true]);
			$url = ['controller' => $controller, 'action' => $table, 'index'];
			return $this->_table->controller->redirect($url);
        }
    }

    /*POCOR-6208 starts*/
    public function removeReport($id)
    {
    	$entity = $this->ReportProgress->get($id);
    	$file = $entity->file_path;
    	unlink($file);
        $this->ReportProgress->delete($entity);
		$controller = $this->_table->controller->name;
		$table = $this->_table->alias();
		$this->_table->Alert->success('general.delete.success');
		$url = ['controller' => $controller, 'action' => $table, 'index'];
		
		return $this->_table->controller->redirect($url);
    }
    /*POCOR-6208 ends*/
}
