<?php
namespace App\Model\Behavior;

use DateTime;
use Exception;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Behavior;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Traits\MessagesTrait;

class ValidationBehavior extends Behavior {
	use MessagesTrait;

	private $validationCode = [];

	public function buildValidator(Event $event, Validator $validator, $name) {
		$properties = ['rule', 'on', 'last', 'message', 'provider', 'pass'];
		$validator->provider('custom', get_class($this));

		foreach ($validator as $field => $set) {
			foreach ($set as $ruleName => $rule) {
				$ruleAttr = [];
				foreach ($properties as $prop) {
					$ruleAttr[$prop] = $rule->get($prop);
				}
				if (empty($ruleAttr['message'])) {
					$code = implode('.', [$this->_table->registryAlias(), $field, $ruleName]);
					if (array_key_exists($code, $this->validationCode)) {
						$code = $this->validationCode[$code];
					}
					$ruleAttr['message'] = $this->getMessage($code);
				}
				if (method_exists($this, $ruleAttr['rule'])) {
					$ruleAttr['provider'] = 'custom';
				}
				$set->add($ruleName, $ruleAttr);
			}
		}
	}

	public function setValidationCode($key, $code) {
		$alias = $this->_table->registryAlias() . '.' . $key;
		$this->validationCode[$alias] = $code . '.' . $key;
	}

    private static function _getFieldType($compareField) {
    	$type = explode('_', $compareField);
		$count = count($type);
		return $type[($count - 1)];
    }

	public static function checkLongitude($check) {

        $isValid = false;
        $longitude = trim($check);

        if(is_numeric($longitude) && floatval($longitude) >= -180.00 && floatval($longitude <= 180.00)){
            $isValid = true;
        }
        return $isValid;
    }

    public static function checkLatitude($check) {

        $isValid = false;
        $latitude = trim($check);

        if(is_numeric($latitude) && floatval($latitude) >= -90.00 && floatval($latitude <= 90.00)){
            $isValid = true;
        }
        return $isValid;
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

	public static function compareDateReverse($field, $compareField, $equals, array $globalData) {
		$type = self::_getFieldType($compareField);
		try {
			$endDate = new DateTime($field);
		} catch (Exception $e) {
		    return __('Please input a proper '.$type);
		}
		if($compareField) {
			$options = ['equals' => $equals, 'reverse' => true, 'type' => $type];
			$result = self::doCompareDates($endDate, $compareField, $options, $globalData);
			return $result;
		} else {
			return true;
		}
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
	public static function compareDate($field, $compareField, $equals, array $globalData) {
		$type = self::_getFieldType($compareField);
		try {
			$startDate = new DateTime($field);
		} catch (Exception $e) {
		    return __('Please input a proper '.$type);
		}
		if($compareField) {
			$options = ['equals' => $equals, 'reverse' => false, 'type' => $type];
			$result = self::doCompareDates($startDate, $compareField, $options, $globalData);
			if (!is_bool($result)) {
				return $result;
			} else {
				return (!$result) ? __(Inflector::humanize($compareField).' should be on a later '.$type) : true;
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
	protected static function doCompareDates($dateOne, $compareField, $options, $globalData) {
		$type = $options['type'];
		$equals = $options['equals'];
		$reverse = $options['reverse'];
		$dateTwo = $globalData['data'][$compareField];
		try {
			$dateTwo = new DateTime($dateTwo);
		} catch (Exception $e) {
			return __('Please input a proper '.$type.' for '.(ucwords(str_replace('_', ' ', $compareField))));
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
	 * Check if user input for date is valid
	 * @param  [type] $field      [description]
	 * @param  [type] $globalData [description]
	 * @return [type]             [description]
	 */
	public static function checkDateInput($field, $globalData) {
		try {
			$field = new DateTime($field);
			return true;
		} catch (Exception $e) {
			return __('Please input a proper value');
		}
	}

	/**
	 * check the existence of AM / PM in a time field
	 * @param  string $field      The field value
	 * @param  array  $globalData [description]
	 * @return mixed              Boolean or String
	 */
	public static function amPmValue($field, $globalData) {
		$explode = explode(' ', $field);
		if (isset($explode[1])) {
			if (!in_array($explode[1], ['am', 'AM', 'pm', 'PM'])) {
				return __('Wrong time format');
			} else {
				return true;
			}
		} else {
			return __('Wrong time format');
		}
	}

	/**
	 * [checkIfStringGotNoNumber description]
	 * @param  [type] $check      [description]
	 * @param  array  $globalData [description]
	 * @return [type]             [description]
	 */
	public static function checkIfStringGotNoNumber($check, array $globalData) {
		return !preg_match('#[0-9]#',$check);
	}

	/**
	 * [validatePreferred description]
	 * @param  [type] $field      [description]
	 * @param  array  $globalData [description]
	 * @return [type]             [description]
	 */
	public static function validatePreferred($field, array $globalData) {
		$flag = false;
		$preferred = $field;
		$contactOption = $globalData['data']['contact_option_id'];
		$userId = $globalData['data']['security_user_id'];

		if ($preferred == "0" && $contactOption != "5") {
			$Contacts = TableRegistry::get('User.Contacts');
			$contactId = (array_key_exists('id', $globalData['data']))? $globalData['data']['id']: null;

			$query = $Contacts->find();
			$query->matching('ContactTypes', function ($q) use ($contactOption) {
				return $q->where(['ContactTypes.contact_option_id' => $contactOption]);
			});

			if (!empty($contactId)) {
				$query->where([$Contacts->aliasField($Contacts->primaryKey()) .'!='. $contactId]);
			}

			$query->where([$Contacts->aliasField('preferred') => 1]);
			$query->where([$Contacts->aliasField('security_user_id') => $userId]);
			$count = $query->count();

			if ($count != 0) {
				$flag = true;
			}
		} else {
			$flag = true;
		}
		return $flag;
	}

	public static function validateNeeded($field, $fieldName, array $additionalParameters, array $globalData) {
		$flag = false;

		if($field == "0"){
			$tableObj =  get_object_vars($globalData['providers']['table']);
			if(!empty($tableObj)) {
				$className = $tableObj['controller']->modelClass;
				$newEntity = TableRegistry::get($className);
				$recordWithField = $newEntity->find()
											->select([$fieldName])
											->where([$fieldName => 1]);

				if(!empty($additionalParameters))
					$recordWithField->andWhere($additionalParameters);
													
				$total = $recordWithField->count();				
				$flag = ($total > 0) ? true : false;
			}
		} else {
			$flag = true;
		}

		return $flag;
	}	

	public static function contactValueValidate($field, array $globalData) {
		$flag = false;
		$contactOption = $globalData['data']['contact_option_id'];

		return $flag;
	}

	public static function comparePasswords($field, $compareField, array $globalData) {
		$fieldOne = $globalData['data'][$globalData['field']];
		$fieldTwo = $globalData['data'][$compareField];
		if(strcmp($fieldOne, $fieldTwo) == 0 ) {
			return true;
		} else {
			return false;
		}
		
	}

	public static function institutionStudentId($field, array $globalData) {
		$Students = TableRegistry::get('Institution.Students');

		$existingRecords = $Students->find()
			->where(
				[
					[$Students->aliasField('academic_period_id') => $globalData['data']['academic_period_id']],
					[$Students->aliasField('education_grade_id') => $globalData['data']['education_grade_id']],
					[$Students->aliasField('institution_id') => $globalData['data']['institution_id']],
					[$Students->aliasField('student_id') => $globalData['data']['student_id']]
				]
				
			)
			->count();
			;
		return ($existingRecords <= 0);
	}

	public static function studentGuardianId($field, array $globalData) {
		$Guardians = TableRegistry::get('Student.Guardians');
// pr($globalData['data']);die;
		$existingRecords = $Guardians->find()
			->where(
				[
					$Guardians->aliasField('guardian_id') => $globalData['data']['guardian_id'],
					$Guardians->aliasField('student_id') => $globalData['data']['student_id']
				]
			)
			->count()
			;
		return $existingRecords <= 0;
	}

}
