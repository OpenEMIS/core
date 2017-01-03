<?php
namespace App\Model\Behavior;

use App\Model\Traits\MessagesTrait;
use Cake\Event\Event;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Session;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Validation\Validation;
use Cake\Validation\Validator;
use DateTime;

class ValidationBehavior extends Behavior {
	use MessagesTrait;

	private $validationCode = [];

	public function buildValidator(Event $event, Validator $validator, $name) {
		$properties = ['rule', 'on', 'last', 'message', 'provider', 'pass'];
		$validator->provider('custom', get_class($this));

		$this->attachDateValidation($validator);

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

	private function attachDateValidation(Validator $validator) {
		$schema = $this->_table->schema();
		$columns = $schema->columns();
		foreach ($columns as $column) {
			$columnAttr = $schema->column($column);
			if (array_key_exists('type', $columnAttr) && $columnAttr['type'] == 'date') {
				// taking existing rules from behavior's parent and storing them
				$rules = $validator->field($column)->rules();
				$rulesStore = [];
				foreach ($rules as $rkey => $rvalue) {
					$rulesStore[$rkey] = $validator->field($column)->rule($rkey);
					$validator->field($column)->remove($rkey);
				}

				// inserting these rules first
				$validator->add($column, [
					'ruleValidDate' => [
						'rule' => ['date', 'ymd'],
						'last' => true,
						'message' => $this->getMessage('general.invalidDate')
					]
				]);

				// then inserting the rules from behavior's parent back
				foreach ($rulesStore as $rkey => $rvalue) {
					$validator->field($column)->add($rkey, $rvalue);
				}
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

	public static function numericPositive($check, array $globalData) {
		return ctype_digit($check);
	}

	public static function checkNotInvigilator($check, array $globalData) {
		$data = $globalData['data'];

        $Table = TableRegistry::get('Examination.ExaminationCentresInvigilators');
        $record = $Table
        	->find()
        	->where([
        		$Table->aliasField('examination_id') => $data['examination_id'],
        		$Table->aliasField('invigilator_id') => $check,
        		$Table->aliasField('academic_period_id') => $data['academic_period_id']
        	])
        	->first();

        if (!empty($record)) {
        	return false;
        } else {
        	return true;
        }
    }

    public static function checkAuthorisedArea($check, array $globalData)
    {
    	$data = $globalData['data'];
    	$isValid = false;

    	if (array_key_exists('superAdmin', $data) && array_key_exists('userId', $data)) {
    		$superAdmin = $globalData['data']['superAdmin'];
    		$userId = $globalData['data']['userId'];

    		if ($superAdmin == 1) {
	        	$isValid = true;
	        } else {
	        	$isSystemGroup = false;
	        	if (!$globalData['newRecord']) { // only applicable for edit mode
	        		if (array_key_exists('isSystemGroup', $data) && $data['isSystemGroup'] == true) {
		        		$isSystemGroup = true;
		        	}
	        	}

	        	$condition = [];
	        	$areaCondition = [];

	        	if (!$isSystemGroup) {
	        		$SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
		        	$Areas = TableRegistry::get('Area.Areas');
		        	// get areas from security group areas
		        	$areasByUser = $SecurityGroupAreas->getAreasByUser($userId);

		        	if (count($areasByUser) > 0) {
						foreach($areasByUser as $area) {
			        		$areaCondition[] = [
								$Areas->aliasField('lft').' >= ' => $area['lft'],
								$Areas->aliasField('rght').' <= ' => $area['rght']
							];
			        	}
			        	$condition['OR'] = $areaCondition;

						$isChild = $Areas->find()
				        	->where([$Areas->aliasField('id') => $check])
				        	->where($condition)
				        	->count();

				        $isValid = $isChild > 0;
					}
	        	} else {
	        		$isValid = true;
	        	}
	        }
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
		$endDate = new DateTime($field);
		if($compareField) {
			$options = ['equals' => $equals, 'reverse' => true, 'type' => $type];
			$result = self::doCompareDates($endDate, $compareField, $options, $globalData);
			return $result;
		} else {
			return true;
		}
	}

	/**
	 * To check end time is earlier than start time
	 * @param  mixed   $field        current field value
	 * @param  string  $compareField name of the field to compare
	 * @param  int  $absenceTypeId The absence type id to validate for
	 * @param  array   $globalData   "huge global data". This array consists of
	 *                               - newRecord [boolean]: states whether the given record is a new record
	 *                               - data 	 [array]  : the model's fields values
	 *                               - field 	 [string] : current field name
	 *                               - providers [object] : consists of provider objects and the current table object
	 *
	 * @return [type]                [description]
	 */

	public static function compareAbsenceTimeReverse($field, $compareField, $absenceTypeId, array $globalData) {
		$type = self::_getFieldType($compareField);

		$endTime = new DateTime($field);
		if($compareField && $globalData['data']['absence_type_id'] == $absenceTypeId) {
			$options = ['equals' => true, 'reverse' => true, 'type' => $type];
			$result = self::doCompareDates($endTime, $compareField, $options, $globalData);
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
		$startDate = new DateTime($field);
		if($compareField) {
			$options = ['equals' => $equals, 'reverse' => false, 'type' => $type];
			$result = self::doCompareDates($startDate, $compareField, $options, $globalData);
			if (!is_bool($result)) {
				return $result;
			} else {
				return (!$result) ? __(Inflector::humanize($globalData['field'])).' should be earlier than '.__(Inflector::humanize($compareField)) : true;
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
		$dateTwo = new DateTime($dateTwo);
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

	public static function dateAfterEnrollment($check, array $globalData) {
		$id = $globalData['data']['student_id'];

		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$enrolledStatus = $StudentStatuses->getIdByCode('CURRENT');

		$studentData = TableRegistry::get('Institution.Students')
			->find()
			->where(['student_id' => $id, 'student_status_id' => $enrolledStatus])
			->first();

		if (!empty($studentData)) {
			$enrolledDate = $studentData['start_date']->format('Y-m-d');
			return $check > $enrolledDate;
		} else {
			return false;
		}
	}

	public static function compareTime($field, $compareField, $equals, array $globalData) {
		$type = self::_getFieldType($compareField);
		$startTime = strtotime($field);
		if($compareField) {
			$options = ['equals' => $equals, 'reverse' => false, 'type' => $type];
			$result = self::doCompareTimes($startTime, $compareField, $options, $globalData);
			if (!is_bool($result)) {
				return $result;
			} else {
				return (!$result) ? __(Inflector::humanize($compareField).' should be on a later '.$type) : true;
			}
		} else {
			return true;
		}
	}

	protected static function doCompareTimes($timeOne, $compareField, $options, $globalData) {
		$equals = $options['equals'];
		$reverse = $options['reverse'];
		$timeTwo = $globalData['data'][$compareField];
		$timeTwo = strtotime($timeTwo);
		if($equals) {
			if ($reverse) {
				return $timeOne >= $timeTwo;
			} else {
				return $timeTwo >= $timeOne;
			}
		} else {
			if ($reverse) {
				return $timeOne > $timeTwo;
			} else {
				return $timeTwo > $timeOne;
			}
		}
	}

	public static function compareWithInstitutionDateOpened($field, array $globalData) {
		$model = $globalData['providers']['table'];
		$startDate = new DateTime($field);
		if (isset($globalData['data']['institution_id'])) {
			$Institution = TableRegistry::get('Institution.Institutions');
			$institution = $Institution->find()->where([$Institution->aliasField($Institution->primaryKey()) => $globalData['data']['institution_id']])->first();
			return $startDate >= $institution->date_opened;
		} else {
		    return $model->getMessage('Institution.Institutions.noActiveInstitution');
		}
	}

	/**
	 * To check date entered is earlier today
	 * @param  mixed   $field        current field value
	 * @param  boolean $equals       whether the equals sign should be included in the comparison
	 * @param  array   $globalData   "huge global data". This array consists of
	 *                               - newRecord [boolean]: states whether the given record is a new record
	 *                               - data 	 [array]  : the model's fields values
	 *                               - field 	 [string] : current field name
	 *                               - providers [object] : consists of provider objects and the current table object
	 *
	 * @return mixed                 returns true if validation passed or the error message if it fails
	 */
	public static function lessThanToday($field, $equal = false, array $globalData) {
		$label = Inflector::humanize($field);
		$enteredDate = new DateTime($field);
		$today = new DateTime('now');
		if($equal) {
			return $today >= $enteredDate;
		} else {
			return $today > $enteredDate;
		}
	}

	/**
	 * To check date entered is later than today
	 * @param  mixed   $field        current field value
	 * @param  boolean $equals       whether the equals sign should be included in the comparison
	 * @param  array   $globalData   "huge global data". This array consists of
	 *                               - newRecord [boolean]: states whether the given record is a new record
	 *                               - data 	 [array]  : the model's fields values
	 *                               - field 	 [string] : current field name
	 *                               - providers [object] : consists of provider objects and the current table object
	 *
	 * @return mixed                 returns true if validation passed or the error message if it fails
	 */
	public static function moreThanToday($field, $equal = false, array $globalData) {
		$label = Inflector::humanize($field);
		$enteredDate = new DateTime($field);
		$today = new DateTime('now');
		if($equal) {
			return $enteredDate >= $today;
		} else {
			return $enteredDate > $today;
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
											->where([$fieldName => 1]);

				if (!$globalData['newRecord']) { //for edit, need to ensure that there is other record which is set as default, or else this one must be set as default.
					$recordWithField ->andWhere([$newEntity->aliasField('id').' IS NOT ' => $globalData['data']['id']]);
				}

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
		$givenDate = new DateTime($field);
		$startDate = new DateTime($start_date);
		$endDate = new DateTime($end_date);

		if($givenDate > $startDate && $givenDate < $endDate) {
			return true;
		} else {
			return __(Inflector::humanize($field_name)).' is not within date range of '.$start_date.' and '.$end_date;
		}
	}

	// Return false if not enrolled in other education system
	public static function checkInstitutionClassMaxLimit($class_id, array $globalData) {
		$ClassStudents = TableRegistry::get("Institution.InstitutionClassStudents");
		$currentNumberOfStudents = $ClassStudents->find()->where([
				$ClassStudents->aliasField('institution_class_id') => $class_id,
				$ClassStudents->aliasField('education_grade_id') => $globalData['data']['education_grade_id']
			])->count();
		/**
		 * @todo  add this max limit to config
		 * This limit value is being used in InstitutionClasses->editAfterAction()
		 */
		return ($currentNumberOfStudents < 100);
	}

	public static function studentNotEnrolledInAnyInstitutionAndSameEducationSystem($field, $options = [], array $globalData) {
		$data = $globalData['data'];

        // excluding data by field name
        $excludeInstitutionsOptions = array_key_exists('excludeInstitutions', $options)? $options['excludeInstitutions']: null;
        $excludeInstitutions = [];
        if (!empty($excludeInstitutionsOptions)) {
            foreach ($excludeInstitutionsOptions as $key => $value) {
                if (array_key_exists($value, $data)) {
                    $excludeInstitutions[] = $data[$value];
                }
            }
        }

		$Students = TableRegistry::get('Institution.Students');

		$educationGradeId = (array_key_exists('education_grade_id', $data))? $data['education_grade_id']: null;
		if (empty($educationGradeId)) {
			// insufficient params to perform search - return true as a default
			return true;
		}

		$educationSystemId = TableRegistry::get('Education.EducationGrades')->getEducationSystemId($educationGradeId);

		// obtains validation message from this function, false is returned if no validation message
        $validateOptions = ['targetInstitutionId' => $data['institution_id']];
        if (!empty($excludeInstitutions)) {
            $validateOptions['excludeInstitutions'] = $excludeInstitutions;
        }
		$validateEnrolledInAnyInstitution = $Students->validateEnrolledInAnyInstitution(
			$globalData['data']['student_id'],
			$educationSystemId,
			$validateOptions
		);
		return ($validateEnrolledInAnyInstitution === false)? true: $validateEnrolledInAnyInstitution;
	}

	public static function studentNotCompletedGrade($field, $options = [], array $globalData) {
		$Students = TableRegistry::get('Institution.Students');
		$educationGradeField = isset($options['educationGradeField']) ? $options['educationGradeField'] : 'education_grade_id';
		$studentIdField = isset($options['studentIdField']) ? $options['studentIdField'] : 'student_id';
		return !$Students->completedGrade($globalData['data'][$educationGradeField], $globalData['data'][$studentIdField]);
	}

    public static function institutionStaffId($field, array $globalData) {
        $Staff = TableRegistry::get('Institution.Staff');

        $existingRecords = $Staff->find()
            ->where(
                [
                    $Staff->aliasField('institution_position_id') => $globalData['data']['institution_position_id'],
                    $Staff->aliasField('institution_id') => $globalData['data']['institution_id'],
                    $Staff->aliasField('staff_id') => $globalData['data']['staff_id'],
                    'OR' => [
                        [$Staff->aliasField('end_date').' IS NULL'],
                        [$Staff->aliasField('end_date').' >= ' => $globalData['data']['start_date']]
                    ],
                ]
            );
        return ($existingRecords->count() <= 0);
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

	public static function checkInstitutionLocation($field, array $globalData) {
		$data = $globalData['data'];
		if (array_key_exists('location_institution_id', $data)) {
			if (empty($data['location_institution_id'])) {
				return false;
			}
		} else {
			return false;
		}
		return true;
	}

	public static function checkShiftAvailable($field, array $globalData) {
		// have to account for edit and itself... do not count itself into the query
		$existingId = (array_key_exists('id', $globalData['data']))? $globalData['data']['id']: null;

		$academicPeriodId = (array_key_exists('academic_period_id', $globalData['data']))? $globalData['data']['academic_period_id']: null;
		$institutionId = (array_key_exists('institution_id', $globalData['data']))? $globalData['data']['institution_id']: null;
		$locationInstitutionId = (array_key_exists('location_institution_id', $globalData['data']))? $globalData['data']['location_institution_id']: null;
		// no academic period or location fails
		if (empty($academicPeriodId)) return false;
		if (empty($locationInstitutionId)) return false;

		$InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
		// find any shift with overlap
		$query = $InstitutionShifts->find()
			->where([
				$InstitutionShifts->aliasField('academic_period_id') => $academicPeriodId,
				'OR' => [
					$InstitutionShifts->aliasField('location_institution_id') => $locationInstitutionId,
					$InstitutionShifts->aliasField('institution_id') => $institutionId
				]
			])
			;

		// to handle edits
		if (!empty($existingId)) {
			$query->where([$InstitutionShifts->aliasField('id') .' != ' . $existingId]);
		}

		$timeConditions = [];
		$startTime = (array_key_exists('start_time', $globalData['data']))? $globalData['data']['start_time']: null;
		$endTime = (array_key_exists('end_time', $globalData['data']))? $globalData['data']['end_time']: null;
		// no academic period or location fails
		if (empty($startTime)) return false;
		if (empty($endTime)) return false;

		$format = 'H:i:s';
		$startTime = date($format, strtotime($startTime));
		$endTime = date($format, strtotime($endTime));

		$timeConditions['OR'] = [
			'OR' => [
				[
					$InstitutionShifts->aliasField('start_time') . ' <= ' => $startTime,
					$InstitutionShifts->aliasField('end_time') . ' > ' => $startTime,
				],
				[
					$InstitutionShifts->aliasField('start_time') . ' < ' => $endTime,
					$InstitutionShifts->aliasField('end_time') . ' >= ' => $endTime,
				],
				[
					$InstitutionShifts->aliasField('start_time') . ' >= ' => $startTime,
					$InstitutionShifts->aliasField('end_time') . ' <= ' => $endTime,
				],
				[
					// means full day
					$InstitutionShifts->aliasField('start_time') . ' IS NULL',
					$InstitutionShifts->aliasField('end_time') . ' IS NULL',
				]
			]
		];

		$query->where($timeConditions);

		// pr($query->toArray());
		// die;

		$query = $query->count();
		return ($query == 0);
	}



	public static function checkAdmissionAgeWithEducationCycleGrade($field, array $globalData) {
		// this function is ONLY catered for 'on' => 'create'
		$model = $globalData['providers']['table'];
		$data = $globalData['data'];
		$validationErrorMsg = $model->getMessage('Institution.Students.student_name.ruleCheckAdmissionAgeWithEducationCycleGrade');

		$educationGradeId = (array_key_exists('education_grade_id', $data))? $data['education_grade_id']: null;
		// if no education grade. fail it
		if (empty($educationGradeId)) return $validationErrorMsg;

		if (array_key_exists('student_id', $data)) {
			// saving for existing students
			$Students = TableRegistry::get('Institution.StudentUser');
			$studentQuery = $Students->find()
				->select([$Students->aliasField('date_of_birth')])
				->where([$Students->aliasField($Students->primaryKey()) => $data['student_id']])
				->first();
				;
			if ($studentQuery) {
				$dateOfBirth = ($studentQuery->has('date_of_birth'))? $studentQuery->date_of_birth: null;
			} else {
				return $model->getMessage('Institution.Students.student_name.studentNotExists');
			}
		} else {
			// saving for new students
			$dateOfBirth = new DateTime($field);
		}

		// for cases where date of birth is null, probably only in cases of data error
		if (is_null($dateOfBirth)) return $validationErrorMsg;

		$EducationGrades = TableRegistry::get('Education.EducationGrades');
		$gradeEntity = $EducationGrades->find()
			->contain('EducationProgrammes.EducationCycles')
			->where([$EducationGrades->aliasField($EducationGrades->primaryKey()) => $educationGradeId])
			->first()
			;
		$admissionAge = $gradeEntity->education_programme->education_cycle->admission_age;

		if (array_key_exists('academic_period_id', $data) && !empty($data['academic_period_id'])) {
			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$academicPeriodData = $AcademicPeriods->get($data['academic_period_id']);
			if (!empty($academicPeriodData)) {
				$academicStartDate = $academicPeriodData->start_date;
				$academicStartYear = $academicStartDate->format('Y');
			}
		}
		// academic period not set in form, return false because there is no way to validate
		if (!isset($academicStartYear)) return $validationErrorMsg;

        $programmeId = $gradeEntity->education_programme_id;

		$birthYear = $dateOfBirth->format('Y');
		$ageOfStudent = $academicStartYear - $birthYear;

		$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
		$enrolmentMinimumAge = $admissionAge - $ConfigItems->value('admission_age_minus');
		$enrolmentMaximumAge = $admissionAge + $ConfigItems->value('admission_age_plus');

        // PHPOE-2284 - 'instead of defining admission age at grade level, please make sure the allowed age range changes according to the grade.'
       // PHPOE-2691 - 'instead of populating the list of grades by education cycle which is its grandparent, populate the list by its parent instead which is education programme.'
       $gradeList = $EducationGrades->find('list')
           ->where([$EducationGrades->aliasField('education_programme_id') => $programmeId])
           ->find('order')
           ->toArray()
           ;

		$yearIncrement = 0;
		foreach ($gradeList as $key => $value) {
			if ($key == $educationGradeId) break;
			$yearIncrement++;
		}

		$enrolmentMinimumAge += $yearIncrement;
		$enrolmentMaximumAge += $yearIncrement;

		// age check
		// pr('academicStartYear = '.$academicStartYear);
		// pr('birthYear = '.$birthYear);
		// pr('ageOfStudent = '.$ageOfStudent);

		// enrolment check check
		// pr('enrolmentMinimumAge = '.$enrolmentMinimumAge);
		// pr('enrolmentMaximumAge = '.$enrolmentMaximumAge);
        // return 'enrolmentMinimumAge = '.$enrolmentMinimumAge . '/' . 'enrolmentMaximumAge = '.$enrolmentMaximumAge;

		if ($enrolmentMinimumAge == $enrolmentMaximumAge) {
			$validationErrorMsg = $model->getMessage('Institution.Students.student_name.ageHint', ['sprintf' => [$enrolmentMinimumAge]]);
		} else {
			$validationErrorMsg = $model->getMessage('Institution.Students.student_name.ageRangeHint', ['sprintf' => [$enrolmentMinimumAge, $enrolmentMaximumAge]]);
		}

		return ($ageOfStudent<=$enrolmentMaximumAge) && ($ageOfStudent>=$enrolmentMinimumAge)? true: $validationErrorMsg;
	}

	// To allow case sensitive entry
	public static function checkUniqueEnglishField($check, array $globalData) {
		$condition = [];
		$englishField = trim($check);
		$Translation = TableRegistry::get('Localization.Translations');
		if(!empty($globalData['data']['id'])) {
			$condition['NOT'] = [
				$Translation->aliasField('id') => $globalData['data']['id']
			];
		}
      	$count = $Translation->find()
      		->where(['Binary('.$Translation->aliasField('en').')' => $englishField])
      		->where($condition)
      		->count();
        return $count==0;
    }

	public static function inAcademicPeriod($field, $academicFieldName, $options = [], $globalData)
	{
		if (array_key_exists($academicFieldName, $globalData['data'])) {
			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$periodObj = $AcademicPeriods
					->findById($globalData['data'][$academicFieldName])
					->first();

			if (!empty($periodObj)) {
				$excludeFirstDay = array_key_exists('excludeFirstDay', $options) ? $options['excludeFirstDay'] : null;
		        $excludeLastDay = array_key_exists('excludeLastDay', $options) ? $options['excludeLastDay'] : null;

		        if ($excludeFirstDay) {
		        	$withFirstDay = Time::parse($periodObj->start_date);
		        	$startDate = strtotime($withFirstDay->modify('+1 day')->format('Y-m-d'));
		        } else {
		        	$startDate = strtotime($periodObj->start_date->format('Y-m-d'));
		        }

		        if ($excludeLastDay) {
		        	$withLastDay = Time::parse($periodObj->end_date);
		        	$endDate = strtotime($withLastDay->modify('-1 day')->format('Y-m-d'));
		        } else {
		        	$endDate = strtotime($periodObj->end_date->format('Y-m-d'));
		        }

		        $checkDate = strtotime(Time::parse($field)->format('Y-m-d'));

		        return ($checkDate >= $startDate && $checkDate <= $endDate);
			}
		}

		return false;
	}

	//check combination of code and academic period. can be re-use for other models.
	public static function uniqueCodeByForeignKeyAcademicPeriod($field, $foreignKeyModel, $foreignKeyField, $academicFieldName, $globalData)
	{
		if (array_key_exists($academicFieldName, $globalData['data'])) {

			$model = $globalData['providers']['table'];

			//if have record then return false.
			return !($model->find('list')
					->contain([$foreignKeyModel], [
						"$foreignKeyModel.id = " . $model->aliasField($foreignKeyField)
					])
					->where([
						$model->aliasField('code') => $globalData['data']['code'],
						"$foreignKeyModel.$academicFieldName = " . $globalData['data']['academic_period_id']
					])
					->count());
		}
	}

	public static function assessmentExistByGradeAcademicPeriod($field, $globalData)
	{
		$model = $globalData['providers']['table'];
		$data = $globalData['data'];
		// pr($data);die;

		return !($model->find()
                    ->where([
                        $model->aliasField('education_grade_id') => $data['education_grade_id'],
                        $model->aliasField('academic_period_id') => $data['academic_period_id']
                    ])
                    ->count());
	}

	public static function compareJoinDate($field, $academicFieldName, $globalData)
	{
		$model = $globalData['providers']['table'];
		if (array_key_exists($academicFieldName, $globalData['data'])) {
			if (!is_null($globalData['data'][$academicFieldName])) {
				if ($academicFieldName == 'staff_id') {
					$Table = TableRegistry::get('Institution.Staff');
					$periodObj = $Table->find()
							->where([
				                $Table->aliasField('staff_id') => $globalData['data'][$academicFieldName],
				                $Table->aliasField('institution_id') => $globalData['data']['institution_id']
							])
							->toArray();
				} else if ($academicFieldName == 'student_id') {
					$Table = TableRegistry::get('Institution.Students');
					$periodObj = $Table->find()
							->where([
				                $Table->aliasField('student_id') => $globalData['data'][$academicFieldName],
				                $Table->aliasField('institution_id') => $globalData['data']['institution_id'],
				                $Table->aliasField('academic_period_id') => $globalData['data']['academic_period_id']
							])
							->toArray();
				}

				$startDate = strtotime($globalData['data']['start_date']);
				$endDate = strtotime($globalData['data']['end_date']);

				if (!empty($periodObj)) {
					$joinStartDateData=[];
					$joinEndDateData=[];

					// Array of the startDate and endDate of the user if user have more than 1 position.
					foreach ($periodObj as $key => $value) {
						$joinStartDateData[$key] = $periodObj[$key]['start_date'];
						$joinEndDateData[$key] = $periodObj[$key]['end_date'];
					}

					if (in_array('', $joinStartDateData)) {
						$joinStartDate = null;
					} else {
						$joinStartDate = min($joinStartDateData)->toUnixString();
					}

					// will check if in the array have any null data, means no restriction on the end date of the staff
					if (in_array('', $joinEndDateData)) {
						$joinEndDate = null;
					} else {
						$joinEndDate = max($joinEndDateData)->toUnixString();
					}

					$joinRangeCheck = (($startDate >= $joinStartDate) && (is_null($joinEndDate))) || (($startDate >= $joinStartDate) && ($endDate <= $joinEndDate) );

					if (!$joinRangeCheck) {
						if (!is_null($joinEndDate)) {
							$startDate = __('Absence date must be within the assigned period, from') . ' ' . date('d-m-Y', $joinStartDate);
							$endDate = ' ' . __('to') . ' ' . date('d-m-Y', $joinEndDate);
							return $startDate . $endDate;
						} else {
							$startDate = __('Absence date must be within the assigned period, from') . ' ' . date('d-m-Y', $joinStartDate);
							return $startDate;
						}
					}

					return $joinRangeCheck;
				}
			}

			return true;
		}

		return false;
	}

	public static function inInstitutionShift($field, $academicFieldName, $globalData)
	{
		$model = $globalData['providers']['table'];
		if (array_key_exists($academicFieldName, $globalData['data'])) {
			$time = strtotime($field);
			$selectedPeriod = $globalData['data'][$academicFieldName];
			$institutionId = $globalData['data']['institution_id'];

			$InstitutionShift = TableRegistry::get('Institution.InstitutionShifts');

			// if Class_id is available then it will used the class_id to get the institution_shift_id
			// for student
			if (isset($globalData['data']['class'])) {
				$selectedClass = $globalData['data']['class'];
				$InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
				$InstitutionShiftId = $InstitutionClasses
					->find()
					->where([$InstitutionClasses->aliasField('id') => $selectedClass])
					->first()->institution_shift_id;
				$conditions = ([$InstitutionShift->aliasField('id') => $InstitutionShiftId]);
			} else {
				// get the shift using periodId and locationInstitutionId, due to changes made on institution_shift table
				// for staff
				$conditions = ([
					$InstitutionShift->aliasField('academic_period_id') => $selectedPeriod,
					$InstitutionShift->aliasField('location_institution_id') => $institutionId
				]);
			}

			$shiftTime = $InstitutionShift
					->find()
					->where($conditions)
					->toArray();

			if (!empty($shiftTime)) {
				$shiftStartTimeArray = [];
				$shiftEndTimeArray = [];
				foreach ($shiftTime as $key => $value) {
					$shiftStartTimeArray[$key] = $value->start_time;
					$shiftEndTimeArray[$key] = $value->end_time;
				}

				// get the earliest shift start time for the start time.
				// get the latest shift end time for the end time.
				$startTime = min($shiftStartTimeArray);
				$endTime = max($shiftEndTimeArray);
			} else {
				$ConfigItems = TableRegistry::get('Configuration.configItems');

				$configStartTime = $ConfigItems->value('start_time');
				$hourPerDay = $ConfigItems->value('hours_per_day');

				$startTime = new time($configStartTime);

				$endTime = new time($configStartTime);
				$endTime->addHour($hourPerDay);
			}

			$institutionShiftStartTime = strtotime($InstitutionShift->formatTime($startTime));
			$institutionShiftEndTime = strtotime($InstitutionShift->formatTime($endTime));

			$rangecheck = ($time >= $institutionShiftStartTime && $time <= $institutionShiftEndTime);

			//If validation is wrong it will gave message contain the start and end time.
			if (!$rangecheck) {
				$startTime = date('h:i A', $institutionShiftStartTime);
				$endTime = date('h:i A', $institutionShiftEndTime);
				return $model->getMessage('Institution.Absences.timeRangeHint', ['sprintf' => [$startTime, $endTime]]);
			}

			return $rangecheck;
		}

		return false;
	}

	public static function noOverlappingAbsenceDate($field, $SearchTable, array $globalData) {
		if ($globalData['data']['start_date'] instanceof Time || $globalData['data']['start_date'] instanceof Date) {
			$startDate = $globalData['data']['start_date']->format('Y-m-d');
		} else {
			$startDate = date('Y-m-d', strtotime($globalData['data']['start_date']));
		}
		if ($globalData['data']['end_date'] instanceof Time || $globalData['data']['end_date'] instanceof Date) {
			$endDate = $globalData['data']['end_date']->format('Y-m-d');
		} else {
			$endDate = date('Y-m-d', strtotime($globalData['data']['end_date']));
		}
		$userId = '';
		$userKey = 'security_user_id';
		if ($SearchTable->table() == 'institution_student_absences') {
			$userId = $globalData['data']['student_id'];
			$userKey = 'student_id';
		} else if ($SearchTable->table() == 'institution_staff_absences') {
			$userId = $globalData['data']['staff_id'];
			$userKey = 'staff_id';
		}
		$institution_id = $globalData['data']['institution_id'];

		// this will assome there will be start date and end date and student_id and academic period
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
			->where([$SearchTable->aliasField($userKey) => $userId])
			->where([$SearchTable->aliasField('institution_id') => $institution_id])
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

	public static function checkStaffExistWithinPeriod($field, array $globalData) {
		// The logic below will prevent duplicate record that will be produce if the user amend the start or end date for a staff that is inactive when there is an active staff
		// in the same institution

		$recordId = $globalData['data']['id'];
		$institutionId = $globalData['data']['institution_id'];
		$newEndDate = strtotime($globalData['data']['end_date']);
		$newStartDate = strtotime($globalData['data']['start_date']);
		$staffId = $globalData['data']['staff_id'];
		$positionId = $globalData['data']['institution_position_id'];

		$InstitutionStaffTable = TableRegistry::get('Institution.Staff');

		$condition = [
			$InstitutionStaffTable->aliasField('staff_id') => $staffId,
			$InstitutionStaffTable->aliasField('institution_position_id') => $positionId,
			$InstitutionStaffTable->aliasField('id').' IS NOT' => $recordId,
			$InstitutionStaffTable->aliasField('institution_id') => $institutionId
		];
		$count = 0;

		if ($newStartDate !== false) {
			if (empty($newEndDate)) {
				$count = $InstitutionStaffTable->find()
					->where($condition)
					->where([
							'OR' => [
								[$InstitutionStaffTable->aliasField('end_date').' IS NULL'],
								[
									$InstitutionStaffTable->aliasField('start_date').' >=' => $newStartDate,
								]
							]
						]);
			} else {
				$count = $InstitutionStaffTable->find()
					->where($condition)
					->where([
							'OR' => [
								[
									$InstitutionStaffTable->aliasField('start_date').' <=' => $newEndDate,
									$InstitutionStaffTable->aliasField('end_date').' IS NULL'
								],
								[
									$InstitutionStaffTable->aliasField('start_date').' <=' => $newStartDate,
									$InstitutionStaffTable->aliasField('end_date').' IS NULL'
								],
								[
									$InstitutionStaffTable->aliasField('start_date').' <=' => $newEndDate,
									$InstitutionStaffTable->aliasField('end_date').' >=' => $newEndDate,
								],
								[
									$InstitutionStaffTable->aliasField('start_date').' <=' => $newStartDate,
									$InstitutionStaffTable->aliasField('end_date').' >=' => $newStartDate,
								],
							]
						]);
			}
			if ($count->count() > 0) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
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
					$InstitutionStaff->aliasField('institution_position_id') => $globalData['data']['institution_position_id']

				]
			);

		// no id this is NOT a add method
		if (array_key_exists('institution_staff_id', $globalData['data']) && !empty($globalData['data']['institution_staff_id'])) {
			$identicalPositionHolders->where([$InstitutionStaff->aliasField('id').' != '. $globalData['data']['institution_staff_id']]);
		} else if (array_key_exists('id', $globalData['data']) && !empty($globalData['data']['id'])) {
			$identicalPositionHolders->where([$InstitutionStaff->aliasField('id').' != '. $globalData['data']['id']]);
		}

		$dateCondition = [];
		// start and end date is of the new entry
		$dateCondition['OR'] = [];

		$todayDate = new Date();
		$todayDate = $todayDate->format('Y-m-d');

		if (empty($endDate)) {
			// current position has no end date
			$dateCondition['OR'][] = 'end_date IS NULL';
			$dateCondition['OR'][] = [
				'end_date IS NOT NULL',
				'end_date >= ' => $startDate,
				'end_date >= ' => $todayDate //to exclude staff which assignment has been ended.
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

			$dateCondition['AND'] = ['end_date >= ' => $todayDate]; //to exclude staff which assignment has been ended.
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

		return $validationResult;
	}

	public static function checkNoSpaces($field, array $globalData) {
		return !strrpos($field," ");
	}

	// move to
	public static function checkNumberExists($field, array $globalData) {
		$match = preg_match('#\d#', $field);
		return !empty($match);
	}

	public static function checkUppercaseExists($field, array $globalData) {
		$match = preg_match('/[A-Z]/', $field);
		return !empty($match);
	}

	public static function checkLowercaseExists($field, array $globalData) {
		$match = preg_match('/[a-z]/', $field);
		return !empty($match);
	}

	public static function checkNonAlphanumericExists($field, array $globalData) {
		return !ctype_alnum($field);
	}

	public static function checkUsername($field, array $globalData) {
		return (filter_var($field, FILTER_VALIDATE_EMAIL)) || ctype_alnum($field);
	}

	public static function validateCustomText($field, array $globalData) {
		if (array_key_exists('params', $globalData['data']) && !empty($globalData['data']['params'])) {
			$model = $globalData['providers']['table'];
			$params = json_decode($globalData['data']['params'], true);
			foreach ($params as $key => $value) {
				if ($key == 'min_length' && strlen($field) < $value) {
					return $model->getMessage('CustomField.text.minLength', ['sprintf' => $value]);
				}
				if ($key == 'max_length' && strlen($field) > $value) {
					return $model->getMessage('CustomField.text.maxLength', ['sprintf' => $value]);
				}
				if ($key == 'range' && is_array($value)) {
					if (array_key_exists('lower', $value) && array_key_exists('upper', $value)) {
						if (strlen($field) < $value['lower'] || strlen($field) > $value['upper']) {
							return $model->getMessage('CustomField.text.range', ['sprintf' => [$value['lower'], $value['upper']]]);
						}
					}
				}
			}

			return true;
		}
	}

	public static function validateCustomNumber($field, array $globalData) {
		if (array_key_exists('params', $globalData['data']) && !empty($globalData['data']['params'])) {
			$model = $globalData['providers']['table'];
			$params = json_decode($globalData['data']['params'], true);
			foreach ($params as $key => $value) {
				if ($key == 'min_value' && $field < $value) {
					return $model->getMessage('CustomField.number.minValue', ['sprintf' => $value]);
				}
				if ($key == 'max_value' && $field > $value) {
					return $model->getMessage('CustomField.number.maxValue', ['sprintf' => $value]);
				}
				if ($key == 'range' && is_array($value)) {
					if (array_key_exists('lower', $value) && array_key_exists('upper', $value)) {
						if ($field < $value['lower'] || $field > $value['upper']) {
							return $model->getMessage('CustomField.number.range', ['sprintf' => [$value['lower'], $value['upper']]]);
						}
					}
				}
			}

			return true;
		}
	}

	public static function checkDateRange($field, array $globalData) {
		$systemDateFormat = TableRegistry::get('Configuration.ConfigItems')->value('date_format');
		$model = $globalData['providers']['table'];
		$params = (!empty($globalData['data']['params']))? json_decode($globalData['data']['params'],true): [];

		if (array_key_exists('start_date', $params) && array_key_exists('end_date', $params)) {
			return (strtotime($field) < strtotime($params['start_date']) || strtotime($field) > strtotime($params['end_date']))? $model->getMessage('CustomField.date.between', ['sprintf' => [date($systemDateFormat, strtotime($params['start_date'])), date($systemDateFormat, strtotime($params['end_date']))]]): true;
		} else if (array_key_exists('start_date', $params)) {
			return (strtotime($field) < strtotime($params['start_date']))? $model->getMessage('CustomField.date.later', ['sprintf' => date($systemDateFormat, strtotime($params['start_date']))]): true;
		} else if (array_key_exists('end_date', $params)) {
			return (strtotime($field) > strtotime($params['end_date']))? $model->getMessage('CustomField.date.earlier', ['sprintf' => date($systemDateFormat, strtotime($params['end_date']))]): true;
		} else {
			return true;
		}
	}

	public static function checkTimeRange($field, array $globalData) {
		$systemTimeFormat = TableRegistry::get('Configuration.ConfigItems')->value('time_format');
		$model = $globalData['providers']['table'];
		$params = (!empty($globalData['data']['params']))? json_decode($globalData['data']['params'],true): [];

		if (array_key_exists('start_time', $params) && array_key_exists('end_time', $params)) {
			return (strtotime($field) < strtotime($params['start_time']) || strtotime($field) > strtotime($params['end_time']))? $model->getMessage('CustomField.time.between', ['sprintf' => [date($systemTimeFormat, strtotime($params['start_time'])), date($systemTimeFormat, strtotime($params['end_time']))]]): true;
		} else if (array_key_exists('start_time', $params)) {;
			return (strtotime($field) < strtotime($params['start_time']))? $model->getMessage('CustomField.time.later', ['sprintf' => [date($systemTimeFormat, strtotime($params['start_time']))]]): true;
		} else if (array_key_exists('end_time', $params)) {
			return (strtotime($field) > strtotime($params['end_time']))? $model->getMessage('CustomField.time.earlier', ['sprintf' => [date($systemTimeFormat, strtotime($params['end_time']))]]): true;
		} else {
			return true;
		}
	}

	public static function checkUniqueCode($code, $groupField, array $globalData) {
		$model = $globalData['providers']['table'];
		$count = 0;
		if (is_null($groupField) || empty($groupField) || !$groupField) {
	    	if (!$globalData['newRecord']) {
		      	$count =  $model->find()
				      		->where([
				      			$model->aliasField('id') .' != ' => $globalData['data']['id'],
				      			$model->aliasField('code') => $code,
				      		])
				      		->count();
			} else {
		      	$count =  $model->find()
				      		->where([$model->aliasField('code') => $code])
				      		->count();
			}
	    } else {
	    	if (!$globalData['newRecord']) {
		      	$count =  $model->find()
				      		->where([
				      			$model->aliasField('id') .' != ' => $globalData['data']['id'],
				      			$model->aliasField('code') => $code,
				      			$model->aliasField($groupField) => $globalData['data'][$groupField],
				      		])
				      		->count();
			}
	    }
        return $count==0;
    }

	public static function checkUniqueCodeWithinForm($code, $parentModel, array $globalData) {
		$model = $globalData['providers']['table'];
		$count = 0;
		$modelAssociation = null;
		foreach ($parentModel->associations() as $assoc) {
			if ($assoc->name()==$model->alias()) {
				$modelAssociation = $assoc;
				break;
			}
		}
		foreach ($parentModel->request->data[$parentModel->alias()][$modelAssociation->property()] as $key => $value) {
      		if ($value['code']==$code) {
      			$count++;
      		}
      	}
        return $count<2;
    }

	public static function inParentAcademicPeriod($field, $parentModel, $globalData) {
		$globalPostData = $parentModel->request->data;
		$parentPostData = $globalPostData[$parentModel->alias()];
		$modelPostData = $globalData['data'];

      	if (!empty($parentPostData['academic_period_id']) && !empty($field)) {
			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			if ($AcademicPeriods->exists($parentPostData['academic_period_id'])) {
				$periodObj = $AcademicPeriods->get($parentPostData['academic_period_id']);
				$date = strtotime($field);

				$academicPeriodStartDate = (!is_null($periodObj['start_date'])) ? $periodObj['start_date']->toUnixString() : null;
				$academicPeriodEndDate = (!is_null($periodObj['end_date'])) ? $periodObj['end_date']->toUnixString() : null;

				$rangecheck = ($date >= $academicPeriodStartDate) &&
				(is_null($academicPeriodEndDate) ||
					(!is_null($academicPeriodEndDate) && ($date <= $academicPeriodEndDate))
				)
				;
				return $rangecheck;

			} else {
				return __('Bad Academic Period Id');
			}
		} else if (!empty($parentPostData['academic_period_id'])) {
			return __('Parent Academic Period Id cannot be empty');
		}

		return true;
	}

    public static function latIsValid($field, array $globalData) {
        $error = false;
        $isRequired = $globalData['data']['mandatory'];
        if (is_array($field)) {
            $latitude = $field['latitude'];
            $longitude = $field['longitude'];
            if (!empty($latitude) || !empty($longitude)) {
                if (empty($latitude)) {
                    $error = __('Latitude cannot be empty');
                } else {
                    $latIsValid = Validation::latitude($latitude);
                    if (!$latIsValid) {
                        $error = __('Latitude value is invalid');
                    }
                }
            } elseif ($isRequired) {
	            $error = __('Latitude value is required');
            }
        } elseif ($isRequired) {
            $error = __('Required data is not available');
        }
        return (!$error) ? true : $error;
    }

    public static function lngIsValid($field, array $globalData) {
        $error = false;
        $isRequired = $globalData['data']['mandatory'];
        if (is_array($field)) {
            $latitude = $field['latitude'];
            $longitude = $field['longitude'];
            if (!empty($latitude) || !empty($longitude)) {
                if (empty($longitude)) {
                    $error = __('Longitude cannot be empty');
                } else {
                    $latIsValid = Validation::longitude($longitude);
                    if (!$latIsValid) {
                        $error = __('Longitude value is invalid');
                    }
                }
            } elseif ($isRequired) {
	            $error = __('Longitude value is required');
            }
        } elseif ($isRequired) {
            $error = __('Required data is not available');
        }
        return (!$error) ? true : $error;
    }

	public static function checkMinNotMoreThanMax($minValue, array $globalData) {
        return intVal($minValue) <= intVal($globalData['data']['max']);
    }

	public static function noNewDropoutRequestInGradeAndInstitution($field, array $globalData)
	{
		$model = $globalData['providers']['table'];
		$data = $globalData['data'];

		$studentId = (array_key_exists('student_id', $data))? $data['student_id']: null;
		$educationGradeId = (array_key_exists('education_grade_id', $data))? $data['education_grade_id']: null;
		$previousInstitutionId = (array_key_exists('previous_institution_id', $data))? $data['previous_institution_id']: null;

		if (empty($studentId) || empty($educationGradeId) || empty($previousInstitutionId)) {
			// insufficient params to perform search - return true as a default
			return true;
		}

		$StudentDropoutTable = TableRegistry::get('Institution.StudentDropout');
    	$conditions = [
			'student_id' => $studentId,
			'status' => $model::NEW_REQUEST,
			'education_grade_id' => $educationGradeId,
			'institution_id' => $previousInstitutionId
		];

		$count = $StudentDropoutTable->find()
			->where($conditions)
			->count();

		return ($count == 0);
	}

	public static function checkLinkedSector($field, array $globalData) {
		$selectedSector = $globalData['data']['institution_sector_id'];
		$Providers = TableRegistry::get('Institution.Providers');
		$LinkedSector = $Providers->get($field)->institution_sector_id;

		return $selectedSector == $LinkedSector;
	}

	public static function validateJsonAPI($field, array $globalData)
	{
		// will pass the url to the areasTable, because the url checking function located in the areasTable.php
		$url = $globalData['data']['value'];
		$Areas = TableRegistry::get('Area.Areas');
		return $Areas->isApiValid($url);
	}

	public static function uniqueWorkflowActionEvent($field, array $globalData)
	{
		$data = $globalData['data'];
		$eventKey = $data['event_key'];
		$workflowStepId = $data['workflow_step_id'];
		if (!empty($eventKey)) {
			$WorkflowActionTable = TableRegistry::get('Workflow.WorkflowActions');
			$workflowId = $WorkflowActionTable
				->find()
				->innerJoinWith('WorkflowSteps')
				->select(['workflowId' => 'WorkflowSteps.workflow_id'])
				->where([
					$WorkflowActionTable->aliasField('workflow_step_id') => $workflowStepId
				])
				->distinct('workflowId');

			$eventKeyExist = $WorkflowActionTable
				->find()
				->innerJoinWith('WorkflowSteps')
				->where([
					'WorkflowSteps.workflow_id' => $workflowId,
					$WorkflowActionTable->aliasField('event_key') => $eventKey
				]);

			if (isset($data['id'])) {
				$eventKeyExist->where([$WorkflowActionTable->aliasField('id').' <> ' => $data['id']]);
			}

			return $eventKeyExist->count() == 0;
		} else {
			return true;
		}
	}

	public static function checkPendingAdmissionExist($field, array $globalData)
	{
		$data = $globalData['data'];
		$studentId = $data['student_id'];
		$institutionId = $data['institution_id'];
		$academicPeriodId = $data['academic_period_id'];
		$educationGradeId = $data['education_grade_id'];
		$AdmissionTable = TableRegistry::get('Institution.StudentAdmission');
		$studentExist = $AdmissionTable->find()
			->where([
					$AdmissionTable->aliasField('status') => 0,
					$AdmissionTable->aliasField('student_id') => $studentId,
					$AdmissionTable->aliasField('institution_id') => $institutionId,
					$AdmissionTable->aliasField('academic_period_id') => $academicPeriodId,
					$AdmissionTable->aliasField('education_grade_id') => $educationGradeId,
					$AdmissionTable->aliasField('type') => 1
				])
			->count();

		return $studentExist == 0;
	}

	public static function validateCustomIdentityNumber($field, array $globalData)
	{
		$subject = $field;
		$pattern = '';
		$model = $globalData['providers']['table'];

		if (array_key_exists('identity_type_id', $globalData['data']) && !empty($globalData['data']['identity_type_id'])) {
			$identityTypeId = $globalData['data']['identity_type_id'];

			$IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
			$IdentityTypesData = $IdentityTypes
				->find()
				->where([$IdentityTypes->aliasField('id') => $identityTypeId])
				->first()
			;

			if (!empty($IdentityTypesData->validation_pattern)) {
				$pattern = '/' . $IdentityTypesData->validation_pattern . '/';
			}
		}

		// custom validation is nullable, have to cater for the null pattern.
		if (!empty($pattern) && !preg_match($pattern, $subject)) {
			return $model->getMessage('User.Identities.number.custom_validation');
		}

		return true;
	}

	public static function validateCustomPattern($field, $code, array $globalData)
	{
		$pattern = '';
		$model = $globalData['providers']['table'];

		$ConfigItems = TableRegistry::get('Configuration.ConfigItems');
		$valuePattern = '/' . $ConfigItems->value($code) . '/';

		if (!empty($valuePattern) && !preg_match($valuePattern, $field)) {
			return $model->getMessage('general.custom_validation_pattern');
		}

		return true;
	}

	public static function validateRoomCapacity($field, array $globalData)
	{
		if (array_key_exists('students', $globalData)) {
			$totalSeats = $globalData['data']['number_of_seats'];
			$currentSeats = count($globalData['data']['students']);
			return $totalSeats >= $currentSeats;
		}

		return true;
	}

	public static function checkNoRunningSystemProcess($check, $processName, array $globalData)
	{
		$RUNNING = 2;
		$SystemProcesses = TableRegistry::get('SystemProcesses');
		$runningProcesses = $SystemProcesses->find()
			->where([
				$SystemProcesses->aliasField('name') => $processName,
				$SystemProcesses->aliasField('status') => $RUNNING
			])
			->toArray();

		if (!empty($runningProcesses)) {
			foreach ($runningProcesses as $key => $obj) {
				$params = json_decode($obj->params);
				if ($params->examination_id && $params->examination_id == $check) {
					return false;
				}
			}
		}

		return true;
	}
}
