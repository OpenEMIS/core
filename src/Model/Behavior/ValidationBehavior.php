<?php
namespace App\Model\Behavior;

use DateTime;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use App\Model\Traits\MessagesTrait;

class ValidationBehavior extends Behavior {
	use MessagesTrait;

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
					$ruleAttr['message'] = $this->getMessage(implode('.', [$this->_table->registryAlias(), $field, $ruleName]));
				}
				if (method_exists($this, $ruleAttr['rule'])) {
					$ruleAttr['provider'] = 'custom';
				}
				$set->add($ruleName, $ruleAttr);
			}
		}
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
		try {
			$endDate = new DateTime($field);
		} catch (Exception $e) {
		    return __('Please input a proper date');
		}
		if($compareField) {
			$options = ['equals' => $equals, 'reverse' => true];
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
		try {
			$startDate = new DateTime($field);
		} catch (Exception $e) {
		    return 'Please input a proper date';
		}
		if($compareField) {
			$options = ['equals' => $equals, 'reverse' => false];
			$result = self::doCompareDates($startDate, $compareField, $options, $globalData);
			if (!is_bool($result)) {
				return $result;
			} else {
				return (!$result) ? Inflector::humanize($compareField).' should be on a later date' : true;
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

	public static function checkSelectedFileAsImage($field, array $globalData) {
		$isValid = true;
		$fileImagesMap = array(
			'jpeg'	=> 'image/jpeg',
			'jpg'	=> 'image/jpeg',
			'gif'	=> 'image/gif',
			'png'	=> 'image/png'
			// 'jpeg'=>'image/pjpeg',
			// 'jpeg'=>'image/x-png'
		);

		if(isset($field['type']) && !in_array($field['type'], $fileImagesMap)){
			$isValid = false;
		} 
		return $isValid;
	}

	public static function checkIfImageExceedsUploadSize($field, array $globalData) {
		$isValid = true;
		$restrictedSize = 2000000; //2MB in bytes

		 if(isset($field['type']) && ($field['size'] > $restrictedSize)){
		 	$isValid = false;
		 }

		return $isValid;
	}


}
