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

		$this->ControllerAction->field('format', ['type' => 'custom_notes', 'visible' => false]);
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
		$buffer = $typeOptions;
		foreach ($buffer as $key => $value) {
			$result = $this->find()->where([$this->aliasField('type') => $value['text'], $this->aliasField('visible') => 1])->count();
			if (!$result) {
				unset($typeOptions[$key]);
			}
		}
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

		$pass = $this->request->param('pass');
		if (is_array($pass) && !empty($pass)) {
			$id = $pass[0];
			$entity = $this->get($id);
		}
		if (isset($entity)) {
			/**
			 * grab validation rules by either record code or record type
			 */
			$validationRules = 'validate' . Inflector::camelize($entity->code);
			if (isset($this->$validationRules)) {
				$this->validator()->add('value', $this->$validationRules);
			} else {
				$validationRules = 'validate' . Inflector::camelize($entity->type);
				if (isset($this->$validationRules)) {
					$this->validator()->add('value', $this->$validationRules);
				}
			}

			if ($entity->type == 'Custom Validation') {
				$this->fields['format']['visible'] = true;
				$this->fields['format']['value'] = '<ul><li>9 (Numbers)</li><li>a (Letter)</li><li>w (Alphanumeric)</li><li>* (Any Character)</li><li>? (Optional - any characters following will become optional)</li></ul>';

				$this->fields['value']['attr']['onkeypress'] = 'return Config.inputMaskCheck(event)';

			}
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (is_array($data[$this->alias()]['value'])) {
			if ($entity->code == 'student_prefix' || $entity->code == 'staff_prefix' || $entity->code == 'guardian_prefix') {
				$value = $data[$this->alias()]['value']['prefix'];
				if (isset($data[$this->alias()]['value']['enable'])) {
					$value .= ',1';
				} else {
					$value .= ',0';
				}
				$data[$this->alias()]['value'] = $value;
			}
		}
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
				} else {
					if ($entity->code == 'start_time') {
						$attr['type'] = 'time';
					} else if ($entity->code == 'hours_per_day' || $entity->code == 'days_per_week') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 1];
					} else if ($entity->type == 'Data Discrepancy') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 0, 'max' => 100];
					} else if ($entity->type == 'Data Outliers') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 1, 'max' => 100];
					} else if ($entity->type == 'Student Admission Age') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 1, 'max' => 100];
					} else if ($entity->code == 'no_of_shifts') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 1, 'max' => 10];
					} else if ($entity->code == 'training_credit_hour') {
						$attr['type'] = 'integer';
						$attr['attr'] = ['min' => 0];
					} else if ($entity->code == 'student_prefix' || $entity->code == 'staff_prefix' || $entity->code == 'guardian_prefix') {
						$attr['type'] = 'element';
						$attr['element'] = 'Configurations/with_prefix';
						$attr['data'] = [];
					}
				}
			}
		}
		return $attr;
	}

	public function onGetValue(Event $event, Entity $entity) {
		if ($entity->type == 'Custom Validation') {
			$attr['type'] = 'string';
			$event->subject()->HtmlField->includes['configItems'] = [
				'include' => true,
				'js' => [
					'config'
				]
			];
		}
		return $this->recordValueForView('value', $entity);
	}

	public function onGetDefaultValue(Event $event, Entity $entity) {
		return $this->recordValueForView('default_value', $entity);
	}

    public function onGetCustomNotesElement(Event $event, $action, $entity, $attr, $options=[]) {
		$fieldLabel = Inflector::humanize($attr['field']);
		if (array_key_exists('label', $attr)) {
			$fieldName = $attr['label'];
		}
		$fieldName = strtolower($attr['model'] . '-' . $attr['field']);
		if (array_key_exists('fieldName', $attr)) {
			$fieldName = $attr['fieldName'];
		}
		if (!array_key_exists('value', $attr)) {
			$attr['value'] = '* Please set the note in your model *';
		}
		$value = '<div class="input text"><label for="'.$fieldName.'">'.$fieldLabel.'</label><div class="button-label" style="width: 65%;">'.$attr['value'].'</div></div>';
		return $value;
    }


/******************************************************************************************************************
**
** essential methods
**
******************************************************************************************************************/
	private function recordValueForView($valueField, $entity) {
		if ($entity->field_type == 'Dropdown') {

			$exp = explode(':', $entity->option_type);
			/**
			 * if options list is from a specific table
			 */
			if (count($exp)>0 && $exp[0]=='database') {
				$model = Inflector::pluralize($exp[1]);
				$model = $this->getActualModeLocation($model);
				$optionsModel = TableRegistry::get($model);
				$value = $optionsModel->get($entity->$valueField);
				if (is_object($value)) {
					return $value->name;
				} else {
					return $entity->$valueField;
				}

			/**
			 * options list is from ConfigItemOptions table
			 */
			} else {
				$optionsModel = TableRegistry::get('ConfigItemOptions');
				$value = $optionsModel->find()
					->where([
						'ConfigItemOptions.option_type' => $entity->option_type,
						'ConfigItemOptions.value' => $entity->$valueField,
					])
					->first();
				if (is_object($value)) {
					if ($entity->code == 'time_format' || $entity->code == 'date_format') {
						return date($value->$valueField);
					} else {
						return $value->option;
					}
				} else {
					return $entity->$valueField;
				}
			}

		} else if ($entity->code == 'student_prefix' || $entity->code == 'staff_prefix' || $entity->code == 'guardian_prefix') {
			$exp = explode(',', $entity->$valueField);
			if (!$exp[1]) {
				return __('Disabled');
			} else {
				return __('Enabled') . ' ('.$exp[0].')';
			}
		} else {
			if ($entity->code == 'time_format' || $entity->code == 'date_format') {
				return date($entity->$valueField);
			} else {
				return $entity->$valueField;
			}
		}
	}

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
** value field validation rules based on specific codes
** refer to editBeforeAction() on how these validation rules are loaded dynamically
**
******************************************************************************************************************/
	// private $validateSupportPhone = [
	// 		'url' => [
	// 			'rule'	=> ['checkLongitude'],
	// 			'provider' => 'table',
	// 		]
 //  		];
	   
	private $validateSupportEmail = [
			'email' => [
				'rule'	=> ['email'],
			]
  		];
	   
	private $validateWhereIsMySchoolStartLong = [
			'checkLongitude' => [
				'rule'	=> ['checkLongitude'],
				'provider' => 'table',
			]
  		];
	   
	private $validateWhereIsMySchoolStartLat = [
			'checkLatitude' => [
				'rule'	=> ['checkLatitude'],
				'provider' => 'table',
			]
  		];
	   
	private $validateWhereIsMySchoolStartRange = [
			'num' => [
				'rule'  => ['numeric'],
			],
  		];
	   
	private $validateSmsProviderUrl = [
			'url' => [
				'rule'	=> ['url', true],
				'message' => 'Please provide a valid URL with http:// or https://',
			]
  		];
	   
	private $validateWhereIsMySchoolUrl = [
			'url' => [
				'rule'	=> ['url', true],
				'message' => 'Please provide a valid URL with http:// or https://',
			]
  		];
	   
	private $validateStartTime = [
			'dateInput' => [
				'rule'	=> ['checkDateInput'],
				'provider' => 'table',
				'last' => true
			],
			'aPValue' => [
				'rule'	=> ['amPmValue'],
				'provider' => 'table',
				'last' => true
			]
  		];
	   
	private $validateLowestYear = [
			'num' => [
				'rule'  => ['numeric'],
				'message' => 'Please provide a valid year',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 1900, 9999],
				'message' => 'Please provide a valid year',
				'last' => true
			]
  		];
	   
	private $validateHoursPerDay = [
			'num' => [
				'rule'  => ['numeric'],
				'message' => 'Numeric Value should be between 0 to 25',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 1, 24],
				'message' => 'Numeric Value should be between 0 to 25',
				'last' => true
			]
  		];
	   
	private $validateDaysPerWeek = [
			'num' => [
				'rule'  => ['numeric'],
				'message' => 'Numeric Value should be between 0 to 8',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 1, 7],
				'message' => 'Numeric Value should be between 0 to 8',
				'last' => true
			]
  		];
	   
	private $validateReportDiscrepancyVariationpercent = [
			'num' => [
				'rule'  => 'numeric',
				'message' => 'Numeric Value should be between -1 to 101',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 0, 100],
				'message' => 'Numeric Value should be between -1 to 101',
				'last' => true
			]
  		];

  	private $validateDataOutliers = [
			'num' => [
				'rule'  => 'numeric',
				'message' => 'Numeric Value should be between 0 to 101',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 1, 100],
				'message' => 'Numeric Value should be between 0 to 101',
				'last' => true
			]
	  	];

  	private $validateStudentAdmissionAge = [
			'num' => [
				'rule'  => 'numeric',
				'message' => 'Numeric Value should be between 0 to 101',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 1, 100],
				'message' => 'Numeric Value should be between 0 to 101',
				'last' => true
			]
	  	];

	private $validateNoOfShifts = [
			'num' => [
				'rule'  => 'numeric',
				'message' => 'Numeric Value should be between 0 to 11',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 1, 10],
				'message' => 'Numeric Value should be between 0 to 11',
				'last' => true
			]
		];

  	private $validateTrainingCreditHour = [
  			'num' => [
				'rule'  => 'numeric',
	  			'message' => 'Value should be numeric',
  			]
  		];
  
	private $validateSmsRetryTime = [
			'num' => [
				'rule'  => 'numeric',
				'message' =>  'Numeric Value should be between 0 to 11',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 1, 10],
				'message' => 'Numeric Value should be between 0 to 11',
				'last' => true
			]
		];

  	private $validateSmsRetryWait = [
			'num' => [
				'rule'  => 'numeric',
				'message' =>  'Numeric Value should be between 0 to 61',
				'last' => true
			],
			'bet' => [
				'rule'	=> ['range', 1, 60],
				'message' => 'Numeric Value should be between 0 to 61',
				'last' => true
			]
		];


/******************************************************************************************************************
**
** version 2 codes
**
******************************************************************************************************************/
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
