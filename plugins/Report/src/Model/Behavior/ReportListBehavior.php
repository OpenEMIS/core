<?php
namespace Report\Model\Behavior;

use ArrayObject;
use ZipArchive;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Report\Model\Table\ReportProgressTable as Process;
use Cake\I18n\I18n;
use Cake\Http\Session;
use Cake\I18n\FrozenTime;
use Cake\FileSystem\File;
use DateTime;
use Cake\Http\Response;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Component\SessionComponent;

class ReportListBehavior extends Behavior {
	public $ReportProgress;

	public function initialize(array $config): void {

		$this->ReportProgress = TableRegistry::getTableLocator()->get('Report.ReportProgress');
		$this->SecurityUsers  = TableRegistry::getTableLocator()->get('Security.Users');

	}

	public function implementedEvents(): array {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.add.beforeSave'] = 'addBeforeSave';
		$events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
		$events['ControllerAction.Model.afterAction'] = 'afterAction';
		$events['Model.excel.onExcelBeforeWrite'] = 'onExcelBeforeWrite';
		$events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
		$events['ExcelTemplates.Model.onExcelTemplateAfterGenerate'] = 'onExcelTemplateAfterGenerate';
		$events['ExcelTemplates.Model.onCsvGenerateComplete'] = 'onCsvGenerateComplete';
		$events['ControllerAction.Model.add.afterSave'] = 'addAfterSave';
		return $events;
	}

	public function afterAction(EventInterface $event, $config) {

		if ($this->_table->action == 'index') {
			/*POCOR-6208 starts*/
			if ($this->_table->Auth->user()['super_admin'] == 0) {
				$SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
	        	$SecurityRoles = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
	        	$securityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions');
	        	$SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
	        	$userRole = $SecurityGroupUsers->find()->select([
	                        	$SecurityGroupUsers->aliasField('security_role_id'),
	                        	$SecurityRoles->aliasField('name')
	                    	])->leftJoin([$SecurityRoles->getAlias() => $SecurityRoles->getTable()], [
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
				if ( in_array($this->_table->getAlias(), $permission_check_array) ) {
	        		$function = $securityFunctions->find()
	        				->select([$securityFunctions->aliasField('id')])
	        				->where([
	        					$securityFunctions->aliasField('module') => 'Institutions',
	        					$securityFunctions->aliasField('_delete') => $this->_table->getAlias() .'.'.'remove'
	        				])
	        				->first();
	        	} else {
	        		$function = $securityFunctions->find()
	        				->select([$securityFunctions->aliasField('id')])
	        				->where([
	        					$securityFunctions->aliasField('module') => 'Reports',
	        					$securityFunctions->aliasField('_delete') => $this->_table->getAlias() .'.'.'delete'
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

	public function indexBeforeAction(EventInterface $event, ArrayObject $settings) {
		//print_r($this->ReportProgress); die;
		//print_r($this->_table->getAlias());die;
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
			$this->ReportProgress->aliasField('module') => $this->_table->getAlias()
		];

		if ($this->_table->Auth->user('super_admin') != 1) { // if user is not super admin, the list will be filtered
			$userId = $this->_table->Auth->user('id');
			$conditions[$this->ReportProgress->aliasField('created_user_id')] = $userId;
		}
		//POCOR-6621 fetch report listing based on module and current institute
		$session = new Session();
		$institutionId = '';
		if($this->_table->controller->getPlugin() != 'Report'){
	    	$institutionId  = $this->_table->getQueryString('institution_id');
	    }
		if($this->_table->getAlias() == 'InstitutionStandards'){ // Inside the institution module report listing
			$query = $this->ReportProgress->find('all')
			//START:POCOR-6629
			// ->where(['JSON_EXTRACT(params, "$.current_institution_id")=' . "'".$institutionId."'",'module'=>'InstitutionStandards'])
			//->where(['JSON_EXTRACT(params, "$.institution_id")=' . $institutionId,'module'=>'InstitutionStandards'])
			//END:POCOR-6629
			->where([
				'JSON_UNQUOTE(JSON_EXTRACT(params, "$.institution_id")) =' => $institutionId,
				'module' => 'InstitutionStandards'
			]) //POCOR-8485
			->order([
				$this->ReportProgress->aliasField('created') => 'DESC',
				$this->ReportProgress->aliasField('expiry_date') => 'DESC'
			]);
		}elseif($this->_table->getAlias() == 'InstitutionStatistics'){ // Inside the institution module report listing
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

	public function onUpdateFieldFormat(EventInterface $event, array $attr, $action, ServerRequest $request)
	{
		if($request->getData()['Staff']['feature'] == 'Report.StaffPhoto' || $request->getData()['Students']['feature'] == 'Report.StudentsPhoto'){
			$attr['options'] = ['zip' => 'Zip'];

		} else {
			$attr['options'] = ['xlsx' => 'Excel'];
		}

		$attr['select'] = false;
		return $attr;
	}

	public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $data) {

		$data[$this->_table->getAlias()]['locale'] = I18n::getLocale();
		$session = new Session();
		$data[$this->_table->getAlias()]['user_id'] = $session->read('Auth.User.id');
		$data[$this->_table->getAlias()]['super_admin'] = $session->read('Auth.User.super_admin');
		$process = function($model, $entity) use ($data) {
			$errors = $entity->getErrors();
			if (empty($errors)) {
				$this->_generate($data);
				return true;
			} else {
				return false;
			}
		};

		return $process;
	}

	public function onExcelGenerate(EventInterface $event, $settings) {
		$requestData = json_decode($settings['process']['params']);
		$locale = $requestData->locale;
		I18n::getLocale($locale);
	}

	public function onExcelStartSheet(EventInterface $event, ArrayObject $settings, $totalCount) {
		$process = $settings['process'];
		$this->ReportProgress->updateAll(
			['total_records' => $totalCount],
			['id' => $process->id]
		);
	}

	public function onExcelBeforeWrite(EventInterface $event, ArrayObject $settings, $rowProcessed, $percentCount) {
		$process = $settings['process'];
		if (($percentCount > 0 && $rowProcessed % $percentCount == 0) || $percentCount == 0)  {
			$this->ReportProgress->updateAll(
				['current_records' => $rowProcessed],
				['id' => $process->id]
			);
		}
	}

	public function onExcelEndSheet(EventInterface $event, ArrayObject $settings, $totalProcessed) {
		$process = $settings['process'];
		$this->ReportProgress->updateAll(
			['current_records' => $totalProcessed],
			['id' => $process->id]
		);
	}

	public function onExcelGenerateComplete(EventInterface $event, ArrayObject $settings) {
		$ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
		$setTime= $ConfigItems->value("time_zone");
		$timeZone= !empty($setTime) ? $setTime : 'UTC'; //POCOR-6732
		date_default_timezone_set($timeZone);
		$currentTimeZone = date("Y-m-d H:i:s");
		$process = $settings['process'];
		$expiryDate = new FrozenTime(); //POCOR-8627
		$expiryDate->addDays(5);
		$this->ReportProgress->updateAll(
			['status' => Process::COMPLETED, 'file_path' => $settings['file_path'], 'expiry_date' => $expiryDate, 'modified' => $currentTimeZone],
			['id' => $process->id]
		);
		$settings['purge'] = false; //for report, dont purge after download.
	}

	public function onExcelTemplateBeforeGenerate(EventInterface $event, array $params, ArrayObject $extra)
	{
		$requestData = json_decode($extra['process']['params']);
		$locale = $requestData->locale;
		I18n::getLocale($locale);
	}

	public function onExcelTemplateAfterGenerate(EventInterface $event, array $params, ArrayObject $extra)
	{
		if (!$extra->offsetExists('process') || !$extra->offsetExists('file_path')) {
			return;
		}
		$process = $extra['process'];
		$processId = is_object($process) ? $process->id : ($process['id'] ?? null);
		if ($processId === null) {
			return;
		}
		$expiryDate = new FrozenTime();
		$expiryDate->addDays(5);
		$this->ReportProgress->updateAll(
			['status' => Process::COMPLETED, 'file_path' => $extra['file_path'], 'expiry_date' => $expiryDate, 'modified' => new FrozenTime()],
			['id' => $processId]
		);
	}

	public function onCsvGenerateComplete(EventInterface $event, ArrayObject $settings)
	{
		$process = $settings['process'];
		$expiryDate = new FrozenTime();
		$expiryDate->addDays(5);
		$this->ReportProgress->updateAll(
			['status' => Process::COMPLETED, 'file_path' => $settings['file_path'], 'expiry_date' => $expiryDate, 'modified' => new FrozenTime()],
			['id' => $process->id]
		);
	}

	protected function _generate($data) {
		$alias = $this->_table->getAlias();
		$featureList = $this->_table->fields['feature']['options'];
		$feature = $data[$alias]['feature'];
		$fields = $this->_table->fields;
		// $table = TableRegistry::getTableLocator()->get($feature);
		if($alias !='CustomReports' && $alias !='InstitutionStatistics'){
			$table = TableRegistry::getTableLocator()->get($feature);
		}

		// Event:
		// $eventKey = 'Model.Report.onGetName';
		// $event = new Event($eventKey, $this, [$data]);
		// $event = $table->eventManager()->dispatch($event);
		// $name = $event->result;
		// End Event

		/*$filters = [];
		foreach ($fields as $key => $obj) {
			if (in_array($obj['type'], ['select', 'chosenSelect']) && !in_array($key, ['feature', 'format'])) {
				$selectedOption = $data[$alias][$key];

				if (isset($obj['options'][$selectedOption]) && !empty($obj['options'][$selectedOption])) {
					$value = $obj['options'][$selectedOption];

					// used for institution rubrics
					if (is_array($value)) {
						if (isset($value['text'])) {
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
		}*/
		//POCOR-9484 start A few lines of code are updated below.The multiselect dropdown was not working previously in above code.
		$filters = [];
		foreach ($fields as $key => $obj) {

		    if (
		        !isset($obj['type']) ||
		        !in_array($obj['type'], ['select', 'chosenSelect']) ||
		        in_array($key, ['feature', 'format'])
		    ) {
		        continue;
		    }

		    $selectedOption = $data[$alias][$key] ?? null;

		    // Normalize selected value
		    $selectedValues = is_array($selectedOption)
		        ? $selectedOption
		        : [$selectedOption];

		    // Ensure options exist and are valid
		    if (empty($obj['options']) || !is_array($obj['options'])) {
		        continue;
		    }

		    foreach ($selectedValues as $opt) {

		        //Prevent illegal offset type for multiselect dropdown
		        if (!is_scalar($opt)) {
		            continue;
		        }

		        if (!array_key_exists($opt, $obj['options'])) {
		            continue;
		        }

		        $value = $obj['options'][$opt];

		        // Handle structured options
		        if (is_array($value)) {
		            $value = $value['text'] ?? '';
		        }

		        if (!empty($value)) {
		            $filters[] = __(trim($value));
		        }
		    }
		} //POCOR-9484 end

		//Check if there exists start and end report date filter, if yes, print out the start and end date.
		if(isset($data[$alias]['report_start_date']) && isset($data[$alias]['report_end_date']) && $data[$alias]['report_start_date'] != 0 && $data[$alias]['report_end_date'] != 0) {

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
		$Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
		$AcademicPeriod = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
		$EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
		if (array_key_exists('institution_id', (array)$data['InstitutionStatistics'])) {
			// $institutionId  = $this->_table->getQueryString('institution_id'); working
			$institutionId = $data['InstitutionStatistics']['institution_id'];
	        $institutionData = $Institutions->get($institutionId);
	        $academicPeriodData = $AcademicPeriod->get($data['InstitutionStatistics']['academic_period_id']);
	        if($feature == 'Report.InstitutionStudents'){
	        	$name .= ' - '.$filterStr;
	        }else{
				$name = $featureList[$feature] .' - '. $academicPeriodData->name .' - '. $institutionData->code .' - '. $institutionData->name;
			}
		}
		if (array_key_exists('institution_id', (array)$data['InstitutionStandards'])) {
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

		$ReportProgress = TableRegistry::getTableLocator()->get('Report.ReportProgress');
		$obj = ['name' => $name, 'module' => $alias, 'params' => $params];
		$id = $ReportProgress->addReport($obj);

		if ($id !== false) {
			$ReportProgress->generate($id, $obj['params']['format']);
		}
	}

	/**
	 * Handles the download of a file associated with a given entity ID.
	 * POCOR-8755
	 * This method retrieves a file path from the entity, validates its existence 
	 * and readability, and serves the file as a downloadable attachment. 
	 * 
	 * @param int $id The ID of the entity whose file is to be downloaded.
	 * @return \Cake\Http\Response|null Redirects on error, otherwise outputs the file.
	 * */
	public function download($id) 
	{
	    $this->_table->controller->autoRender = false;
	    // Clear any existing output
	    if (ob_get_level()) {
	        ob_end_clean();
	    }
	    
	    try {
	        $entity = $this->ReportProgress->get($id);
	        $path = $entity->file_path;
	        
	        if (empty($path) || !file_exists($path) || !is_readable($path)) {
	            throw new Exception('File not found or not readable');
	        }
	        
	        $pathInfo = pathinfo($path);
	        $ext = $pathInfo['extension'];
	        $filename = $entity->name . ' - ' . date('Ymd') . 'T' . date('His') . '.' . $ext;
	        
	        // Get file size
	        $fileSize = filesize($path);
	        if ($fileSize === false) {
	            throw new Exception('Unable to determine file size');
	        }
	        
	        // Get MIME type
	        $contentType = mime_content_type($path);
	        if ($contentType === false) {
	            $contentType = 'application/octet-stream'; // fallback
	        }
	        
	        // Set headers
	        header('Content-Type: ' . $contentType);
	        header('Content-Description: File Transfer');
	        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
	        header('Content-Transfer-Encoding: binary');
	        header('Expires: 0');
	        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	        header('Pragma: public');
	        header('Content-Length: ' . $fileSize);
	        
	        // Output file in chunks to handle large files
	        $handle = fopen($path, 'rb');
	        if ($handle === false) {
	            throw new Exception('Unable to open file');
	        }
	        
	        while (!feof($handle)) {
	            echo fread($handle, 8192);
	            flush();
	        }
	        
	        fclose($handle);
	        exit;
	        
	    } catch (Exception $e) {
	        error_log('Download error: ' . $e->getMessage());
	        $this->ReportProgress->delete($entity);
	        $controller = $this->_table->controller->getName();
	        $table = $this->_table->getAlias();
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
			$controller = $this->_table->controller->getName();
			$table = $this->_table->getAlias();
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
		$controller = $this->_table->controller->getName();
		$table = $this->_table->getAlias();
		$this->_table->Alert->success('general.delete.success');
		$institution_id = $this->_table->request->getQuery('institution_id');
		$queryString = $this->_table->paramsEncode(['institution_id' => $institution_id]); 
		$url = ['controller' => $controller, 'action' => $table, 'index', $queryString];

		return $this->_table->controller->redirect($url);
    }
    /*POCOR-6208 ends*/

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
    	if($this->_table->controller->getPlugin() == 'Report'){
    		$controller = $this->_table->controller->getName();
			$table = $this->_table->getAlias();
			$this->_table->Alert->success('general.add.success');
			$url = ['controller' => $controller, 'action' => $table, 'index'];
			return $this->_table->controller->redirect($url);
		}
    }
}
