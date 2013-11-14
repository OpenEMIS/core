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

class StaffController extends StaffAppController {
    public $staffId;
    public $staffObj;

    public $uses = array(
            'Area',
            'Institution',
            'InstitutionSite',
            'InstitutionSiteType',
            'InstitutionSiteStaff',
            'Bank',
            'Staff.StaffBankAccount',
            'BankBranch',
            'Staff.InstitutionSiteStaff',
            'Staff.Staff',
            'Staff.StaffHistory',
            'Staff.StaffCustomField',
            'Staff.StaffCustomFieldOption',
            'Staff.StaffCustomValue',
            'Staff.StaffAttachment',
            'Staff.StaffAttendance',
			'Staff.StaffLeave',
			'Staff.StaffLeaveType',
			'Staff.StaffBehaviour',
            'Staff.StaffBehaviourCategory',
            'Staff.StaffQualification',
            'QualificationLevel',
            'QualificationInstitution',
            'QualificationSpecialisation',
            'SchoolYear',
            'ConfigItem',
            'LeaveStatus',
            'StaffLeaveAttachment'
        );

    public $helpers = array('Js' => array('Jquery'), 'Paginator');
    public $components = array(
        'UserSession',
        'Paginator'
    );

    public function beforeFilter() {
        parent::beforeFilter();

        $this->Navigation->addCrumb('Staff', array('controller' => 'Staff', 'action' => 'index'));
        $actions = array('index', 'advanced', 'add', 'viewStaff');
		if(in_array($this->action, $actions)) {
			$this->bodyTitle = 'Staff';
		} else {
            if($this->Session->check('StaffId') && $this->action!=='Home' ) {
                $this->staffId = $this->Session->read('StaffId');
                $this->staffObj = $this->Session->read('StaffObj');
                $staffFirstName = $this->Staff->field('first_name', array('Staff.id' => $this->staffId));
                $staffLastName = $this->Staff->field('last_name', array('Staff.id' => $this->staffId));
                $name = $staffFirstName ." ". $staffLastName;
                $this->bodyTitle = $name;
                $this->Navigation->addCrumb($name, array('action' => 'view'));
            }
        }
    }

    public function index() {
        $this->Navigation->addCrumb('List of Staff');
        if($this->request->is('post')){
            if(isset($this->request->data['Staff']['SearchField'])){
               $this->request->data['Staff']['SearchField'] = Sanitize::escape($this->request->data['Staff']['SearchField']);
                if($this->request->data['Staff']['SearchField'] != $this->Session->read('Search.SearchFieldStaff')) {
                    $this->Session->delete('Search.SearchFieldStaff');
                    $this->Session->write('Search.SearchFieldStaff', $this->request->data['Staff']['SearchField']);
                }
            }

            if(isset($this->request->data['sortdir']) && isset($this->request->data['order'])) {
                if($this->request->data['sortdir'] != $this->Session->read('Search.sortdirStaff')) {
                    $this->Session->delete('Search.sortdirStaff');
                    $this->Session->write('Search.sortdirStaff', $this->request->data['sortdir']);
                }
                if($this->request->data['order'] != $this->Session->read('Search.orderStaff')) {
                    $this->Session->delete('Search.orderStaff');
                    $this->Session->write('Search.orderStaff', $this->request->data['order']);
                }
            }
        }

        $fieldordername = ($this->Session->read('Search.orderStaff'))?$this->Session->read('Search.orderStaff'):'Staff.first_name';
        $fieldorderdir = ($this->Session->read('Search.sortdirStaff'))?$this->Session->read('Search.sortdirStaff'):'asc';
		
		$searchKey = stripslashes($this->Session->read('Search.SearchFieldStaff'));
		$conditions = array(
			'SearchKey' => $searchKey, 
			'AdvancedSearch' => $this->Session->check('Staff.AdvancedSearch') ? $this->Session->read('Staff.AdvancedSearch') : null,
			'isSuperAdmin' => $this->Auth->user('super_admin'),
			'userId' => $this->Auth->user('id')
		);
		$order = array('order' => array($fieldordername => $fieldorderdir));
		$limit = ($this->Session->read('Search.perpageStaff')) ? $this->Session->read('Search.perpageStaff') : 30;
        $this->Paginator->settings = array_merge(array('limit' => $limit, 'maxLimit' => 100), $order);
		
        $data = $this->paginate('Staff', $conditions);
		if(empty($searchKey) && !$this->Session->check('Staff.AdvancedSearch')) {
			if(count($data) == 1 && !$this->AccessControl->check($this->params['controller'], 'add')) {
				$this->redirect(array('action' => 'viewStaff', $data[0]['Staff']['id']));
			}
		}
		if(empty($data) && !$this->request->is('ajax')) {
			$this->Utility->alert($this->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
		}
        $this->set('staff', $data);
        $this->set('sortedcol', $fieldordername);
        $this->set('sorteddir', ($fieldorderdir == 'asc')?'up':'down');
        $this->set('searchField', $searchKey);
        if($this->request->is('post')){
            $this->render('index_records','ajax');
        }
    }
	
	public function advanced() {
		$key = 'Staff.AdvancedSearch';
		if($this->request->is('get')) {
			if($this->request->is('ajax')) {
				$this->autoRender = false;
				$search = $this->params->query['term'];
				$result = $this->Area->autocomplete($search);
				return json_encode($result);
			} else {
				$this->Navigation->addCrumb('List of Staff', array('controller' => 'Staff', 'action' => 'index'));
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
        
        public function getCustomFieldsSearch($sitetype = 0, $customfields = 'Staff'){
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

    public function viewStaff($id) {
        $this->Session->write('StaffId', $id);
        $obj = $this->Staff->find('first',array('conditions'=>array('Staff.id' => $id)));
        $this->Session->write('StaffObj', $obj);
        $this->redirect(array( 'action' => 'view'));
    }

    public function view() {
        $this->Navigation->addCrumb('Overview');
        $this->Staff->id = $this->Session->read('StaffId');
        $data = $this->Staff->read();

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('data', $data);
    }

    public function edit() {
        $this->Navigation->addCrumb('Edit');
        $this->Staff->id = $this->Session->read('StaffId');

        $imgValidate = new ImageValidate();
		$data = $this->data;
        if ($this->request->is('post')) {

            $reset_image = $data['Staff']['reset_image'];

            $img = new ImageMeta($data['Staff']['photo_content']);
            unset($data['Staff']['photo_content']);

            if($reset_image == 0 ) {
                $validated = $imgValidate->validateImage($img);
                if($img->getFileUploadError() !== 4 && $validated['error'] < 1){
                    $data['Staff']['photo_content'] = $img->getContent();
                    $img->setContent('');
    //                $data['Staff']['photo_name'] = serialize($img);
                    $data['Staff']['photo_name'] = $img->getFilename();
                }
            }else{
                $data['Staff']['photo_content'] = '';
                $data['Staff']['photo_name'] = '';
            }

            $this->Staff->set($data);
            if($this->Staff->validates() && ($reset_image == 1 || $validated['error'] < 1)) {
                unset($data['Staff']['rest_image']);
                $this->Staff->set($data);
                $this->Staff->save();
                $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view');
                $this->redirect(array('action' => 'view'));
            }else{
                // display message of validation error
                $this->set('imageUploadError', __(array_shift($validated['message'])));
            }
        }else{
			$data = $this->Staff->find('first',array('conditions'=>array('id'=>$this->Session->read('StaffId'))));
		}

        $gender = array(0 => __('--Select--'), 'M' => __('Male'), 'F' => __('Female'));
		$this->set('autoid', $this->getUniqueID());
        $this->set('gender', $gender);
        $this->set('data', $data);
    }

    public function employment() {
        $this->Navigation->addCrumb(ucfirst($this->action));
        $staffId = $this->Session->read('StaffId');
        $data = array();
		
		$list = $this->InstitutionSiteStaff->getPositions($staffId);
        foreach($list as $row) {
            $result = array();
            $dataKey = '';
            foreach($row as $element){ // compact array
                if(array_key_exists('institution', $element)){
                    $dataKey .= $element['institution'];
                    continue;
                }
                if(array_key_exists('institution_site', $element)){
                    $dataKey .= ' - '.$element['institution_site'];
                    continue;
                }
                $result = array_merge($result, $element);
            }
            $data[$dataKey][] = $result;
        }
		if(empty($data)) {
			$this->Utility->alert($this->Utility->getMessage('NO_EMPLOYMENT'), array('type' => 'info', 'dismissOnClick' => false));
		}
        $this->set('data', $data);
    }

    public function fetchImage($id){
        $this->autoRender = false;
		
		$url = Router::url('/Staff/img/default_staff_profile.jpg', true);
        $mime_types = ImageMeta::mimeTypes();

        $imageRawData = $this->Staff->findById($id);
		$imageFilename = $imageRawData['Staff']['photo_name'];
		$fileExt = pathinfo(strtolower($imageFilename), PATHINFO_EXTENSION);
	
		
		if(empty($imageRawData['Staff']['photo_content']) || empty($imageRawData['Staff']['photo_name']) || !in_array($mime_types[$fileExt], $mime_types)){
			if($this->Session->check('Staff.defaultImg'))
    		{
				$imageContent = $this->Session->read('Staff.defaultImg');
			}else{
				$imageContent = file_get_contents($url);
				$this->Session->write('Staff.defaultImg', $imageContent);
			}
			echo $imageContent;
		}else{
			$imageContent = $imageRawData['Staff']['photo_content'];
			header("Content-type: " . $mime_types[$fileExt]);
			echo $imageContent;
		}
    }

    public function delete() {
        $id = $this->Session->read('StaffId');
        $name = $this->Staff->field('first_name', array('Staff.id' => $id));
        $this->Staff->delete($id);
        $this->Utility->alert(sprintf(__("%s have been deleted successfully."), $name));
        $this->redirect(array('action' => 'index'));
    }

    public function add() {
        $this->Navigation->addCrumb('Add new Staff');
        $imgValidate = new ImageValidate();
		$data = $this->data;
        if($this->request->is('post')) {
            $reset_image = $data['Staff']['reset_image'];

            $img = new ImageMeta($data['Staff']['photo_content']);
            unset($data['Staff']['photo_content']);

            if($reset_image == 0 ) {
                $validated = $imgValidate->validateImage($img);
                if($img->getFileUploadError() !== 4 && $validated['error'] < 1){
                    $data['Staff']['photo_content'] = $img->getContent();
                    $img->setContent('');
    //                $data['Staff']['photo_name'] = serialize($img);
                    $data['Staff']['photo_name'] = $img->getFilename();
                }
            }else{
                $data['Staff']['photo_content'] = '';
                $data['Staff']['photo_name'] = '';
            }
           $this->Staff->set($data);
            if($this->Staff->validates() && ($reset_image == 1 || $validated['error'] < 1)) {
                unset($data['Staff']['rest_image']);
                $newStaffRec =  $this->Staff->save($data);
                $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view');
                $this->redirect(array('action' => 'viewStaff', $newStaffRec['Staff']['id']));
            }else{
                                 $this->set('imageUploadError', __(array_shift($validated['message'])));
				$errors = $this->Staff->validationErrors;
				if($this->getUniqueID()!=''){ // If Auto id
					if(isset($errors["identification_no"])){ // If its ID error
						if(sizeof($errors)<2){ // If only 1 faulty
							$this->Staff->set($this->request->data);
							do{
								$this->request->data["Staff"]["identification_no"] = $this->getUniqueID();
								$conditions = array(
									'Staff.identification_no' => $this->request->data["Staff"]["identification_no"]
								);
							}while($this->Staff->hasAny($conditions));
							$this->Staff->set($this->request->data);
							$newStaffRec =  $this->Staff->save($this->request->data);
							// create the session for successfully adding of Staff
							$this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view'); 
							$this->redirect(array('action' => 'viewStaff', $newStaffRec['Staff']['id']));
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

    public function additional() {
        $this->Navigation->addCrumb('Additional Info');

        // get all staff custom field in order
        $datafields = $this->StaffCustomField->find('all', array('conditions' => array('StaffCustomField.visible' => 1), 'order'=>'StaffCustomField.order'));

        $this->StaffCustomValue->unbindModel(
            array('belongsTo' => array('Staff'))
            );
        $datavalues = $this->StaffCustomValue->find('all', array(
            'conditions'=> array('StaffCustomValue.staff_id' => $this->staffId))
        );

        // pr($datafields);
        // pr($datavalues);
        $tmp=array();
        foreach($datavalues as $arrV){
            $tmp[$arrV['StaffCustomField']['id']][] = $arrV['StaffCustomValue'];
            // pr($arrV);
        }
        $datavalues = $tmp;
        // pr($tmp);die;
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('datafields', $datafields);
        $this->set('datavalues', $tmp);
    }

    public function additionalEdit() {
        $this->Navigation->addCrumb('Edit Additional Info');

        if ($this->request->is('post')) {
            //pr($this->data);
            //die();
            $arrFields = array('textbox','dropdown','checkbox','textarea');
            /**
             * Note to Preserve the Primary Key to avoid exhausting the max PK limit
             */
            foreach($arrFields as $fieldVal){
                // pr($fieldVal);
                // pr($this->request->data['StaffCustomValue']);
                if(!isset($this->request->data['StaffCustomValue'][$fieldVal])) continue;
                foreach($this->request->data['StaffCustomValue'][$fieldVal] as $key => $val){

                    if($fieldVal == "checkbox"){

                        $arrCustomValues = $this->StaffCustomValue->find('list',array('fields'=>array('value'),'conditions' => array('StaffCustomValue.staff_id' => $this->staffId,'StaffCustomValue.staff_custom_field_id' => $key)));

                        $tmp = array();
                            if(count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
                            foreach($arrCustomValues as $pk => $intVal){
                                //pr($val['value']); echo "$intVal";
                                if(!in_array($intVal, $val['value'])){
                                    //echo "not in db so remove \n";
                                   $this->StaffCustomValue->delete($pk);
                               }
                           }
                           $ctr = 0;
                            if(count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
                            foreach($val['value'] as $intVal){
                                //pr($val['value']); echo "$intVal";
                                if(!in_array($intVal, $arrCustomValues)){
                                    $this->StaffCustomValue->create();
                                    $arrV['staff_custom_field_id']  = $key;
                                    $arrV['value']  = $val['value'][$ctr];
                                    $arrV['staff_id']  = $this->staffId;
                                    $this->StaffCustomValue->save($arrV);
                                    unset($arrCustomValues[$ctr]);
                                }
                                $ctr++;
                            }
                    }else{ // if editing reuse the Primary KEY; so just update the record
                        $datafields = $this->StaffCustomValue->find('first',array('fields'=>array('id','value'),'conditions' => array('StaffCustomValue.staff_id' => $this->staffId,'StaffCustomValue.staff_custom_field_id' => $key)));
                        $this->StaffCustomValue->create();
                        if($datafields) $this->StaffCustomValue->id = $datafields['StaffCustomValue']['id'];
                        $arrV['staff_custom_field_id'] = $key;
                        $arrV['value'] = $val['value'];
                        $arrV['staff_id'] = $this->staffId;
                        $this->StaffCustomValue->save($arrV);
                    }

                }
            }
            $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'additional');
            $this->redirect(array('action' => 'additional'));
        }
        $this->StaffCustomField->unbindModel(array('hasMany' => array('StaffCustomFieldOption')));

        $this->StaffCustomField->bindModel(array(
            'hasMany' => array(
                'StaffCustomFieldOption' => array(
                    'conditions' => array(
                        'StaffCustomFieldOption.visible' => 1),
                    'order' => array('StaffCustomFieldOption.order' => "ASC")
                )
            )
        ));

        $datafields = $this->StaffCustomField->find('all', array('conditions' => array('StaffCustomField.visible' => 1), 'order'=>'StaffCustomField.order'));
        $this->StaffCustomValue->unbindModel(array('belongsTo' => array('Staff')));
        $datavalues = $this->StaffCustomValue->find('all',array('conditions'=>array('StaffCustomValue.staff_id' => $this->staffId)));
        $tmp=array();
        foreach($datavalues as $arrV){
            $tmp[$arrV['StaffCustomField']['id']][] = $arrV['StaffCustomValue'];
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
        $id = $this->Session->read('StaffId');
        $arrMap = array('model'=>'Staff.StaffAttachment', 'foreignKey' => 'staff_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

        $data = $FileAttachment->getList($id);
        $this->set('data', $data);
        $this->set('_model', 'StaffAttachment');
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/view');
    }
    
    public function attachmentsEdit() {
        $this->Navigation->addCrumb('Edit Attachments');

        $id = $this->Session->read('StaffId');
        
        $arrMap = array('model'=>'Staff.StaffAttachment', 'foreignKey' => 'staff_id');
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
        $this->set('data',$data);
        $this->set('_model', 'StaffAttachment');
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/edit');
    }
    
    public function attachmentsAdd() {
        $this->layout = 'ajax';
        $this->set('params', $this->params->query);
        $this->set('_model', 'StaffAttachment');
        $this->render('/Elements/attachment/add');
    }
       
    public function attachmentsDelete() {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = $this->params->data['id'];

            $arrMap = array('model'=>'Staff.StaffAttachment', 'foreignKey' => 'staff_id');
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
        $arrMap = array('model'=>'Staff.StaffAttachment', 'foreignKey' => 'staff_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

        $FileAttachment->download($id);
    }

    public function history(){
        $this->Navigation->addCrumb('History');

        $arrTables = array('StaffHistory');
        $historyData = $this->StaffHistory->find('all',array(
            'conditions' => array('StaffHistory.staff_id'=>$this->staffId),
            'order' => array('StaffHistory.created' => 'desc')
        ));
        $data = $this->Staff->findById($this->staffId);
        $data2 = array();
        foreach ($historyData as $key => $arrVal) {
            foreach($arrTables as $table){
                foreach($arrVal[$table] as $k => $v){
                    $keyVal = ($k == 'name')?$table.'_name':$k;
                    $data2[$keyVal][$v] = $arrVal['StaffHistory']['created'];
                }
            }
        }
		if(empty($data2)) {
			$this->Utility->alert($this->Utility->getMessage('NO_HISTORY'), array('type' => 'info', 'dismissOnClick' => false));
		}
        
        $this->set('data',$data);
        $this->set('data2',$data2);
    }
	
	private function custFieldYrInits(){
		$this->Navigation->addCrumb('Annual Info');
		$action = $this->action;
		$siteid = @$this->request->params['pass'][2];
		$id = $this->staffId;
		$schoolYear = ClassRegistry::init('SchoolYear');
		$years = $schoolYear->getYearList();
		$selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
		$condParam = array('staff_id'=>$id,'institution_site_id'=>$siteid,'school_year_id'=>$selectedYear);
		
		$arrMap = array('CustomField'=>'StaffDetailsCustomField',
						'CustomFieldOption'=>'StaffDetailsCustomFieldOption',
						'CustomValue'=>'StaffDetailsCustomValue',
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
        $this->Navigation->addCrumb("More", array('controller' => 'Staff', 'action' => 'additional'));
		extract($this->custFieldYrInits());
		$customfield = $this->Components->load('CustomField',$arrMap);
		$data = array();
		if($id && $selectedYear && $siteid) $data = $customfield->getCustomFieldView($condParam);
		$institution_sites = $customfield->getCustomValuebyCond('list',array('fields'=>array('institution_site_id','school_year_id'),'conditions'=>array('school_year_id'=>$selectedYear,'staff_id'=>$id)));
		$institution_sites = $this->custFieldSites(array_keys($institution_sites));
		if(count($institution_sites)<2)  $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
		$displayEdit = false;
		$this->set(compact('arrMap','selectedYear','siteid','years','action','id','institution_sites','displayEdit'));
		$this->set($data);
        $this->set('myview', 'additional');
		$this->render('/Elements/customfields/view');
	}

    // Staff ATTENDANCE PART
    public function attendance(){
        $staffId = $this->staffId;
        $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
        $this->Navigation->addCrumb('Attendance');

        $id = @$this->request->params['pass'][0];
        $yearList = $this->SchoolYear->getYearList();
        $yearId = $this->getAvailableYearId($yearList);
        $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));

        $data = $this->StaffAttendance->getAttendanceData($this->Session->read('InstitutionSiteStaffId'),isset($id)? $id:$yearId);

        $this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
        $this->set('data', $data);
        $this->set('schoolDays', $schoolDays);
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
     	$str = $this->Staff->find('first', array('order' => array('Staff.id DESC'), 'limit' => 1, 'fields'=>'Staff.id'));
		$prefix = $this->ConfigItem->find('first', array('limit' => 1, 
													  'fields'=>'ConfigItem.value',
													  'conditions'=>array(
																			'ConfigItem.name' => 'staff_prefix'
																		 )
									   ));
		$prefix = explode(",",$prefix['ConfigItem']['value']);
    	
		if($prefix[1]>0){
			$id = $str['Staff']['id']+1; 
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
		$staffId = $this->Session->read('StaffId');
		$data = $this->StaffLeave->find('all', array(
			'recursive' => 0, 
			'conditions' => array('StaffLeave.staff_id' => $staffId),
			'order' => array('StaffLeave.date_from')
		));
		$this->set('data', $data);
	}
	
	public function leavesAdd() {
		$this->Navigation->addCrumb('Leaves');
		$typeOptions = $this->StaffLeaveType->findList(true);
        $statusOptions = $this->LeaveStatus->findList(true);
		
		if($this->request->is('post')) {
			$data = $this->request->data;
			$data['StaffLeave']['staff_id'] = $this->Session->read('StaffId');
			$this->StaffLeave->set($data);
			if($this->StaffLeave->validates()) {
				$this->StaffLeave->create();
				$obj = $this->StaffLeave->save($data);
                $id = $this->StaffLeave->getInsertID();

                $arrMap = array('model'=>'Staff.StaffLeaveAttachment', 'foreignKey' => 'staff_leave_id');
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
        $this->set('_model', 'StaffLeaveAttachment');
	}
	
	public function leavesView($id=null) {
		$this->Navigation->addCrumb('Leaves');
		if(!is_null($id) && $this->StaffLeave->exists($id)) { 
			$typeOptions = $this->StaffLeaveType->findList(true);
            $statusOptions = $this->LeaveStatus->findList(true);
			$data = $this->StaffLeave->find('first', array('recursive' => 0, 'conditions' => array('StaffLeave.id' => $id)));
            $arrMap = array('model'=>'Staff.StaffLeaveAttachment', 'foreignKey' => 'staff_leave_id');
            $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

            $attachments = $FileAttachment->getList($id);
            $this->set('typeOptions', $typeOptions);
            $this->set('statusOptions', $statusOptions);
			$this->set('data', $data);
            $this->set('attachments', $attachments);
            $this->set('_model', 'StaffLeaveAttachment');
		} else {
			return $this->redirect(array('action' => 'leaves'));
		}
	}
	
	public function leavesEdit($id=null) {
		$this->Navigation->addCrumb('Leaves');
		if(!is_null($id) && $this->StaffLeave->exists($id)) {
            $arrMap = array('model'=>'Staff.StaffLeaveAttachment', 'foreignKey' => 'staff_leave_id');
            $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

			if($this->request->is('post') || $this->request->is('put')) {
				$data = $this->request->data;
				$data['StaffLeave']['id'] = $id;
				$data['StaffLeave']['staff_id'] = $this->Session->read('StaffId');
				$this->StaffLeave->set($data);
				if($this->StaffLeave->validates()) {
					$obj = $this->StaffLeave->save($data);
                    if(!empty($_FILES)){
                        $errors = $FileAttachment->saveAll($this->data, $_FILES, $id);
                    }
					if($obj) {
						$this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
						return $this->redirect(array('action' => 'leavesView', $obj['StaffLeave']['id']));
					}
				}
			}
            
            $attachments = $FileAttachment->getList($id);
            $this->set('attachments',$attachments);
            $this->set('_model','StaffLeaveAttachment');

			$typeOptions = $this->StaffLeaveType->findList(true);
            $statusOptions = $this->LeaveStatus->findList(true);
			$this->request->data = $this->StaffLeave->find('first', array('recursive' => 0, 'conditions' => array('StaffLeave.id' => $id)));
            $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
			$this->set('typeOptions', $typeOptions);
            $this->set('statusOptions', $statusOptions);
		} else {
			return $this->redirect(array('action' => 'leaves'));
		}
	}
	
	public function leavesDelete($id=null) {
		if(!is_null($id) && $this->StaffLeave->exists($id) && $this->Session->check('StaffId')) {
			$this->StaffLeave->delete($id);
			$this->Utility->alert($this->Utility->getMessage('DELETE_SUCCESS'));
		}
		return $this->redirect(array('action' => 'leaves'));
	}

    public function attachmentsLeaveAdd() {
        $this->layout = 'ajax';
        $this->set('params', $this->params->query);
        $this->set('_model', 'StaffLeaveAttachment');
        $this->render('/Elements/attachment/compact_add');
    }

    public function attachmentsLeaveDelete() {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = $this->params->data['id'];

            $arrMap = array('model'=>'Staff.StaffLeaveAttachment', 'foreignKey' => 'staff_leave_id');
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
        $arrMap = array('model'=>'Staff.StaffLeaveAttachment', 'foreignKey' => 'staff_leave_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

        $FileAttachment->download($id);
    }
       

    public function qualifications() {
        $this->Navigation->addCrumb('Qualifications');
        $list = $this->StaffQualification->getData($this->staffId);

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('list', $list);
    }

	public function qualificationsAdd() {
        if ($this->request->is('post')) {
            $this->StaffQualification->create();
            $this->request->data['StaffQualification']['staff_id'] = $this->staffId;
            
            $staffQualificationData = $this->data['StaffQualification'];

            $this->StaffQualification->set($staffQualificationData);

            if ($this->StaffQualification->validates()) {
                if(empty($staffQualificationData['qualification_institution_id'])){
                    $data = array(
                        'QualificationInstitution'=>
                            array(
                                'name' => $staffQualificationData['qualification_institution'],
                                'order' => 0,
                                'visible' => 1,
                                'created_user_id' => $this->Auth->user('id'),
                                'created' => date('Y-m-d h:i:s')
                            )
                    );
                    $this->QualificationInstitution->save($data);
                    $qualificationInstitutionId = $this->QualificationInstitution->getInsertID();
                    $staffQualificationData['qualification_institution_id'] = $qualificationInstitutionId;
                }

                $this->StaffQualification->save($staffQualificationData);
    
                $staffQualificationId = $this->StaffQualification->getInsertID();

                $arrMap = array('model'=>'Staff.StaffQualification');
                $Q = $this->Components->load('FileAttachment', $arrMap);
                $staffQualificationData['id'] = $staffQualificationId;
                $errors = $Q->save($staffQualificationData, $_FILES);

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
        $staffQualificationId = $this->params['pass'][0];
        $staffQualificationObj = $this->StaffQualification->find('all',array('conditions'=>array('StaffQualification.id' => $staffQualificationId)));
        
        if(!empty($staffQualificationObj)) {
            $this->Navigation->addCrumb('Qualification Details');
            
            $levels = $this->QualificationLevel->getOptions();
            $specializations = $this->QualificationSpecialisation->getOptions();
            $institutes = $this->QualificationInstitution->getOptions();

            $this->Session->write('StaffQualificationId', $staffQualificationId);
            $this->set('levels', $levels);
            $this->set('specializations', $specializations);
            $this->set('institutes', $institutes);
            $this->set('staffQualificationObj', $staffQualificationObj);

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
            $staffQualificationId = $this->params['pass'][0];
            $staffQualificationObj = $this->StaffQualification->find('first',array('conditions'=>array('StaffQualification.id' => $staffQualificationId)));
  
            if(!empty($staffQualificationObj)) {
                $this->Navigation->addCrumb('Edit Qualification Details');
                $staffQualificationObj['StaffQualification']['qualification_institution'] = $institutes[$staffQualificationObj['StaffQualification']['qualification_institution_id']];
                $this->request->data = $staffQualificationObj;
                $this->set('id', $staffQualificationId);
            } else {
                //$this->redirect(array('action' => 'studentsBehaviour'));
            }

         } else {
            $staffQualificationData = $this->data['StaffQualification'];
            $staffQualificationData['staff_id'] = $this->staffId;
            
            $this->StaffQualification->set($staffQualificationData);

            if ($this->StaffQualification->validates()) {
                if(empty($staffQualificationData['qualification_institution_id'])){
                    $data = array(
                        'QualificationInstitution'=>
                            array(
                                'name' => $staffQualificationData['qualification_institution'],
                                'order' => 0,
                                'visible' => 1,
                                'created_user_id' => $this->Auth->user('id'),
                                'created' => date('Y-m-d h:i:s')
                            )
                    );
                    $this->QualificationInstitution->save($data);
                    $qualificationInstitutionId = $this->QualificationInstitution->getInsertID();
                    $staffQualificationData['qualification_institution_id'] = $qualificationInstitutionId;
                }
                $this->StaffQualification->save($staffQualificationData);
                $arrMap = array('model'=>'Staff.StaffQualification');
                $Q = $this->Components->load('FileAttachment', $arrMap);

                $errors = $Q->save($staffQualificationData, $_FILES);

                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'qualificationsView', $staffQualificationData['id']));
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

        $staffQualification = $this->StaffQualification->findById($id);
        $name = $staffQualification['StaffQualification']['qualification_title'];
        $staffQualification['StaffQualification']['file_name'] = null;
        $staffQualification['StaffQualification']['file_content'] = null;

        if($this->StaffQualification->save($staffQualification)) {
            //$this->Utility->alert($name . ' have been deleted successfully.');
        } else {
           //$this->Utility->alert('Error occurred while deleting file.');
        }
        
        $this->redirect(array('action' => 'qualificationsEdit', $id));
        
    }
        
    public function qualificationAttachmentsDownload($id) {
        $arrMap = array('model'=>'Staff.StaffQualification');
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
        
         /***BANK ACCOUNTS - sorry have to copy paste to othe modules too lazy already**/
        public function bankAccounts() {
                $this->Navigation->addCrumb('Bank Accounts');

                if($this->request->is('post')) {
                        $this->StaffBankAccount->create();
                        $this->request->data['StaffBankAccount']['staff_id'] = $this->staffId;
                        $this->StaffBankAccount->save($this->request->data['StaffBankAccount']);
                }

                $data = $this->StaffBankAccount->find('all',array('conditions'=>array('StaffBankAccount.staff_id'=>$this->staffId)));
                $bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));
                $banklist = $this->Bank->find('list',array('conditions'=>Array('Bank.visible'=>1)));
                $this->set('data',$data);
                $this->set('bank',$bank);
                $this->set('banklist',$banklist);
        }

        public function bankAccountsEdit() {
                $this->Navigation->addCrumb('Edit Bank Accounts');
                if($this->request->is('post')) { // save

                        foreach($this->request->data['StaffBankAccount'] as &$arrVal){
                                if($arrVal['id'] == $this->data['StaffBankAccount']['active']){
                                        $arrVal['active'] = 1;
                                        unset($this->request->data['StaffBankAccount']['active']);
                                        break;
                                }
                        }
                        $this->StaffBankAccount->saveAll($this->request->data['StaffBankAccount']);
                        $this->redirect(array('controller' => 'Staff', 'action' => 'bankAccounts'));
                }
                $data = $this->StaffBankAccount->find('all',array('conditions'=>array('StaffBankAccount.staff_id'=>$this->staffId)));
                $bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));

                $this->set('data',$data);
                $this->set('bank',$bank);
        }

        public function bankAccountsDelete($id) {
                if ($this->request->is('get')) {
                        throw new MethodNotAllowedException();
                }
                $this->StaffBankAccount->id = $id;
                $info = $this->StaffBankAccount->read();
                if ($this->StaffBankAccount->delete($id)) {
                         $message = __('Record Deleted!', true);

                }else{
                         $message = __('Error Occured. Please, try again.', true);
                }
                if($this->RequestHandler->isAjax()){
                        $this->autoRender = false;
                        $this->layout = 'ajax';
                        echo json_encode(compact('message'));
                }
        }

        public function bankAccountsBankBranches() {
                $this->autoRender = false;
                $bank = $this->Bank->find('all',array('conditions'=>Array('Bank.visible'=>1)));
                echo json_encode($bank);
        }

    // Staff behaviour part
    public function behaviour(){
        $this->Navigation->addCrumb('List of Behaviour');

        $data = $this->StaffBehaviour->getBehaviourData($this->staffId);
        if(empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
        }

        $this->set('data', $data);
    }

    public function behaviourView() {
        $staffBehaviourId = $this->params['pass'][0];
        $staffBehaviourObj = $this->StaffBehaviour->find('all',array('conditions'=>array('StaffBehaviour.id' => $staffBehaviourId)));

        if(!empty($staffBehaviourObj)) {
            $staffId = $staffBehaviourObj[0]['StaffBehaviour']['staff_id'];
            $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
            $this->Navigation->addCrumb('Behaviour Details');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();
            $categoryOptions = array();
            $categoryOptions = $this->StaffBehaviourCategory->getCategory();

            $this->Session->write('StaffBehaviourId', $staffBehaviourId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('yearOptions', $yearOptions);
            $this->set('staffBehaviourObj', $staffBehaviourObj);
        } else {
            $this->redirect(array('action' => 'behaviour'));
        }
    }
}