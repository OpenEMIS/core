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
        'Staff.InstitutionSiteStaff',
        'Staff.Staff',
        'Staff.StaffHistory',
        'Staff.StaffCustomField',
        'Staff.StaffCustomFieldOption',
        'Staff.StaffCustomValue',
        'Staff.StaffAttendance',
        
       // 'Staff.StaffLeaveType',
        'Staff.StaffBehaviour',
        'Staff.StaffBehaviourCategory',
        
        'Staff.StaffExtracurricular',
        'Staff.StaffEmployment',
        'Staff.StaffSalary',
        'Staff.StaffSalaryAddition',
        'Staff.StaffSalaryDeduction',
        /**/
        'SchoolYear',
        'ConfigItem',
        'LeaveStatus',
        'Country',
        'IdentityType',
        //'StaffLeaveAttachment',
        'Language',
        'ContactOption',
        'ContactType',
        'ExtracurricularType',
        'EmploymentType',
        'SalaryAdditionType',
        'SalaryDeductionType',
        'TrainingCourse'
    );
    public $helpers = array('Js' => array('Jquery'), 'Paginator');
    public $components = array(
        'UserSession',
        'Paginator',
		'FileUploader',
    );
    public $modules = array(
        'healthHistory' => 'Staff.StaffHealthHistory',
        'healthFamily' => 'Staff.StaffHealthFamily',
        'healthImmunization' => 'Staff.StaffHealthImmunization',
        'healthMedication' => 'Staff.StaffHealthMedication',
        'healthAllergy' => 'Staff.StaffHealthAllergy',
        'healthTest' => 'Staff.StaffHealthTest',
        'healthConsultation' => 'Staff.StaffHealthConsultation',
        'health' => 'Staff.StaffHealth',
        'specialNeed' => 'Staff.StaffSpecialNeed',
        'award' => 'Staff.StaffAward',
        'membership' => 'Staff.StaffMembership',
        'license' => 'Staff.StaffLicense',
        'training_need' => 'Staff.StaffTrainingNeed',
        'training_result' => 'Staff.StaffTrainingResult',
        'training_self_study' => 'Staff.StaffTrainingSelfStudy',
		'contacts' => 'Staff.StaffContact',
		'identities' => 'Staff.StaffIdentity',
		'nationalities' => 'Staff.StaffNationality',
		'languages' => 'Staff.StaffLanguage',
		'bankAccounts' => 'Staff.StaffBankAccount',
		'comments' => 'Staff.StaffComment',
		'attachments' => 'Staff.StaffAttachment',
		'qualifications' => 'Staff.StaffQualification',
		'leaves' => 'Staff.StaffLeave',
    );

    public $className = 'Staff';

    public function beforeFilter() {
        parent::beforeFilter();

        $this->Navigation->addCrumb('Staff', array('controller' => 'Staff', 'action' => 'index'));
        $actions = array('index', 'advanced', 'add', 'viewStaff');
        $this->set('WizardMode', false);
        if (in_array($this->action, $actions)) {
            $this->bodyTitle = 'Staff';
            if($this->action=='add'){
                $this->Session->delete('StaffId');
                $this->Session->write('WizardMode', true);
                $wizardLink = $this->Navigation->getWizardLinks('Staff');
                $this->Session->write('WizardLink', $wizardLink);
                $this->redirect(array('action'=>'edit'));
            }
        } else {
            if($this->Session->check('WizardMode') && $this->Session->read('WizardMode')==true){
                $this->set('WizardMode', true);
                $this->Navigation->getWizard($this->action);
            }
            if ($this->Session->check('StaffId') && $this->action !== 'Home') {
                $this->staffId = $this->Session->read('StaffId');
                $this->staffObj = $this->Session->read('StaffObj');
                $staffFirstName = $this->Staff->field('first_name', array('Staff.id' => $this->staffId));
                $staffMiddleName = $this->Staff->field('middle_name', array('Staff.id' => $this->staffId));
                $staffLastName = $this->Staff->field('last_name', array('Staff.id' => $this->staffId));
                $name = $staffFirstName . " " . $staffMiddleName . " " . $staffLastName;
                $this->bodyTitle = $name;
                $this->Navigation->addCrumb($name, array('action' => 'view'));
            }else if (!$this->Session->check('StaffId') && $this->action !== 'Home') {
                $name = __('New Staff');
                $this->bodyTitle = $name;
            }
        }
    }

    public function index() {
        $this->Navigation->addCrumb('List of Staff');
        if ($this->request->is('post')) {
            if (isset($this->request->data['Staff']['SearchField'])) {
                $this->request->data['Staff']['SearchField'] = Sanitize::escape($this->request->data['Staff']['SearchField']);
                if ($this->request->data['Staff']['SearchField'] != $this->Session->read('Search.SearchFieldStaff')) {
                    $this->Session->delete('Search.SearchFieldStaff');
                    $this->Session->write('Search.SearchFieldStaff', $this->request->data['Staff']['SearchField']);
                }
            }

            if (isset($this->request->data['sortdir']) && isset($this->request->data['order'])) {
                if ($this->request->data['sortdir'] != $this->Session->read('Search.sortdirStaff')) {
                    $this->Session->delete('Search.sortdirStaff');
                    $this->Session->write('Search.sortdirStaff', $this->request->data['sortdir']);
                }
                if ($this->request->data['order'] != $this->Session->read('Search.orderStaff')) {
                    $this->Session->delete('Search.orderStaff');
                    $this->Session->write('Search.orderStaff', $this->request->data['order']);
                }
            }
        }

        $fieldordername = ($this->Session->read('Search.orderStaff')) ? $this->Session->read('Search.orderStaff') : 'Staff.first_name';
        $fieldorderdir = ($this->Session->read('Search.sortdirStaff')) ? $this->Session->read('Search.sortdirStaff') : 'asc';

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
        if (empty($searchKey) && !$this->Session->check('Staff.AdvancedSearch')) {
            if (count($data) == 1 && !$this->AccessControl->newCheck($this->params['controller'], 'add')) {
                $this->redirect(array('action' => 'viewStaff', $data[0]['Staff']['id']));
            }
        }
        if (empty($data) && !$this->request->is('ajax')) {
            $this->Utility->alert($this->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
        }
        $this->set('staff', $data);
        $this->set('sortedcol', $fieldordername);
        $this->set('sorteddir', ($fieldorderdir == 'asc') ? 'up' : 'down');
        $this->set('searchField', $searchKey);
        if ($this->request->is('post')) {
            $this->render('index_records', 'ajax');
        }
    }

    public function advanced() {
        $key = 'Staff.AdvancedSearch';
        if ($this->request->is('get')) {
            if ($this->request->is('ajax')) {
                $this->autoRender = false;
                $search = $this->params->query['term'];
                $result = $this->Area->autocomplete($search);
                return json_encode($result);
            } else {
                $this->Navigation->addCrumb('List of Staff', array('controller' => 'Staff', 'action' => 'index'));
                $this->Navigation->addCrumb('Advanced Search');

                if (isset($this->params->pass[0])) {
                    if (intval($this->params->pass[0]) === 0) {
                        $this->Session->delete($key);
                        $this->redirect(array('action' => 'index'));
                    }
                }
            }
        } else {
            //$search = $this->data['Search'];
            $search = $this->data;
            if (!empty($search)) {
                $this->Session->write($key, $search);
            }
            $this->redirect(array('action' => 'index'));
        }
    }

    public function getCustomFieldsSearch($sitetype = 0, $customfields = 'Staff') {
        $this->layout = false;
        $arrSettings = array(
            'CustomField' => $customfields . 'CustomField',
            'CustomFieldOption' => $customfields . 'CustomFieldOption',
            'CustomValue' => $customfields . 'CustomValue',
            'Year' => ''
        );
        if ($this->{$customfields}->hasField('institution_site_type_id')) {
            $arrSettings = array_merge(array('institutionSiteTypeId' => $sitetype), $arrSettings);
        }
        $arrCustFields = array($customfields => $arrSettings);

        $instituionSiteCustField = $this->Components->load('CustomField', $arrCustFields[$customfields]);
        $dataFields[$customfields] = $instituionSiteCustField->getCustomFields();
        $types = $this->InstitutionSiteType->findList(1);
        $this->set("customfields", array($customfields));
        $this->set('types', $types);
        $this->set('typeSelected', $sitetype);
        $this->set('dataFields', $dataFields);
        $this->render('/Elements/customfields/search');
    }

    public function viewStaff($id) {
        $this->Session->write('StaffId', $id);
        $obj = $this->Staff->find('first', array('conditions' => array('Staff.id' => $id)));
        $this->Session->write('StaffObj', $obj);
        $this->redirect(array('action' => 'view'));
    }

    public function view() {
        $this->Navigation->addCrumb('Overview');
        $this->Staff->id = $this->Session->read('StaffId');
        $data = $this->Staff->read();

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('data', $data);
    }

    public function edit() {
        $this->Staff->id = null;
        if($this->Session->check('StaffId')){
            $this->Staff->id = $this->Session->read('StaffId');
            $this->Navigation->addCrumb('Edit');
        }else{
             $this->Navigation->addCrumb('Add');
        }
        $imgValidate = new ImageValidate();
        $data = $this->data;
        if ($this->request->is('post')) {
            if(isset($this->data['submit']) && $this->data['submit']==__('Cancel')){
                $this->Navigation->exitWizard();
            }

            $reset_image = $data['Staff']['reset_image'];

            $img = new ImageMeta($data['Staff']['photo_content']);
            unset($data['Staff']['photo_content']);

            if ($reset_image == 0) {
                $validated = $imgValidate->validateImage($img);
                if ($img->getFileUploadError() !== 4 && $validated['error'] < 1) {
                    $data['Staff']['photo_content'] = $img->getContent();
                    $img->setContent('');
                    //                $data['Staff']['photo_name'] = serialize($img);
                    $data['Staff']['photo_name'] = $img->getFilename();
                }
            } else {
                $data['Staff']['photo_content'] = '';
                $data['Staff']['photo_name'] = '';
            }
            $this->Staff->set($data);
            if ($this->Staff->validates() && ($reset_image == 1 || $validated['error'] < 1)) {
                unset($data['Staff']['rest_image']);
                $rec = $this->Staff->save();
                if(!$this->Session->check('StaffId')){
                    $id = $this->Staff->getLastInsertId();
                }else{
                    $id = $this->Session->read('StaffId');
                }
                $this->Navigation->updateWizard('view', $id);
                $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view');
                $this->redirect(array('action' => 'view'));
            } else {
                // display message of validation error
                $this->set('imageUploadError', __(array_shift($validated['message'])));
            }
        } else {
            $data = $this->Staff->find('first', array('conditions' => array('id' => $this->Session->read('StaffId'))));
        }

        $gender = array(0 => __('--Select--'), 'M' => __('Male'), 'F' => __('Female'));
        $this->set('autoid', $this->getUniqueID());
        $this->set('gender', $gender);
        $this->set('data', $data);
    }

    public function positions() {
        $this->Navigation->addCrumb(ucfirst($this->action));
		$this->set('header', 'Positions');
        $staffId = $this->Session->read('StaffId');
        $data = array();

        $list = $this->InstitutionSiteStaff->getPositions($staffId);
        foreach ($list as $row) {
            $result = array();
            $dataKey = '';
            foreach ($row as $key => $element) { // compact array
                if (array_key_exists('institution', $element)) {
                    $dataKey .= $element['institution'];
                    continue;
                }
                if (array_key_exists('institution_site', $element)) {
                    $dataKey .= ' - ' . $element['institution_site'];
                    continue;
                }

                $result = array_merge($result, array($key => $element));
            }
            $data[$dataKey][] = $result;
        }
        if (empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('NO_POSITION'), array('type' => 'info', 'dismissOnClick' => false));
        }
        $this->set('data', $data);
    }

    public function fetchImage($id) {
        $this->autoRender = false;

        $url = Router::url('/Staff/img/default_staff_profile.jpg', true);
        $mime_types = ImageMeta::mimeTypes();

        $imageRawData = $this->Staff->findById($id);
        $imageFilename = $imageRawData['Staff']['photo_name'];
        $fileExt = pathinfo(strtolower($imageFilename), PATHINFO_EXTENSION);


        if (empty($imageRawData['Staff']['photo_content']) || empty($imageRawData['Staff']['photo_name']) || !in_array($mime_types[$fileExt], $mime_types)) {
            if ($this->Session->check('Staff.defaultImg')) {
                $imageContent = $this->Session->read('Staff.defaultImg');
            } else {
                $imageContent = file_get_contents($url);
                $this->Session->write('Staff.defaultImg', $imageContent);
            }
            echo $imageContent;
        } else {
            $imageContent = $imageRawData['Staff']['photo_content'];
            header("Content-type: " . $mime_types[$fileExt]);
            echo $imageContent;
        }
    }

    public function delete() {
        $id = $this->Session->read('StaffId');
        $name = $this->Staff->field('first_name', array('Staff.id' => $id));
        if ($name !== false) {
            $this->Staff->delete($id);
            $this->Utility->alert(sprintf(__("%s have been deleted successfully."), $name));
        } else {
            $this->Utility->alert(__($this->Utility->getMessage('DELETED_ALREADY')));
        }

        $this->redirect(array('action' => 'index'));
    }

    public function add() {
        $this->Navigation->addCrumb('Add new Staff');
        $imgValidate = new ImageValidate();
        $data = $this->data;
        if ($this->request->is('post')) {
            $reset_image = $data['Staff']['reset_image'];

            $img = new ImageMeta($data['Staff']['photo_content']);
            unset($data['Staff']['photo_content']);

            if ($reset_image == 0) {
                $validated = $imgValidate->validateImage($img);
                if ($img->getFileUploadError() !== 4 && $validated['error'] < 1) {
                    $data['Staff']['photo_content'] = $img->getContent();
                    $img->setContent('');
                    //                $data['Staff']['photo_name'] = serialize($img);
                    $data['Staff']['photo_name'] = $img->getFilename();
                }
            } else {
                $data['Staff']['photo_content'] = '';
                $data['Staff']['photo_name'] = '';
            }
            $this->Staff->set($data);
            if ($this->Staff->validates() && ($reset_image == 1 || $validated['error'] < 1)) {
                unset($data['Staff']['rest_image']);
                $newStaffRec = $this->Staff->save($data);
                $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view');
                $this->redirect(array('action' => 'viewStaff', $newStaffRec['Staff']['id']));
            } else {
                $this->set('imageUploadError', __(array_shift($validated['message'])));
                $errors = $this->Staff->validationErrors;
                if ($this->getUniqueID() != '') { // If Auto id
                    if (isset($errors["identification_no"])) { // If its ID error
                        if (sizeof($errors) < 2) { // If only 1 faulty
                            $this->Staff->set($this->request->data);
                            do {
                                $this->request->data["Staff"]["identification_no"] = $this->getUniqueID();
                                $conditions = array(
                                    'Staff.identification_no' => $this->request->data["Staff"]["identification_no"]
                                );
                            } while ($this->Staff->hasAny($conditions));
                            $this->Staff->set($this->request->data);
                            $newStaffRec = $this->Staff->save($this->request->data);
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
        $datafields = $this->StaffCustomField->find('all', array('conditions' => array('StaffCustomField.visible' => 1), 'order' => 'StaffCustomField.order'));

        $this->StaffCustomValue->unbindModel(
                array('belongsTo' => array('Staff'))
        );
        $datavalues = $this->StaffCustomValue->find('all', array(
            'conditions' => array('StaffCustomValue.staff_id' => $this->staffId))
        );

        // pr($datafields);
        // pr($datavalues);
        $tmp = array();
        foreach ($datavalues as $arrV) {
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
            if(isset($this->data['submit']) && $this->data['submit']==__('Previous')){
                $this->Navigation->previousWizardLink($this->action);
            }
            $mandatory = $this->Navigation->getMandatoryWizard($this->action);
            $error = false;
            //pr($this->data);
            //die();
            $arrFields = array('textbox', 'dropdown', 'checkbox', 'textarea');
            /**
             * Note to Preserve the Primary Key to avoid exhausting the max PK limit
             */
            foreach ($arrFields as $fieldVal) {
                // pr($fieldVal);
                // pr($this->request->data['StaffCustomValue']);
                if (!isset($this->request->data['StaffCustomValue'][$fieldVal]))
                    continue;
                foreach ($this->request->data['StaffCustomValue'][$fieldVal] as $key => $val) {

                    if ($fieldVal == "checkbox") {
                        if($mandatory && count($val['value'])==0){
                            $this->Utility->alert(__('Record is not added due to errors encountered.'), array('type' => 'error'));
                            $error = true;
                            break;
                        }
                        $arrCustomValues = $this->StaffCustomValue->find('list', array('fields' => array('value'), 'conditions' => array('StaffCustomValue.staff_id' => $this->staffId, 'StaffCustomValue.staff_custom_field_id' => $key)));

                        $tmp = array();
                        if (count($arrCustomValues) > count($val['value'])) //if db has greater value than answer, remove
                            foreach ($arrCustomValues as $pk => $intVal) {
                                //pr($val['value']); echo "$intVal";
                                if (!in_array($intVal, $val['value'])) {
                                    //echo "not in db so remove \n";
                                    $this->StaffCustomValue->delete($pk);
                                }
                            }
                        $ctr = 0;
                        if (count($arrCustomValues) < count($val['value'])) //if answer has greater value than db, insert
                            foreach ($val['value'] as $intVal) {
                                //pr($val['value']); echo "$intVal";
                                if (!in_array($intVal, $arrCustomValues)) {
                                    $this->StaffCustomValue->create();
                                    $arrV['staff_custom_field_id'] = $key;
                                    $arrV['value'] = $val['value'][$ctr];
                                    $arrV['staff_id'] = $this->staffId;
                                    $this->StaffCustomValue->save($arrV);
                                    unset($arrCustomValues[$ctr]);
                                }
                                $ctr++;
                            }
                    } else { // if editing reuse the Primary KEY; so just update the record
                        if($mandatory && empty($val['value'])){
                            $this->Utility->alert(__('Record is not added due to errors encountered.'), array('type' => 'error'));
                            $error = true;
                            break;
                        }
                        $datafields = $this->StaffCustomValue->find('first', array('fields' => array('id', 'value'), 'conditions' => array('StaffCustomValue.staff_id' => $this->staffId, 'StaffCustomValue.staff_custom_field_id' => $key)));
                        $this->StaffCustomValue->create();
                        if ($datafields)
                            $this->StaffCustomValue->id = $datafields['StaffCustomValue']['id'];
                        $arrV['staff_custom_field_id'] = $key;
                        $arrV['value'] = $val['value'];
                        $arrV['staff_id'] = $this->staffId;
                        $this->StaffCustomValue->save($arrV);
                    }
                }
            }
            if(!$error){
                $this->Navigation->updateWizard($this->action, null);    
                $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'additional');
                $this->redirect(array('action' => 'additional'));
            }
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

        $datafields = $this->StaffCustomField->find('all', array('conditions' => array('StaffCustomField.visible' => 1), 'order' => 'StaffCustomField.order'));
        $this->StaffCustomValue->unbindModel(array('belongsTo' => array('Staff')));
        $datavalues = $this->StaffCustomValue->find('all', array('conditions' => array('StaffCustomValue.staff_id' => $this->staffId)));
        $tmp = array();
        foreach ($datavalues as $arrV) {
            $tmp[$arrV['StaffCustomField']['id']][] = $arrV['StaffCustomValue'];
        }
        $datavalues = $tmp;

        // pr($datafields);
        // pr($datavalues);
        //pr($tmp);die;
        $this->set('datafields', $datafields);
        $this->set('datavalues', $tmp);
    }

    public function history() {
        $this->Navigation->addCrumb('History');

        $arrTables = array('StaffHistory');
        $historyData = $this->StaffHistory->find('all', array(
            'conditions' => array('StaffHistory.staff_id' => $this->staffId),
            'order' => array('StaffHistory.created' => 'desc')
        ));
        $data = $this->Staff->findById($this->staffId);
        $data2 = array();
        foreach ($historyData as $key => $arrVal) {
            foreach ($arrTables as $table) {
                foreach ($arrVal[$table] as $k => $v) {
                    $keyVal = ($k == 'name') ? $table . '_name' : $k;
                    $data2[$keyVal][$v] = $arrVal['StaffHistory']['created'];
                }
            }
        }
        if (empty($data2)) {
            $this->Utility->alert($this->Utility->getMessage('NO_HISTORY'), array('type' => 'info', 'dismissOnClick' => false));
        }

        $this->set('data', $data);
        $this->set('data2', $data2);
    }

    private function custFieldYrInits() {
        $this->Navigation->addCrumb('Annual Info');
        $action = $this->action;
        $siteid = @$this->request->params['pass'][2];
        $id = $this->staffId;
        $schoolYear = ClassRegistry::init('SchoolYear');
        $years = $schoolYear->getYearList();
        $selectedYear = isset($this->params['pass'][1]) ? $this->params['pass'][1] : key($years);
        $condParam = array('staff_id' => $id, 'institution_site_id' => $siteid, 'school_year_id' => $selectedYear);

        $arrMap = array('CustomField' => 'StaffDetailsCustomField',
            'CustomFieldOption' => 'StaffDetailsCustomFieldOption',
            'CustomValue' => 'StaffDetailsCustomValue',
            'Year' => 'SchoolYear');
        return compact('action', 'siteid', 'id', 'years', 'selectedYear', 'condParam', 'arrMap');
    }

    private function custFieldSY($school_yr_ids) {
        return $this->InstitutionSite->find('list', array('conditions' => array('InstitutionSite.id' => $school_yr_ids)));
    }

    private function custFieldSites($institution_sites) {
        $institution_sites = $this->InstitutionSite->find('all', array('fields' => array('InstitutionSite.id', 'InstitutionSite.name', 'Institution.name'), 'conditions' => array('InstitutionSite.id' => $institution_sites)));
        $tmp = array('0' => '--');
        foreach ($institution_sites as $arrVal) {
            $tmp[$arrVal['InstitutionSite']['id']] = $arrVal['Institution']['name'] . ' - ' . $arrVal['InstitutionSite']['name'];
        }
        return $tmp;
    }

    public function custFieldYrView() {
        $this->Navigation->addCrumb("More", array('controller' => 'Staff', 'action' => 'additional'));
        extract($this->custFieldYrInits());
        $customfield = $this->Components->load('CustomField', $arrMap);
        $data = array();
        if ($id && $selectedYear && $siteid)
            $data = $customfield->getCustomFieldView($condParam);
        $institution_sites = $customfield->getCustomValuebyCond('list', array('fields' => array('institution_site_id', 'school_year_id'), 'conditions' => array('school_year_id' => $selectedYear, 'staff_id' => $id)));
        $institution_sites = $this->custFieldSites(array_keys($institution_sites));
        if (count($institution_sites) < 2)
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
        $displayEdit = false;
        $this->set(compact('arrMap', 'selectedYear', 'siteid', 'years', 'action', 'id', 'institution_sites', 'displayEdit'));
        $this->set($data);
        $this->set('myview', 'additional');
        $this->render('/Elements/customfields/view');
    }

    // Staff ATTENDANCE PART
    public function attendance() {
        $staffId = $this->staffId;
        $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
        $this->Navigation->addCrumb('Attendance');

        $id = @$this->request->params['pass'][0];
        $yearList = $this->SchoolYear->getYearList();
        $yearId = $this->getAvailableYearId($yearList);
        $schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $yearId));

        $data = $this->StaffAttendance->getAttendanceData($this->Session->read('InstitutionSiteStaffId'), isset($id) ? $id : $yearId);

        $this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
        $this->set('data', $data);
        $this->set('schoolDays', $schoolDays);
    }

    private function getAvailableYearId($yearList) {
        $yearId = 0;
        if (isset($this->params['pass'][0])) {
            $yearId = $this->params['pass'][0];
            if (!array_key_exists($yearId, $yearList)) {
                $yearId = key($yearList);
            }
        } else {
            $yearId = key($yearList);
        }
        return $yearId;
    }

    public function getUniqueID() {
        $generate_no = '';
        $str = $this->Staff->find('first', array('order' => array('Staff.id DESC'), 'limit' => 1, 'fields' => 'Staff.id'));
        $prefix = $this->ConfigItem->find('first', array('limit' => 1,
            'fields' => 'ConfigItem.value',
            'conditions' => array(
                'ConfigItem.name' => 'staff_prefix'
            )
        ));
        $prefix = explode(",", $prefix['ConfigItem']['value']);

        if ($prefix[1] > 0) {
            $id = $str['Staff']['id'] + 1;
            if (strlen($id) < 6) {
                $str = str_pad($id, 6, "0", STR_PAD_LEFT);
            }else{
                $str = $id;
            }
            // Get two random number
            $rnd1 = rand(0, 9);
            $rnd2 = rand(0, 9);
            $generate_no = $prefix[0] . $str . $rnd1 . $rnd2;
        }

        return $generate_no;
    }

    


    

    // Staff behaviour part
    public function behaviour() {
        $this->Navigation->addCrumb('List of Behaviour');

        $data = $this->StaffBehaviour->getBehaviourData($this->staffId);
        if (empty($data)) {
            $this->Utility->alert($this->Utility->getMessage('CUSTOM_FIELDS_NO_RECORD'));
        }

        $this->set('data', $data);
    }

    public function behaviourView() {
        $staffBehaviourId = $this->params['pass'][0];
        $staffBehaviourObj = $this->StaffBehaviour->find('all', array('conditions' => array('StaffBehaviour.id' => $staffBehaviourId)));

        if (!empty($staffBehaviourObj)) {
            $staffId = $staffBehaviourObj[0]['StaffBehaviour']['staff_id'];
            $data = $this->Staff->find('first', array('conditions' => array('Staff.id' => $staffId)));
            $this->Navigation->addCrumb('Behaviour Details');

            $yearOptions = array();
            $yearOptions = $this->SchoolYear->getYearList();
            $categoryOptions = array();
            $categoryOptions = $this->StaffBehaviourCategory->getCategory();

            $institutionSiteOptions = $this->InstitutionSite->find('list', array('recursive' => -1));
            $this->set('institution_site_id', $staffBehaviourObj[0]['StaffBehaviour']['institution_site_id']);
            $this->set('institutionSiteOptions', $institutionSiteOptions);
            $this->Session->write('StaffBehaviourId', $staffBehaviourId);
            $this->set('categoryOptions', $categoryOptions);
            $this->set('yearOptions', $yearOptions);
            $this->set('staffBehaviourObj', $staffBehaviourObj);
        } else {
            $this->redirect(array('action' => 'behaviour'));
        }
    }
	
    public function extracurricular() {
        $this->Navigation->addCrumb('Extracurricular');
        $data = $this->StaffExtracurricular->getAllList('staff_id', $this->staffId);
        $this->set('list', $data);
    }

    public function extracurricularView() {
        $id = $this->params['pass'][0];
        $data = $this->StaffExtracurricular->getAllList('id', $id);
        if (!empty($data)) {
            $this->Navigation->addCrumb('Extracurricular Details');

            $this->Session->write('StaffExtracurricularId', $id);
            $this->set('data', $data);
        }
    }

    public function extracurricularAdd() {
        $this->Navigation->addCrumb('Add Extracurricular');

        $yearList = $this->SchoolYear->getYearList();
        $yearId = $this->getAvailableYearId($yearList);
        $typeList = $this->ExtracurricularType->findList(array('fields' => array('id', 'name'), 'conditions' => array('visible' => '1'), 'orderBy' => 'name'));

        $this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
        $this->set('types', $typeList);
        if ($this->request->isPost()) {
            $data = $this->request->data;
            $data['StaffExtracurricular']['staff_id'] = $this->staffId;
            if ($this->StaffExtracurricular->save($data)) {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'extracurricular'));
            }
        }
    }

    public function extracurricularEdit() {
        $id = $this->params['pass'][0];
        $this->Navigation->addCrumb('Edit Extracurricular Details');
        if ($this->request->is('get')) {
            $data = $this->StaffExtracurricular->find('first', array('conditions' => array('StaffExtracurricular.id' => $id)));

            if (!empty($data)) {
                $this->request->data = $data;
            }
        } else {
            $data = $this->data;
            $data['StaffExtracurricular']['staff_id'] = $this->staffId;
            $data['StaffExtracurricular']['id'] = $id;
            if ($this->StaffExtracurricular->save($data)) {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'extracurricularView', $data['StaffExtracurricular']['id']));
            }
        }

        $yearList = $this->SchoolYear->getYearList();
        $yearId = $this->getAvailableYearId($yearList);
        $typeList = $this->ExtracurricularType->findList(array('fields' => array('id', 'name'), 'conditions' => array('visible' => '1'), 'orderBy' => 'name'));

        $this->set('selectedYear', $yearId);
        $this->set('years', $yearList);
        $this->set('types', $typeList);

        $this->set('id', $id);
    }

    public function extracurricularDelete($id) {
        if ($this->Session->check('StaffId') && $this->Session->check('StaffExtracurricularId')) {
            $id = $this->Session->read('StaffExtracurricularId');
            $staffId = $this->Session->read('StaffId');
            $name = $this->StaffExtracurricular->field('name', array('StaffExtracurricular.id' => $id));

            $this->StaffExtracurricular->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'extracurricular'));
        }
    }

    public function searchAutoComplete() {
        if ($this->request->is('get')) {
            if ($this->request->is('ajax')) {
                $this->autoRender = false;
                $search = $this->params->query['term'];
                $result = $this->StaffExtracurricular->autocomplete($search);
                return json_encode($result);
            }
        }
    }

    public function employments() {
        $this->Navigation->addCrumb('Employment');
        $data = $this->StaffEmployment->find('all', array('conditions' => array('StaffEmployment.staff_id' => $this->staffId)));
        $this->set('list', $data);
    }

    public function employmentsAdd() {
        if ($this->request->is('post')) {
            $this->StaffEmployment->create();
            $this->request->data['StaffEmployment']['staff_id'] = $this->staffId;

            $data = $this->data['StaffEmployment'];

            if ($this->StaffEmployment->save($data)) {
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
        $employmentObj = $this->StaffEmployment->find('all', array('conditions' => array('StaffEmployment.id' => $employmentId)));

        if (!empty($employmentObj)) {
            $this->Navigation->addCrumb('Employment Details');

            $this->Session->write('StaffEmploymentId', $employmentId);
            $this->set('employmentObj', $employmentObj);
        }
    }

    public function employmentsEdit() {
        $employmentId = $this->params['pass'][0];
        if ($this->request->is('get')) {
            $employmentObj = $this->StaffEmployment->find('first', array('conditions' => array('StaffEmployment.id' => $employmentId)));

            if (!empty($employmentObj)) {
                $this->Navigation->addCrumb('Edit Employment Details');
                $this->request->data = $employmentObj;
            }
        } else {
            $employmentData = $this->data['StaffEmployment'];
            $employmentData['staff_id'] = $this->staffId;

            if ($this->StaffEmployment->save($employmentData)) {
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'employmentsView', $employmentData['id']));
            }
        }

        $employmentTypeOptions = $this->EmploymentType->getOptions();
        $this->set('employmentTypeOptions', $employmentTypeOptions);

        $this->set('id', $employmentId);
    }

    public function employmentsDelete($id) {
        if ($this->Session->check('StaffId') && $this->Session->check('StaffEmploymentId')) {
            $id = $this->Session->read('StaffEmploymentId');
            $staffId = $this->Session->read('StaffId');
            $employmentTypeId = $this->StaffEmployment->field('employment_type_id', array('StaffEmployment.id' => $id));
            $name = $this->EmploymentType->field('name', array('EmploymentType.id' => $employmentTypeId));
            $this->StaffEmployment->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'employments', $staffId));
        }
    }

    public function salaries() {
        $this->Navigation->addCrumb('Salary');
        $data = $this->StaffSalary->find('all', array('conditions' => array('StaffSalary.staff_id' => $this->staffId)));
        $this->set('list', $data);
    }

    public function salariesAdd() {
        if ($this->request->is('post')) {
            $this->request->data['StaffSalary']['staff_id'] = $this->staffId;

            $this->StaffSalary->create();

            $this->StaffSalary->saveAll($this->request->data['StaffSalary'], array('validate' => 'only'));
            if (isset($this->request->data['StaffSalaryAddition'])) {
                $this->StaffSalaryAddition->saveAll($this->request->data['StaffSalaryAddition'], array('validate' => 'only'));
            }
            if (isset($this->request->data['StaffSalaryDeduction'])) {
                $this->StaffSalaryDeduction->saveAll($this->request->data['StaffSalaryDeduction'], array('validate' => 'only'));
            }

            if (!$this->StaffSalary->validationErrors &&
                    !$this->StaffSalaryAddition->validationErrors &&
                    !$this->StaffSalaryDeduction->validationErrors) {
                $this->StaffSalary->saveAll($this->request->data);
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'salaries'));
            } else {
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
        $salaryObj = $this->StaffSalary->find('all', array('conditions' => array('StaffSalary.id' => $salaryId)));

        if (!empty($salaryObj)) {
            $this->Navigation->addCrumb('Salary Details');

            $this->Session->write('StaffSalaryId', $salaryId);
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
        if ($this->request->is('get')) {
            $salaryObj = $this->StaffSalary->find('first', array('conditions' => array('StaffSalary.id' => $salaryId)));

            if (!empty($salaryObj)) {
                $this->Navigation->addCrumb('Edit Salary Details');
                $this->request->data = $salaryObj;
            }
        } else {
            if (isset($this->request->data['DeleteAddition'])) {
                $deletedId = array();
                foreach ($this->request->data['DeleteAddition'] as $key => $value) {
                    $deletedId[] = $value['id'];
                    pr('test');
                    unset($this->request->data['StaffSalaryAddition'][$key]);
                }
                $this->StaffSalaryAddition->deleteAll(array('StaffSalaryAddition.id' => $deletedId, 'StaffSalaryAddition.staff_salary_id' => $salaryId), false);
            }
            if (isset($this->request->data['DeleteDeduction'])) {
                $deletedId = array();
                foreach ($this->request->data['DeleteDeduction'] as $key => $value) {
                    $deletedId[] = $value['id'];
                    unset($this->request->data['StaffSalaryDeduction'][$key]);
                }
                $this->StaffSalaryDeduction->deleteAll(array('StaffSalaryDeduction.id' => $deletedId, 'StaffSalaryDeduction.staff_salary_id' => $salaryId), false);
            }
            $this->request->data['StaffSalary']['staff_id'] = $this->staffId;

            $this->StaffSalary->saveAll($this->request->data['StaffSalary'], array('validate' => 'only'));
            if (isset($this->request->data['StaffSalaryAddition'])) {
                $this->StaffSalaryAddition->saveAll($this->request->data['StaffSalaryAddition'], array('validate' => 'only'));
            }
            if (isset($this->request->data['StaffSalaryDeduction'])) {
                $this->StaffSalaryDeduction->saveAll($this->request->data['StaffSalaryDeduction'], array('validate' => 'only'));
            }

            if (!$this->StaffSalary->validationErrors &&
                    !$this->StaffSalaryAddition->validationErrors &&
                    !$this->StaffSalaryDeduction->validationErrors) {
                $this->StaffSalary->saveAll($this->request->data);
                $this->Utility->alert($this->Utility->getMessage('SAVE_SUCCESS'));
                $this->redirect(array('action' => 'salariesView', $salaryId));
            } else {
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

    public function salaryAdditionAdd() {
        $this->layout = 'ajax';
        $order = $this->params->query['order'];
        $this->set('order', $order);

        $visible = true;
        $categories = $this->SalaryAdditionType->findList($visible);

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('categories', $categories);
    }

    public function salaryDeductionAdd() {
        $this->layout = 'ajax';
        $order = $this->params->query['order'];
        $this->set('order', $order);

        $visible = true;
        $categories = $this->SalaryDeductionType->findList($visible);

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('categories', $categories);
    }

    public function salariesDelete($id) {
        if ($this->Session->check('StaffId') && $this->Session->check('StaffSalaryId')) {
            $id = $this->Session->read('StaffSalaryId');
            $staffId = $this->Session->read('StaffId');
            $name = $this->StaffSalary->field('salary_date', array('StaffSalary.id' => $id));
            $this->StaffSalary->delete($id);
            $this->Utility->alert($name . ' have been deleted successfully.');
            $this->redirect(array('action' => 'salaries', $staffId));
        }
    }


    

    

    public function attachmentsTrainingSelfStudyAdd() {
        $this->layout = 'ajax';
        $this->set('params', $this->params->query);
        $this->set('_model', 'StaffTrainingSelfStudyAttachment');
        $this->set('jsname', 'objTrainingSelfStudies');
        $this->render('/Elements/attachment/compact_add');
    }

    public function attachmentsTrainingSelfStudyDelete() {
        $this->autoRender = false;
        if ($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = $this->params->data['id'];

            $arrMap = array('model' => 'Staff.StaffTrainingSelfStudyAttachment', 'foreignKey' => 'staff_training_self_study_id');
            $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

            if ($FileAttachment->delete($id)) {
                $result['alertOpt']['text'] = __('File is deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting file.');
            }

            return json_encode($result);
        }
    }

    public function attachmentsTrainingSelfStudyDownload($id) {
        $arrMap = array('model' => 'Staff.StaffTrainingSelfStudyAttachment', 'foreignKey' => 'staff_training_self_study_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

        $FileAttachment->download($id);
    }

    public function getTrainingCoursesById() {
        $this->autoRender = false;

        if (isset($this->params['pass'][0]) && !empty($this->params['pass'][0])) {
            $id = $this->params['pass'][0];
            $type = $this->params['pass'][1];
            if ($type == 1) {
                $courseData = $this->TrainingCourse->find('all', array(
                    'fields' => array('TrainingCourse.*', 'TrainingRequirement.*'),
                    'joins' => array(
                        array(
                            'type' => 'LEFT',
                            'table' => 'training_requirements',
                            'alias' => 'TrainingRequirement',
                            'conditions' => array('TrainingRequirement.id = TrainingCourse.training_requirement_id')
                        )
                    ),
                    'conditions' => array('TrainingCourse.id' => $id),
                    'recursive' => -1)
                );
            } else {
                $courseData = $this->TrainingCourse->find('all', array(
                    'fields' => array('TrainingCourse.*', 'TrainingSession.*'),
                    'joins' => array(
                        array(
                            'type' => 'INNER',
                            'table' => 'training_sessions',
                            'alias' => 'TrainingSession',
                            'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id')
                        )
                    ),
                    'conditions' => array('TrainingSession.id' => $id),
                    'recursive' => -1)
                );
            }
            echo json_encode($courseData);
        }
    }

}
