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

	public static function compareWithInstitutionDateOpened($field, array $globalData) {
		$model = $globalData['providers']['table'];
		try {
			$startDate = new DateTime($field);
		} catch (Exception $e) {
		    return $model->getMessage('general.invalidDate');
		}
		if ($model->institutionId) {
			$Institution = TableRegistry::get('Institution.Institutions');
			$institution = $Institution->find()->where([$Institution->aliasField($Institution->primaryKey()) => $model->institutionId])->first();
			return $startDate >= $institution->date_opened;
		} else {
		    return $model->getMessage('Institution.Institutions.noActiveInstitution');
		}
	}

	/**
	 * Check if user input for date is valid
	 * @param  [type] $field      [description]
	 * @param  [type] $globalData [description]
	 * @return [type]             [description]
	 */
	public static function checkDateInput($field, array $globalData) {
		$model = $globalData['providers']['table'];
		try {
			$field = new DateTime($field);
			return true;
		} catch (Exception $e) {
		    return $model->getMessage('general.invalidDate');
		}
	}

	/**
	 * check the existence of AM / PM in a time field
	 * @param  string $field      The field value
	 * @param  array  $globalData [description]
	 * @return mixed              Boolean or String
	 */
	public static function amPmValue($field, array $globalData) {
		$model = $globalData['providers']['table'];
		$explode = explode(' ', $field);
		if (isset($explode[1])) {
			if (!in_array($explode[1], ['am', 'AM', 'pm', 'PM'])) {
			    return $model->getMessage('general.invalidTime');
			} else {
				return true;
			}
		} else {
		    return $model->getMessage('general.invalidTime');
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
											->where([
												$fieldName => 1,
												$newEntity->aliasField('id').' IS NOT ' => $globalData['data']['id']
											]);

				if(!empty($additionalParameters)) {
					$recordWithField->andWhere($additionalParameters);
				}								
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

	/**
	 * To check whether given input is within given start and end dates
	 * @param  mixed   	$field        			current field value
	 * @param  mixed   	$start_date       start date field value
	 * @param  mixed   	$end_date        end date field value
	 */
	public static function checkInputWithinRange($field, $field_name, $start_date, $end_date) {
		$type = self::_getFieldType($field_name);
		try {
			$givenDate = new DateTime($field);
			$startDate = new DateTime($start_date);
			$endDate = new DateTime($end_date);
		} catch (Exception $e) {
		    return __('Please input a proper '.$type);
		}

		if($givenDate > $startDate && $givenDate < $endDate) {
			return true;
		} else {
			return __(Inflector::humanize($field_name)).' is not within date range of '.$start_date.' and '.$end_date;
		}
	}

	// Return false if not enrolled in other education system
	public static function checkEnrolledInOtherInstitution($field, array $globalData) {
		$Students = TableRegistry::get('Institution.Students');
		$enrolled = false;
		if (!empty($globalData['data']['academic_period_id'])) {
			$educationSystemId = TableRegistry::get('Education.EducationGrades')->getEducationSystemId($globalData['data']['education_grade_id']);
			$enrolled = $Students->checkIfEnrolledInAllInstitution($globalData['data']['student_id'], $globalData['data']['academic_period_id'], $educationSystemId);
		}
		return !$enrolled;
	}                                                                                                                                                                 

	public static function institutionStudentId($field, array $globalData) {
		$Students = TableRegistry::get('Institution.Students');
		$existingRecords = 0;

		// Added the check for academic_period_id as the academic period id is possible to be all disabled 
		// due to no programme found
		if (!empty($globalData['data']['academic_period_id'])) {
			$existingRecords = $Students->find()
				->where(
					[
						$Students->aliasField('academic_period_id') => $globalData['data']['academic_period_id'],
						$Students->aliasField('education_grade_id') => $globalData['data']['education_grade_id'],
						$Students->aliasField('institution_id') => $globalData['data']['institution_id'],
						$Students->aliasField('student_id') => $globalData['data']['student_id']
					]
					
				)
				->count();
				;
		}
		return ($existingRecords <= 0);
	}

	public static function institutionStaffId($field, array $globalData) {
		$Staff = TableRegistry::get('Institution.Staff');

		$existingRecords = $Staff->find()
			->where(
				[
					$Staff->aliasField('institution_site_position_id') => $globalData['data']['institution_site_position_id'],
					$Staff->aliasField('institution_site_id') => $globalData['data']['institution_site_id'],
					$Staff->aliasField('security_user_id') => $globalData['data']['security_user_id']
				]
				
			)
			->count();
			;
		return ($existingRecords <= 0);
	}

	public static function studentGuardianId($field, array $globalData) {
		$Guardians = TableRegistry::get('Student.Guardians');

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

	// To allow case sensitive entry
	public static function checkUniqueEnglishField($check) {
		$englishField = trim($check);
		$Translation = TableRegistry::get('Localization.Translations');
      	$count = $Translation->find()
      		->where(['Binary('.$Translation->aliasField('en').')' => $englishField])
      		->count();
        return $count==0;
    }

	public static function inAcademicPeriod($field, $academicFieldName, $globalData) {
		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodObj = $AcademicPeriods
				->findById($globalData['data'][$academicFieldName])
				->first();
		$startDate = strtotime($globalData['data']['start_date']);
		$endDate = strtotime($globalData['data']['end_date']);

		if (!empty($periodObj)) {
			$academicPeriodStartDate = (!is_null($periodObj['start_date']))? $periodObj['start_date']->toUnixString(): null;
			$academicPeriodEndDate = (!is_null($periodObj['end_date']))? $periodObj['end_date']->toUnixString(): null;


			$rangecheck = ($startDate >= $academicPeriodStartDate) && 
			(is_null($academicPeriodEndDate) ||
				(!is_null($academicPeriodEndDate) && ($endDate <= $academicPeriodEndDate))
			)
			;
			return $rangecheck;
		}

		return false;
	}

	public static function noOverlappingAbsenceDate($field, $SearchTable, array $globalData) {
		if ($globalData['data']['start_date'] instanceof Time) {
			$startDate = $globalData['data']['start_date']->format('Y-m-d');
		} else {
			$startDate = date('Y-m-d', strtotime($globalData['data']['start_date']));
		}
		if ($globalData['data']['end_date'] instanceof Time) {
			$endDate = $globalData['data']['end_date']->format('Y-m-d');
		} else {
			$endDate = date('Y-m-d', strtotime($globalData['data']['end_date']));
		}
		$security_user_id = $globalData['data']['security_user_id'];
		$institution_site_id = $globalData['data']['institution_site_id'];

		// this will assome there will be start date and end date and security_user_id and academic period
		$overlapDateCondition = [];
		$overlapDateCondition['OR'] = [
			'OR' => [
				[
					$SearchTable->aliasField('end_date') . ' IS NOT NULL',
					$SearchTable->aliasField('start_date') . ' <=' => $startDate,
					$SearchTable->aliasField('end_date') . ' >=' => $startDate
				],
				[
					$SearchTable->aliasField('end_date') . ' IS NOT NULL',
					$SearchTable->aliasField('start_date') . ' <=' => $endDate,
					$SearchTable->aliasField('end_date') . ' >=' => $endDate
				],
				[
					$SearchTable->aliasField('end_date') . ' IS NOT NULL',
					$SearchTable->aliasField('start_date') . ' >=' => $startDate,
					$SearchTable->aliasField('end_date') . ' <=' => $endDate
				]
			],
			[
				$SearchTable->aliasField('end_date') . ' IS NULL',
				$SearchTable->aliasField('start_date') . ' <=' => $endDate
			]
		];

		$timeConditions = [];
		if (!$globalData['data']['full_day']) {
			$startTime = $globalData['data']['start_time'];
			$endTime = $globalData['data']['end_time'];

			$timeConditions['OR'] = [
				'OR' => [
					[	
						$SearchTable->aliasField('start_time') . ' <=' => $startTime,
						$SearchTable->aliasField('end_time') . ' >=' => $startTime,
					],
					[
						$SearchTable->aliasField('start_time') . ' <=' => $endTime,
						$SearchTable->aliasField('end_time') . ' >=' => $endTime,
					],
					[
						$SearchTable->aliasField('start_time') . ' >=' => $startTime,
						$SearchTable->aliasField('end_time') . ' <=' => $endTime,
					],
					[
						// means full day
						$SearchTable->aliasField('start_time') . ' IS NULL',	
						$SearchTable->aliasField('end_time') . ' IS NULL',
					]
				]
			];
		}

		// need to check for overlap time
		$found = $SearchTable->find()
			->where($overlapDateCondition)
			->where([$SearchTable->aliasField('security_user_id') => $security_user_id])
			->where([$SearchTable->aliasField('institution_site_id') => $institution_site_id])
			;
			// ->toArray();

		if (!empty($timeConditions)) {
			$found->where($timeConditions);
		}

		if (array_key_exists('id', $globalData['data']) && !empty($globalData['data']['id'])) {
			$found->where([$SearchTable->aliasField('id').' != ' => $globalData['data']['id']]);
		}

		$found = $found->count();
			// ->sql();
			// return false;
		// pr($found == 0);
		return ($found == 0);
	}

	public static function checkFTE($field, array $globalData) {
		if (!empty($globalData['data']['start_date'])) {
			$date = new DateTime($globalData['data']['start_date']);
			$startDate = date_format($date, 'Y-m-d');
		} else {
			$startDate = null;
		}

		if (!empty($globalData['data']['end_date'])) {
			$date = new DateTime($globalData['data']['end_date']);
			$endDate = date_format($date, 'Y-m-d');
		} else {
			$endDate = null;
		}


		$InstitutionStaff = TableRegistry::get('Institution.Staff');
		$identicalPositionHolders = $InstitutionStaff->find()
			->where(
				[
					$InstitutionStaff->aliasField('institution_site_position_id') => $globalData['data']['institution_site_position_id']
					
				]
			)
			;

		// no id this is NOT a add method
		if (array_key_exists('id', $globalData['data']) && !empty($globalData['data']['id'])) {
			$identicalPositionHolders->where([$InstitutionStaff->aliasField('id').' != '. $globalData['data']['id']]);
		}

		$dateCondition = [];
		// start and end date is of the new entry
		$dateCondition['OR'] = [];
		if (empty($endDate)) {
			// current position has no end date
			$dateCondition['OR'][] = 'end_date IS NULL';
			$dateCondition['OR'][] = [
				'end_date IS NOT NULL',
				'end_date >= ' => $startDate
			];
		} else {
			// current position HAS end date
			$dateCondition['OR'][] = [
				'end_date IS NULL',
				'start_date'.' <= ' => $endDate
			];
			$dateCondition['OR']['OR'] = [];
			$dateCondition['OR']['OR'][] = ['start_date' . ' >= ' => $startDate, 'start_date' . ' <= ' => $endDate];
			$dateCondition['OR']['OR'][] = ['end_date' . ' >= ' => $startDate, 'end_date' . ' <= ' => $endDate];
			$dateCondition['OR']['OR'][] = ['start_date' . ' <= ' => $startDate, 'end_date' . ' >= ' => $endDate];
		}

		$identicalPositionHolders->where($dateCondition);

		$FTEused = 0;
		if ($identicalPositionHolders->count()>0) {
			// need to tally all the FTE
			foreach ($identicalPositionHolders->toArray() as $key => $value) {
				$FTEused += $value->FTE;
			}
		}

		$validationResult = (($FTEused+$globalData['data']['FTE']) <= 1);
		// got id this is a add method
		if (!(array_key_exists('id', $globalData['data']) && !empty($globalData['data']['id'])) && (!$validationResult)) {
			$model = $globalData['providers']['table'];
			$model->Alert->error(__('No available FTE.'), ['type' => 'text']);
		}

		return $validationResult;
	}

}
