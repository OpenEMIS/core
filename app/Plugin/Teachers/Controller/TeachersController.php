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
        'Institution',
        'Teachers.InstitutionSiteTeacher',
        'Teachers.Teacher',
        'Teachers.TeacherHistory',
        'Teachers.TeacherCustomField',
        'Teachers.TeacherCustomFieldOption',
        'Teachers.TeacherCustomValue',
        'Teachers.TeacherAttachment',
        'Teachers.TeacherTraining',
        'Teachers.TeacherTrainingCategory',
        'Teachers.TeacherQualification',
        'Teachers.TeacherQualificationCategory',
        'Teachers.TeacherQualificationCertificate',
        'Teachers.TeacherQualificationInstitution'
        );

    public $helpers = array('Js' => array('Jquery'), 'Paginator');
    public $components = array(
        'UserSession',
        'Paginator',
        'FileAttachment' => array(
            'model' => 'Teachers.TeacherAttachment',
            'foreignKey' => 'teacher_id'
        )
    );

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Navigation->addCrumb('Teachers', array('controller' => 'Teachers', 'action' => 'index'));
            
        if($this->action==='index' || $this->action==='add' || $this->action==='viewTeacher') {
            $this->bodyTitle = 'Teachers';
        } else {
            if($this->Session->check('TeacherId') && $this->action!=='Home') {
                $this->teacherId = $this->Session->read('TeacherId');
                $this->teacherObj = $this->Session->read('TeacherObj');
                $teacherFirstName = $this->Teacher->field('first_name', array('Teacher.id' => $this->teacherId));
                $teacherLastName = $this->Teacher->field('last_name', array('Teacher.id' => $this->teacherId));
                $name = $teacherFirstName ." ". $teacherLastName;
                $this->bodyTitle = $name;
                $this->Navigation->addCrumb($name, array('action' => 'view'));
            } else {
                
                if($this->Auth->User('id') > 0){
                        $this->redirect(array('action' => 'index'));
                }else{
                        $this->redirect(array('controller'=>'SecurityUsers','action' => 'login'));
                }
            }
        }
    }

    public function index() {
        $this->Navigation->addCrumb('List of Teachers');
		$tmp = $this->AccessControl->getAccessibleSites();
		$security = array('OR'=>array('InstitutionSiteTeacher.id'=>null,'InstitutionSiteTeacher.institution_site_id'=>$tmp));
        if ($this->request->is('post')){
            if(isset($this->request->data['Teacher']['SearchField'])){
                Sanitize::clean($this->request->data);
                if($this->request->data['Teacher']['SearchField'] != $this->Session->read('Search.SearchFieldTeacher')) {
                    $this->Session->delete('Search.SearchFieldTeacher');
                    $this->Session->write('Search.SearchFieldTeacher', $this->request->data['Teacher']['SearchField']);
                }
            }

            if(isset($this->request->data['sortdir']) && isset($this->request->data['order']) ){

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
        $order = array('order'=>array($fieldordername => $fieldorderdir));

        $cond = array('SearchKey' => $this->Session->read('Search.SearchFieldTeacher'));
		$cond = array_merge($cond,array('Security'=>$security));
        $limit = ($this->Session->read('Search.perpageTeacher'))?$this->Session->read('Search.perpageTeacher'):30;

        $this->Paginator->settings = array_merge(array('limit' => $limit,'maxLimit' => 100), $order);
        $data = $this->paginate('Teacher', $cond);

        $this->set('teachers', $data);
        $this->set('totalcount', $this->Teacher->find('count'));
        $this->set('sortedcol', $fieldordername);
        $this->set('sorteddir', ($fieldorderdir == 'asc')?'up':'down');
        $this->set('searchField', $this->Session->read('Search.SearchFieldTeacher'));
        if ($this->request->is('post')){
            $this->render('index_records','ajax');
        }
    }

    public function viewTeacher($id) {
        $this->Session->write('TeacherId', $id);
        $obj = $this->Teacher->find('first',array('conditions'=>array('Teacher.id' => $id)));
        $this->Session->write('TeacherObj', $obj);
        $this->redirect(array( 'action' => 'view'));
    }

    public function view() {
        $this->Navigation->addCrumb('General');
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
        $this->set('gender', $gender);
		$this->set('data', $data);
    }

    public function fetchImage($id){
        $this->autoRender = false;

        $mime_type= ImageMeta::mimeTypes();

        $imageRawData = $this->Teacher->findById($id);
//        $imageMeta = unserialize($imageRawData['Teacher']['photo_name']);
        if(empty($imageRawData['Teacher']['photo_content']) || empty($imageRawData['Teacher']['photo_name'])){
            header("HTTP/1.0  404 Not Found");
            die();
        }else{
            $imageFilename = $imageRawData['Teacher']['photo_name'];
            $fileExt = pathinfo($imageFilename, PATHINFO_EXTENSION);
            $imageContent = $imageRawData['Teacher']['photo_content'];
//        header("Content-type: {$imageMeta->getMime()}");
            header("Content-type: {$mime_type[$fileExt]}");
            echo $imageContent;
        }
    }

    public function add() {
        $this->Navigation->addCrumb('Add new Teacher');
        if($this->request->is('post')) {
            $this->Teacher->set($this->data);
            if($this->Teacher->validates()) {
                $newTeacherRec =  $this->Teacher->save($this->data);
                // create the session for successfully adding of teacher
                $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view');
                $this->redirect(array('action' => 'viewTeacher', $newTeacherRec['Teacher']['id']));
            }
        }
        $gender = array(0 => __('--Select--'), 'M' => __('Male'), 'F' => __('Female'));
        $this->set('gender', $gender);
		$this->set('data', $this->data);
    }
    
    public function delete() {
        $id = $this->Session->read('TeacherId');
        $name = $this->Teacher->field('first_name', array('Teacher.id' => $id));
        $this->Teacher->delete($id);
        $this->Utility->alert(sprintf(__("%s have been deleted successfully."), $name));
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
                                    $arrV['teacher_id']  = $this->TeacherId;
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
        $data = $this->FileAttachment->getList($id);
        $this->set('data', $data);
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/view');
    }
    
    public function attachmentsEdit() {
        $this->Navigation->addCrumb('Edit Attachments');
        $id = $this->Session->read('TeacherId');
        
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
     * Institutions that the teacher has taught till date
     * @return [type] [description]
     */
    public function institutions() {
        $this->Navigation->addCrumb('Institutions');

        $data = $this->InstitutionSiteTeacher->getData($this->teacherId);
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('records', $data);

    }
    
    public function institutionsAdd() {
        // $this->Navigation->addCrumb('Edit Institutions');
        $this->layout = 'ajax';
        $order = $this->params->query['order'] + 1;
        $this->set('order', $order);
        $sites = $this->AccessControl->getAccessibleSites(true);
        $institutions = $this->InstitutionSiteTeacher->getInstitutionSelectionValues($sites);
//        array_unshift($institutions, array('0' => __("--Select--")));
        $this->set('institutions', $institutions);
    }

    public function institutionsEdit() {
        $this->Navigation->addCrumb('Edit Institutions');

        if($this->request->is('post')) { // save                    }
            if (isset($this->data['InstitutionSiteTeacher'])) {
                $dataValues = $this->data['InstitutionSiteTeacher'];
                for($i=1; $i <= count($dataValues); $i++) {
                    $dataValues[$i]['teacher_id'] = $this->teacherId;
                }
                $result = $this->InstitutionSiteTeacher->saveAll($dataValues);
                if($result){
                    $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'institutions');
                    $this->redirect(array('controller' => $this->params['controller'], 'action' => 'institutions'));
                }
            }
        }

        $data = $this->InstitutionSiteTeacher->getData($this->teacherId);
        $sites = $this->AccessControl->getAccessibleSites(true);
        $institutions = $this->InstitutionSiteTeacher->getInstitutionSelectionValues($sites);
//        array_unshift($institutions, array('0' => "--Select--"));

        $this->set('records', $data);
        $this->set('institutions', $institutions);
        
    }

    public function institutionsDelete($id) {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            // $id = $this->params->data['id'];
            
            if($this->InstitutionSiteTeacher->delete($id)) {
                $result['alertOpt']['text'] = __('Records have been deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting record.');
            }
            
            return json_encode($result);
        }
    }

    /**
     * Programmes that the teacher has taught till date
     * @return [type] [description]
     */
    public function programmes() {
        $this->Navigation->addCrumb('Programmes');
    }

    /**
     * Qualifications that the teacher has attained till date
     * @return [type] [description]
     */
    /**
     * Qualifications that the teacher has attained till date
     * @return [type] [description]
     */
    public function qualifications() {
        $this->Navigation->addCrumb('Qualifications');

        if ($this->request->is('post')) {
            $this->TeacherQualification->create();
            $this->request->data['TeacherQualification']['teacher_id'] = $this->teacherId;
            $this->TeacherQualification->save($this->request->data['TeacherQualification']);
        }

        $list = $this->TeacherQualification->getData($this->teacherId);
        // $categories = $this->TeacherQualificationCategory->findAllByVisible(1);
        $institutes = $this->TeacherQualificationInstitution->findAllByVisible(1);

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('list', $list);
        // $this->set('categories', $categories);
        $this->set('institutes', $institutes);
    }

    public function qualificationsAdd() {
        $this->layout = 'ajax';

        $order = $this->params->query['order'] + 1;
        $this->set('order', $order);

        $visible = true;
        // $categories = $this->TeacherTrainingCategory->findList($visible);
        // array_unshift($categories, __('--Select--'));
        $certificates = $this->TeacherQualificationCertificate->findAllByVisible(1);
        $institutes = $this->TeacherQualificationInstitution->findAllByVisible(1);

        $list = $this->TeacherTraining->getData($this->teacherId);
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('data', $list);
        $this->set('certificates', $certificates);
        // $this->set('categories', $categories);
        $this->set('institutes', $institutes);
    }

    public function qualificationsEdit() {
        $this->Navigation->addCrumb('Qualifications');
        if($this->request->is('post')) { // save                    }

            if (isset($this->data['TeacherQualification'])) {
                $dataValues = $this->data['TeacherQualification'];
                
                for($i=1; $i <= count($dataValues); $i++) {
                    $dataValues[$i]['teacher_id'] = $this->teacherId;
                }
                // pr($dataValues); die();

                $result = $this->TeacherQualification->saveAll($dataValues);
                if($result){
                    $this->UserSession->writeStatusSession('ok', __('Records have been deleted successfully.'), 'qualifications');
                    $this->redirect(array('controller' => $this->params['controller'], 'action' => 'qualifications'));
                    //$this->Session->setFlash('Saved.');
                }else{
                    //$this->Session->setFlash('Error in Saving.');
                }
            }
        }

        $list = $this->TeacherQualification->getData($this->teacherId);
        // $categories = $this->TeacherQualificationCategory->findAllByVisible(1);
        $certificates = $this->TeacherQualificationCertificate->findAllByVisible(1);
        $institutes = $this->TeacherQualificationInstitution->findAllByVisible(1);

        $this->set('list', $list);
        // $this->set('categories', $categories);
        $this->set('certificates', $certificates);
        $this->set('institutes', $institutes);

    }

    public function qualificationsDelete($id) {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            // $id = $this->params->data['id'];
            
            if($this->TeacherQualification->delete($id)) {
                $result['alertOpt']['text'] = __('Records have been deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting record.');
            }
            
            return json_encode($result);
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
}
