<?php 
namespace App\Model\Validation;

use DateTime;
use Exception;
use Cake\I18n\Time;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Validation\ValidationSet;
use App\Model\Traits\AppValidatorTrait;
use Cake\Controller\Component\AuthComponent;

class AppValidator extends Validator {
	use AppValidatorTrait;

    // private $_modelAlias = '';
    // private $_data = [];

	public function __construct()
    {
    	parent::__construct();
    }

    public function checkLongitude($check, array $globalData){

        $isValid = false;
        $longitude = trim($check);

        if(is_numeric($longitude) && floatval($longitude) >= -180.00 && floatval($longitude <= 180.00)){
            $isValid = true;
        }
        return $isValid;
    }

    public function checkLatitude($check, array $globalData){

        $isValid = false;
        $latitude = trim($check);

        if(is_numeric($latitude) && floatval($latitude) >= -90.00 && floatval($latitude <= 90.00)){
            $isValid = true;
        }
        return $isValid;
    }

	/**
	 * To check start date is earlier than end date from start date field
	 * @param  mixed   $field        current field value
	 * @param  string  $compareField name of the field to compare
	 * @param  boolean $equals       whether the equals sign should be included in the comparison
	 * @param  array   $globalData   "huge global data". This array consists of
	 *                               - newRecord [boolean]: states whether the given record is a new record
	 *                               - data 	 [array]  : the model's fields values
	 *                               - field 	 [string] : current field name
	 *                               - providers [object] : consists of provider objects and the current table object
	 * 
	 * @return mixed                 returns true if validation passed or the error message if it fails
	 */
	public function compareDate($field, $compareField, $equals, array $globalData) {
		try {
			$startDate = new DateTime($field);
		} catch (Exception $e) {
		    return 'Please input a proper date';
		}
		if($compareField) {
			$options = ['equals' => $equals, 'reverse' => false];
			$result = $this->doCompareDates($startDate, $compareField, $options, $globalData);
			if (!is_bool($result)) {
				return $result;
			} else {
				return (!$result) ? Inflector::humanize($compareField).' should be on a later date' : true;
			}
		} else {
			return true;
		}
	}

	public function validatePreferred($field, array $globalData) {
		$flag = false;
		// foreach ($check1 as $key => $value1) {
			$preferred = $field;
			$contactOption = $globalData['data']['contact_option_id'];
			if ($preferred == "0" && $contactOption != "5") {
				if (!$globalData['newRecord']) {
					// todo:mlee: not converted yet
					$contactId = $globalData['data']['id'];
					$count = $this->find('count', array('conditions' => array('ContactType.contact_option_id' => $contactOption, array('NOT' => array('StaffContact.id' => array($contactId))))));
					if ($count != 0) {
						$flag = true;
					}
				} else {
					$query = $model->find();
					$query->matching('ContactTypes', function ($q) {
						return $q->where(['ContactTypes.contact_option_id' => $contactOption]);
					});
					$count = $query->count();

					if ($count != 0) {
						$flag = true;
					}
				}
			} else {
				$flag = true;
			}
		// }
		return $flag;
	}

	/**
	 * To check end date is later than start date from end date field
	 * @param  mixed   $field        current field value
	 * @param  string  $compareField name of the field to compare
	 * @param  boolean $equals       whether the equals sign should be included in the comparison
	 * @param  array   $globalData   "huge global data". This array consists of
	 *                               - newRecord [boolean]: states whether the given record is a new record
	 *                               - data 	 [array]  : the model's fields values
	 *                               - field 	 [string] : current field name
	 *                               - providers [object] : consists of provider objects and the current table object
	 * 
	 * @return [type]                [description]
	 */
	public function compareDateReverse($field, $compareField, $equals, array $globalData) {
		try {
			$endDate = new DateTime($field);
		} catch (Exception $e) {
		    return 'Please input a proper date';
		}
		if($compareField) {
			$options = ['equals' => $equals, 'reverse' => true];
			$result = $this->doCompareDates($endDate, $compareField, $options, $globalData);
			if (!is_bool($result)) {
				return $result;
			} else {
				return (!$result) ? Inflector::humanize($compareField).' should be on an earlier date' : true;
			}
		} else {
			return true;
		}
	}

	/**
	 * [doCompareDates description]
	 * @param  [type] $dateOne      [description]
	 * @param  [type] $compareField [description]
	 * @param  [type] $options      [description]
	 * @return [type]               [description]
	 */
	protected function doCompareDates($dateOne, $compareField, $options, $globalData) {
		$equals = $options['equals'];
		$reverse = $options['reverse'];
		$dateTwo = $globalData['data'][$compareField];
		try {
			$dateTwo = new DateTime($dateTwo);
		} catch (Exception $e) {
			return 'Please input a proper date for '.(ucwords(str_replace('_', ' ', $compareField)));
		}
		if($equals) {
			if ($reverse) {
				return $dateOne >= $dateTwo;
			} else {
				return $dateTwo >= $dateOne;
			}
		} else {
			if ($reverse) {
				return $dateOne > $dateTwo;
			} else {
				return $dateTwo > $dateOne;
			}
		}
	}

	/**
	 * created this function to overwrite Validator.php notEmpty.
	 * seems like that file has a bug?
	 * @param  [type]  $field   [description]
	 * @param  [type]  $message [description]
	 * @param  boolean $when    [description]
	 * @return [type]           [description]
	 */
    public function notEmpty($field, $message = null, $when = false)
    {
    	if ($this->hasField($field)) {
	    	$this->field($field)->isEmptyAllowed($when);
	        if ($message) {
	            $this->_allowEmptyMessages[$field] = $message;
	        }
	        return $this;
	    } else {
	    	return $this->notBlank($field);
	    }
    }

	public function changePassword($field, $allowChangeAll, array $array) {
		$model = $array['providers']['table'];
		$username = array_key_exists('username', $array['data']) ? $array['data']['username'] : null;
		$password = array_key_exists('password', $array['data']) ? $array['data']['password'] : null;

		if (!$allowChangeAll) {
			if (AuthComponent::user('id') != $array['data']['id']) {
				die('illegal cp');
			}
		}
		$password = AuthComponent::password($password);
		$count = $model->find('count', array('recursive' => -1, 'conditions' => array('username' => $username, 'password' => $password)));
		return $count==1;
	}	

	public function checkDateInput($field) {
		try {
		    $date = new DateTime($field);
		} catch (Exception $e) {
		    return 'Please input a proper date';
		}
		return true;		
	}

	/**
	 * Checks that input has no number
	 * @param  [type] $check [description]
	 * @return [type]        [description]
	 */
	public function checkIfStringGotNoNumber($check, array $globalData) {
		return !preg_match('#[0-9]#',$check);
	}

	/********************shiva added*******************************/

	/**
	* Checks whether uploaded image exceeds size
	* @param  [type] $field [description]
	*/
	// public function checkIfImageExceedsUploadSize($field) {
	// 	$fileTypesMap = array(
	// 		'jpeg'	=> 'image/jpeg',
	// 		'jpg'	=> 'image/jpeg',
	// 		'gif'	=> 'image/gif',
	// 		'png'	=> 'image/png',
	// 	);
	// 	$restrictedSize = 2000000; //2MB in bytes
	// 	$errMsg = array();
		
	// 	 if(isset($field['type']) && in_array($field['type'], $fileTypesMap)){
	// 	 	$errMsg[] = "Uploaded file is not of type image.";	
	// 	 } 

	// 	 if(isset($field['type']) && ($field['size'] > $restrictedSize)){
	// 	 	$errMsg[] = "Uploaded file exceeds 2MB in size.";	
	// 	 }

	// 	return $errMsg;
	// }

	/***************************************************/

	// public function testerCheckWithParms($check, $second, $third, array $array) {
	// 	if ($check == $second) {
	// 		return true;
	// 	} else {
 //            return $this->newGetMessage(
 //            	[
 //            		'model'=>$array['providers']['table']->alias(),
 //            		'field'=>$array['field'],
 //            		'rule'=>'testerCheckWithParms'
 //            	]
 //            );
	// 	}
	// }

	// public function testerCheck($check, array $array) {
	// 	if ($check === 'sss') {
	// 		return true;
	// 	} else {
 //            return $this->newGetMessage(
 //            	[
 //            		'model'=>$array['providers']['table']->alias(),
 //            		'field'=>$array['field'],
 //            		'rule'=>'testerCheck'
 //            	]
 //            );
	// 	}
	// }

	// public function newGetMessage($params=[]) {
	// 	if ( array_key_exists('model', $params) && array_key_exists('field', $params) && array_key_exists('rule', $params) ) {
	// 		if ( $params['model']!='' && $params['field']!='' && $params['rule']!='' ) {
	// 			$message = $this->validationMessages;
	// 			if (isset($message[$params['model']][$params['field']][$params['rule']])) {
	// 				return $message[$params['model']][$params['field']][$params['rule']];
	// 			} else {
	// 				return '[Message Not Found]';
	// 			}
	// 		}
	// 		return 'Your validation function has empty parameter(s)';
	// 	}
	// 	return 'Your validation function has missing parameter(s)';
	// }

	// public $validationMessages = array(
	// 	'general' => array(
	// 		'name' => array('ruleRequired' => 'Please enter a name'),
	// 		'code' => array(
	// 			'ruleRequired' => 'Please enter a code',
	// 			'ruleUnique' => 'Please enter a unique code'
	// 		),
	// 		'title' => array('ruleRequired' => 'Please enter a title'),
	// 		'address' => array(
	// 			'ruleRequired' => 'Please enter a valid Address',
	// 			'ruleMaximum255' => 'Please enter an address within 255 characters'
	// 		),
	// 		'postal_code' => array('ruleRequired' => 'Please enter a Postal Code'),
	// 		'email' => array('ruleRequired' => 'Please enter a valid Email'),
	// 	),

	// 	'Institutions' => array(

	// 		/* For testing */
	// 		'contact_person' => [
	// 			'testerCheck' => 'Value should be triple "s"'
	// 		],
	// 		'telephone' => [
	// 			'testerCheckWithParms' => 'Test with Parameters'
	// 		],
	// 		/* End For testing */

	// 		'institution_site_locality_id' => array(
	// 			'ruleRequired' => 'Please select a Locality'
	// 		),
	// 		'institution_site_status_id' => array(
	// 			'ruleRequired' => 'Please select a Status'
	// 		),
	// 		'institution_site_type_id' => array(
	// 			'ruleRequired' => 'Please select a Type'
	// 		),
	// 		'institution_site_ownership_id' => array(
	// 			'ruleRequired' => 'Please select an Ownership'
	// 		),
	// 		'area_id_select' => array(
	// 			'ruleRequired' => 'Please select a valid Area'
	// 		),
	// 		'date_opened' => array(
	// 			'ruleRequired' => 'Please select the Date Opened',
	// 			'ruleCompare' => 'Please select the Date Opened'
	// 		),
	// 		'date_closed' => array(
	// 			'ruleCompare' => 'Date Closed cannot be earlier than Date Opened'
	// 		),
	// 		'longitude' => array(
	// 			'ruleLongitude' => 'Please enter a valid Longitude'
	// 		),
	// 		'latitude' => array(
	// 			'ruleLatitude' => 'Please enter a valid Latitude'
	// 		),
	// 		'institution_site_provider_id' => array(
	// 			'ruleRequired' => 'Please select a Provider'
	// 		),
	// 		'institution_site_sector_id' => array(
	// 			'ruleRequired' => 'Please select a Sector'
	// 		),
	// 		'institution_site_gender_id' => array(
	// 			'ruleRequired' => 'Please select a Gender'
	// 		)
	// 	)

	// );

	// public function loadValidationMessages($model) {
	// 	$alias = $model->alias;

	// 	if (!empty($model->validate)) {
	// 		foreach ($model->validate as $field => $rules) {
	// 			foreach ($rules as $rule => $attr) {

	// 				// need to check if $attr is an array, else InstitutionSites/dashboard will have errors
	// 				if (is_array($attr) && !isset($attr['message'])) {
	// 					$code = $model->alias . '.' . $field . '.' . $rule;
	// 					if (isset($attr['messageCode'])) {
	// 						$code = $attr['messageCode'] . '.' . $field . '.' . $rule;
	// 					}
	// 					$message = $this->get($code);
	// 					$model->validate[$field][$rule]['message'] = $message;
	// 				}
	// 			}
	// 		}
	// 	}
	// }

	// public function get($code) {
	// 	$index = explode('.', $code);
	// 	$message = $this->validationMessages;
		
	// 	foreach ($index as $i) {
	// 		if (isset($message[$i])) {
	// 			$message = $message[$i];
	// 		} else {
	// 			$message = '[Message Not Found]';
	// 			break;
	// 		}
	// 	}
	// 	return !is_array($message) ? __($message) : $message;
	// }

	// public function getValidationMessage(Model $model, $code) {
	// 	return $this->get($code);
	// }

}
