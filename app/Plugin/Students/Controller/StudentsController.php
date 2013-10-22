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

App::uses('Sanitize', 'Utility');
App::uses('DateTime', 'Component');
App::uses('ImageMeta', 'Image');
App::uses('ImageValidate', 'Image');

class StudentsController extends StudentsAppController {
    public $studentId;
    public $studentObj;
    private  $debug = false;
    public $uses = array(
	'Area',
        'Institution',
	'InstitutionSite',
	'InstitutionSiteProgramme',
        'InstitutionSiteClass',
        'InstitutionSiteType',
	'InstitutionSiteClassGradeStudent',
        'Students.Student',
        'Students.StudentHistory',
        'Students.StudentCustomField',
        'Students.StudentCustomFieldOption',
        'Students.StudentCustomValue',
        'Students.StudentAttachment',
        'Students.StudentBehaviour',
        'Students.StudentBehaviourCategory',
        'Students.StudentAttendance',
        'Students.StudentAssessment',
        'SchoolYear',
	'ConfigItem'
    );
        
    public $helpers = array('Js' => array('Jquery'), 'Paginator');
	
    public $components = array(
        'UserSession',
        'Paginator',
        'FileAttachment' => array(
            'model' => 'Students.StudentAttachment',
            'foreignKey' => 'student_id'
        ),
		'AccessControl'
    );

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Navigation->addCrumb('Students', array('controller' => 'Students', 'action' => 'index'));
		$actions = array('index', 'advanced', 'add', 'viewStudent');
		if(in_array($this->action, $actions)) {
			$this->bodyTitle = 'Students';
		} else {
			if($this->Session->check('StudentId') && $this->action!=='Home') {
	            $this->studentId = $this->Session->read('StudentId');
				$this->studentObj = $this->Session->read('StudentObj');
				$studentFirstName = $this->Student->field('first_name', array('Student.id' => $this->studentId));
				$studentLastName = $this->Student->field('last_name', array('Student.id' => $this->studentId));
				$name = $studentFirstName ." ". $studentLastName;
				$this->bodyTitle = $name;
				$this->Navigation->addCrumb($name, array('controller' => 'Students', 'action' => 'view'));
			} 
		}
    }
    
    public function index() {
		$this->Navigation->addCrumb('List of Students');
				
        if ($this->request->is('post')){
            if(isset($this->request->data['Student']['SearchField'])){
                $this->request->data['Student']['SearchField'] = Sanitize::escape($this->request->data['Student']['SearchField']);
                if($this->request->data['Student']['SearchField'] != $this->Session->read('Search.SearchFieldStudent')) {
                    $this->Session->delete('Search.SearchFieldStudent');
                    $this->Session->write('Search.SearchFieldStudent', $this->request->data['Student']['SearchField']);
                }
            }

            if(isset($this->request->data['sortdir']) && isset($this->request->data['order'])) {
                if($this->request->data['sortdir'] != $this->Session->read('Search.sortdirStudent')) {
                    $this->Session->delete('Search.sortdirStudent');
                    $this->Session->write('Search.sortdirStudent', $this->request->data['sortdir']);
                }
                if($this->request->data['order'] != $this->Session->read('Search.orderStudent')) {
                    $this->Session->delete('Search.orderStudent');
                    $this->Session->write('Search.orderStudent', $this->request->data['order']);
                }
            }
        }
		
        $fieldordername = ($this->Session->read('Search.orderStudent'))?$this->Session->read('Search.orderStudent'):'Student.first_name';
        $fieldorderdir = ($this->Session->read('Search.sortdirStudent'))?$this->Session->read('Search.sortdirStudent'):'asc';
		
		$searchKey = stripslashes($this->Session->read('Search.SearchFieldStudent'));
		$conditions = array(
			'SearchKey' => $searchKey, 
			'AdvancedSearch' => $this->Session->check('Student.AdvancedSearch') ? $this->Session->read('Student.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id')
		);
		$order = array('order' => array($fieldordername => $fieldorderdir));
		$limit = ($this->Session->read('Search.perpageStudent')) ? $this->Session->read('Search.perpageStudent') : 30;
        $this->Paginator->settings = array_merge(array('limit' => $limit, 'maxLimit' => 100), $order);
		
        $data = $this->paginate('Student', $conditions);
		if(empty($searchKey) && !$this->Session->check('Student.AdvancedSearch')) {
			if(count($data) == 1 && !$this->AccessControl->check($this->params['controller'], 'add')) {
				$this->redirect(array('action' => 'viewStudent', $data[0]['Student']['id']));
			}
		}
		if(empty($data) && !$this->request->is('ajax')) {
			$this->Utility->alert($this->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
		}
        $this->set('students', $data);
        $this->set('sortedcol', $fieldordername);
        $this->set('sorteddir', ($fieldorderdir == 'asc')?'up':'down');
		$this->set('searchField', $searchKey);
        if($this->request->is('post')){
            $this->render('index_records','ajax');
        }
    }
	
	public function advanced() {
		$key = 'Student.AdvancedSearch';
		if($this->request->is('get')) {
			if($this->request->is('ajax')) {
				$this->autoRender = false;
				$search = $this->params->query['term'];
				$result = $this->Area->autocomplete($search);
				return json_encode($result);
			} else {
				$this->Navigation->addCrumb('List of Students', array('controller' => 'Students', 'action' => 'index'));
				$this->Navigation->addCrumb('Advanced Search');
				
				if(isset($this->params->pass[0])) {
					if(intval($this->params->pass[0])===0) {
						$this->Session->delete($key);
						$this->redirect(array('action' => 'index'));
					}
				}
			}
		} else {
			//$search = $this->data['Search'];
                        $search = $this->data;
			if(!empty($search)) {
				$this->Session->write($key, $search);
			}
			$this->redirect(array('action' => 'index'));
		}
	}
        
        public function getCustomFieldsSearch($sitetype = 0,$customfields = 'Student'){
             $this->layout = false;
             $arrSettings = array(
                                                            'CustomField'=>$customfields.'CustomField',
                                                            'CustomFieldOption'=>$customfields.'CustomFieldOption',
                                                            'CustomValue'=>$customfields.'CustomValue',
                                                            'Year'=>''
                                                        );
             if($this->{$customfields}->hasField('institution_site_type_id')){
                 $arrSettings = array_merge(array('institutionSiteTypeId'=>$sitetype),$arrSettings);
             }
             $arrCustFields = array($customfields => $arrSettings);
             
            $instituionSiteCustField = $this->Components->load('CustomField',$arrCustFields[$customfields]);
            $dataFields[$customfields] = $instituionSiteCustField->getCustomFields();
            $types = $this->InstitutionSiteType->findList(1);
            $this->set("customfields",array($customfields));
            $this->set('types',  $types);        
            $this->set('typeSelected',  $sitetype);
            $this->set('dataFields',  $dataFields);
            $this->render('/Elements/customfields/search');
        }
	
	public function viewStudent($id) {
        $this->Session->write('StudentId', $id);
        $obj = $this->Student->find('first',array('conditions'=>array('Student.id' => $id)));
        $this->Session->write('StudentObj', $obj);
        $this->DateTime->getConfigDateFormat();

        $this->redirect(array('action' => 'view'));
    }
	
	public function view() {
		$this->Navigation->addCrumb('Overview');
		$this->Student->id = $this->Session->read('StudentId');
        $data = $this->Student->read();

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('data', $data);
    }
	
	public function edit() {
		$this->Navigation->addCrumb('Edit');
                $this->Student->id = $this->Session->read('StudentId');

                $imgValidate = new ImageValidate();
		$data = $this->data;
		
		if($this->request->is('post')) {
                        $reset_image = $data['Student']['reset_image'];

                        $img = new ImageMeta($this->data['Student']['photo_content']);
                        unset($data['Student']['photo_content']);

                        if($reset_image == 0){
                            $validated = $imgValidate->validateImage($img);

                            if($img->getFileUploadError() !== 4 && $validated['error'] < 1){
                                $data['Student']['photo_content'] = $img->getContent();
                                $img->setContent('');
                                $data['Student']['photo_name'] = $img->getFilename();
                            }
                        }else{
                            $data['Student']['photo_content'] = '';
                            $data['Student']['photo_name'] = '';
                        }
                        $this->Student->set($data);
                        if($this->Student->validates() && ($reset_image == 1 || $validated['error'] < 1)) {
                            unset($data['Student']['reset_image']);
                            $rec = $this->Student->save($data);
                            $this->redirect(array('action' => 'view'));
                        }else{
                            // display message of validation error
                            $this->set('imageUploadError', __(array_shift($validated['message'])));
                        }
		} else {
			$data = $this->Student->find('first',array('conditions'=>array('id'=>$this->Session->read('StudentId'))));
		}
		
		$gender = array(0 => __('--Select--'), 'M' => __('Male'), 'F' => __('Female'));
		$this->set('autoid', $this->getUniqueID());
		$this->set('gender', $gender);
		$this->set('data', $data);
    }

    public function classes(){
        $this->Navigation->addCrumb(ucfirst($this->action));
        $studentId = $this->Session->read('StudentId');
        $data = array();
		$classes = $this->InstitutionSiteClassGradeStudent->getListOfClassByStudent($studentId);
		
        foreach($classes as $row) {
			$key = $row['Institution']['name'] . ' - ' . $row['InstitutionSite']['name'];
			$data[$key][] = $row;
        }
		if(empty($data)) {
			$this->Utility->alert($this->Utility->getMessage('NO_CLASSES'), array('type' => 'info', 'dismissOnClick' => false));
		}
        $this->set('data', $data);
    }

    public function fetchImage($id){
		$this->autoRender = false;
		
		$url = Router::url('/Students/img/default_student_profile.jpg', true);
        $mime_types = ImageMeta::mimeTypes();

        $imageRawData = $this->Student->findById($id);
		$imageFilename = $imageRawData['Student']['photo_name'];
		$fileExt = pathinfo(strtolower($imageFilename), PATHINFO_EXTENSION);
	
		
		if(empty($imageRawData['Student']['photo_content']) || empty($imageRawData['Student']['photo_name']) || !in_array($mime_types[$fileExt], $mime_types)){
			if($this->Session->check('Student.defaultImg'))
    		{
				$imageContent = $this->Session->read('Student.defaultImg');
			}else{
				$imageContent = file_get_contents($url);
				$this->Session->write('Student.defaultImg', $imageContent);
			}
			echo $imageContent;
		}else{
			$imageContent = $imageRawData['Student']['photo_content'];
			header("Content-type: " . $mime_types[$fileExt]);
			echo $imageContent;
		}
    }
	
    public function add() {
		$this->Navigation->addCrumb('Add new Student');
                $imgValidate = new ImageValidate();
                $data = $this->data;
		if($this->request->is('post')) {
                        $reset_image = $data['Student']['reset_image'];

                        $img = new ImageMeta($this->data['Student']['photo_content']);
                        unset($data['Student']['photo_content']);

                        if($reset_image == 0){
                            $validated = $imgValidate->validateImage($img);

                            if($img->getFileUploadError() !== 4 && $validated['error'] < 1){
                                $data['Student']['photo_content'] = $img->getContent();
                                $img->setContent('');
                                $data['Student']['photo_name'] = $img->getFilename();
                            }
                        }else{
                            $data['Student']['photo_content'] = '';
                            $data['Student']['photo_name'] = '';
                        }
			$this->Student->set($data);
			if($this->Student->validates()  && ($reset_image == 1 || $validated['error'] < 1)) {
                             unset($data['Student']['reset_image']);
                             
				$newStudentRec =  $this->Student->save($data);
				// create the session for successfully adding of student
                $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view');
				$this->redirect(array('action' => 'viewStudent', $newStudentRec['Student']['id']));
			}else{
                                $this->set('imageUploadError', __(array_shift($validated['message'])));
				$errors = $this->Student->validationErrors;
                                
				if($this->getUniqueID()!=''){ // If Auto id
					if(isset($errors["identification_no"])){ // If its ID error
						if(sizeof($errors)<2){ // If only 1 faulty
							$this->Student->set($this->request->data);
							do{
								$this->request->data["Student"]["identification_no"] = $this->getUniqueID();
								$conditions = array(
									'Student.identification_no' => $this->request->data["Student"]["identification_no"]
								);
							}while($this->Student->hasAny($conditions));
							$this->Student->set($this->request->data);
							$newStudentRec =  $this->Student->save($this->request->data);
							// create the session for successfully adding of student
							$this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view'); 
							$this->redirect(array('action' => 'viewStudent', $newStudentRec['Student']['id']));
						}
					}
				}
				
			}
		}
		$gender = array(0 => __('--Select--'), 'M' => __('Male'), 'F' => __('Female'));
		$this->set('autoid', $this->getUniqueID());
		$this->set('gender', $gender);
		$this->set('data', $this->data);
	}

    public function delete() {
        $id = $this->Session->read('StudentId');
        $name = $this->Student->field('first_name', array('Student.id' => $id));
        $this->Student->delete($id);
        // $this->Utility->alert($name . __(' have been deleted successfully.'));
        $this->Utility->alert(sprintf(__("%s have been deleted successfully."), $name));
        $this->redirect(array('action' => 'index'));
    }

    public function additional() {
		$this->Navigation->addCrumb('More');
		
        // get all student custom field in order
        $datafields = $this->StudentCustomField->find('all', array('conditions' => array('StudentCustomField.visible' => 1), 'order'=>'StudentCustomField.order'));

        $this->StudentCustomValue->unbindModel(
            array('belongsTo' => array('Student'))
            );
        $datavalues = $this->StudentCustomValue->find('all', array(
            'conditions'=> array('StudentCustomValue.student_id' => $this->studentId))
        );

        // pr($datafields);
        // pr($datavalues);
        $tmp=array();
        foreach($datavalues as $arrV){
            $tmp[$arrV['StudentCustomField']['id']][] = $arrV['StudentCustomValue'];
            // pr($arrV);
        }
        $datavalues = $tmp;
        // pr($tmp);die;
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('datafields', $datafields);
        $this->set('datavalues', $tmp);
    }

    public function additionalEdit() {
		$this->Navigation->addCrumb('Edit More');

        if ($this->request->is('post')) {
            //pr($this->data);
            //die();
            $arrFields = array('textbox','dropdown','checkbox','textarea');
            /**
             * Note to Preserve the Primary Key to avoid exhausting the max PK limit
             */
            foreach($arrFields as $fieldVal){
                // pr($fieldVal);
                // pr($this->request->data['StudentCustomValue']);
                if(!isset($this->request->data['StudentCustomValue'][$fieldVal])) continue;
                foreach($this->request->data['StudentCustomValue'][$fieldVal] as $key => $val){

                    if($fieldVal == "checkbox"){

                        $arrCustomValues = $this->StudentCustomValue->find('list',array('fields'=>array('value'),'conditions' => array('StudentCustomValue.student_id' => $this->studentId,'StudentCustomValue.student_custom_field_id' => $key)));

                        $tmp = array();
                            if(count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
                            foreach($arrCustomValues as $pk => $intVal){
                                //pr($val['value']); echo "$intVal";
                                if(!in_array($intVal, $val['value'])){
                                    //echo "not in db so remove \n";
                                 $this->StudentCustomValue->delete($pk);
                             }
                         }
                         $ctr = 0;
                            if(count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
                            foreach($val['value'] as $intVal){
                                //pr($val['value']); echo "$intVal";
                                if(!in_array($intVal, $arrCustomValues)){
                                    $this->StudentCustomValue->create();
                                    $arrV['student_custom_field_id']  = $key;
                                    $arrV['value']  = $val['value'][$ctr];
                                    $arrV['student_id']  = $this->StudentId;
                                    $this->StudentCustomValue->save($arrV);
                                    unset($arrCustomValues[$ctr]);
                                }
                                $ctr++;
                            }
                    }else{ // if editing reuse the Primary KEY; so just update the record
                        $datafields = $this->StudentCustomValue->find('first',array('fields'=>array('id','value'),'conditions' => array('StudentCustomValue.student_id' => $this->studentId,'StudentCustomValue.student_custom_field_id' => $key)));
                        $this->StudentCustomValue->create();
                        if($datafields) $this->StudentCustomValue->id = $datafields['StudentCustomValue']['id'];
                        $arrV['student_custom_field_id'] = $key;
                        $arrV['value'] = $val['value'];
                        $arrV['student_id'] = $this->studentId;
                        $this->StudentCustomValue->save($arrV);
                    }

                }
            }
            $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'additional');
            $this->redirect(array('action' => 'additional'));
        }
        $this->StudentCustomField->unbindModel(array('hasMany' => array('StudentCustomFieldOption')));

        $this->StudentCustomField->bindModel(array(
            'hasMany' => array(
                'StudentCustomFieldOption' => array(
                    'conditions' => array(
                        'StudentCustomFieldOption.visible' => 1),
                    'order' => array('StudentCustomFieldOption.order' => "ASC")
                )
            )
        ));
        $datafields = $this->StudentCustomField->find('all', array('conditions' => array('StudentCustomField.visible' => 1), 'order'=>'StudentCustomField.order'));
        $this->StudentCustomValue->unbindModel(
            array('belongsTo' => array('Student'))
            );
        $datavalues = $this->StudentCustomValue->find('all',array('conditions'=>array('StudentCustomValue.student_id' => $this->studentId)));
        $tmp=array();
        foreach($datavalues as $arrV){
            $tmp[$arrV['StudentCustomField']['id']][] = $arrV['StudentCustomValue'];
        }
        $datavalues = $tmp;
        $this->set('datafields',$datafields);
        $this->set('datavalues',$tmp);
    }

    public function attachments() {
        $this->Navigation->addCrumb('Attachments');
        $id = $this->Session->read('StudentId');
        $data = $this->FileAttachment->getList($id);
        $this->set('data', $data);
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/view');
    }
    
    public function attachmentsEdit() {
        $this->Navigation->addCrumb('Edit Attachments');
        $id = $this->Session->read('StudentId');
        
        if($this->request->is('post')) { // save
            $errors = $this->FileAttachment->saveAll($this->data, $_FILES, $id);
            if(sizeof($errors) == 0) {
                $this->Utility->alert(__('Files have been saved successfully.'));
                $this->redirect(array('action' => 'attachments'));
            } else {
                $this->Utility->alert(__('Some errors have been encountered while saving files.'), array('type' => 'error'));
            }
        }
        
        $data = $this->FileAttachment->getList($id);
        $this->set('data',$data);
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/edit');
    }
    
    public function attachmentsAdd() {
        $this->layout = 'ajax';
        $this->set('params', $this->params->query);
        $this->render('/Elements/attachment/add');
    }
       
    public function attachmentsDelete() {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = $this->params->data['id'];
            
            if($this->FileAttachment->delete($id)) {
                $result['alertOpt']['text'] = __('File is deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting file.');
            }
            
            return json_encode($result);
        }
    }
        
    public function attachmentsDownload($id) {
        $this->FileAttachment->download($id);
    }

    public function history(){
		$this->Navigation->addCrumb('History');

        $arrTables = array('StudentHistory');
        $historyData = $this->StudentHistory->find('all',array(
            'conditions' => array('StudentHistory.student_id'=>$this->studentId),
            'order' => array('StudentHistory.created' => 'desc')
		));

        // pr($historyData);
        $data = $this->Student->findById($this->studentId);
        $data2 = array();
        foreach ($historyData as $key => $arrVal) {
            foreach($arrTables as $table){
            //pr($arrVal);die;
                foreach($arrVal[$table] as $k => $v){
                    $keyVal = ($k == 'name')?$table.'_name':$k;
                    //echo $k.'<br>';
                    $data2[$keyVal][$v] = $arrVal['StudentHistory']['created'];
                }
            }
        }
		if(empty($data2)) {
			$this->Utility->alert($this->Utility->getMessage('NO_HISTORY'), array('type' => 'info', 'dismissOnClick' => false));
		}
		
        $this->set('data',$data);
        $this->set('data2',$data2);
    }

    /**
     * Assessments that the student has achieved till date
     * @return [type] [description]
     */
    public function assessments() {
		$this->Navigation->addCrumb('Assessments');
        if(is_null($this->studentId)){
            var_dump($this->name);
            $this->redirect(array('controller' => $this->name));
        }

        $years = $this->StudentAssessment->getYears($this->studentId);
        $programmeGrades = $this->StudentAssessment->getProgrammeGrades($this->studentId);

        reset($years);
        reset($programmeGrades);

        if($this->request->isPost()){
            $selectedYearId = $this->request->data['year'];
            if(!$this->Session->check('Student.assessment.year')){
                $this->Session->write('Student.assessment.year', $selectedYearId);
            }
            $isYearChanged = $this->Session->read('Student.assessment.year') !== $this->request->data['year'];

            $programmeGrades = $this->StudentAssessment->getProgrammeGrades($this->studentId, $selectedYearId);
            $selectedProgrammeGrade = $isYearChanged?key($programmeGrades):$this->request->data['programmeGrade'];

        }else{
            $selectedYearId = key($years);
            $selectedProgrammeGrade = key($programmeGrades);
        }

        $data = $this->StudentAssessment->getData($this->studentId, $selectedYearId, $selectedProgrammeGrade);

        if(empty($data) && empty($years) && empty($programmeGrades)) {
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
        }

        $this->set('years', $years);
        $this->set('selectedYear', $selectedYearId);
        $this->set('programmeGrades', $programmeGrades);
        $this->set('selectedProgrammeGrade', $selectedProgrammeGrade);
        $this->set('data', $data);
    }

	private function custFieldYrInits(){
		$this->Navigation->addCrumb('Annual Info');
		$action = $this->action;
		$siteid = @$this->request->params['pass'][2];
		$id = $this->studentId;
		$schoolYear = ClassRegistry::init('SchoolYear');
		$years = $schoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('student_id'=>$id,'institution_site_id'=>$siteid,'school_year_id'=>$selectedYear);
		
		$arrMap = array('CustomField'=>'StudentDetailsCustomField',
						'CustomFieldOption'=>'StudentDetailsCustomFieldOption',
						'CustomValue'=>'StudentDetailsCustomValue',
						'Year'=>'SchoolYear');
		return compact('action','siteid','id','years','selectedYear','condParam','arrMap');
	}
	private function custFieldSY($school_yr_ids){
		return $this->InstitutionSite->find('list',array('conditions'=>array('InstitutionSite.id'=>$school_yr_ids)));
	}
	private function custFieldSites($institution_sites){
		$institution_sites = $this->InstitutionSite->find('all',array('fields'=>array('InstitutionSite.id','InstitutionSite.name','Institution.name'),'conditions'=>array('InstitutionSite.id'=>$institution_sites)));
		$tmp = array('0'=>'--');
		foreach($institution_sites as $arrVal){ 
			$tmp[$arrVal['InstitutionSite']['id']] = $arrVal['Institution']['name'].' - '.$arrVal['InstitutionSite']['name']; 
		}
		return $tmp;
	}
	
	public function custFieldYrView(){
        $this->Navigation->addCrumb("More", array('controller' => 'Students', 'action' => 'additional'));
		extract($this->custFieldYrInits());
		$customfield = $this->Components->load('CustomField',$arrMap);

		$data = array();
		if($id && $selectedYear && $siteid) $data = $customfield->getCustomFieldView($condParam);
		
		//if(empty($data['dataFields'])) $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_CONFIG'));
		$institution_sites = $customfield->getCustomValuebyCond('list',array('fields'=>array('institution_site_id','school_year_id'),'conditions'=>array('school_year_id'=>$selectedYear,'student_id'=>$id)));
		$institution_sites = $this->custFieldSites(array_keys($institution_sites));
		if(count($institution_sites)<2)  $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
		$displayEdit = false;
		$this->set(compact('arrMap','selectedYear','siteid','years','action','id','institution_sites','displayEdit'));
		$this->set($data);
        $this->set('myview', 'additional');
		$this->render('/Elements/customfields/view');
	}

    // STUDENT ATTENDANCE PART
    public function attendance(){
        $studentId = $this->studentId;
        $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
        $this->Navigation->addCrumb('Attendance');

        $id = @$this->request->params['pass'][0];
        $yearList = $this->SchoolYear->getYearList();
        $yearId = $this->getAvailableYearId($yearList);
        $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));

        $data = $this->StudentAttendance->getAttendanceData($studentId,isset($id)? $id:$yearId);
        foreach($data as $id=>$val){
            $class = $this->InstitutionSiteClass->getClass($data[$id]['StudentAttendance']['institution_site_class_id'],$data[$id]['StudentAttendance']['institution_site_id']);
            $data[$id]['StudentAttendance']['name'] = $class['InstitutionSiteClass']['name'];
        }

        if(empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
        }

        $this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
        $this->set('data', $data);
        $this->set('schoolDays', $schoolDays);
    }

    // Student behaviour part
    public function behaviour(){
        $this->Navigation->addCrumb('List of Behaviour');

        $data = $this->StudentBehaviour->getBehaviourData($this->studentId);
        if(empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
        }

        $this->set('data', $data);
    }

    public function behaviourView() {
        $studentBehaviourId = $this->params['pass'][0];
        $studentBehaviourObj = $this->StudentBehaviour->find('all',array('conditions'=>array('StudentBehaviour.id' => $studentBehaviourId)));

        if(!empty($studentBehaviourObj)) {
            $studentId = $studentBehaviourObj[0]['StudentBehaviour']['student_id'];
            $data = $this->Student->find('first', array('conditions' => array('Student.id' => $studentId)));
            $this->Navigation->addCrumb('Behaviour Details');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();
            $categoryOptions = array();
            $categoryOptions = $this->StudentBehaviourCategory->getCategory();

            $this->Session->write('StudentBehavourId', $studentBehaviourId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('yearOptions', $yearOptions);
            $this->set('studentBehaviourObj', $studentBehaviourObj);
        } else {
            $this->redirect(array('action' => 'behaviour'));
        }
    }

    private function getAvailableYearId($yearList) {
        $yearId = 0;
        if(isset($this->params['pass'][0])) {
            $yearId = $this->params['pass'][0];
            if(!array_key_exists($yearId, $yearList)) {
                $yearId = key($yearList);
            }
        } else {
            $yearId = key($yearList);
        }
        return $yearId;
    }
	
	public function getUniqueID() {
		$generate_no = '';
     	$str = $this->Student->find('first', array('order' => array('Student.id DESC'), 'limit' => 1, 'fields'=>'Student.id'));
		$prefix = $this->ConfigItem->find('first', array('limit' => 1, 
													  'fields'=>'ConfigItem.value',
													  'conditions'=>array(
																			'ConfigItem.name' => 'student_prefix'
																		 )
									   ));
		$prefix = explode(",",$prefix['ConfigItem']['value']);
    	
		if($prefix[1]>0){
			$id = $str['Student']['id']+1; 
			if(strlen($id)<6){
				$str = str_pad($id,6,"0",STR_PAD_LEFT);
			}
			// Get two random number
			$rnd1 = rand(0,9);
			$rnd2 = rand(0,9);
			$generate_no = $prefix[0].$str.$rnd1.$rnd2;
		}
		
		return $generate_no;
    }
}
