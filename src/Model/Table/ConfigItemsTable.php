<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

class ConfigItemsTable extends AppTable {
	use OptionsTrait;

	private $configurations = [];

	public function initialize(array $config) {
		parent::initialize($config);

		// $this->belongsTo('ConfigItemOptions', ['foreignKey'=>'value']);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('visible', ['visible' => false]);
		$this->ControllerAction->field('editable', ['visible' => false]);
		$this->ControllerAction->field('field_type', ['visible' => false]);
		$this->ControllerAction->field('option_type', ['visible' => false]);
		$this->ControllerAction->field('code', ['visible' => false]);

		$this->ControllerAction->field('name', ['visible' => ['index'=>true]]);
		$this->ControllerAction->field('default_value', ['visible' => ['view'=>true]]);
		
		$this->ControllerAction->field('type', ['visible' => ['view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('label', ['visible' => ['view'=>true, 'edit'=>true]]);
		$this->ControllerAction->field('value', ['visible' => true]);
	}


/******************************************************************************************************************
**
** index action methods
**
******************************************************************************************************************/
	public function indexBeforeAction(Event $event) {
		$toolbarElements = [
			['name' => 'Configurations/controls', 'data' => [], 'options' => []]
		];
		$this->controller->set('toolbarElements', $toolbarElements);

		$typeOptions = array_keys($this->find('list', ['keyField' => 'type', 'valueField' => 'type'])->order('type')->toArray());

		$selectedType = $this->queryString('type', $typeOptions);
		$this->advancedSelectOptions($typeOptions, $selectedType);
		$this->request->query['type_value'] = $typeOptions[$selectedType]['text'];

		$this->controller->set('typeOptions', $typeOptions);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$type = $request->query['type_value'];
		$options['finder'] = ['visible' => []];
		$options['conditions'][$this->aliasField('type')] = $type;
	}


/******************************************************************************************************************
**
** edit action methods
**
******************************************************************************************************************/
	public function editBeforeAction(Event $event) {
		$this->fields['type']['type'] = 'readonly';
		$this->fields['label']['type'] = 'readonly';
	}


/******************************************************************************************************************
**
** view action methods
**
******************************************************************************************************************/
	public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request) {
		if (in_array($action, ['edit', 'add'])) {
			$pass = $request->param('pass');
			if (!empty($pass)) {
				$id = $pass[0];
				$entity = $this->get($id);


				if ($entity->field_type == 'Dropdown') {

					$exp = explode(':', $entity->option_type);
					/**
					 * if options list is from a specific table
					 */
					if (count($exp)>0 && $exp[0]=='database') {
						$model = Inflector::pluralize($exp[1]);
						$model = $this->getActualModeLocation($model);
						$optionTable = TableRegistry::get($model);
						$attr['options'] = $optionTable->getList();
						
					/**
					 * if options list is from ConfigItemOptions table
					 */
					} else {
						$optionTable = TableRegistry::get('ConfigItemOptions');
						$options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
							->where([
								'ConfigItemOptions.option_type' => $entity->option_type
							])
							->toArray();
						$attr['options'] = $options;
					}

				// } else if ($entity->code == 'student_prefix' || $entity->code == 'staff_prefix') {
				// 	$exp = explode(',', $entity->value);
				// 	if (!$exp[1]) {
				// 		return __('Disabled');
				// 	} else {
				// 		return __('Enabled') . ' ('.$exp[0].')';
				// 	}
				// } else {
				// 	if ($entity->code == 'time_format' || $entity->code == 'date_format') {
				// 		return date($entity->value);
				// 	} else {
				// 		return $entity->value;
				// 	}
				}

			}
			// pr($attr);
		}
		return $attr;
	}

	public function onGetValue(Event $event, Entity $entity) {

		if ($entity->field_type == 'Dropdown') {

			$exp = explode(':', $entity->option_type);
			/**
			 * if options list is from a specific table
			 */
			if (count($exp)>0 && $exp[0]=='database') {
				$model = Inflector::pluralize($exp[1]);
				$model = $this->getActualModeLocation($model);
				$optionValues = TableRegistry::get($model);
				$value = $optionValues->get($entity->value);
				if (is_object($value)) {
					return $value->name;
				} else {
					return $entity->value;
				}

			/**
			 * if options list is from ConfigItemOptions table
			 */
			} else {
				$optionValues = TableRegistry::get('ConfigItemOptions');
				$value = $optionValues->find()
					->where([
						'ConfigItemOptions.option_type' => $entity->option_type,
						'ConfigItemOptions.value' => $entity->value,
					])
					->first();
				if (is_object($value)) {
					if ($entity->code == 'time_format' || $entity->code == 'date_format') {
						return date($value->value);
					} else {
						return $value->option;
					}
				} else {
					return $entity->value;
				}
			}

		} else if ($entity->code == 'student_prefix' || $entity->code == 'staff_prefix') {
			$exp = explode(',', $entity->value);
			if (!$exp[1]) {
				return __('Disabled');
			} else {
				return __('Enabled') . ' ('.$exp[0].')';
			}
		} else {
			if ($entity->code == 'time_format' || $entity->code == 'date_format') {
				return date($entity->value);
			} else {
				return $entity->value;
			}
		}
	}


	public function onGetDefaultValue(Event $event, Entity $entity) {

		if ($entity->field_type == 'Dropdown') {

			$exp = explode(':', $entity->option_type);
			/**
			 * if options list is from a specific table
			 */
			if (count($exp)>0 && $exp[0]=='database') {
				$model = Inflector::pluralize($exp[1]);
				$model = $this->getActualModeLocation($model);
				$optionValues = TableRegistry::get($model);
				$value = $optionValues->get($entity->value);
				if (is_object($value)) {
					return $value->name;
				} else {
					return $entity->value;
				}

			/**
			 * if options list is from ConfigItemOptions table
			 */
			} else {
				$optionValues = TableRegistry::get('ConfigItemOptions');
				$value = $optionValues->find()
					->where([
						'ConfigItemOptions.option_type' => $entity->option_type,
						'ConfigItemOptions.value' => $entity->value,
					])
					->first();
				if (is_object($value)) {
					if ($entity->code == 'time_format' || $entity->code == 'date_format') {
						return date($value->value);
					} else {
						return $value->option;
					}
				} else {
					return $entity->value;
				}
			}

		} else if ($entity->code == 'student_prefix' || $entity->code == 'staff_prefix') {
			$exp = explode(',', $entity->value);
			if (!$exp[1]) {
				return __('Disabled');
			} else {
				return __('Enabled') . ' ('.$exp[0].')';
			}
		} else {
			if ($entity->code == 'time_format' || $entity->code == 'date_format') {
				return date($entity->value);
			} else {
				return $entity->value;
			}
		}
	}


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	public function value($code) {
		$value = '';
		if (array_key_exists($code, $this->configurations)) {
			$value = $this->configurations[$code];
		} else {
			$entity = $this->findByCode($code)->first();
			$value = strlen($entity->value) ? $entity->value : $entity->default_value;
			$this->configurations[$code] = $value;
		}
		return $value;
	}

	public function defaultValue($code) {
		$value = '';
		if (array_key_exists($code, $this->configurations)) {
			$value = $this->configurations[$code];
		} else {
			$entity = $this->findByCode($code)->first();
			$value = $entity->default;
			$this->configurations[$code] = $value;
		}
		return $value;
	}

	private function getActualModeLocation($model) {
		$dir = dirname(__FILE__);
		if (!file_exists($dir . '/' . $model . 'Table.php')) {
			$dir = dirname(dirname(dirname(dirname(__FILE__)))).'/plugins';
			$folders = scandir($dir);
			foreach ($folders as $folder) {
				if (!in_array($folder, ['.', '..', '.DS_Store'])) {
					if (file_exists($dir . '/' . $folder . '/src/Model/Table/' . $model . 'Table.php')) {
						$model = $folder .'.'. $model;
						break;
					}
				}
			}
		}
		return $model;
	}


/******************************************************************************************************************
**
** version 2 codes
**
******************************************************************************************************************/
	// public $validateDataDiscrepancy = array(
	// 	'value' => array(
	// 		'num'=>array(
	// 			'rule'  => 'numeric',
	// 			'message' => 'Numeric Value should be between 0 to 100'
	// 		),
	// 		'bet' => array(
	// 			'rule'	=> array('range', -1, 101),
	// 			'message' => 'Numeric Value should be between 0 to 100'
	// 		)
	// 	)
 //  	);

 //  	public $validateDataOutlier = array(
	// 	'value' => array(
	// 		'num'=>array(
	// 			'rule'  => 'numeric',
	// 			'message' => 'Numeric Value should be between 0 to 100'
	// 		),
	// 		'bet' => array(
	// 			'rule'	=> array('range', -1, 101),
	// 			'message' => 'Numeric Value should be between 0 to 100'
	// 		)
	// 	)
 //  	);

 //  	public $validateStudentAdmissionAge = array(
	// 	'value' => array(
	// 		'num'=>array(
	// 			'rule'  => 'numeric',
	// 			'message' => 'Numeric Value should be between 0 to 100'
	// 		),
	// 		'bet' => array(
	// 			'rule'	=> array('range', -1, 101),
	// 			'message' => 'Numeric Value should be between 0 to 100'
	// 		)
	// 	)
 //  	);

	// public $validateNoOfShift = array(
	// 	'value' => array(
	// 		'num'=>array(
	// 			'rule'  => 'numeric',
	// 			'message' => 'Numeric Value should be between 0 to 10'
	// 		),
	// 		'bet' => array(
	// 			'rule'	=> array('range', -1, 101),
	// 			'message' => 'Numeric Value should be between 0 to 10'
	// 		)
	// 	)
 //  	);

 //  	public $validateCreditHour = array(
	// 	'value' => array(
	// 		'rule'  => 'numeric',
 //  			'message' => 'Value should be numeric'
 //  		)
 //  	);
  
	// public $validateSmsRetryTime = array(
	// 	'value' => array(
	// 		'num'=>array(
	// 			'rule'  => 'numeric',
	// 			'message' =>  'Numeric Value should be between 0 to 10'
	// 		),
	// 		'bet' => array(
	// 			'rule'	=> array('range', -1, 11),
	// 			'message' => 'Numeric Value should be between 0 to 10'
	// 		)
	// 	)
 //  	);
	
 //  	public $validateSmsRetryWait = array(
	// 	'value' => array(
	// 		'num'=>array(
	// 			'rule'  => 'numeric',
	// 			'message' =>  'Numeric Value should be between 0 to 60'
	// 		),
	// 		'bet' => array(
	// 			'rule'	=> array('range', -1, 61),
	// 			'message' => 'Numeric Value should be between 0 to 60'
	// 		)
	// 	)
 //  	);
	
 // 	public function beforeValidate($options = array()) {
	// 	// We might want to check data
	// 	if ($this->data['ConfigItem']['type']=='Data Discrepancy') {
	// 		$this->validate = array_merge($this->validate, $this->validateDataDiscrepancy);
	// 	} else if ($this->data['ConfigItem']['type']=='Data Outliers') {
	// 		$this->validate = array_merge($this->validate, $this->validateDataOutlier);
	// 	} else if ($this->data['ConfigItem']['type']=='Student Admission Age') {
	// 		$this->validate = array_merge($this->validate, $this->validateStudentAdmissionAge);
	// 	} else if ($this->data['ConfigItem']['name']=='no_of_shifts') {
	// 		$this->validate = array_merge($this->validate, $this->validateNoOfShift);
	// 	} else if ($this->data['ConfigItem']['name']=='training_credit_hour') {
	// 		$this->validate = array_merge($this->validate, $this->validateCreditHour);
	// 	} else if ($this->data['ConfigItem']['name']=='sms_retry_times') {
	// 		$this->validate = array_merge($this->validate, $this->validateSmsRetryTime);
	// 	} else if ($this->data['ConfigItem']['name']=='sms_retry_wait') {
	// 		$this->validate = array_merge($this->validate, $this->validateSmsRetryWait);
	// 	}
	// 	/*
	// 	// Maybe only on an edit action?
	// 	// We know it's edit because there is an id
	// 	if (isset($this->data['Post']['id'])) {
	// 		$this->validate = array_merge($this->validate, $this->validatePost);
	// 	}
		
	// 	// Perhaps we want to add a single new rule for add using the validator?
	// 	// We know it's add because there is no id
	// 	if (!isset($this->data['Post']['id'])) {
	// 		$this->validator()->add('pubDate', array(
	// 				'one' => array(
	// 					'rule' => array('datetime', 'ymd'),
	// 					'message' => 'Publish date must be ymd'
	// 				)
	// 			)
	// 		)
	// 	}*/
		
	// 	return true;
	// }

	// public function getYearbook() {
	// 	$yearbook = array();
	// 	$yearbook['yearbook_organization_name'] = $this->getValue('yearbook_organization_name');
	// 	$yearbook['yearbook_school_year'] = $this->getValue('yearbook_school_year');
	// 	$yearbook['yearbook_title'] = $this->getValue('yearbook_title');
	// 	$yearbook['yearbook_publication_date'] = $this->getValue('yearbook_publication_date');
	// 	$yearbook['yearbook_logo'] = $this->getValue('yearbook_logo');
	// 	$yearbook['yearbook_orientation'] = $this->getValue('yearbook_orientation');
	// 	return $yearbook;
	// }
	
	// public function getVersion() {
	// 	return $this->getValue('version');
	// }
	
	// public function getWebFooter(){
	// 	$systemYear = str_replace('year', date('Y'), $this->getValue('footer'));
	// 	return '<span class="copyright-notice" dir="ltr">'.$systemYear.'</span> | ';
	// }

	// public function getTypes() {
	// 	$types = array();
	// 	$rawData = $this->find('all',array(
	// 		'fields' => array('DISTINCT ConfigItem.type'),
	// 		'recursive' => 0
	// 	));

	// 	$data = $this->formatArray($rawData);
	// 	foreach ($data as $element) {
	// 		$types[] = $element['type'];
	// 	}
	// 	return $types;
	// }

	// public function getSupport() {
	// 	$supportInfo = array();
	// 	$supportInfo['phone'] = $this->getValue('support_phone');
	// 	$supportInfo['email'] = $this->getValue('support_email');
	// 	$supportInfo['address'] = $this->getValue('support_address');
	// 	return $supportInfo;
	// }
	
	// public function getAdaptation() {	
	// 	// return $this->getValue('adaptation');
	// 	$results = $this->find('all', array(
	// 		'fields' => array('ConfigItems.id', 'ConfigItems.name', 'ConfigItems.value', 'ConfigItems.default_value'),
	// 		'recursive' => 0,
	// 		'limit' => 1,
	// 		'conditions' => array('name' => 'adaptation')
	// 	));

	// 	$adaptation = array_shift($results);

	// 	return (!is_null($adaptation['ConfigItem']['value']) && !empty($adaptation['ConfigItem']['value']))? $adaptation['ConfigItem']['value']: $adaptation['ConfigItem']['default_value'];
	// }
	
	// public function getCountry() {
	// 	return $this->getValue('country');
	// }

	// public function getLabel($name) {
	// 	return $this->field('label', array('name' => $name));
	// }
	
	// public function getValue($name) {
	// 	$value = $this->field('value', array('name' => $name));
	// 	return (empty($value))? $this->getDefaultValue($name):$value;
	// }

	// public function getOptionValue($name) {

	// 	$data = $this->find(
	// 		'first',
	// 		array(
	// 			'recursive' => -1,
	// 			'conditions' => array(
	// 				'name' => $name
	// 			)
	// 		)
	// 	);

	// 	$optionType = $data['ConfigItem']['option_type'];
	// 	$value = $this->getValue($name);

	// 	$ConfigItemOption = TableRegistry::get('ConfigItemOptions');
	// 	$result = $ConfigItemOption->field('option',
	// 		array(
	// 			'option_type' => $optionType,
	// 			'value' => $value
	// 		)
	// 	);

	// 	return $result;
	// }

	// public function getDefaultValue($name) {
	// 	return $this->field('default_value', array('name' => $name));
	// }

	// public function editDashboardImage($x=null, $y=null){
	// 	$isUpdated = false;
	// 	if(is_null($x) || is_null($y)){
	// 		return false;
	// 	}

	// 	$timestamp = '';
	// 	$newX = $x;
	// 	$newY = $y;

	// 	$imageFolder = $this->getValue('dashboard_img_folder');
	// 	$path = IMAGES.$imageFolder.DS;

	// 	$filenames = $this->getUserImageFiles($imageFolder);


	// 	if(count($filenames) > 0){
	// 		$filename = $filenames[0];
	// 		$ext = $this->findExtension($filename);

	// 		$spilt_filename = explode('_', $filename);
			
	// 		if(isset($spilt_filename[0])){
	// 			$timestamp = $spilt_filename[0];
	// 		}
	// 		$newFilename = $timestamp . '_' . $newX . '_' . $newY . '.' . $ext;
			
	// 		$isUpdated = rename($path.$filename, $path.$newFilename);
			
	// 	}else{
	// 		return false;
	// 	}

	// 	return $isUpdated;

	// }

	// public function saveDashboardImage($newImage=null){
	// 	// $fields = array('dashboard_img_orignal', 'dashboard_img_x_offset', 'dashboard_img_y_offset', 'dashboard_img_folder' );

	// 	$returnResult = array();

	// 	$isSave = false;

	// 	$isFolderEmpty = false;

	// 	if(is_null($newImage)){
	// 		return false;
	// 	}

	// 	$image = $newImage;

	// 	$folder = $this->getValue('dashboard_img_folder');
 // 		$filename = time()."_0_0.". $this->findExtension($image['name']); //str_ireplace(' ', '_', strtolower($image['name']));
 // 		try {
 // 			$isFolderEmpty = $this->emptyFolder($folder);
 // 			$isSave = move_uploaded_file($image['tmp_name'], IMAGES.$folder.DS.$filename);

	// 		$returnResult['saved'] = $isSave;
 			
 // 		} catch (Exception $e) {
 // 			$returnResult['saved'] = $isSave;
 			
 // 		}

	// 	return $isSave;

	// }
	
	// public function getDashboardMasthead(){
	// 	$image = array(
	// 		'imagePath' => '',
	// 		'x' => 0,
	// 		'y' => 0,
	// 		'width' => 700,
	// 		'height' => 320
	// 	);

	// 	$imageFolder = '';
	// 	$imageFilename = '';
	// 	$isDefault = false;

	// 	list($image, $defaultIamge, $x, $y, $width, $height) = array('','',0,0,700,200);

	// 	$imageFolder = $this->getValue('dashboard_img_folder');

	// 	$filenames = $this->getUserImageFiles($imageFolder);

	// 	$filename = (count($filenames)> 0)?$filenames[0]: $this->getDefaultImageFile();

	// 	$width = $this->getValue('dashboard_img_width');

	// 	$height = $this->getValue('dashboard_img_height');

	// 	$coordinates = $this->getCoordinates($filename);

	// 	$image['imagePath'] = $imageFolder.DS.$filename;

	// 	$image['x'] = $coordinates['x'];
	// 	$image['y'] = $coordinates['y'];
	// 	$image['width'] = $width;
	// 	$image['height'] = $height;

	// 	return $image;
	// }

	// public function getImageConfItem() {
	// 	$rawImageConfig = $this->find('all', array(
	// 		'fields' => array('ConfigItem.name', 'ConfigItem.value', 'ConfigItem.default_value'),
	// 		'conditions' => array('ConfigItem.name' => array( 'dashboard_img_default', 'dashboard_img_width', 'dashboard_img_height', 'dashboard_img_size_limit'))
	// 	));

	// 	// $imageFolder = $this->getValue('dashboard_img_folder');
	// 	$defaultImageId = $this->getValue('dashboard_img_default');
	// 	// $filenames = $this->getUserImageFiles($imageFolder);
	// 	// $filename = (count($filenames)>0)?$filenames[0]:$this->getDefaultImageFile();
	// 	$width = $this->getValue('dashboard_img_width');
	// 	$height = $this->getValue('dashboard_img_height');
	// 	// $coordinates = $this->getCoordinates($filename);
	// 	$size_limit = $this->getValue('dashboard_img_size_limit');
	// 	// (int)(ini_get('upload_max_filesize'));

	// 	$imageConfig = array();

	// 	$isDefault = false;

	// 	// $imageConfig['dashboard_img_x_offset'] = (isset($coordinates['x']))? $coordinates['x']:0;
	// 	// $imageConfig['dashboard_img_y_offset'] = (isset($coordinates['y']))? $coordinates['y']:0;
	// 	// $imageConfig['dashboard_img_folder'] = $imageFolder;
	// 	// $imageConfig['dashboard_img_file'] = $filename;
	// 	$imageConfig['dashboard_img_default'] = $defaultImageId;
	// 	$imageConfig['dashboard_img_width'] = $width;
	// 	$imageConfig['dashboard_img_height'] = $height;
	// 	$imageConfig['dashboard_img_size_limit'] = (int) ($size_limit)? $size_limit: ini_get('upload_max_filesize');

	// 	// $imageFilenames = $this->getUserImageFiles();
		
	// 	// $imageOrignalPath = '';
		

	// 	// $imageOrignalPath = $imageFolder.DS.$filename;

	// 	// list($orignalWidth, $orignalHeight) = getimagesize(IMAGES.$imageOrignalPath);
		
	// 	// $imageConfig['orignal_width'] = $orignalWidth;
	// 	// $imageConfig['orignal_height'] = $orignalHeight;

	// 	return $imageConfig;

	// }

	// private function findExtension ($filename) {
	// 	$filename = strtolower($filename) ;
	// 	$exts = explode(".", $filename) ;
	// 	$n = count($exts)-1;
	// 	$exts = $exts[$n];
	// 	return $exts;
	// }

	// private function getUserImageFiles($folder=null) {
	// 	$filenames = array();
	// 	$path = IMAGES;

	// 	$path .= (is_null($folder))? $this->getValue('dashboard_img_folder'): $folder;

	// 	foreach (new DirectoryIterator($path) as $fileInfo){
	// 		if(!$fileInfo->isDot()){
	// 			if(!stristr($fileInfo->getFilename(), 'default')) {
	// 				$filenames[] = $fileInfo->getFilename();
	// 			}
	// 		}
	// 	}
	// 	return $filenames;
	// }

	// private function getDefaultImageFile($folder=null) {
	// 	$filename = '';
	// 	$path = IMAGES;

	// 	$path .= (is_null($folder))? $this->getValue('dashboard_img_folder'): $folder;

	// 	foreach (new DirectoryIterator($path) as $fileInfo){
	// 		if(!$fileInfo->isDot()){
	// 			if(stristr($fileInfo->getFilename(), 'default')) {
	// 				$filename = $fileInfo->getFilename();
	// 				break;
	// 			}
	// 		}
	// 	}
	// 	return $filename;
	// }

	// private function getUserImageFile($folder=null) {
	// 	$filenames = array();
	// 	$path = IMAGES;
	// 	$path .= (is_null($folder))? $this->getValue('dashboard_img_folder'): $folder;

	// 	foreach (new DirectoryIterator($path) as $fileInfo){
	// 		if(!$fileInfo->isDot()){
	// 			if(!stristr($fileInfo->getFilename(), 'default')) {
	// 				$filenames[] = $fileInfo->getFilename();
	// 			}
	// 		}
	// 	}


	// 	return $filenames;
	// }

	// private function emptyFolder($folder=null) {
	// 	$isFolderEmpty = false;
	// 	$path = IMAGES.$folder;

	// 	if(is_null($folder)){
	// 		return $isFolderEmpty;
	// 	}
		
	// 	$filenames = $this->getUserImageFiles($folder);

	// 	if(count($filenames) < 0){
	// 		$isFolderEmpty = true;
	// 	}else{

	// 		foreach ($filenames as $filename){
	// 			unlink($path.DS.$filename);
	// 		}

	// 		$checkFiles = new DirectoryIterator($path);

	// 		if(count($checkFiles) < 1){
	// 			$isFolderEmpty = true;
	// 		}
			
	// 	}

	// 	return $isFolderEmpty;
	// }

	// private function getCoordinates($filename=null) {
	// 	$coordinates = array('x' => 0, 'y' => 0);
	// 	if(is_null($filename)){
	// 		return false;
	// 	}

	// 	if(empty($filename)){
	// 		$coordinates['x'] = 0;
	// 		$coordinates['y'] = 0;
	// 		return $coordinates;
	// 	}

	// 	$fileExtension = $this->findExtension($filename);
	// 	$imageName = str_ireplace('.'.$fileExtension, '', $filename);
	// 	$timestamp = '';
	// 	$x = 0;
	// 	$y = 0;

	// 	$filenameSections = explode('_', $imageName);
	// 	if(count($filenameSections)>0){
	// 		$timestamp = array_shift($filenameSections);
	// 	}
	// 	if(count($filenameSections)>0){
	// 		$x = array_shift($filenameSections);
	// 	}
	// 	if(count($filenameSections)>0){
	// 		$y = array_shift($filenameSections);
	// 	}

	// 	$coordinates['x'] = $x;
	// 	$coordinates['y'] = $y;

	// 	return $coordinates;

	// }
	
	// public function getAllCustomValidation() {
	// 	$data = $this->findAllByType('custom validation');
	// 	$newArr = array();
	// 	foreach($data as $arrVal){
	// 		if($arrVal['ConfigItem']['value'] != '')
	// 		$newArr[$arrVal['ConfigItem']['name']] = str_replace("[a[\\-]zA[\\-]Z]", "[a-zA-Z]",str_replace (array("N","A","_", " ", "(", ")", "-"), array("\\d","[a-zA-Z]", "[_]", "\\s", "[(]", "[)]", "[\\-]"), $arrVal['ConfigItem']['value']));
	// 	}
	// 	return $newArr;
	// }
	
	// public function getAllLDAPConfig() {
	// 	$tmp = array();
	// 	$data = $this->findAllByType('LDAP Configuration');

	// 	foreach($data as $k => $arrV){
	// 		foreach($arrV as $arrVal){
	// 			$tmp[$arrVal['name']] = ($arrVal['value'] != '')?$arrVal['value']:$arrVal['default_value'];
	// 		}
	// 	}
	// 	return $tmp;
	// }
}
