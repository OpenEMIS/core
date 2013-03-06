<?php
App::uses('Sanitize', 'Utility');
App::uses('DateTime', 'Component');
App::uses('ImageMeta', 'Image');
App::uses('ImageValidate', 'Image');

class StudentsController extends StudentsAppController {
	public $studentId;
    public $studentObj;

    public $uses = array(
        'Institution',
        'Students.Student',
        'Students.StudentHistory',
        'Students.StudentCustomField',
        'Students.StudentCustomFieldOption',
        'Students.StudentCustomValue',
        'Students.StudentAttachment',
        'Students.InstitutionSiteStudent'
    );
        
    public $helpers = array('Js' => array('Jquery'), 'Paginator');
    // public $components = array('Paginator');
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
		if($this->action==='index' || $this->action==='add' || $this->action==='viewStudent') {
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
		$this->Navigation->addCrumb('List of Students');
		$tmp = $this->AccessControl->getAccessibleSites();
		
		$security = array('OR'=>array('InstitutionSiteStudent.id'=>null,'AND'=>array('InstitutionSiteStudent.institution_site_id'=>$tmp,'InstitutionSiteStudent.end_date >='=>date('Y-m-d'))));
				
        if ($this->request->is('post')){
            if(isset($this->request->data['Student']['SearchField'])){
                $this->request->data['Student']['SearchField'] = Sanitize::escape($this->request->data['Student']['SearchField']);
                if($this->request->data['Student']['SearchField'] != $this->Session->read('Search.SearchFieldStudent')) {
                    $this->Session->delete('Search.SearchFieldStudent');
                    $this->Session->write('Search.SearchFieldStudent', $this->request->data['Student']['SearchField']);
                }
            }

            if(isset($this->request->data['sortdir']) && isset($this->request->data['order']) ){

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
        $order = array('order'=>array($fieldordername => $fieldorderdir));

        $cond = array('SearchKey' => $this->Session->read('Search.SearchFieldStudent'));
		$cond = array_merge($cond,array('Security'=>$security));
		
        $limit = ($this->Session->read('Search.perpageStudent'))?$this->Session->read('Search.perpageStudent'):30;

        $this->Paginator->settings = array_merge(array('limit' => $limit,'maxLimit' => 100), $order);
        $data = $this->paginate('Student', $cond);

        $this->set('students', $data);
        $this->set('totalcount', $this->Student->sqlPaginateCount);
        $this->set('sortedcol', $fieldordername);
        $this->set('sorteddir', ($fieldorderdir == 'asc')?'up':'down');
		$this->set('searchField',  stripslashes($this->Session->read('Search.SearchFieldStudent')));
        if ($this->request->is('post')){
            $this->render('index_records','ajax');
        }
    }
	
	public function viewStudent($id) {
        $this->Session->write('StudentId', $id);
        $obj = $this->Student->find('first',array('conditions'=>array('Student.id' => $id)));
        $this->Session->write('StudentObj', $obj);
        $this->DateTime->getConfigDateFormat();

        $this->redirect(array('action' => 'view'));
    }
	
	public function view() {
		$this->Navigation->addCrumb('Details');
		$this->Student->id = $this->Session->read('StudentId');
        $data = $this->Student->read();

        $this->UserSession->readStatusSession($this->request->action);
        $this->set('data', $data);
    }
	
	public function edit() {
		$this->Navigation->addCrumb('Edit Details');
                $this->Student->id = $this->Session->read('StudentId');

        $imgValidate = new ImageValidate();
		
		if($this->request->is('post')) {

            $data = $this->data;
            $reset_image = $data['Student']['reset_image'];

            $img = new ImageMeta($this->data['Student']['photo_content']);
            unset($data['Student']['photo_content']);

            if($reset_image == 0){
                $validated = $imgValidate->validateImage($img);

                if($img->getFileUploadError() !== 4 && $validated['error'] < 1){
                    $data['Student']['photo_content'] = $img->getContent();
                    $img->setContent('');
//                $data['Student']['photo_name'] = serialize($img);
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

		}else{
			$data = $this->Student->find('first',array('conditions'=>array('id'=>$this->Session->read('StudentId'))));
			$this->set('data', $data);
		}
		
		
		$gender = array(0 => __('--Select--'), 'M' => __('Male'), 'F' => __('Female'));
		$this->set('gender', $gender);
		
    }

    public function fetchImage($id){
        $this->autoRender = false;

        $mime_types = ImageMeta::mimeTypes();

        $imageRawData = $this->Student->findById($id);
//        $imageMeta = unserialize($imageRawData['Student']['photo_name']);

        if(empty($imageRawData['Student']['photo_content']) || empty($imageRawData['Student']['photo_name'])){
            header("HTTP/1.0 404 Not Found");
            die();
        }else{
            $imageFilename = $imageRawData['Student']['photo_name'];
            $fileExt = pathinfo($imageFilename, PATHINFO_EXTENSION);
            $imageContent = $imageRawData['Student']['photo_content'];
//        header("Content-type: {$imageMeta->getMime()}");
            header("Content-type: " . $mime_types[$fileExt]);
            echo $imageContent;
        }
    }
	
    public function add() {
		$this->Navigation->addCrumb('Add new Student');
		if($this->request->is('post')) {
			$this->Student->set($this->data);
			if($this->Student->validates()) {
				$newStudentRec =  $this->Student->save($this->data);
				$this->redirect(array('action' => 'viewStudent', $newStudentRec['Student']['id']));
			}
		}

		$gender = array(0 => __('--Select--'), 'M' => __('Male'), 'F' => __('Female'));
		$this->set('gender', $gender);
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
		$this->Navigation->addCrumb('Additional Info');
		
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
        $this->set('data',$data);
        $this->set('data2',$data2);
    }

    /**
     * Institutions that the student has attended till date
     * @return [type] [description]
     */
    public function institutions() {
        $this->Navigation->addCrumb('Institutions');
        $data = $this->InstitutionSiteStudent->getData($this->studentId);
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('records', $data);
    }
    
    public function institutionsAdd() {
        // $this->Navigation->addCrumb('Edit Institutions');
        $this->layout = 'ajax';
        $order = $this->params->query['order'] + 1;
        $this->set('order', $order);
		$sites = $this->AccessControl->getAccessibleSites();
		$institutions = $this->InstitutionSiteStudent->getInstitutionSelectionValues($sites);
        $this->set('institutions', $institutions);
    }

    public function institutionsEdit() {
        $this->Navigation->addCrumb('Edit Institutions');

        if($this->request->is('post')) { // save                    }
            if (isset($this->data['InstitutionSiteStudent'])) {
                $dataValues = $this->data['InstitutionSiteStudent'];
				
                for($i=1; $i <= count($dataValues); $i++) {
                    $dataValues[$i]['student_id'] = $this->studentId;
                }
				
                $result = $this->InstitutionSiteStudent->saveAll($dataValues);
                if($result){
                    $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'institutions');
                    $this->redirect(array('controller' => $this->params['controller'], 'action' => 'institutions'));
                    
                }
            }
        }

        $data = $this->InstitutionSiteStudent->getData($this->studentId);
		$sites = $this->AccessControl->getAccessibleSites();
		$institutions = $this->InstitutionSiteStudent->getInstitutionSelectionValues($sites);

        $this->set('records', $data);
        $this->set('institutions', $institutions);
    }
    public function institutionsDelete($id) {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            // $id = $this->params->data['id'];
            
            if($this->InstitutionSiteStudent->delete($id)) {
                $result['alertOpt']['text'] = __('Records have been deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting record.');
            }
            
            return json_encode($result);
        }
    }

    /**
     * Programmes that the student has attended till date
     * @return [type] [description]
     */
    public function programmes() {
		$this->Navigation->addCrumb('Programmes');
    }


    /**
     * Assessments that the student has achieved till date
     * @return [type] [description]
     */
    public function assessments() {
		$this->Navigation->addCrumb('Assessment Results');
    }
}
