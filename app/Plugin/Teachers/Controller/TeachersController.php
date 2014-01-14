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
App::uses('ImageMeta', 'Image');
App::uses('ImageValidate', 'Image');

class TeachersController extends TeachersAppController {
    public $teacherId;
    public $teacherObj;

    public $uses = array(
	    'Area',
        'Institution',
	    'InstitutionSite',
        'Bank',
        'BankBranch',
	    'Teachers.TeacherBankAccount',
        'InstitutionSiteTeacher',
        'InstitutionSiteType',
        'Teachers.Teacher',
        'Teachers.TeacherHistory',
        'Teachers.TeacherCustomField',
        'Teachers.TeacherCustomFieldOption',
        'Teachers.TeacherCustomValue',
        'Teachers.TeacherAttachment',
        'Teachers.TeacherTraining',
        'Teachers.TeacherTrainingCategory',
        'Teachers.TeacherQualification',
        'QualificationLevel',
        'QualificationInstitution',
        'QualificationSpecialisation',
        'Teachers.TeacherAttendance',
	    'Teachers.TeacherLeave',
        'Teachers.TeacherLeaveType',
        'Teachers.TeacherBehaviour',
        'Teachers.TeacherBehaviourCategory',
        'Teachers.TeacherComment',
        'Teachers.TeacherNationality',
	    'Teachers.TeacherIdentity',
        'Teachers.TeacherLanguage',
        'Teachers.TeacherContact',
        'Teachers.TeacherEmployment',
        'Teachers.TeacherSalary',
        'Teachers.TeacherSalaryAddition',
        'Teachers.TeacherSalaryDeduction',
        'Country',
	    'IdentityType',
        'ContactType',
        'ContactOption',
        'SchoolYear',
        'Language',
	    'ConfigItem',
        'LeaveStatus',
	    'Teachers.TeacherExtracurricular',
	    'ExtracurricularType',
        'EmploymentType',
        'SalaryAdditionType',
        'SalaryDeductionType'
	);

    public $helpers = array('Js' => array('Jquery'), 'Paginator');
    public $components = array(
        'UserSession',
        'Paginator'
    );
	
	public $modules = array(
		'health_history' => 'Teachers.TeacherHealthHistory',
		'health_family' => 'Teachers.TeacherHealthFamily',
		'health_immunization' => 'Teachers.TeacherHealthImmunization',
		'health_medication' => 'Teachers.TeacherHealthMedication',
		'health_allergy' => 'Teachers.TeacherHealthAllergy',
		'health_test' => 'Teachers.TeacherHealthTest',
		'health_consultation' => 'Teachers.TeacherHealthConsultation',
		'health' => 'Teachers.TeacherHealth'
	); 

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Navigation->addCrumb('Teachers', array('controller' => 'Teachers', 'action' => 'index'));
        
		$actions = array('index', 'advanced', 'add', 'viewTeacher');
		if(in_array($this->action, $actions)) {
			$this->bodyTitle = 'Teachers';
        } else {
            if($this->Session->check('TeacherId') && $this->action!=='Home') {
                $this->teacherId = $this->Session->read('TeacherId');
                $this->teacherObj = $this->Session->read('TeacherObj');
                $teacherFirstName = $this->Teacher->field('first_name', array('Teacher.id' => $this->teacherId));
                $teacherMiddleName = $this->Teacher->field('middle_name', array('Teacher.id' => $this->teacherId));
                $teacherLastName = $this->Teacher->field('last_name', array('Teacher.id' => $this->teacherId));
                $name = $teacherFirstName ." ". $teacherMiddleName ." ". $teacherLastName;
                $this->bodyTitle = $name;
                $this->Navigation->addCrumb($name, array('action' => 'view'));
            }
        }
    }

    public function index() {
        $this->Navigation->addCrumb('List of Teachers');
		
        if ($this->request->is('post')){
            if(isset($this->request->data['Teacher']['SearchField'])){
                Sanitize::clean($this->request->data);
                if($this->request->data['Teacher']['SearchField'] != $this->Session->read('Search.SearchFieldTeacher')) {
                    $this->Session->delete('Search.SearchFieldTeacher');
                    $this->Session->write('Search.SearchFieldTeacher', $this->request->data['Teacher']['SearchField']);
                }
            }

            if(isset($this->request->data['sortdir']) && isset($this->request->data['order'])) {
                if($this->request->data['sortdir'] != $this->Session->read('Search.sortdirTeacher')) {
                    $this->Session->delete('Search.sortdirTeacher');
                    $this->Session->write('Search.sortdirTeacher', $this->request->data['sortdir']);
                }
                if($this->request->data['order'] != $this->Session->read('Search.orderTeacher')) {
                    $this->Session->delete('Search.orderTeacher');
                    $this->Session->write('Search.orderTeacher', $this->request->data['order']);
                }
            }
        }

        $fieldordername = ($this->Session->read('Search.orderTeacher'))?$this->Session->read('Search.orderTeacher'):'Teacher.first_name';
        $fieldorderdir = ($this->Session->read('Search.sortdirTeacher'))?$this->Session->read('Search.sortdirTeacher'):'asc';
		
		$searchKey = stripslashes($this->Session->read('Search.SearchFieldTeacher'));
		$conditions = array(
			'SearchKey' => $searchKey, 
			'AdvancedSearch' => $this->Session->check('Teacher.AdvancedSearch') ? $this->Session->read('Teacher.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id')
		);
		$order = array('order' => array($fieldordername => $fieldorderdir));
		$limit = ($this->Session->read('Search.perpageTeacher')) ? $this->Session->read('Search.perpageTeacher') : 30;
        $this->Paginator->settings = array_merge(array('limit' => $limit, 'maxLimit' => 100), $order);
		
        $data = $this->paginate('Teacher', $conditions);
		if(empty($searchKey) && !$this->Session->check('Teacher.AdvancedSearch')) {
			if(count($data) == 1 && !$this->AccessControl->check($this->params['controller'], 'add')) {
				$this->redirect(array('action' => 'viewTeacher', $data[0]['Teacher']['id']));
			}
		}
		if(empty($data) && !$this->request->is('ajax')) {
			$this->Utility->alert($this->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
		}
        $this->set('teachers', $data);
        $this->set('sortedcol', $fieldordername);
        $this->set('sorteddir', ($fieldorderdir == 'asc')?'up':'down');
        $this->set('searchField', $searchKey);
        if($this->request->is('post')){
            $this->render('index_records','ajax');
        }
    }
	
	public function advanced() {
		$key = 'Teacher.AdvancedSearch';
		if($this->request->is('get')) {
			if($this->request->is('ajax')) {
				$this->autoRender = false;
				$search = $this->params->query['term'];
				$result = $this->Area->autocomplete($search);
				return json_encode($result);
			} else {
				$this->Navigation->addCrumb('List of Teachers', array('controller' => 'Teachers', 'action' => 'index'));
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
        
        public function getCustomFieldsSearch($sitetype = 0,$customfields = 'Teacher'){
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
        
    public function viewTeacher($id) {
        $this->Session->write('TeacherId', $id);
        $obj = $this->Teacher->find('first',array('conditions'=>array('Teacher.id' => $id)));
        $this->Session->write('TeacherObj', $obj);
        $this->redirect(array( 'action' => 'view'));
    }

    public function view() {
        $this->Navigation->addCrumb('Overview');
        $this->Teacher->id = $this->Session->read('TeacherId');
        $data = $this->Teacher->read();

        // check session for alert upon successfully adding a teacher
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('data', $data);
    }

    public function edit() {
        $this->Navigation->addCrumb('Edit');
        $this->Teacher->id = $this->Session->read('TeacherId');

        $imgValidate = new ImageValidate();
		$data = $this->data;
        if ($this->request->is('post')) {

            $reset_image = $data['Teacher']['reset_image'];

            $img = new ImageMeta($data['Teacher']['photo_content']);
            unset($data['Teacher']['photo_content']);

            if($reset_image == 0 ) {
                $validated = $imgValidate->validateImage($img);
                if($img->getFileUploadError() !== 4 && $validated['error'] < 1){
                    $data['Teacher']['photo_content'] = $img->getContent();
                    $img->setContent('');
    //                $data['Teacher']['photo_name'] = serialize($img);
                    $data['Teacher']['photo_name'] = $img->getFilename();
                }
            }else{
                $data['Teacher']['photo_content'] = '';
                $data['Teacher']['photo_name'] = '';
            }

            $this->Teacher->set($data);
            if($this->Teacher->validates() && ($reset_image == 1 || $validated['error'] < 1)) {
                unset($data['Teacher']['reset_image']);
                $this->Teacher->set($data);
                $this->Teacher->save();
                $this->UserSession->writeStatusSession('ok', 'Successfully Updated', 'view');
                $this->redirect(array('action' => 'view'));
            }else{
                // display message of validation error
                $this->set('imageUploadError', __(array_shift($validated['message'])));
            }
        }else{
			$data = $this->Teacher->find('first',array('conditions'=>array('id'=>$this->Session->read('TeacherId'))));

		}

        $gender = array(0 => __('--Select--'), 'M' => __('Male'), 'F' => __('Female'));
		$this->set('autoid', $this->getUniqueID());
        $this->set('gender', $gender);
		$this->set('data', $data);
    }

    public function positions() {
        $this->Navigation->addCrumb(ucfirst($this->action));
        $teacherId = $this->Session->read('TeacherId');
        $data = array();
		
        $list = $this->InstitutionSiteTeacher->getPositions($teacherId);
    
        foreach($list as $row) {
            $result = array();
            $dataKey = '';
            foreach($row as $key => $element){ // compact array
                if(array_key_exists('institution', $element)){
                    $dataKey .= $element['institution'];
                    continue;
                }
                if(array_key_exists('institution_site', $element)){
                    $dataKey .= ' - '.$element['institution_site'];
                    continue;
                }
               
                $result = array_merge($result, array($key => $element));
            }
            $data[$dataKey][] = $result;
        }
		if(empty($data)) {
			$this->Utility->alert($this->Utility->getMessage('NO_POSITION'), array('type' => 'info', 'dismissOnClick' => false));
		}
        $this->set('data', $data);
    }

    public function fetchImage($id){
        $this->autoRender = false;
		
		$url = Router::url('/Teachers/img/default_teacher_profile.jpg', true);
        $mime_types = ImageMeta::mimeTypes();

        $imageRawData = $this->Teacher->findById($id);
		$imageFilename = $imageRawData['Teacher']['photo_name'];
		$fileExt = pathinfo(strtolower($imageFilename), PATHINFO_EXTENSION);
	
		
		if(empty($imageRawData['Teacher']['photo_content']) || empty($imageRawData['Teacher']['photo_name']) || !in_array($mime_types[$fileExt], $mime_types)){
			if($this->Session->check('Teacher.defaultImg'))
    		{
				$imageContent = $this->Session->read('Teacher.defaultImg');
			}else{
				$imageContent = file_get_contents($url);
				$this->Session->write('Teacher.defaultImg', $imageContent);
			}
			echo $imageContent;
		}else{
			$imageContent = $imageRawData['Teacher']['photo_content'];
			header("Content-type: " . $mime_types[$fileExt]);
			echo $imageContent;
		}
    }

    public function add() {
        $this->Navigation->addCrumb('Add new Teacher');
        $imgValidate = new ImageValidate();
	$data = $this->data;
        if($this->request->is('post')) {$reset_image = $data['Teacher']['reset_image'];

            $img = new ImageMeta($data['Teacher']['photo_content']);
            unset($data['Teacher']['photo_content']);

            if($reset_image == 0 ) {
                $validated = $imgValidate->validateImage($img);
                if($img->getFileUploadError() !== 4 && $validated['error'] < 1){
                    $data['Teacher']['photo_content'] = $img->getContent();
                    $img->setContent('');
    //                $data['Teacher']['photo_name'] = serialize($img);
                    $data['Teacher']['photo_name'] = $img->getFilename();
                }
            }else{
                $data['Teacher']['photo_content'] = '';
                $data['Teacher']['photo_name'] = '';
            }

            $this->Teacher->set($data);
            if($this->Teacher->validates() && ($reset_image == 1 || $validated['error'] < 1)) {
                $newTeacherRec =  $this->Teacher->save($data);
                // create the session for successfully adding of teacher
                $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view');
                $this->redirect(array('action' => 'viewTeacher', $newTeacherRec['Teacher']['id']));
            }else{
                    $this->set('imageUploadError', __(array_shift($validated['message'])));
				$errors = $this->Teacher->validationErrors;
				if($this->getUniqueID()!=''){ // If Auto id
					if(isset($errors["identification_no"])){ // If its ID error
						if(sizeof($errors)<2){ // If only 1 faulty
							$this->Teacher->set($this->request->data);
							do{
								$this->request->data["Teacher"]["identification_no"] = $this->getUniqueID();
								$conditions = array(
									'Teacher.identification_no' => $this->request->data["Teacher"]["identification_no"]
								);
							}while($this->Teacher->hasAny($conditions));
							$this->Teacher->set($this->request->data);
							$newTeacherRec =  $this->Teacher->save($this->request->data);
							// create the session for successfully adding of teacher
							$this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view'); 
							$this->redirect(array('action' => 'viewTeacher', $newTeacherRec['Teacher']['id']));
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
        $id = $this->Session->read('TeacherId');
        $name = $this->Teacher->field('first_name', array('Teacher.id' => $id));
        if($name !== false){
            $this->Teacher->delete($id);
            $this->Utility->alert(sprintf(__("%s have been deleted successfully."), $name));
        }else{
            $this->Utility->alert(__($this->Utility->getMessage('DELETED_ALREADY')));
        }
        
        $this->redirect(array('action' => 'index'));
    }

    public function additional() {
        $this->Navigation->addCrumb('More');

        // get all teacher custom field in order
        $datafields = $this->TeacherCustomField->find('all', array('conditions' => array('TeacherCustomField.visible' => 1), 'order'=>'TeacherCustomField.order'));

        $this->TeacherCustomValue->unbindModel(
            array('belongsTo' => array('Teacher'))
            );
        $datavalues = $this->TeacherCustomValue->find('all', array(
            'conditions'=> array('TeacherCustomValue.teacher_id' => $this->teacherId))
        );

        // pr($datafields);
        // pr($datavalues);
        $tmp=array();
        foreach($datavalues as $arrV){
            $tmp[$arrV['TeacherCustomField']['id']][] = $arrV['TeacherCustomValue'];
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
                // pr($this->request->data['TeacherCustomValue']);
                if(!isset($this->request->data['TeacherCustomValue'][$fieldVal])) continue;
                foreach($this->request->data['TeacherCustomValue'][$fieldVal] as $key => $val){

                    if($fieldVal == "checkbox"){

                        $arrCustomValues = $this->TeacherCustomValue->find('list',array('fields'=>array('value'),'conditions' => array('TeacherCustomValue.teacher_id' => $this->teacherId,'TeacherCustomValue.teacher_custom_field_id' => $key)));

                        $tmp = array();
                            if(count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
                            foreach($arrCustomValues as $pk => $intVal){
                                //pr($val['value']); echo "$intVal";
                                if(!in_array($intVal, $val['value'])){
                                    //echo "not in db so remove \n";
                                   $this->TeacherCustomValue->delete($pk);
                               }
                           }
                           $ctr = 0;
                            if(count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
                            foreach($val['value'] as $intVal){
                                //pr($val['value']); echo "$intVal";
                                if(!in_array($intVal, $arrCustomValues)){
                                    $this->TeacherCustomValue->create();
                                    $arrV['teacher_custom_field_id']  = $key;
                                    $arrV['value']  = $val['value'][$ctr];
                                    $arrV['teacher_id']  = $this->teacherId;
                                    $this->TeacherCustomValue->save($arrV);
                                    unset($arrCustomValues[$ctr]);
                                }
                                $ctr++;
                            }
                    }else{ // if editing reuse the Primary KEY; so just update the record
                        $datafields = $this->TeacherCustomValue->find('first',array('fields'=>array('id','value'),'conditions' => array('TeacherCustomValue.teacher_id' => $this->teacherId,'TeacherCustomValue.teacher_custom_field_id' => $key)));
                        $this->TeacherCustomValue->create();
                        if($datafields) $this->TeacherCustomValue->id = $datafields['TeacherCustomValue']['id'];
                        $arrV['teacher_custom_field_id'] = $key;
                        $arrV['value'] = $val['value'];
                        $arrV['teacher_id'] = $this->teacherId;
                        $this->TeacherCustomValue->save($arrV);
                    }

                }
            }
            $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'additional');
            $this->redirect(array('action' => 'additional'));
        }
        $this->TeacherCustomField->unbindModel(array('hasMany' => array('TeacherCustomFieldOption')));

        $this->TeacherCustomField->bindModel(array(
            'hasMany' => array(
                'TeacherCustomFieldOption' => array(
                    'conditions' => array(
                        'TeacherCustomFieldOption.visible' => 1),
                    'order' => array('TeacherCustomFieldOption.order' => "ASC")
                )
            )
        ));
        $datafields = $this->TeacherCustomField->find('all', array('conditions' => array('TeacherCustomField.visible' => 1), 'order' => 'TeacherCustomField.order'));
        $this->TeacherCustomValue->unbindModel(array('belongsTo' => array('Teacher')));
        $datavalues = $this->TeacherCustomValue->find('all',array('conditions'=>array('TeacherCustomValue.teacher_id' => $this->teacherId)));
        $tmp=array();
        foreach($datavalues as $arrV){
            $tmp[$arrV['TeacherCustomField']['id']][] = $arrV['TeacherCustomValue'];
        }
        $datavalues = $tmp;

        // pr($datafields);
        // pr($datavalues);
        //pr($tmp);die;
        $this->set('datafields',$datafields);
        $this->set('datavalues',$tmp);
    }

    public function attachments() {
        $this->Navigation->addCrumb('Attachments');
        $id = $this->Session->read('TeacherId');

        $arrMap = array('model'=>'Teachers.TeacherAttachment', 'foreignKey' => 'teacher_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

        $data = $FileAttachment->getList($id);
        $this->set('_model', 'TeacherAttachment');
        $this->set('data', $data);
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/view');
    }
    
    public function attachmentsEdit() {
        $this->Navigation->addCrumb('Edit Attachments');
        $id = $this->Session->read('TeacherId');

        $arrMap = array('model'=>'Teachers.TeacherAttachment', 'foreignKey' => 'teacher_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);
        if($this->request->is('post')) { // save
            $errors = $FileAttachment->saveAll($this->data, $_FILES, $id);
            if(sizeof($errors) == 0) {
                $this->Utility->alert(__('Files have been saved successfully.'));
                $this->redirect(array('action' => 'attachments'));
            } else {
                $this->Utility->alert(__('Some errors have been encountered while saving files.'), array('type' => 'error'));
            }
        }
        
        $data = $FileAttachment->getList($id);
        $this->set('_model', 'TeacherAttachment');
        $this->set('data',$data);
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/edit');
    }
    
    public function attachmentsAdd() {
        $this->layout = 'ajax';
        $this->set('params', $this->params->query);
        $this->set('_model', 'TeacherAttachment');
        $this->render('/Elements/attachment/add');
    }
       
    public function attachmentsDelete() {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = $this->params->data['id'];

            $arrMap = array('model'=>'Teachers.TeacherAttachment', 'foreignKey' => 'teacher_id');
            $FileAttachment = $this->Components->load('FileAttachment', $arrMap);
            
            if($FileAttachment->delete($id)) {
                $result['alertOpt']['text'] = __('File is deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting file.');
            }
            
            return json_encode($result);
        }
    }
        
    public function attachmentsDownload($id) {
        $arrMap = array('model'=>'Teachers.TeacherAttachment', 'foreignKey' => 'teacher_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);
        $FileAttachment->download($id);
    }

    public function history(){
        $this->Navigation->addCrumb('History');

        $arrTables = array('TeacherHistory');
        $historyData = $this->TeacherHistory->find('all',array(
            'conditions' => array('TeacherHistory.teacher_id'=>$this->teacherId),
            'order' => array('TeacherHistory.created' => 'desc')
        ));
        $data = $this->Teacher->findById($this->teacherId);
        $data2 = array();
        foreach ($historyData as $key => $arrVal) {
            foreach($arrTables as $table){
            //pr($arrVal);die;
                foreach($arrVal[$table] as $k => $v){
                    $keyVal = ($k == 'name')?$table.'_name':$k;
                    //echo $k.'<br>';
                    $data2[$keyVal][$v] = $arrVal['TeacherHistory']['created'];
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
     * Qualifications that the teacher has attained till date
     * @return [type] [description]
     */
    public function qualifications() {
        $this->Navigation->addCrumb('Qualifications');

        $list = $this->TeacherQualification->getData($this->teacherId);

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('list', $list);
    }


    public function qualificationsAdd() {
        if ($this->request->is('post')) {
            $this->TeacherQualification->create();
            $this->request->data['TeacherQualification']['teacher_id'] = $this->teacherId;
            
            $teacherQualificationData = $this->data['TeacherQualification'];

            $this->TeacherQualification->set($teacherQualificationData);


            if ($this->TeacherQualification->validates()) {
                if(empty($teacherQualificationData['qualification_institution_id'])){
                    $data = array(
                        'QualificationInstitution'=>
                            array(
                                'name' => $teacherQualificationData['qualification_institution'],
                                'order' => 0,
                                'visible' => 1,
                                'created_user_id' => $this->Auth->user('id'),
                                'created' => date('Y-m-d h:i:s')
                            )
                    );
                    $this->QualificationInstitution->save($data);
                    $qualificationInstitutionId = $this->QualificationInstitution->getInsertID();
                    $teacherQualificationData['qualification_institution_id'] = $qualificationInstitutionId;
                }
               


                $this->TeacherQualification->save($teacherQualificationData);
    
                $teacherQualificationId = $this->TeacherQualification->getInsertID();

                $arrMap = array('model'=>'Teachers.TeacherQualification');
                $Q = $this->Components->load('FileAttachment', $arrMap);
                $teacherQualificationData['id'] = $teacherQualificationId;
                $errors = $Q->save($teacherQualificationData, $_FILES);

                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'qualifications'));
            }
        }

        $levels = $this->QualificationLevel->getOptions();
        $specializations = $this->QualificationSpecialisation->getOptions();
        $institutes = $this->QualificationInstitution->getOptions();
        
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('specializations', $specializations);
        $this->set('levels', $levels);
        $this->set('institutes', $institutes);
    }

     public function qualificationsView() {
        $teacherQualificationId = $this->params['pass'][0];
        $teacherQualificationObj = $this->TeacherQualification->find('all',array('conditions'=>array('TeacherQualification.id' => $teacherQualificationId)));
        
        if(!empty($teacherQualificationObj)) {
            $this->Navigation->addCrumb('Qualification Details');
            
            $levels = $this->QualificationLevel->getOptions();
            $specializations = $this->QualificationSpecialisation->getOptions();
            $institutes = $this->QualificationInstitution->getOptions();

            $this->Session->write('TeacherQualificationId', $teacherQualificationId);
            $this->set('levels', $levels);
            $this->set('specializations', $specializations);
            $this->set('institutes', $institutes);
            $this->set('teacherQualificationObj', $teacherQualificationObj);

            $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        } else {
            //$this->redirect(array('action' => 'classesList'));
        }
    }

    public function qualificationsEdit() {
        $levels = $this->QualificationLevel->getOptions();
        $institutes = $this->QualificationInstitution->getOptions();
        $specializations = $this->QualificationSpecialisation->getOptions();

        $this->set('levels', $levels);
        $this->set('institutes', $institutes);
        $this->set('specializations', $specializations);

        if($this->request->is('get')) {
            $teacherQualificationId = $this->params['pass'][0];
            $teacherQualificationObj = $this->TeacherQualification->find('first',array('conditions'=>array('TeacherQualification.id' => $teacherQualificationId)));
  
            if(!empty($teacherQualificationObj)) {
                $this->Navigation->addCrumb('Edit Qualification Details');
                $teacherQualificationObj['TeacherQualification']['qualification_institution'] = $institutes[$teacherQualificationObj['TeacherQualification']['qualification_institution_id']];
                $this->request->data = $teacherQualificationObj;
                $this->set('id', $teacherQualificationId);
            } else {
                //$this->redirect(array('action' => 'studentsBehaviour'));
            }
         } else {
            $teacherQualificationData = $this->data['TeacherQualification'];
            $teacherQualificationData['teacher_id'] = $this->teacherId;
            
            $this->TeacherQualification->set($teacherQualificationData);

            if ($this->TeacherQualification->validates()) {
                if(empty($teacherQualificationData['qualification_institution_id'])){
                    $data = array(
                        'QualificationInstitution'=>
                            array(
                                'name' => $teacherQualificationData['qualification_institution'],
                                'order' => 0,
                                'visible' => 1,
                                'created_user_id' => $this->Auth->user('id'),
                                'created' => date('Y-m-d h:i:s')
                            )
                    );
                    $this->QualificationInstitution->save($data);
                    $qualificationInstitutionId = $this->QualificationInstitution->getInsertID();
                    $teacherQualificationData['qualification_institution_id'] = $qualificationInstitutionId;
                }
                $this->TeacherQualification->save($teacherQualificationData);
                $arrMap = array('model'=>'Teachers.TeacherQualification');
                $Q = $this->Components->load('FileAttachment', $arrMap);

                $errors = $Q->save($teacherQualificationData, $_FILES);

                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'qualificationsView', $teacherQualificationData['id']));
            }
         }
       
    }

    public function qualificationsDelete($id) {
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherQualificationId')) {
            $id = $this->Session->read('TeacherQualificationId');
            $teacherId = $this->Session->read('TeacherId');
            $name = $this->TeacherQualification->field('qualification_title', array('TeacherQualification.id' => $id));
            $this->TeacherQualification->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'qualifications', $teacherId));
        }
    }

    public function qualificationAttachmentsDelete($id) {
        $this->autoRender = false;
       
        $result = array('alertOpt' => array());
        $this->Utility->setAjaxResult('alert', $result);

        $teacherQualification = $this->TeacherQualification->findById($id);
        $name = $teacherQualification['TeacherQualification']['qualification_title'];
        $teacherQualification['TeacherQualification']['file_name'] = null;
        $teacherQualification['TeacherQualification']['file_content'] = null;

        if($this->TeacherQualification->save($teacherQualification)) {
            //$this->Utility->alert($name . ' have been deleted successfully.');
        } else {
           //$this->Utility->alert('Error occurred while deleting file.');
        }
        
        $this->redirect(array('action' => 'qualificationsEdit', $id));
        
    }
        
    public function qualificationAttachmentsDownload($id) {
        $arrMap = array('model'=>'Teachers.TeacherQualification');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);
        $FileAttachment->download($id);
        exit;
    }

    public function ajax_find_institution() {
        if($this->request->is('ajax')) {
            $this->autoRender = false;
            $search = $this->params->query['term'];
            $data = $this->QualificationInstitution->autocomplete($search);
 
            return json_encode($data);
        }
    }


    public function categoryCertificates() {
        $this->autoRender = false;
        $value = $this->TeacherQualificationCategory->find('all', array('conditions' => array('TeacherQualificationCategory.visible' => 1), 'recursive' => 1));
        echo json_encode($value);
    }

    /**
     * Trainings that the teacher has gone for till date
     * @return [type] [description]
     */
    public function training() {
        $this->Navigation->addCrumb('Training');
        if ($this->request->is('post')) {
            $this->TeacherTraining->create();
            $this->request->data['TeacherTraining']['teacher_id'] = $this->teacherId;
            $this->TeacherTraining->save($this->request->data['TeacherTraining']);
        }

        // $categories = $this->TeacherTrainingCategory->findAllByVisible(1);
        $visible = true;
        $categories = $this->TeacherTrainingCategory->findList($visible);
        array_unshift($categories, __('--Select--'));

        $list = $this->TeacherTraining->getData($this->teacherId);
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('data', $list);
        $this->set('categories', $categories);
    }


    public function trainingAdd() {

        $this->layout = 'ajax';
        $order = $this->params->query['order'] + 1;
        $this->set('order', $order);

        $visible = true;
        $categories = $this->TeacherTrainingCategory->findList($visible);
        array_unshift($categories, __('--Select--'));

        $list = $this->TeacherTraining->getData($this->teacherId);
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('data', $list);
        $this->set('categories', $categories);

    }


        public function trainingEdit() {
        $this->Navigation->addCrumb('Training');
        
        if($this->request->is('post')) { // save                    }
            if (isset($this->data['TeacherTraining'])) {

                $dataValues = $this->data['TeacherTraining'];
                for($i=1; $i <= count($dataValues); $i++) {
                    $dataValues[$i]['teacher_id'] = $this->teacherId;
                }
                // pr($dataValues); die();

                $result = $this->TeacherTraining->saveAll($dataValues);
                if($result){
                    $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'training');
                    $this->redirect(array('controller' => $this->params['controller'], 'action' => 'training'));
                    //$this->Session->setFlash('Saved.');
                }else{
                    //$this->Session->setFlash('Error in Saving.');
                }
            }
        }

        $list = $this->TeacherTraining->getData($this->teacherId);
        $categories = $this->TeacherTrainingCategory->findAllByVisible(1);

        $this->set('data', $list);
        $this->set('categories', $categories);

    }

    public function trainingDelete($id) {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            // $id = $this->params->data['id'];
            
            if($this->TeacherTraining->delete($id)) {
                $result['alertOpt']['text'] = __('Records have been deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting record.');
            }
            
            return json_encode($result);
        }
    }
	
	private function custFieldYrInits(){
		$this->Navigation->addCrumb('Annual Info');
		$action = $this->action;
		$siteid = @$this->request->params['pass'][2];
		$id = $this->teacherId;;
		$schoolYear = ClassRegistry::init('SchoolYear');
		$years = $schoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('teacher_id'=>$id,'institution_site_id'=>$siteid,'school_year_id'=>$selectedYear);
		
		$arrMap = array('CustomField'=>'TeacherDetailsCustomField',
						'CustomFieldOption'=>'TeacherDetailsCustomFieldOption',
						'CustomValue'=>'TeacherDetailsCustomValue',
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
        $this->Navigation->addCrumb("More", array('controller' => 'Teachers', 'action' => 'additional'));
		extract($this->custFieldYrInits());
		$customfield = $this->Components->load('CustomField',$arrMap);
		$data = array();
		if($id && $selectedYear && $siteid) $data = $customfield->getCustomFieldView($condParam);
		
		$institution_sites = $customfield->getCustomValuebyCond('list',array('fields'=>array('institution_site_id','school_year_id'),'conditions'=>array('school_year_id'=>$selectedYear,'teacher_id'=>$id)));
		
		$institution_sites = $this->custFieldSites(array_keys($institution_sites));
		if(count($institution_sites)<2)  $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
		$displayEdit = false;
		$this->set(compact('arrMap','selectedYear','siteid','years','action','id','institution_sites','displayEdit'));
		$this->set($data);
        $this->set('myview', 'additional');
		$this->render('/Elements/customfields/view');
	}

    // Teacher ATTENDANCE PART
    public function attendance(){
        $teacherId = $this->teacherId;
        $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
        $name = sprintf('%s %s', $data['Teacher']['first_name'], $data['Teacher']['last_name']);
        $this->Navigation->addCrumb($name, array('controller' => 'InstitutionSites', 'action' => 'teachersView', $teacherId));
        $this->Navigation->addCrumb('Attendance');

        $id = @$this->request->params['pass'][0];
        $yearList = $this->SchoolYear->getYearList();
        $yearId = $this->getAvailableYearId($yearList);
        $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));

        $data = $this->TeacherAttendance->getAttendanceData($this->Session->read('InstitutionSiteTeachersId'),isset($id)? $id:$yearId);

        $this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
        $this->set('data', $data);
        $this->set('schoolDays', $schoolDays);

        $id = @$this->request->params['pass'][0];
        $yearList = $this->SchoolYear->getYearList();
        $yearId = $this->getAvailableYearId($yearList);
        $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));
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
     	$str = $this->Teacher->find('first', array('order' => array('Teacher.id DESC'), 'limit' => 1, 'fields'=>'Teacher.id'));
		$prefix = $this->ConfigItem->find('first', array('limit' => 1, 
													  'fields'=>'ConfigItem.value',
													  'conditions'=>array(
																			'ConfigItem.name' => 'teacher_prefix'
																		 )
									   ));
		$prefix = explode(",",$prefix['ConfigItem']['value']);
    	
		if($prefix[1]>0){
			$id = $str['Teacher']['id']+1; 
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
	
	public function leaves() {
		$this->Navigation->addCrumb('Leaves');
		$teacherId = $this->Session->read('TeacherId');
		$data = $this->TeacherLeave->find('all', array(
			'recursive' => 0, 
			'conditions' => array('TeacherLeave.teacher_id' => $teacherId),
			'order' => array('TeacherLeave.date_from')
		));
		$this->set('data', $data);
	}
	
	public function leavesAdd() {
		$this->Navigation->addCrumb('Leaves');
		$typeOptions = $this->TeacherLeaveType->findList(true);
		$statusOptions = $this->LeaveStatus->findList(true);

		if($this->request->is('post')) {
			$data = $this->request->data;
			$data['TeacherLeave']['teacher_id'] = $this->Session->read('TeacherId');
			$this->TeacherLeave->set($data);
			if($this->TeacherLeave->validates()) {
				$this->TeacherLeave->create();
				$obj = $this->TeacherLeave->save($data);
                $id = $this->TeacherLeave->getInsertID();
                $arrMap = array('model'=>'Teachers.TeacherLeaveAttachment', 'foreignKey' => 'teacher_leave_id');
                $FileAttachment = $this->Components->load('FileAttachment', $arrMap);
                
                if(!empty($_FILES)){
                    $errors = $FileAttachment->saveAll($this->data, $_FILES, $id);
                }
				if($obj) {
					return $this->redirect(array('action' => 'leaves'));
				}
			}
		}
        $this->set('statusOptions', $statusOptions);
		$this->set('typeOptions', $typeOptions);
        $this->set('_model', 'TeacherLeaveAttachment');
	}
	
	public function leavesView($id=null) {
		$this->Navigation->addCrumb('Leaves');
		if(!is_null($id) && $this->TeacherLeave->exists($id)) { 
			$typeOptions = $this->TeacherLeaveType->findList(true);
            $statusOptions = $this->LeaveStatus->findList(true);
			$data = $this->TeacherLeave->find('first', array('recursive' => 0, 'conditions' => array('TeacherLeave.id' => $id)));
            $arrMap = array('model'=>'Teachers.TeacherLeaveAttachment', 'foreignKey' => 'teacher_leave_id');
            $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

            $attachments = $FileAttachment->getList($id);
			$this->set('statusOptions', $statusOptions);
            $this->set('typeOptions', $typeOptions);
			$this->set('data', $data);
            $this->set('attachments', $attachments);
            $this->set('_model', 'TeacherLeaveAttachment');
		} else {
			return $this->redirect(array('action' => 'leaves'));
		}
	}
	
	public function leavesEdit($id=null) {
		$this->Navigation->addCrumb('Leaves');
		if(!is_null($id) && $this->TeacherLeave->exists($id)) {
            $arrMap = array('model'=>'Teachers.TeacherLeaveAttachment', 'foreignKey' => 'teacher_leave_id');
            $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

			if($this->request->is('post') || $this->request->is('put')) {
				$data = $this->request->data;
				$data['TeacherLeave']['id'] = $id;
				$data['TeacherLeave']['teacher_id'] = $this->Session->read('TeacherId');
				$this->TeacherLeave->set($data);
				if($this->TeacherLeave->validates()) {
					$obj = $this->TeacherLeave->save($data);
                    if(!empty($_FILES)){
                        $errors = $FileAttachment->saveAll($this->data, $_FILES, $id);
                    }
					if($obj) {
						$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
						return $this->redirect(array('action' => 'leavesView', $obj['TeacherLeave']['id']));
					}
				}
			}

            $attachments = $FileAttachment->getList($id);
            $this->set('attachments',$attachments);
            $this->set('_model','TeacherLeaveAttachment');

			$typeOptions = $this->TeacherLeaveType->findList(true);
            $statusOptions = $this->LeaveStatus->findList(true);
			$this->request->data = $this->TeacherLeave->find('first', array('recursive' => 0, 'conditions' => array('TeacherLeave.id' => $id)));
			$this->set('statusOptions', $statusOptions);
            $this->set('typeOptions', $typeOptions);
		} else {
			return $this->redirect(array('action' => 'leaves'));
		}
	}
	
	public function leavesDelete($id=null) {
		if(!is_null($id) && $this->TeacherLeave->exists($id) && $this->Session->check('TeacherId')) {

			$this->TeacherLeave->delete($id);
			$this->Utility->alert($this->Utility->getMessage('DELETE_SUCCESS'));
		}
		return $this->redirect(array('action' => 'leaves'));
	}

    public function attachmentsLeaveAdd() {
        $this->layout = 'ajax';
        $this->set('params', $this->params->query);
        $this->set('_model', 'TeacherLeaveAttachment');
        $this->set('jsname', 'objTeacherLeaves');
        $this->render('/Elements/attachment/compact_add');
    }

    public function attachmentsLeaveDelete() {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = $this->params->data['id'];

            $arrMap = array('model'=>'Teacher.TeacherLeaveAttachment', 'foreignKey' => 'teacher_leave_id');
            $FileAttachment = $this->Components->load('FileAttachment', $arrMap);
            
            if($FileAttachment->delete($id)) {
                $result['alertOpt']['text'] = __('File is deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting file.');
            }
            
            return json_encode($result);
        }
    }
        
    public function attachmentsLeaveDownload($id) {
        $arrMap = array('model'=>'Teacher.TeacherLeaveAttachment', 'foreignKey' => 'teacher_leave_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

        $FileAttachment->download($id);
    }
       
        
    /***BANK ACCOUNTS - sorry have to copy paste to othe modules too lazy already**/
    public function bankAccounts() {
        $this->Navigation->addCrumb('Bank Accounts');

        $data = $this->TeacherBankAccount->find('all',array('conditions'=>array('TeacherBankAccount.teacher_id'=>$this->teacherId)));
        $bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));
        $banklist = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
        $this->set('data',$data);
        $this->set('bank',$bank);
        $this->set('banklist',$banklist);
    }


    public function bankAccountsView() {
        $bankAccountId = $this->params['pass'][0];
        $bankAccountObj = $this->TeacherBankAccount->find('all',array('conditions'=>array('TeacherBankAccount.id' => $bankAccountId)));
        
        if(!empty($bankAccountObj)) {
            $this->Navigation->addCrumb('Bank Account Details');
            
            $this->Session->write('TeacherBankAccountId', $bankAccountId);
            $this->set('bankAccountObj', $bankAccountObj);
        }
        $banklist = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
        $this->set('banklist',$banklist);

    }

    public function bankAccountsAdd() {
        $this->Navigation->addCrumb('Add Bank Accounts');
        if($this->request->is('post')) { // save
            $this->TeacherBankAccount->create();
            if($this->TeacherBankAccount->save($this->request->data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'bankAccounts'));
            }
        }
        $bank = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));

        $bankId = isset($this->request->data['TeacherBankAccount']['bank_id']) ? $this->request->data['TeacherBankAccount']['bank_id'] : "";
        if(!empty($bankId)){
            $bankBranches = $this->BankBranch->find('list', array('conditions'=>array('bank_id'=>$bankId, 'visible'=>1), 'recursive' => -1));
        }else{
            $bankBranches = array();
        }
        
        //pr($bankBranches);
        $this->set('bankBranches', $bankBranches);
        $this->set('selectedBank', $bankId);
        $this->set('teacher_id', $this->teacherId);
        $this->set('bank',$bank);
    }

    public function bankAccountsEdit() {
        $bankBranch = array();

        $bankAccountId = $this->params['pass'][0];
        $this->Navigation->addCrumb('Edit Bank Account Details');
        if($this->request->is('get')) {
            $bankAccountObj = $this->TeacherBankAccount->find('first',array('conditions'=>array('TeacherBankAccount.id' => $bankAccountId)));
  
            if(!empty($bankAccountObj)) {
                //$bankAccountObj['StaffQualification']['qualification_institution'] = $institutes[$staffQualificationObj['StaffQualification']['qualification_institution_id']];
                $this->request->data = $bankAccountObj;
            }
         } else {
            $this->request->data['TeacherBankAccount']['teacher_id'] = $this->teacherId;
            if($this->TeacherBankAccount->save($this->request->data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));  
                $this->redirect(array('action' => 'bankAccountsView', $this->request->data['TeacherBankAccount']['id']));
            }
         }
        $bankId = isset($this->request->data['TeacherBankAccount']['bank_id']) ? $this->request->data['TeacherBankAccount']['bank_id'] : $bankAccountObj['BankBranch']['bank_id'];
        $this->set('selectedBank', $bankId);

        $bankBranch = $this->BankBranch->find('list', array('conditions'=>array('bank_id'=>$bankId, 'visible'=>1), 'recursive' => -1));
        $this->set('bankBranch', $bankBranch);

        $bank = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
        $this->set('bank',$bank);

        $this->set('id', $bankAccountId);
    }

   
    public function bankAccountsDelete($id) {
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherBankAccountId')) {
            $id = $this->Session->read('TeacherBankAccountId');

            $teacherId = $this->Session->read('TeacherId');
            $name = $this->TeacherBankAccount->field('account_number', array('TeacherBankAccount.id' => $id));
            $this->TeacherBankAccount->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'bankAccounts'));
        }
    }

    public function bankAccountsBankBranches() {
            $this->autoRender = false;
            $bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));
            echo json_encode($bank);
    }
    
    public function getBranchesByBankId(){
            $this->autoRender = false;

            if(isset($this->params['pass'][0]) && !empty($this->params['pass'][0])) {
                $bankId = $this->params['pass'][0];
                $bankBranches = $this->BankBranch->find('all', array('conditions'=>array('bank_id'=>$bankId, 'visible'=>1), 'recursive' => -1));
                //pr($bankBranches);
                echo json_encode($bankBranches);
            }
    }

    // Staff behaviour part
    public function behaviour(){
        $this->Navigation->addCrumb('List of Behaviour');

        $data = $this->TeacherBehaviour->getBehaviourData($this->teacherId);
        if(empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
        }

        $this->set('data', $data);
    }

    public function behaviourView() {
        $teacherBehaviourId = $this->params['pass'][0];
        $teacherBehaviourObj = $this->TeacherBehaviour->find('all',array('conditions'=>array('TeacherBehaviour.id' => $teacherBehaviourId)));

        if(!empty($teacherBehaviourObj)) {
            $teacherId = $teacherBehaviourObj[0]['TeacherBehaviour']['teacher_id'];
            $data = $this->Teacher->find('first', array('conditions' => array('Teacher.id' => $teacherId)));
            $this->Navigation->addCrumb('Behaviour Details');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();
            $categoryOptions = array();
            $categoryOptions = $this->TeacherBehaviourCategory->getCategory();

            $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive'=>-1));
            $this->set('institution_site_id', $teacherBehaviourObj[0]['TeacherBehaviour']['institution_site_id']);
            $this->set('institutionSiteOptions', $institutionSiteOptions);

            $this->Session->write('TeacherBehaviourId', $teacherBehaviourId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('yearOptions', $yearOptions);
            $this->set('teacherBehaviourObj', $teacherBehaviourObj);
        } else {
            $this->redirect(array('action' => 'behaviour'));
        }
    }

    public function comments(){
        $this->Navigation->addCrumb('Comments');
        $data = $this->TeacherComment->find('all',array('conditions'=>array('TeacherComment.teacher_id'=>$this->teacherId), 'recursive' => -1, 'order'=>'TeacherComment.comment_date'));

        $this->set('list', $data);
    }

    public function commentsAdd() {
        if ($this->request->is('post')) {
            $this->TeacherComment->create();
            $this->request->data['TeacherComment']['teacher_id'] = $this->teacherId;
            
            $data = $this->data['TeacherComment'];

            if ($this->TeacherComment->save($data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'comments'));
            }
        }

        $this->UserSession->readStatusSession($this->request->action);
    }

    public function commentsView() {
        $commentId = $this->params['pass'][0];
        $commentObj = $this->TeacherComment->find('all',array('conditions'=>array('TeacherComment.id' => $commentId)));
        
        if(!empty($commentObj)) {
            $this->Navigation->addCrumb('Comment Details');
            
            $this->Session->write('TeacherCommentId', $commentId);
            $this->set('commentObj', $commentObj);
        } 
    }

    public function commentsEdit() {
        $commentId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $commentObj = $this->TeacherComment->find('first',array('conditions'=>array('TeacherComment.id' => $commentId)));
  
            if(!empty($commentObj)) {
                $this->Navigation->addCrumb('Edit Comment Details');
                $this->request->data = $commentObj;
               
            }
         } else {
            $commentData = $this->data['TeacherComment'];
            $commentData['teacher_id'] = $this->teacherId;
            
            if ($this->TeacherComment->save($commentData)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'commentsView', $commentData['id']));
            }
         }

        $this->set('id', $commentId);
       
    }

    public function commentsDelete($id) {
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherCommentId')) {
            $id = $this->Session->read('TeacherCommentId');
            $teacherId = $this->Session->read('TeacherId');
            $name = $this->TeacherComment->field('title', array('TeacherComment.id' => $id));
            $this->TeacherComment->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'comments', $teacherId));
        }
    }

    public function nationalities(){
        $this->Navigation->addCrumb('Nationalities');
        $data = $this->TeacherNationality->find('all',array('conditions'=>array('TeacherNationality.teacher_id'=>$this->teacherId)));
		$this->set('list', $data);
    }
	
	public function nationalitiesAdd() {
        if ($this->request->is('post')) {
            $this->TeacherNationality->create();
            $this->request->data['TeacherNationality']['teacher_id'] = $this->teacherId;
            
            $data = $this->data['TeacherNationality'];

            if ($this->TeacherNationality->save($data)){
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
        $nationalityObj = $this->TeacherNationality->find('all',array('conditions'=>array('TeacherNationality.id' => $nationalityId)));
        
        if(!empty($nationalityObj)) {
            $this->Navigation->addCrumb('Nationality Details');
            
            $this->Session->write('TeacherNationalityId', $nationalityId);
            $this->set('nationalityObj', $nationalityObj);
        } 
    }

    public function nationalitiesEdit() {
        $nationalityId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $nationalityObj = $this->TeacherNationality->find('first',array('conditions'=>array('TeacherNationality.id' => $nationalityId)));
  
            if(!empty($nationalityObj)) {
                $this->Navigation->addCrumb('Edit Nationality Details');
                $this->request->data = $nationalityObj;
               
            }
         } else {
            $nationalityData = $this->data['TeacherNationality'];
            $nationalityData['teacher_id'] = $this->teacherId;
            
            if ($this->TeacherNationality->save($nationalityData)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'nationalitiesView', $nationalityData['id']));
            }
         }

        $countryOptions = $this->Country->getOptions();
        $this->set('countryOptions', $countryOptions);

        $this->set('id', $nationalityId);
       
    }
	
	public function nationalitiesDelete($id) {
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherNationalityId')) {
            $id = $this->Session->read('TeacherNationalityId');
            $teacherId = $this->Session->read('TeacherId');
            $countryId = $this->TeacherNationality->field('country_id', array('TeacherNationality.id' => $id));
            $name = $this->Country->field('name', array('Country.id' => $countryId));
            $this->TeacherNationality->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'nationalities', $teacherId));
		}
    }
	
	public function identities(){
        $this->Navigation->addCrumb('Identities');
        $data = $this->TeacherIdentity->find('all',array('conditions'=>array('TeacherIdentity.teacher_id'=>$this->teacherId)));
        $this->set('list', $data);
    }
	
    public function identitiesAdd() {
        if ($this->request->is('post')) {
            $this->TeacherIdentity->create();
            $this->request->data['TeacherIdentity']['teacher_id'] = $this->teacherId;
            
            $data = $this->data['TeacherIdentity'];

            if ($this->TeacherIdentity->save($data)){
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
        $identityObj = $this->TeacherIdentity->find('all',array('conditions'=>array('TeacherIdentity.id' => $identityId)));
        
        if(!empty($identityObj)) {
            $this->Navigation->addCrumb('Identity Details');
            
            $this->Session->write('TeacherIdentityId', $identityId);
            $this->set('identityObj', $identityObj);
        } 
    }

    public function identitiesEdit() {
        $identityId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $identityObj = $this->TeacherIdentity->find('first',array('conditions'=>array('TeacherIdentity.id' => $identityId)));
  
            if(!empty($identityObj)) {
                $this->Navigation->addCrumb('Edit Identity Details');
                $this->request->data = $identityObj;
               
            }
         } else {
            $identityData = $this->data['TeacherIdentity'];
            $identityData['teacher_id'] = $this->teacherId;
            
            if ($this->TeacherIdentity->save($identityData)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'identitiesView', $identityData['id']));
            }
         }

        $identityTypeOptions = $this->IdentityType->getOptions();
        $this->set('identityTypeOptions', $identityTypeOptions);

        $this->set('id', $identityId);
       
    }
	
    public function identitiesDelete($id) {
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherIdentityId')) {
            $id = $this->Session->read('TeacherIdentityId');
            $teacherId = $this->Session->read('TeacherId');
            $name = $this->TeacherIdentity->field('number', array('TeacherIdentity.id' => $id));
            $this->TeacherIdentity->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'identities', $teacherId));
        }
    }

    public function languages(){
        $this->Navigation->addCrumb('Languages');
        $data = $this->TeacherLanguage->find('all',array('conditions'=>array('TeacherLanguage.teacher_id'=>$this->teacherId)));
        $this->set('list', $data);
    }
    
    public function languagesAdd() {
        if ($this->request->is('post')) {
            $this->TeacherLanguage->create();
            $this->request->data['TeacherLanguage']['teacher_id'] = $this->teacherId;
            
            $data = $this->data['TeacherLanguage'];

            if ($this->TeacherLanguage->save($data)){
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
        $languageObj = $this->TeacherLanguage->find('all',array('conditions'=>array('TeacherLanguage.id' => $languageId)));
        
        if(!empty($languageObj)) {
            $this->Navigation->addCrumb('Language Details');
            
            $this->Session->write('TeacherLanguageId', $languageId);
            $this->set('languageObj', $languageObj);
        } 
    }

    public function languagesEdit() {
        $languageId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $languageObj = $this->TeacherLanguage->find('first',array('conditions'=>array('TeacherLanguage.id' => $languageId)));
  
            if(!empty($languageObj)) {
                $this->Navigation->addCrumb('Edit Language Details');
                $this->request->data = $languageObj;
               
            }
         } else {
            $languageData = $this->data['TeacherLanguage'];
            $languageData['teacher_id'] = $this->teacherId;
           
            if ($this->TeacherLanguage->save($languageData)){
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
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherLanguageId')) {
            $id = $this->Session->read('TeacherLanguageId');
            $teacherId = $this->Session->read('TeacherId');
            $languageId = $this->TeacherLanguage->field('language_id', array('TeacherLanguage.id' => $id));
            $name = $this->Language->field('name', array('Language.id' => $languageId));
            $this->TeacherLanguage->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'languages', $teacherId));
	}
    }

    public function contacts(){
        $this->Navigation->addCrumb('Contacts');
        $data = $this->TeacherContact->find('all',array('conditions'=>array('TeacherContact.teacher_id'=>$this->teacherId), 'order'=>array('ContactType.contact_option_id', 'TeacherContact.preferred DESC')));

        $contactOptions = $this->ContactOption->getOptions();
        $this->set('contactOptions', $contactOptions);

        $this->set('list', $data);
    }
    
    public function contactsAdd() {
        if ($this->request->is('post')) {
            $this->TeacherContact->create();
            $this->request->data['TeacherContact']['teacher_id'] = $this->teacherId;
            
            $contactData = $this->data['TeacherContact'];

            if ($this->TeacherContact->save($contactData)){
                if($contactData['preferred']=='1'){
                    $this->TeacherContact->updateAll(array('TeacherContact.preferred' =>'0'), array('ContactType.contact_option_id'=>$contactData['contact_option_id'], array('NOT'=>array('TeacherContact.id'=>array($this->TeacherContact->getLastInsertId())))));
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
        $contactObj = $this->TeacherContact->find('all',array('conditions'=>array('TeacherContact.id' => $contactId)));
        
        if(!empty($contactObj)) {
            $this->Navigation->addCrumb('Contact Details');
            
            $this->Session->write('TeacherContactId', $contactId);
            $this->set('contactObj', $contactObj);
        } 

        $contactOptions = $this->ContactOption->getOptions();
        $this->set('contactOptions', $contactOptions);
    }

    public function contactsEdit() {
        $contactId = $this->params['pass'][0];
        $contactObj = array();
        if($this->request->is('get')) {
            $contactObj = $this->TeacherContact->find('first',array('conditions'=>array('TeacherContact.id' => $contactId)));
  
            if(!empty($contactObj)) {
                $this->Navigation->addCrumb('Edit Contact Details');
                $this->request->data = $contactObj;
            }
         } else {
            $contactData = $this->data['TeacherContact'];
            $contactData['student_id'] = $this->studentId;

            if ($this->TeacherContact->save($contactData)){
                if($contactData['preferred']=='1'){
                    $this->TeacherContact->updateAll(array('TeacherContact.preferred' =>'0'), array('ContactType.contact_option_id'=>$contactData['contact_option_id'], array('NOT'=>array('TeacherContact.id'=>array($contactId)))));
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
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherContactId')) {
            $id = $this->Session->read('TeacherContactId');
            $teacherId = $this->Session->read('TeacherId');
            $name = $this->TeacherContact->field('value', array('TeacherContact.id' => $id));
            $this->TeacherContact->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'contacts', $teacherId));
        }
    }
	
    public function extracurricular(){
        $this->Navigation->addCrumb('Extracurricular');
		$data = $this->TeacherExtracurricular->getAllList('teacher_id',$this->teacherId);
        $this->set('list', $data);
    }
	
	public function extracurricularView() {
        $id = $this->params['pass'][0];
        $data = $this->TeacherExtracurricular->getAllList('id',$id);
        if(!empty($data)) {
            $this->Navigation->addCrumb('Extracurricular Details');
            
            $this->Session->write('TeacherExtracurricularId', $id);
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
			$data['TeacherExtracurricular']['teacher_id'] = $this->teacherId;
			
			if ($this->TeacherExtracurricular->save($data)){
				$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
				$this->redirect(array('action' => 'extracurricular'));
			}
		}
    }
	
	public function extracurricularEdit() {
        $id = $this->params['pass'][0];
        $this->Navigation->addCrumb('Edit Extracurricular Details');
		
        if($this->request->is('get')) {
            $data = $this->TeacherExtracurricular->find('first',array('conditions'=>array('TeacherExtracurricular.id' => $id)));
  
            if(!empty($data)) {
                $this->request->data = $data;
            }
         } else {
            $data = $this->data;
			$data['TeacherExtracurricular']['teacher_id'] = $this->teacherId;
			$data['TeacherExtracurricular']['id'] = $id;
			if ($this->TeacherExtracurricular->save($data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'extracurricularView', $data['TeacherExtracurricular']['id']));
            }
         }

        $yearList = $this->SchoolYear->getYearList();
		$yearId = $this->getAvailableYearId($yearList);
		$typeList = $this->ExtracurricularType->findList(array('fields' =>array('id','name'), 'conditions'=>array('visible' => '1'), 'orderBy' => 'name'));
		
		$this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
		$this->set('types', $typeList);

        $this->set('id', $id);
    }
	
	public function extracurricularDelete($id) {
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherExtracurricularId')) {
            $id = $this->Session->read('TeacherExtracurricularId');
            $teacherId = $this->Session->read('TeacherId');
            $name = $this->TeacherExtracurricular->field('name', array('TeacherExtracurricular.id' => $id));
			
            $this->TeacherExtracurricular->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'extracurricular'));
        }
    }
	
	public function searchAutoComplete(){
		if($this->request->is('get')) {
			if($this->request->is('ajax')) {
				$this->autoRender = false;
				$search = $this->params->query['term'];
				$result = $this->TeacherExtracurricular->autocomplete($search);
				return json_encode($result);
			} 
		}
	}

    public function employments(){
        $this->Navigation->addCrumb('Employment');
        $data = $this->TeacherEmployment->find('all',array('conditions'=>array('TeacherEmployment.teacher_id'=>$this->teacherId)));
        $this->set('list', $data);
    }
    
    public function employmentsAdd() {
        if ($this->request->is('post')) {
            $this->TeacherEmployment->create();
            $this->request->data['TeacherEmployment']['teacher_id'] = $this->teacherId;
            
            $data = $this->data['TeacherEmployment'];

            if ($this->TeacherEmployment->save($data)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'employments'));
            }
        }

        $employmentTypeOptions = $this->EmploymentType->getOptions();
        $this->set('employmentTypeOptions', $employmentTypeOptions);
        $this->UserSession->readStatusSession($this->request->action);
    }
    
    public function employmentsView() {
        $employmentId = $this->params['pass'][0];
        $employmentObj = $this->TeacherEmployment->find('all',array('conditions'=>array('TeacherEmployment.id' => $employmentId)));
        
        if(!empty($employmentObj)) {
            $this->Navigation->addCrumb('Employment Details');
            
            $this->Session->write('TeacherEmploymentId', $employmentId);
            $this->set('employmentObj', $employmentObj);
        } 
    }
    
    public function employmentsEdit() {
        $employmentId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $employmentObj = $this->TeacherEmployment->find('first',array('conditions'=>array('TeacherEmployment.id' => $employmentId)));
  
            if(!empty($employmentObj)) {
                $this->Navigation->addCrumb('Edit Employment Details');
                $this->request->data = $employmentObj;
               
            }
         } else {
            $employmentData = $this->data['TeacherEmployment'];
            $employmentData['teacher_id'] = $this->teacherId;
            
            if ($this->TeacherEmployment->save($employmentData)){
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'employmentsView', $employmentData['id']));
            }
         }

        $employmentTypeOptions = $this->EmploymentType->getOptions();
        $this->set('employmentTypeOptions', $employmentTypeOptions);

        $this->set('id', $employmentId);  
    }

    public function employmentsDelete($id) {
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherEmploymentId')) {
            $id = $this->Session->read('TeacherEmploymentId');
            $teacherId = $this->Session->read('TeacherId');
            $employmentTypeId = $this->TeacherEmployment->field('employment_type_id', array('TeacherEmployment.id' => $id));
            $name = $this->EmploymentType->field('name', array('EmploymentType.id' => $employmentTypeId));
            $this->TeacherEmployment->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'employments', $teacherId));
        }
    }
    
    public function salaries(){
        $this->Navigation->addCrumb('Salary');
        $data = $this->TeacherSalary->find('all',array('conditions'=>array('TeacherSalary.teacher_id'=>$this->teacherId)));
        $this->set('list', $data);
    }
    
    public function salariesAdd() {
        if ($this->request->is('post')) {
            $this->request->data['TeacherSalary']['teacher_id'] = $this->teacherId;

            $this->TeacherSalary->create(); 
   
            $this->TeacherSalary->saveAll($this->request->data['TeacherSalary'], array('validate' => 'only'));
            if(isset($this->request->data['TeacherSalaryAddition'])){
                $this->TeacherSalaryAddition->saveAll($this->request->data['TeacherSalaryAddition'], array('validate' => 'only'));
            }
            if(isset($this->request->data['TeacherSalaryDeduction'])){
                $this->TeacherSalaryDeduction->saveAll($this->request->data['TeacherSalaryDeduction'], array('validate' => 'only'));
            }

            if (!$this->TeacherSalary->validationErrors && 
            !$this->TeacherSalaryAddition->validationErrors &&
            !$this->TeacherSalaryDeduction->validationErrors){
                $this->TeacherSalary->saveAll($this->request->data);
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'salaries'));
            }else{
                $this->Utility->alert($this->Utility->getMessage('ADD_ERROR'), array('type' => 'warn', 'dismissOnClick' => false));
            }
        }

        $visible = true;
        $additionOptions = $this->SalaryAdditionType->findList($visible);
        $this->set('additionOptions', $additionOptions);
        $deductionOptions = $this->SalaryDeductionType->findList($visible);
        $this->set('deductionOptions', $deductionOptions);

        $this->UserSession->readStatusSession($this->request->action);
    }
    
    public function salariesView() {
        $salaryId = $this->params['pass'][0];
        $salaryObj = $this->TeacherSalary->find('all',array('conditions'=>array('TeacherSalary.id' => $salaryId)));
        
        if(!empty($salaryObj)) {
            $this->Navigation->addCrumb('Salary Details');
            
            $this->Session->write('TeacherSalaryId', $salaryId);
            $this->set('salaryObj', $salaryObj);
            $visible = true;
            $additionOptions = $this->SalaryAdditionType->findList($visible);
            $this->set('additionOptions', $additionOptions);
            $deductionOptions = $this->SalaryDeductionType->findList($visible);
            $this->set('deductionOptions', $deductionOptions);
        } 
    }

    public function salariesEdit() {
        $salaryId = $this->params['pass'][0];
        if($this->request->is('get')) {
            $salaryObj = $this->TeacherSalary->find('first',array('conditions'=>array('TeacherSalary.id' => $salaryId)));
  
            if(!empty($salaryObj)) {
                $this->Navigation->addCrumb('Edit Salary Details');
                $this->request->data = $salaryObj;
               
            }
         } else {
            if(isset($this->request->data['DeleteAddition'])){
                $deletedId = array();
                foreach($this->request->data['DeleteAddition'] as $key=>$value){
                    $deletedId[] = $value['id'];
                    pr('test');
                    unset($this->request->data['TeacherSalaryAddition'][$key]);
                }
                $this->TeacherSalaryAddition->deleteAll(array('TeacherSalaryAddition.id' => $deletedId, 'TeacherSalaryAddition.teacher_salary_id'=> $salaryId), false);
            }
            if(isset($this->request->data['DeleteDeduction'])){
                $deletedId = array();
                foreach($this->request->data['DeleteDeduction'] as $key=>$value){
                    $deletedId[] = $value['id'];
                    unset($this->request->data['TeacherSalaryDeduction'][$key]);
                }
                $this->TeacherSalaryDeduction->deleteAll(array('TeacherSalaryDeduction.id' => $deletedId, 'TeacherSalaryDeduction.teacher_salary_id'=> $salaryId), false);
            }
            $this->request->data['TeacherSalary']['teacher_id'] = $this->teacherId;

            $this->TeacherSalary->saveAll($this->request->data['TeacherSalary'], array('validate' => 'only'));
            if(isset($this->request->data['TeacherSalaryAddition'])){
                $this->TeacherSalaryAddition->saveAll($this->request->data['TeacherSalaryAddition'], array('validate' => 'only'));
            }
            if(isset($this->request->data['TeacherSalaryDeduction'])){
                $this->TeacherSalaryDeduction->saveAll($this->request->data['TeacherSalaryDeduction'], array('validate' => 'only'));
            }

            if (!$this->TeacherSalary->validationErrors && 
            !$this->TeacherSalaryAddition->validationErrors &&
            !$this->TeacherSalaryDeduction->validationErrors){
                $this->TeacherSalary->saveAll($this->request->data);
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'salariesView', $salaryId));
            }else{
                 $this->Utility->alert($this->Utility->getMessage('UPDATE_ERROR'), array('type' => 'warn', 'dismissOnClick' => false));
            }
         }

        $visible = true;
        $additionOptions = $this->SalaryAdditionType->findList($visible);
        $this->set('additionOptions', $additionOptions);
        $deductionOptions = $this->SalaryDeductionType->findList($visible);
        $this->set('deductionOptions', $deductionOptions);

        $this->set('id', $salaryId);
    }

    public function salaryAdditionAdd(){
        $this->layout = 'ajax';
        $order = $this->params->query['order'];
        $this->set('order', $order);
     
        $visible = true;
        $categories = $this->SalaryAdditionType->findList($visible);

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('categories', $categories);
    }

       public function salaryDeductionAdd(){
        $this->layout = 'ajax';
        $order = $this->params->query['order'];
        $this->set('order', $order);
     
        $visible = true;
        $categories = $this->SalaryDeductionType->findList($visible);

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('categories', $categories);
    }
    
    public function salariesDelete($id) {
        if($this->Session->check('TeacherId') && $this->Session->check('TeacherSalaryId')) {
            $id = $this->Session->read('TeacherSalaryId');
            $teacherId = $this->Session->read('TeacherId');
            $name = $this->TeacherSalary->field('salary_date', array('TeacherSalary.id' => $id));
            $this->TeacherSalary->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'salaries', $teacherId));
        }
    }
}

