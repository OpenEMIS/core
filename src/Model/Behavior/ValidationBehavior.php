<?php
namespace App\Model\Behavior;

use DateTime;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Behavior;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Network\Session;
use App\Model\Traits\MessagesTrait;

class ValidationBehavior extends Behavior {
	use MessagesTrait;

	private $validationCode = [];

	public function buildValidator(Event $event, Validator $validator, $name) {
		$this->attachDefaultValidation($validator);

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

	private function attachDefaultValidation($validator) {
		$schema = $this->_table->schema();
		$columns = $schema->columns();

		// added this temporary, will need to revisit this code
		$ignoreFields = ['modified_user_id', 'created_user_id', 'modified', 'created', 'order'];

		foreach ($columns as $col) {
			$columnInfo = $schema->column($col);
			if ($validator->hasField($col)) {
				$set = $validator->field($col);

				if (!$set->isEmptyAllowed()) {
					$set->add('notBlank', ['rule' => 'notBlank']);
				}
				if (!$set->isPresenceRequired()) {
					if ($this->isForeignKey($col)) {
						$validator->requirePresence($col);
					}
				}
			} else { // field not presence in validator
				if (array_key_exists('null', $columnInfo)) {
					if ($columnInfo['null'] === false && $col !== 'id' && !in_array($col, $ignoreFields)) {
						$validator->add($col, 'notBlank', ['rule' => 'notBlank']);
						if ($this->isForeignKey($col)) {
							$validator->requirePresence($col);
						}
					}
				}
			}
		}
	}

	private function isForeignKey($field) {
		$model = $this->_table;
		foreach ($model->associations() as $assoc) {
			if ($assoc->type() == 'manyToOne') { // belongsTo associations
				if ($field === $assoc->foreignKey()) {
					return true;
				}
			}
		}
		return false;
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

    public static function checkAuthorisedArea($check, array $globalData) {
        $isValid = false;
        $session = new Session();
        if ($session->read('Auth.User.super_admin') == 1) {
        	$isValid = true;
        } else {
        	$condition = [];
        	$areaCondition = [];

			$SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
        	$Areas = TableRegistry::get('Area.Areas');
        	// get areas from security group areas
        	$areasByUser = $SecurityGroupAreas->getAreasByUser($session->read('Auth.User.id'));
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
			$StudentStatusesTable = TableRegistry::get('Student.StudentStatuses');
			$statuses = $StudentStatusesTable->findCodeList();
			$existingRecords = $Students->find()
				->where(
					[
						$Students->aliasField('academic_period_id') => $globalData['data']['academic_period_id'],
						$Students->aliasField('education_grade_id') => $globalData['data']['education_grade_id'],
						$Students->aliasField('institution_id') => $globalData['data']['institution_id'],
						$Students->aliasField('student_id') => $globalData['data']['student_id'],
						$Students->aliasField('student_status_id').' IS NOT ' => $statuses['DROPOUT']
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

	public static function checkAdmissionAgeWithEducationCycleGrade($field, array $globalData) {
		$data = $globalData['data'];
		if ((array_key_exists('education_grade_id', $data)) && (array_key_exists('student_id', $data))) {
			// getting admission  age
			$EducationGrades = TableRegistry::get('Education.EducationGrades');
			$educationGradeQuery = $EducationGrades->find()
				->select(['EducationCycles.name', 'EducationCycles.admission_age', 'EducationCycles.id'])
				->contain('EducationProgrammes.EducationCycles')
				->where([$EducationGrades->aliasField($EducationGrades->primaryKey()) => $data['education_grade_id']])
				->first()
				;
			$admissionAge = $educationGradeQuery->EducationCycles->admission_age;
			$cycleId = $educationGradeQuery->EducationCycles->id;

			// getting age fo student
			$Students = TableRegistry::get('Student.Students');
			$studentQuery = $Students->find()
				->select([$Students->aliasField('date_of_birth')])
				->where([$Students->aliasField($Students->primaryKey()) => $data['student_id']])
				->first();
				;
			$dateOfBirth = ($studentQuery->has('date_of_birth'))? $studentQuery->date_of_birth: null;
			if (is_null($dateOfBirth)) return false;

			$birthYear = $dateOfBirth->format('Y');
			$nowYear = Time::now()->format('Y');
			$ageOfStudent = $nowYear - $birthYear;


			$ConfigItems = TableRegistry::get('ConfigItems');
			$enrolmentMinimumAge = $admissionAge - $ConfigItems->value('admission_age_minus');
			$enrolmentMaximumAge = $admissionAge + $ConfigItems->value('admission_age_plus');

			// PHPOE-2284 - 'instead of defining admission age at grade level, please make sure the allowed age range changes according to the grade.'
			$EducationGrades = TableRegistry::get('Education.EducationGrades');
			$gradeInCycleList = $EducationGrades->find('list')
				->contain('EducationProgrammes.EducationCycles')
				->where(['EducationCycles.id' => $cycleId])
				->find('order');

			$yearIncrement = 0;
			foreach ($gradeInCycleList as $key => $value) {
				if ($key == $data['education_grade_id']) break;
				$yearIncrement++;
			}

			$enrolmentMinimumAge += $yearIncrement;
			$enrolmentMaximumAge += $yearIncrement;

			return ($ageOfStudent<=$enrolmentMaximumAge) && ($ageOfStudent>=$enrolmentMinimumAge);
		}
		
		// if there is no cycle to check with, allow validation to pass
		return true;;
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

		return $validationResult;
	}

	public static function checkNoSpaces($field, array $globalData) {
		return !strrpos($field," ");
	}
}
