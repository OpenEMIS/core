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
        'Institution',
		'InstitutionSite',
        'InstitutionSiteStaff',
        'Staff.InstitutionSiteStaff',
        'Staff.Staff',
        'Staff.StaffHistory',
        'Staff.StaffCustomField',
        'Staff.StaffCustomFieldOption',
        'Staff.StaffCustomValue',
        'Staff.StaffAttachment',
		'ConfigItem'
        );

    public $helpers = array('Js' => array('Jquery'), 'Paginator');
    public $components = array(
        'UserSession',
        'Paginator',
        'FileAttachment' => array(
            'model' => 'Staff.StaffAttachment',
            'foreignKey' => 'staff_id'
        )
    );

    public function beforeFilter() {
        parent::beforeFilter();

        $this->Navigation->addCrumb('Staff', array('controller' => 'Staff', 'action' => 'index'));
            
        if($this->action==='index' || $this->action==='add' || $this->action==='viewStaff') {
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
		$tmp = $this->AccessControl->getAccessibleSites();
		$security = array('OR'=>array('InstitutionSiteStaff.id'=>null,'InstitutionSiteStaff.institution_site_id'=>$tmp));
        if ($this->request->is('post')){
            if(isset($this->request->data['Staff']['SearchField'])){
               $this->request->data['Staff']['SearchField'] = Sanitize::escape($this->request->data['Staff']['SearchField']);
                if($this->request->data['Staff']['SearchField'] != $this->Session->read('Search.SearchFieldStaff')) {
                    $this->Session->delete('Search.SearchFieldStaff');
                    $this->Session->write('Search.SearchFieldStaff', $this->request->data['Staff']['SearchField']);
                }
            }

            if(isset($this->request->data['sortdir']) && isset($this->request->data['order']) ){

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
        $order = array('order'=>array($fieldordername => $fieldorderdir));

        $cond = array('SearchKey' => $this->Session->read('Search.SearchFieldStaff'));
		$cond = array_merge($cond,array('Security'=>$security));
        $limit = ($this->Session->read('Search.perpageStaff'))?$this->Session->read('Search.perpageStaff'):30;

        $this->Paginator->settings = array_merge(array('limit' => $limit,'maxLimit' => 100), $order);
        $data = $this->paginate('Staff', $cond);

        $this->set('staff', $data);
        $this->set('totalcount', $this->Staff->find('count'));
        $this->set('sortedcol', $fieldordername);
        $this->set('sorteddir', ($fieldorderdir == 'asc')?'up':'down');
        $this->set('searchField', stripslashes($this->Session->read('Search.SearchFieldStaff')));
        if ($this->request->is('post')){
            $this->render('index_records','ajax');
        }
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

        $mime_types = ImageMeta::mimeTypes();

        $imageRawData = $this->Staff->findById($id);
//        $imageMeta = unserialize($imageRawData['Staff']['photo_name']);
        if(empty($imageRawData['Staff']['photo_content']) || empty($imageRawData['Staff']['photo_name'])){
            header("HTTP/1.0  404 Not Found");
            die();
        }else{
            $imageFilename = $imageRawData['Staff']['photo_name'];
            $fileExt = pathinfo($imageFilename, PATHINFO_EXTENSION);
            $imageContent = $imageRawData['Staff']['photo_content'];
    //        header("Content-type: {$imageMeta->getMime()}");
            header("Content-type: {$mime_types[$fileExt]}");
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
        if($this->request->is('post')) {
            $this->Staff->set($this->data);
            if($this->Staff->validates()) {
                $newStaffRec =  $this->Staff->save($this->data);
                $this->UserSession->writeStatusSession('ok', __('Records have been added/updated successfully.'), 'view');
                $this->redirect(array('action' => 'viewStaff', $newStaffRec['Staff']['id']));
            }
        }
        $gender = array(0 => __('--Select--'), 'M' => __('Male'), 'F' => __('Female'));
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
                                    $arrV['staff_id']  = $this->StaffId;
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
        $data = $this->FileAttachment->getList($id);
        $this->set('data', $data);
        $this->set('arrFileExtensions', $this->Utility->getFileExtensionList());
        $this->render('/Elements/attachment/view');
    }
    
    public function attachmentsEdit() {
        $this->Navigation->addCrumb('Edit Attachments');

        $id = $this->Session->read('StaffId');
        
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

    /**
     * Institutions that the staff has been to till date
     * @return [type] [description]
     */
    public function institutions() {
        $this->Navigation->addCrumb('Institutions');

        $data = $this->InstitutionSiteStaff->getData($this->staffId);
        $this->UserSession->readStatusSession($this->request->action);
        $this->set('records', $data);
    }
    
    public function institutionsAdd() {
        // $this->Navigation->addCrumb('Edit Institutions');
        $this->layout = 'ajax';
        $order = $this->params->query['order'] + 1;
        $this->set('order', $order);
        $sites = $this->AccessControl->getAccessibleSites();
        $institutions = $this->InstitutionSiteStaff->getInstitutionSelectionValues($sites);
//        array_unshift($institutions, array('0' => __("--Select--")));

        $this->set('institutions', $institutions);
    }

    public function institutionsEdit() {
        $this->Navigation->addCrumb('Edit Institution');

        if($this->request->is('post')) { // save                    }
            if (isset($this->data['InstitutionSiteStaff'])) {
                $dataValues = $this->data['InstitutionSiteStaff'];
                for($i=1; $i <= count($dataValues); $i++) {
                    $dataValues[$i]['staff_id'] = $this->staffId;
                }
                $result = $this->InstitutionSiteStaff->saveAll($dataValues);
                if($result){
                    $this->UserSession->writeStatusSession('ok', 'Records have been added/updated successfully.', 'institutions');
                    $this->redirect(array('controller' => $this->params['controller'], 'action' => 'institutions'));
                }
            }
        }

        $data = $this->InstitutionSiteStaff->getData($this->staffId);
        $sites = $this->AccessControl->getAccessibleSites();
        $institutions = $this->InstitutionSiteStaff->getInstitutionSelectionValues($sites);
//        array_unshift($institutions, array('0' => __("--Select--")));

        $this->set('records', $data);
        $this->set('institutions', $institutions);
        
    }
    
    public function institutionsDelete($id) {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            // $id = $this->params->data['id'];
            
            if($this->InstitutionSiteStaff->delete($id)) {
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
		$this->render('/Elements/customfields/view');
	}

}

	public function getUniqueID() {
        $this->autoRender = false;
		$generate_no = '';
     	$str = $this->Staff->find('first', array('order' => array('Staff.id DESC'), 'limit' => 1, 'fields'=>'Staff.id'));
		$pattern = $this->ConfigItem->find('first', array('limit' => 1, 
													  'fields'=>'ConfigItem.value',
													  'conditions'=>array(
																			'ConfigItem.name' => 'staff_identification'
																		 )
									   ));
		$pattern = $pattern['ConfigItem']['value'];
    	$id = $str['Staff']['id']+1; 
		
		if(isset($pattern) && $pattern!=''){
			$str = str_replace("C", 'E', $pattern);
			if(substr_count($pattern, 'N')>=strlen($id)){
				$id = str_pad($id,substr_count($pattern, 'N'),"0",STR_PAD_LEFT);
				$strlen = strlen($str);
				$cnt = 0;
				for( $i = 0; $i <= $strlen; $i++ ) {
					$char = substr( $str, $i, 1 );
					if($char!='E')
					{
						$char = substr($id,$cnt,1);
						$cnt++;
					}
					$generate_no .= $char;
				}
			}else{
				$generate_no = 'Fail';
			}
		}else{
			// Apply default pattern
			$str = str_pad($id,7,"0",STR_PAD_LEFT);
			$generate_no = 'E'.$str.'E';
		}
		echo $generate_no;
    }
}
