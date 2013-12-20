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
        'Bank',
        'BankBranch',
        'IdentityType',
        'ContactOption',
        'ContactType',
        'Students.StudentBankAccount',
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
        'Students.StudentComment',
        'Students.StudentNationality',
        'Students.StudentIdentity',
        'Students.StudentLanguage',
	'Students.StudentContact',
        'SchoolYear',
        'Country',
        'Language',
	'ConfigItem',
	'Students.StudentExtracurricular',
	'ExtracurricularType'
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
                                    $arrV['student_id']  = $this->studentId;
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
		$this->Navigation->addCrumb('Results');
        if(is_null($this->studentId)){
            //var_dump($this->name);
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

            $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
            $this->set('institution_site_id', $studentBehaviourObj[0]['StudentBehaviour']['institution_site_id']);
            $this->set('institutionSiteOptions', $institutionSiteOptions);
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
    /***BANK ACCOUNTS - sorry have to copy paste to othe modules too lazy already**/
     public function bankAccounts() {
        $this->Navigation->addCrumb('Bank Accounts');

        $data = $this->StudentBankAccount->find('all',array('conditions'=>array('StudentBankAccount.student_id'=>$this->studentId)));
        $bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));
        $banklist = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
        $this->set('data',$data);
        $this->set('bank',$bank);
        $this->set('banklist',$banklist);
    }


    public function bankAccountsView() {
        $bankAccountId = $this->params['pass'][0];
        $bankAccountObj = $this->StudentBankAccount->find('all',array('conditions'=>array('StudentBankAccount.id' => $bankAccountId)));
        
        if(!empty($bankAccountObj)) {
            $this->Navigation->addCrumb('Bank Account Details');
            
            $this->Session->write('StudentBankAccountId', $bankAccountId);
            $this->set('bankAccountObj', $bankAccountObj);
        }
        $banklist = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
        $this->set('banklist',$banklist);

    }

    public function bankAccountsAdd() {
        $this->Navigation->addCrumb('Add Bank Accounts');
        if($this->request->is('post')) { // save
            $this->StudentBankAccount->create();
            if($this->StudentBankAccount->save($this->request->data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'bankAccounts'));
            }
        }
        $bank = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));

        $bankId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : "";
        $bankBranches = $this->BankBranch->find('list', array('conditions'=>array('bank_id'=>$bankId, 'visible'=>1), 'recursive' => -1));
        $this->set('bankBranches', $bankBranches);
        $this->set('selectedBank', $bankId);
        $this->set('student_id', $this->studentId);
        $this->set('bank',$bank);
    }

    public function bankAccountsEdit() {
        $bankBranch = array();

        $bankAccountId = $this->params['pass'][0];
        $this->Navigation->addCrumb('Edit Bank Account Details');
        if($this->request->is('get')) {
            $bankAccountObj = $this->StudentBankAccount->find('first',array('conditions'=>array('StudentBankAccount.id' => $bankAccountId)));
  
            if(!empty($bankAccountObj)) {
                //$bankAccountObj['StaffQualification']['qualification_institution'] = $institutes[$staffQualificationObj['StaffQualification']['qualification_institution_id']];
                $this->request->data = $bankAccountObj;
            }
         } else {
            $this->request->data['StudentBankAccount']['student_id'] = $this->studentId;
            if($this->StudentBankAccount->save($this->request->data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));  
                $this->redirect(array('action' => 'bankAccountsView', $this->request->data['StudentBankAccount']['id']));
            }
         }
        
        $bankId = isset($this->params['pass'][1]) ? $this->params['pass'][1] : $bankAccountObj['BankBranch']['bank_id'];
        $this->set('selectedBank', $bankId);

        $bankBranch = $this->BankBranch->find('list', array('conditions'=>array('bank_id'=>$bankId, 'visible'=>1), 'recursive' => -1));
        $this->set('bankBranch', $bankBranch);

        $bank = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
        $this->set('bank',$bank);

        $this->set('id', $bankAccountId);
    }

   
    public function bankAccountsDelete($id) {
        if($this->Session->check('StudentId') && $this->Session->check('StudentBankAccountId')) {
            $id = $this->Session->read('StudentBankAccountId');

            $studentId = $this->Session->read('StudentId');
            $name = $this->StudentBankAccount->field('account_number', array('StudentBankAccount.id' => $id));
            $this->StudentBankAccount->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'bankAccounts'));
        }
    }

    public function bankAccountsBankBranches() {
        $this->autoRender = false;
        $bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));
        echo json_encode($bank);
    }

    public function comments(){
        $this->Navigation->addCrumb('Comments');
        $data = $this->StudentComment->find('all',array('conditions'=>array('StudentComment.student_id'=>$this->studentId), 'recursive' => -1, 'order'=>'StudentComment.comment_date'));

        $this->set('list', $data);
    }

    public function commentsAdd() {
        if ($this->request->is('post')) {
            $this->StudentComment->create();
            $this->request->data['StudentComment']['student_id'] = $this->studentId;
            
            $data = $this->data['StudentComment'];

            if ($this->StudentComment->save($data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'comments'));
            }
        }

        $this->UserSession->readStatusSession($this->request->action);
    }

    public function commentsView() {
        $commentId = $this->params['pass'][0];
        $commentObj = $this->StudentComment->find('all',array('conditions'=>array('StudentComment.id' => $commentId)));
        if(!empty($commentObj)) {
            $this->Navigation->addCrumb('Comment Details');
            
            $this->Session->write('StudentCommentId', $commentId);
            $this->set('commentObj', $commentObj);
        } 
    }

    public function commentsEdit() {
        $commentId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $commentObj = $this->StudentComment->find('first',array('conditions'=>array('StudentComment.id' => $commentId)));
  
            if(!empty($commentObj)) {
                $this->Navigation->addCrumb('Edit Comment Details');
                $this->request->data = $commentObj;
               
            }
         } else {
            $commentData = $this->data['StudentComment'];
            $commentData['student_id'] = $this->studentId;
            
            if ($this->StudentComment->save($commentData)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'commentsView', $commentData['id']));
            }
         }


        $this->set('id', $commentId);
       
    }

    public function commentsDelete($id) {
        if($this->Session->check('StudentId') && $this->Session->check('StudentCommentId')) {
            $id = $this->Session->read('StudentCommentId');
            $studentId = $this->Session->read('StudentId');
            $name = $this->StudentComment->field('title', array('StudentComment.id' => $id));
            $this->StudentComment->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'comments', $studentId));
        }
    }

    public function nationalities(){
        $this->Navigation->addCrumb('Nationalities');
        $data = $this->StudentNationality->find('all',array('conditions'=>array('StudentNationality.student_id'=>$this->studentId)));
		$this->set('list', $data);
    }
	
	public function nationalitiesAdd() {
        if ($this->request->is('post')) {
            $this->StudentNationality->create();
            $this->request->data['StudentNationality']['student_id'] = $this->studentId;
            
            $data = $this->data['StudentNationality'];

            if ($this->StudentNationality->save($data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'nationalities'));
            }
        }

        $countryOptions = $this->Country->getOptions();
        $this->set('countryOptions', $countryOptions);
		$this->UserSession->readStatusSession($this->request->action);
    }
	
	public function nationalitiesView() {
        $nationalityId = $this->params['pass'][0];
        $nationalityObj = $this->StudentNationality->find('all',array('conditions'=>array('StudentNationality.id' => $nationalityId)));
        
        if(!empty($nationalityObj)) {
            $this->Navigation->addCrumb('Nationality Details');
            
            $this->Session->write('StudentNationalityId', $nationalityId);
            $this->set('nationalityObj', $nationalityObj);
        } 
    }

    public function nationalitiesEdit() {
        $nationalityId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $nationalityObj = $this->StudentNationality->find('first',array('conditions'=>array('StudentNationality.id' => $nationalityId)));
  
            if(!empty($nationalityObj)) {
                $this->Navigation->addCrumb('Edit Nationality Details');
                $this->request->data = $nationalityObj;
               
            }
         } else {
            $nationalityData = $this->data['StudentNationality'];
            $nationalityData['student_id'] = $this->studentId;
            
            if ($this->StudentNationality->save($nationalityData)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'nationalitiesView', $nationalityData['id']));
            }
         }

        $countryOptions = $this->Country->getOptions();
        $this->set('countryOptions', $countryOptions);

        $this->set('id', $nationalityId);
       
    }
	
	public function nationalitiesDelete($id) {
        if($this->Session->check('StudentId') && $this->Session->check('StudentNationalityId')) {
            $id = $this->Session->read('StudentNationalityId');
            $studentId = $this->Session->read('StudentId');
            $countryId = $this->StudentNationality->field('country_id', array('StudentNationality.id' => $id));
            $name = $this->Country->field('name', array('Country.id' => $countryId));
            $this->StudentNationality->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'nationalities', $studentId));
		}
    }
	
    public function identities(){
        $this->Navigation->addCrumb('Identities');
        $data = $this->StudentIdentity->find('all',array('conditions'=>array('StudentIdentity.student_id'=>$this->studentId)));
        $this->set('list', $data);
    }
	
    public function identitiesAdd() {
        if ($this->request->is('post')) {
            $this->StudentIdentity->create();
            $this->request->data['StudentIdentity']['student_id'] = $this->studentId;
            
            $data = $this->data['StudentIdentity'];

            if ($this->StudentIdentity->save($data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'identities'));
            }
        }

        $identityTypeOptions = $this->IdentityType->getOptions();
        $this->set('identityTypeOptions', $identityTypeOptions);
        $this->UserSession->readStatusSession($this->request->action);
    }
	
	public function identitiesView() {
        $identityId = $this->params['pass'][0];
        $identityObj = $this->StudentIdentity->find('all',array('conditions'=>array('StudentIdentity.id' => $identityId)));
        
        if(!empty($identityObj)) {
            $this->Navigation->addCrumb('Identity Details');
            
            $this->Session->write('StudentIdentityId', $identityId);
            $this->set('identityObj', $identityObj);
        } 
    }

    public function identitiesEdit() {
        $identityId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $identityObj = $this->StudentIdentity->find('first',array('conditions'=>array('StudentIdentity.id' => $identityId)));
  
            if(!empty($identityObj)) {
                $this->Navigation->addCrumb('Edit Identity Details');
                $this->request->data = $identityObj;
               
            }
         } else {
            $identityData = $this->data['StudentIdentity'];
            $identityData['student_id'] = $this->studentId;
            
            if ($this->StudentIdentity->save($identityData)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'identitiesView', $identityData['id']));
            }
         }

        $identityTypeOptions = $this->IdentityType->getOptions();
        $this->set('identityTypeOptions', $identityTypeOptions);

        $this->set('id', $identityId);
       
    }

    public function identitiesDelete($id) {
        if($this->Session->check('StudentId') && $this->Session->check('StudentIdentityId')) {
            $id = $this->Session->read('StudentIdentityId');
            $studentId = $this->Session->read('StudentId');
            $name = $this->StudentIdentity->field('number', array('StudentIdentity.id' => $id));
            $this->StudentIdentity->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'identities', $studentId));
        }
    }

    public function languages(){
        $this->Navigation->addCrumb('Languages');
        $data = $this->StudentLanguage->find('all',array('conditions'=>array('StudentLanguage.student_id'=>$this->studentId)));
        $this->set('list', $data);
    }
    
    public function languagesAdd() {
        if ($this->request->is('post')) {
            $this->StudentLanguage->create();
            $this->request->data['StudentLanguage']['student_id'] = $this->studentId;
            
            $data = $this->data['StudentLanguage'];

            if ($this->StudentLanguage->save($data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'languages'));
            }
        }

        $gradeOptions = array();
        for($i=0;$i<6;$i++){
            $gradeOptions[$i] = $i;
        }
        $this->set('gradeOptions', $gradeOptions);

        $languageOptions = $this->Language->getOptions();
        $this->set('languageOptions', $languageOptions);
        $this->UserSession->readStatusSession($this->request->action);
    }
    
    public function languagesView() {
        $languageId = $this->params['pass'][0];
        $languageObj = $this->StudentLanguage->find('all',array('conditions'=>array('StudentLanguage.id' => $languageId)));
        
        if(!empty($languageObj)) {
            $this->Navigation->addCrumb('Language Details');
            
            $this->Session->write('StudentLanguageId', $languageId);
            $this->set('languageObj', $languageObj);
        } 
    }

    public function languagesEdit() {
        $languageId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $languageObj = $this->StudentLanguage->find('first',array('conditions'=>array('StudentLanguage.id' => $languageId)));
  
            if(!empty($languageObj)) {
                $this->Navigation->addCrumb('Edit Language Details');
                $this->request->data = $languageObj;
               
            }
         } else {
            $languageData = $this->data['StudentLanguage'];
            $languageData['student_id'] = $this->studentId;
           
            if ($this->StudentLanguage->save($languageData)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'languagesView', $languageData['id']));
            }
         }
        $gradeOptions = array();
        for($i=0;$i<6;$i++){
            $gradeOptions[$i] = $i;
        }
        $this->set('gradeOptions', $gradeOptions);

        $languageOptions = $this->Language->getOptions();
        $this->set('languageOptions', $languageOptions);

        $this->set('id', $languageId);
       
    }

    public function languagesDelete($id) {
        if($this->Session->check('StudentId') && $this->Session->check('StudentLanguageId')) {
            $id = $this->Session->read('StudentLanguageId');
            $studentId = $this->Session->read('StudentId');
            $languageId = $this->StudentLanguage->field('language_id', array('StudentLanguage.id' => $id));
            $name = $this->Language->field('name', array('Language.id' => $languageId));
            $this->StudentLanguage->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'languages', $studentId));
	}
    }
     
    public function contacts(){
        $this->Navigation->addCrumb('Contacts');
        $data = $this->StudentContact->find('all',array('conditions'=>array('StudentContact.student_id'=>$this->studentId), 'order'=>array('ContactType.contact_option_id', 'StudentContact.preferred DESC')));

        $contactOptions = $this->ContactOption->getOptions();
        $this->set('contactOptions', $contactOptions);

        $this->set('list', $data);
    }
    
    public function contactsAdd() {
        if ($this->request->is('post')) {
            $this->StudentContact->create();
            $this->request->data['StudentContact']['student_id'] = $this->studentId;
            
            $contactData = $this->data['StudentContact'];
        
            if ($this->StudentContact->save($contactData)){
                if($contactData['preferred']=='1'){
                    $this->StudentContact->updateAll(array('StudentContact.preferred' =>'0'), array('ContactType.contact_option_id'=>$contactData['contact_option_id'], array('NOT'=>array('StudentContact.id'=>array($this->StudentContact->getLastInsertId())))));
                }
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'contacts'));
            }
        }


        $contactOptions = $this->ContactOption->getOptions();
        $this->set('contactOptions', $contactOptions);

        $contactOptionId = isset($this->params['pass'][0]) ? $this->params['pass'][0] : key($contactOptions);
        $contactTypeOptions = $this->ContactType->find('list', array('conditions'=>array('contact_option_id'=>$contactOptionId, 'visible'=>1), 'recursive' => -1));
        $this->set('contactTypeOptions', $contactTypeOptions);
        $this->set('selectedContactOptions', $contactOptionId);
       
        $this->UserSession->readStatusSession($this->request->action);
    }
    
    public function contactsView() {
        $contactId = $this->params['pass'][0];
        $contactObj = $this->StudentContact->find('all',array('conditions'=>array('StudentContact.id' => $contactId)));
        
        if(!empty($contactObj)) {
            $this->Navigation->addCrumb('Contact Details');
            
            $this->Session->write('StudentContactId', $contactId);
            $this->set('contactObj', $contactObj);
        } 

        $contactOptions = $this->ContactOption->getOptions();
        $this->set('contactOptions', $contactOptions);
    }

    public function contactsEdit() {
        $contactId = $this->params['pass'][0];
        $contactObj = array();
        if($this->request->is('get')) {
            $contactObj = $this->StudentContact->find('first',array('conditions'=>array('StudentContact.id' => $contactId)));
  
            if(!empty($contactObj)) {
                $this->Navigation->addCrumb('Edit Contact Details');
                $this->request->data = $contactObj;
            }
         } else {
            $contactData = $this->data['StudentContact'];
            $contactData['student_id'] = $this->studentId;

            if ($this->StudentContact->save($contactData)){
                if($contactData['preferred']=='1'){
                    $this->StudentContact->updateAll(array('StudentContact.preferred' =>'0'), array('ContactType.contact_option_id'=>$contactData['contact_option_id'], array('NOT'=>array('StudentContact.id'=>array($contactId)))));
                }
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'contactsView', $contactData['id']));
            }
         }

        $contactOptions = $this->ContactOption->getOptions();
        $this->set('contactOptions', $contactOptions);

        $contactOptionId = isset($this->params['pass'][1]) ? $this->params['pass'][1] : $contactObj['ContactType']['contact_option_id'];
        $contactTypeOptions = $this->ContactType->find('list', array('conditions'=>array('contact_option_id'=>$contactOptionId, 'visible'=>1), 'recursive' => -1));
        $this->set('contactTypeOptions', $contactTypeOptions);
        $this->set('selectedContactOptions', $contactOptionId);

        $this->set('id', $contactId);
       
    }

    public function contactsDelete($id) {
        if($this->Session->check('StudentId') && $this->Session->check('StudentContactId')) {
            $id = $this->Session->read('StudentContactId');
            $studentId = $this->Session->read('StudentId');
           
            $name = $this->StudentContact->field('value', array('StudentContact.id' => $id));
            $this->StudentContact->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'contacts', $studentId));
        }
    }
	
    public function extracurricular(){
        $this->Navigation->addCrumb('Extracurricular');
		$data = $this->StudentExtracurricular->getAllList('student_id',$this->studentId);
        $this->set('list', $data);
    }
	
	public function extracurricularView() {
        $id = $this->params['pass'][0];
        $data = $this->StudentExtracurricular->getAllList('id',$id);
        if(!empty($data)) {
            $this->Navigation->addCrumb('Extracurricular Details');
            
            $this->Session->write('StudentExtracurricularId', $id);
            $this->set('data', $data);
        } 
    }

    public function extracurricularAdd(){
        $this->Navigation->addCrumb('Add Extracurricular');
		
		$yearList = $this->SchoolYear->getYearList();
		$yearId = $this->getAvailableYearId($yearList);
		$typeList = $this->ExtracurricularType->findList(array('fields' =>array('id','name'), 'conditions'=>array('visible' => '1'), 'orderBy' => 'name'));
		
		
		$this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
		$this->set('types', $typeList);
		if($this->request->isPost()){
			$data = $this->request->data;
			$data['StudentExtracurricular']['student_id'] = $this->studentId;
			if ($this->StudentExtracurricular->save($data)){
				$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
				$this->redirect(array('action' => 'extracurricular'));
			}
		}
    }
	
	public function extracurricularEdit() {
        $id = $this->params['pass'][0];
		$this->Navigation->addCrumb('Edit Extracurricular Details');
		 
        if($this->request->is('get')) {
            $data = $this->StudentExtracurricular->find('first',array('conditions'=>array('StudentExtracurricular.id' => $id)));
  
            if(!empty($data)) {
                $this->request->data = $data;
            }
         } else {
            $data = $this->data;
			$data['StudentExtracurricular']['student_id'] = $this->studentId;
			$data['StudentExtracurricular']['id'] = $id;
			if ($this->StudentExtracurricular->save($data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'extracurricularView', $data['StudentExtracurricular']['id']));
            }
         }

        $yearList = $this->SchoolYear->getYearList();
		$yearId = $this->getAvailableYearId($yearList);
		$typeList =  $this->ExtracurricularType->findList(array('fields' =>array('id','name'), 'conditions'=>array('visible' => '1'), 'orderBy' => 'name'));
		
		$this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
		$this->set('types', $typeList);

        $this->set('id', $id);
    }
	
	public function extracurricularDelete($id) {
        if($this->Session->check('StudentId') && $this->Session->check('StudentExtracurricularId')) {
            $id = $this->Session->read('StudentExtracurricularId');
            $studentId = $this->Session->read('StudentId');
            $name = $this->StudentExtracurricular->field('name', array('StudentExtracurricular.id' => $id));
			
            $this->StudentExtracurricular->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'extracurricular'));
        }
    }
	
	public function searchAutoComplete(){
		if($this->request->is('get')) {
			if($this->request->is('ajax')) {
				$this->autoRender = false;
				$search = $this->params->query['term'];
				$result = $this->StudentExtracurricular->autocomplete($search);
				return json_encode($result);
			} 
		}
	}
}
