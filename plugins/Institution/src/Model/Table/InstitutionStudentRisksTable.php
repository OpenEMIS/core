<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class InstitutionStudentRisksTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Risks', ['className' => 'Risk.Risks', 'foreignKey' =>'risk_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' =>'student_id']);

        $this->hasMany('StudentRisksCriterias', ['className' => 'Institution.StudentRisksCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        // for search
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];

        // student absence and attendance
        $events['Model.InstitutionStudentAbsences.afterSave'] = 'afterSaveOrDelete';
        $events['Model.InstitutionStudentAbsences.afterDelete'] = 'afterSaveOrDelete';

        // student behaviour
        $events['Model.StudentBehaviours.afterSave'] = 'afterSaveOrDelete';
        $events['Model.StudentBehaviours.afterDelete'] = 'afterSaveOrDelete';

        // student gender
        $events['Model.StudentUser.afterSave'] = 'afterSaveOrDelete';

        // student guardian
        $events['Model.Guardians.afterSave'] = 'afterSaveOrDelete';
        $events['Model.Guardians.afterDelete'] = 'afterSaveOrDelete';

        // student with special need
        $events['Model.SpecialNeedsAssessments.afterSave'] = 'afterSaveOrDelete';
        $events['Model.SpecialNeedsAssessments.afterDelete'] = 'afterSaveOrDelete';

        // student dropout (Students), repeated (IndividualPromotion), Overage will trigger the Students
        $events['Model.Students.afterSave'] = 'afterSaveOrDelete';
        $events['Model.Students.afterDelete'] = 'afterSaveOrDelete';
        $events['Model.IndividualPromotion.afterSave'] = 'afterSaveOrDelete';
        $events['Model.IndividualPromotion.afterDelete'] = 'afterSaveOrDelete';

        // Assessment result item
        $events['Model.AssessmentItemResults.afterSave'] = 'afterSaveOrDelete';
        $events['Model.AssessmentItemResults.afterDelete'] = 'afterSaveOrDelete';
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields) {
        $searchableFields[] = 'student_id';
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openEMIS_ID');
        $this->field('risk_id',['visible' => false]);
        $this->field('average_risk',['visible' => false]);
        $this->field('student_id', [
            'sort' => ['field' => 'Users.first_name']
        ]);
        $this->field('total_risk', ['sort' => true]);
        $this->field('academic_period_id',['visible' => false]);

        $session = $this->request->session();
        $requestQuery = $this->request->query;
        $params = $this->paramsDecode($requestQuery['queryString']);
        $institutionId = $session->read('Institution.Institutions.id');

        // back buttons
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Risks',
            'index'
        ];
        $toolbarButtonsArray['back'] = $this->getButtonTemplate();
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = $url;

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end back buttons

        // element control
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $selectedAcademicPeriodId = $params['academic_period_id'];

        $classOptions = $Classes->getClassOptions($selectedAcademicPeriodId, $institutionId);
        if (!empty($classOptions)) {
            $classOptions = [0 => 'All Classes'] + $classOptions;
        }

        $selectedClassId = $this->queryString('class_id', $classOptions);
        $this->advancedSelectOptions($classOptions, $selectedClassId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
            'callable' => function ($id) use ($InstitutionClassStudents) {
                return $InstitutionClassStudents
                    ->find()
                    ->where([
                        $InstitutionClassStudents->aliasField('institution_class_id') => $id
                    ])
                    ->count();
            }
        ]);
        $extra['selectedClass'] = $selectedClassId;

        $extra['elements']['control'] = [
            'name' => 'StudentRisks/controls',
            'data' => [
                'classOptions'=>$classOptions,
                'selectedClass'=>$selectedClassId,
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $params = $this->paramsDecode($requestQuery['queryString']);
        $session = $this->request->session();

        $institutionId = $session->read('Institution.Institutions.id');
        $academicPeriodId = $params['academic_period_id'];
        $classId = $extra['selectedClass'];

        $conditions = [
            $this->aliasField('risk_id') => $params['risk_id'],
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('total_risk') . ' >' => 0
        ];

        if ($classId > 0) {
            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $studentList = $InstitutionClassStudents->getStudentsList($academicPeriodId, $institutionId, $classId);

            $conditions = [
                $this->aliasField('risk_id') => $params['risk_id'],
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('student_id') . ' IN ' => $studentList,
                $this->aliasField('total_risk') . ' >' => 0
            ];
        }

        // for sorting of student_id by name and total_risk
        $sortList = [
            $this->fields['student_id']['sort']['field'],
            'total_risk'
        ];

        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
        // end sorting (refer to commentsTable)

        $query->where([$conditions]);

        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('grade');
        $this->field('class');
        $this->field('risk_id', ['after' => 'academic_period_id']);
        $this->field('total_risk', ['after' => 'risk_id']);
        $this->field('risk_criterias', ['type' => 'custom_criterias', 'after' => 'total_risk']);
        $this->field('average_risk', ['visible' => false]);
        $this->field('student_id', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        // BreadCrumb
        $requestQuery = $this->request->query;
        $params = $this->paramsDecode($requestQuery['queryString']);

        $riskId = $params['risk_id'];
        $academicPeriodId = $params['academic_period_id'];
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'InstitutionStudentRisks'
        ];

        $risksUrl = $this->setQueryString($url, [
            'risk_id' => $riskId,
            'academic_period_id' => $academicPeriodId
        ]);

        $this->Navigation->substituteCrumb('Institution Student Risks', 'Institution Student Risks', $risksUrl);

        // Header
        $studentName = $entity->user->first_name . ' ' . $entity->user->last_name;
        $header = $studentName . ' - ' . __(Inflector::humanize(Inflector::underscore($this->alias())));

        $this->controller->set('contentHeader', $header);
    }

    public function onGetOpenemisId(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return $entity->user->name;
    }

    public function onGetGrade(Event $event, Entity $entity)
    {
        // some class not configure in the institutionClassStudents, therefore using the institutionStudents
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $studentId = $entity->student_id;
        $academicPeriodId = $entity->academic_period_id;

        $educationGradeData = $InstitutionStudents->find()
            ->where([
                'student_id' => $studentId,
                'academic_period_id' => $academicPeriodId,
                'student_status_id' => 1 // enrolled status
            ])
            ->first();

        $educationGradesName = '';
        if (isset($educationGradeData->education_grade_id)) {
            $educationGradesName = $EducationGrades->get($educationGradeData->education_grade_id)->name;
        }

        return $educationGradesName;
    }

    public function onGetClass(Event $event, Entity $entity)
    {
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $studentId = $entity->student_id;
        $academicPeriodId = $entity->academic_period_id;

        $institutionClassesData = $InstitutionClassStudents->find()
            ->where([
                'student_id' => $studentId,
                'academic_period_id' => $academicPeriodId,
                'student_status_id' => 1 // enrolled status
            ])
            ->first();

        $institutionClassesName = '';
        if (isset($institutionClassesData->institution_class_id)) {
            $institutionClassesName = $InstitutionClasses->get($institutionClassesData->institution_class_id)->name;
        }

        return $institutionClassesName;
    }

    public function afterSaveOrDelete(Event $mainEvent, Entity $afterSaveOrDeleteEntity)
    {
        $criteriaModel = $afterSaveOrDeleteEntity->source();

        // on student admission this will be updated (student gender, guardians, student repeated)
        $consolidatedModel = ['Institution.StudentUser', 'Student.Guardians', 'Institution.IndividualPromotion'];

        if (in_array($criteriaModel, $consolidatedModel)) {
            $criteriaModel = 'Institution.Students';
        }

        $RiskCriterias = TableRegistry::get('Risk.RiskCriterias');
        $criteriaTable = TableRegistry::get($criteriaModel);

        // to get studentId
        if (isset($afterSaveOrDeleteEntity->student_id)) {
            $studentId = $afterSaveOrDeleteEntity->student_id;
        } else {
            // for gender will be using security_user table the student_id is the ID
            $studentId = $this->getStudentId($criteriaTable, $afterSaveOrDeleteEntity);
        }

        // to get the academicPeriodId
        if (isset($afterSaveOrDeleteEntity->academic_period_id)) {
            $academicPeriodId = $afterSaveOrDeleteEntity->academic_period_id;
        } else {
            // afterDelete $afterSaveOrDeleteEntity doesnt have academicPeriodId, model also have different date
            $academicPeriodId = $this->getAcademicPeriodId($criteriaTable, $afterSaveOrDeleteEntity);
        }

        // to get the institutionId
        if (isset($afterSaveOrDeleteEntity->institution_id)) {
            $institutionId = $afterSaveOrDeleteEntity->institution_id;
        } else {
            // for gender will be using security_user table, doesnt have any institution
            $institutionId = $this->getInstitutionId($criteriaTable, $afterSaveOrDeleteEntity, $academicPeriodId);
        }

        if (!empty($institutionId)) {
            $criteriaRecord = $this->Risks->getCriteriaByModel($criteriaModel, $institutionId);

            foreach ($criteriaRecord as $criteriaDataKey => $criteriaDataObj) {
                // to get the risks criteria to get the value on the student_risk_criterias
                $risksCriteriaResults = $RiskCriterias->find('ActiveRiskCriteria', ['criteria_key' => $criteriaDataKey, 'institution_id' => $institutionId]);

                if (!$risksCriteriaResults->isEmpty()) {
                    foreach ($risksCriteriaResults as $key => $risksCriteriaData) {
                        $riskId = $risksCriteriaData->risk_id;
                        $threshold = $risksCriteriaData->threshold;
                        $operator = $risksCriteriaData->operator;
                        $criteria = $risksCriteriaData->criteria;

                        $params = new ArrayObject([
                            'institution_id' => $institutionId,
                            'student_id' => $studentId,
                            'academic_period_id' => $academicPeriodId,
                            'criteria_name' => $criteriaDataKey
                        ]);

                        $event = $criteriaTable->dispatchEvent('Model.InstitutionStudentRisks.calculateRiskValue', [$params], $this);

                        if ($event->isStopped()) {
                            $mainEvent->stopPropagation();
                            return $event->result;
                        }

                        $valueIndexData = $event->result;


                        // if the condition fulfilled then the value will be saved as its value, if not saved as null
                        switch ($operator) {
                            case 1: // '<='
                                if($valueIndexData <= $threshold){
                                    $valueIndex = $valueIndexData;
                                } else {
                                    $valueIndex = null;
                                }
                                break;

                            case 2: // '>='
                                if($valueIndexData >= $threshold){
                                    $valueIndex = $valueIndexData;
                                } else {
                                    $valueIndex = null;
                                }
                                break;

                            case 3: // '='
                            case 11: // for status Repeated
                                // value risk is an array (valueRisk[threshold] = value)
                                if ($threshold = $valueIndexData) {
                                    $valueIndex = 'True';
                                } else {
                                    $valueIndex = null;
                                }
                                break;
                        }

                        // saving association to student_risks_criterias
                        $criteriaData = [
                            'value' => $valueIndex,
                            'risk_criteria_id' => $risksCriteriaData->id
                        ];

                        $conditions = [
                            $this->aliasField('academic_period_id') => $academicPeriodId,
                            $this->aliasField('institution_id') => $institutionId,
                            $this->aliasField('student_id') => $studentId,
                            $this->aliasField('risk_id') => $riskId
                        ];

                        if ($criteria == 'SpecialNeeds') {
                            if (isset($afterSaveOrDeleteEntity->trigger_from) && $afterSaveOrDeleteEntity->trigger_from == 'shell') {
                            } else {
                                $conditions = [
                                    $this->aliasField('academic_period_id') => $academicPeriodId,
                                    $this->aliasField('student_id') => $studentId,
                                    $this->aliasField('risk_id') => $riskId
                                ];
                            }
                        }

                        $institutionStudentRisksResults = $this->find()
                            ->where([$conditions])
                            ->all();

                        // to update and add new records into the institution_student_risks
                        if (!$institutionStudentRisksResults->isEmpty()) {
                            // $entity = $institutionStudentRisksResults->first();
                            foreach ($institutionStudentRisksResults as $institutionStudentRisksResultsObj) {
                                $entity = $institutionStudentRisksResultsObj;

                                $studentRisksCriteriaResults = $this->StudentRisksCriterias->find()
                                    ->where([
                                        $this->StudentRisksCriterias->aliasField('institution_student_risk_id') => $entity->id,
                                        $this->StudentRisksCriterias->aliasField('risk_criteria_id') => $risksCriteriaData->id
                                    ])
                                    ->all();

                                // find id from db
                                if (!$studentRisksCriteriaResults->isEmpty()) {
                                    $criteriaEntity = $studentRisksCriteriaResults->first();
                                    $criteriaData['id'] = $criteriaEntity->id;
                                }

                                $data = [];
                                $data['student_risks_criterias'][] = $criteriaData;

                                $patchOptions = ['validate' => false];
                                $entity = $this->patchEntity($entity, $data, $patchOptions);

                                $this->save($entity);
                            }
                        } else {
                            $entity = $this->newEntity([
                                'average_risk' => 0,
                                'total_risk' => 0,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                                'student_id' => $studentId,
                                'risk_id' => $riskId
                            ]);

                            $data = [];
                            $data['student_risks_criterias'][] = $criteriaData;

                            $patchOptions = ['validate' => false];
                            $entity = $this->patchEntity($entity, $data, $patchOptions);

                            $this->save($entity);
                        }
                    }
                }
            }
        }

		$InstitutionStudents = TableRegistry::get('Institution.Students');
		$eventAction = explode('.', $mainEvent->name);
		
		if(!empty($eventAction[2]) && ($eventAction[2] == 'afterSave')) {  
		
			$bodyData = $InstitutionStudents->find('all',
									[ 'contain' => [
										'Institutions',
										'EducationGrades',
										'AcademicPeriods',
										'StudentStatuses',
										'Users',
										'Users.Genders',
										'Users.MainNationalities',
										'Users.Identities.IdentityTypes',
										'Users.AddressAreas',
										'Users.BirthplaceAreas',
										'Users.Contacts.ContactTypes'
									],
						])->where([
							$InstitutionStudents->aliasField('student_id') => $afterSaveOrDeleteEntity->id
						]);

			
			if (!empty($bodyData)) { 
				foreach ($bodyData as $key => $value) { 
					$user_id = $value->user->id;
					$openemis_no = $value->user->openemis_no;
					$first_name = $value->user->first_name;
					$middle_name = $value->user->middle_name;
					$third_name = $value->user->third_name;
					$last_name = $value->user->last_name;
					$preferred_name = $value->user->preferred_name;
					$gender = $value->user->gender->name;
					$nationality = $value->user->main_nationality->name;
					
					if(!empty($value->user->date_of_birth)) {
						foreach ($value->user->date_of_birth as $key => $date) {
							$dateOfBirth = $date;
						}
					}
					
					$address = $value->user->address;
					$postalCode = $value->user->postal_code;
					$addressArea = $value->user->address_area->name;
					$birthplaceArea = $value->user->birthplace_area->name;
					
					$contactValue = [];
					$contactType = [];
					if(!empty($value->user['contacts'])) {
						foreach ($value->user['contacts'] as $key => $contact) {
							$contactValue[] = $contact->value;
							$contactType[] = $contact->contact_type->name;
						}
					}
					
					$identityNumber = [];
					$identityType = [];
					if(!empty($value->user['identities'])) {
						foreach ($value->user['identities'] as $key => $identity) {
							$identityNumber[] = $identity->number;
							$identityType[] = $identity->identity_type->name;
						}
					}
					
					$username = $value->user->username;
					$institution_id = $value->institution->id;
					$institutionName = $value->institution->name;
					$institutionCode = $value->institution->code;
					$educationGrade = $value->education_grade->name;
					$academicCode = $value->academic_period->code;
					$academicGrade = $value->academic_period->name;
					$studentStatus = $value->student_status->name;
					
					if(!empty($value->start_date)) {
						foreach ($value->start_date as $key => $date) {
							$startDate = $date;
						}
					}
					
					if(!empty($value->end_date)) {
						foreach ($value->end_date as $key => $date) {
							$endDate = $date;
						}
					}
					
				}
			}
			$body = array();
				   
			$body = [   
				'security_users_id' => !empty($user_id) ? $user_id : NULL,
				'security_users_openemis_no' => !empty($openemis_no) ? $openemis_no : NULL,
				'security_users_first_name' =>	!empty($first_name) ? $first_name : NULL,
				'security_users_middle_name' => !empty($middle_name) ? $middle_name : NULL,
				'security_users_third_name' => !empty($third_name) ? $third_name : NULL,
				'security_users_last_name' => !empty($last_name) ? $last_name : NULL,
				'security_users_preferred_name' => !empty($preferred_name) ? $preferred_name : NULL,
				'security_users_gender' => !empty($gender) ? $gender : NULL,
				'security_users_date_of_birth' => !empty($dateOfBirth) ? date("d-m-Y", strtotime($dateOfBirth)) : NULL,
				'security_users_address' => !empty($address) ? $address : NULL,
				'security_users_postal_code' => !empty($postalCode) ? $postalCode : NULL,
				'area_administrative_name_birthplace' => !empty($addressArea) ? $addressArea : NULL,
				'area_administrative_name_address' => !empty($birthplaceArea) ? $birthplaceArea : NULL,
				'contact_type_name' => !empty($contactType) ? $contactType : NULL,
				'user_contact_type_value' => !empty($contactValue) ? $contactValue : NULL,
				'nationality_name' => !empty($nationality) ? $nationality : NULL,
				'identity_type_name' => !empty($identityType) ? $identityType : NULL,
				'user_identities_number' => !empty($identityNumber) ? $identityNumber : NULL,
				'security_user_username' => !empty($username) ? $username : NULL,
				'institutions_id' => !empty($institution_id) ? $institution_id : NULL,
				'institutions_code' => !empty($institutionCode) ? $institutionCode : NULL,
				'institutions_name' => !empty($institutionName) ? $institutionName : NULL,
				'academic_period_code' => !empty($academicCode) ? $academicCode : NULL,
				'academic_period_name' => !empty($academicGrade) ? $academicGrade : NULL,
				'education_grade_name' => !empty($educationGrade) ? $educationGrade : NULL,
				'student_status_name' => !empty($studentStatus) ? $studentStatus : NULL,
				'institution_students_start_date' => !empty($startDate) ? date("d-m-Y", strtotime($startDate)) : NULL,
				'institution_students_end_date' => !empty($endDate) ? date("d-m-Y", strtotime($endDate)) : NULL,	
			];
			
			if (!$afterSaveOrDeleteEntity->isNew()) {
				$Webhooks = TableRegistry::get('Webhook.Webhooks');
				if (!empty($afterSaveOrDeleteEntity->modified_user_id)) {
					$Webhooks->triggerShell('student_update', ['username' => ''], $body);
				}
			}
		}
		
    }

    // will update the total risk on the institution_student_risks
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $academicPeriodId = $entity->academic_period_id;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;

        // $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');

        $InstitutionStudentRisksData = $this->find()
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
            ])
            ->all();

        if (!$InstitutionStudentRisksData->isEmpty()) {
            foreach ($InstitutionStudentRisksData as $institutionStudentRisksObj) {
                $InstitutionStudentRisksid = $institutionStudentRisksObj->id;

                $StudentRisksCriteriasResults = $this->StudentRisksCriterias->find()
                    ->where([$this->StudentRisksCriterias->aliasField('institution_student_risk_id') => $InstitutionStudentRisksid])
                    ->all();

                $riskTotal = [];
                // to get the total of the risk of the student
                foreach ($StudentRisksCriteriasResults as $studentRisksCriteriasObj) {
                    $value = $studentRisksCriteriasObj->value;
                    $riskCriteriaId = $studentRisksCriteriasObj->risk_criteria_id;

                    $riskValue = $this->StudentRisksCriterias->getRiskValue($value, $riskCriteriaId, $institutionId, $studentId, $academicPeriodId);
                    $riskTotal[$studentRisksCriteriasObj->institution_student_risk_id] = !empty($riskTotal[$studentRisksCriteriasObj->institution_student_risk_id]) ? $riskTotal[$studentRisksCriteriasObj->institution_student_risk_id] : 0 ;
                    $riskTotal[$studentRisksCriteriasObj->institution_student_risk_id] = $riskTotal[$studentRisksCriteriasObj->institution_student_risk_id] + $riskValue;
                }

                foreach ($riskTotal as $key => $obj) {
                    $this->query()
                        ->update()
                        ->set(['total_risk' => $obj])
                        ->where([
                            'id' => $key
                        ])
                        ->execute();
                }
            }
        }
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        // $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
        $tableHeaders = $this->getMessage('Risk.TableHeader');
        array_splice($tableHeaders, 3, 0, __('Value')); // adding value header
        $tableHeaders[] = __('References');
        $tableCells = [];
        $fieldKey = 'risk_criterias';

        $riskId = $entity->risk->id;
        $institutionId = $entity->institution->id;
        $studentId = $entity->user->id;
        $academicPeriodId = $entity->academic_period_id;

        $institutionStudentRiskId = $this->paramsDecode($this->paramsPass(0))['id']; // paramsPass(0) after the hash of Id

        if ($action == 'view') {
            $studentRisksCriteriasResults = $this->StudentRisksCriterias->find()
                ->contain(['RiskCriterias'])
                ->where([
                    $this->StudentRisksCriterias->aliasField('institution_student_risk_id') => $institutionStudentRiskId,
                    $this->StudentRisksCriterias->aliasField('value') . ' IS NOT NULL'
                ])
                ->order(['criteria','threshold'])
                ->all();

            foreach ($studentRisksCriteriasResults as $obj) {
                if (isset($obj->risk_criteria)) {
                    $riskCriteriasId = $obj->risk_criteria->id;

                    $criteriaName = $obj->risk_criteria->criteria;
                    $operatorId = $obj->risk_criteria->operator;
                    $operator = $this->Risks->getOperatorDetails($operatorId);
                    $threshold = $obj->risk_criteria->threshold;

                    $value = $this->StudentRisksCriterias->getValue($institutionStudentRiskId, $riskCriteriasId);

                    $criteriaDetails = $this->Risks->getCriteriasDetails($criteriaName);
                    $CriteriaModel = TableRegistry::get($criteriaDetails['model']);

                    if ($value == 'True') {
                        // Comparison like behaviour
                        $LookupModel = TableRegistry::get($criteriaDetails['threshold']['lookupModel']);

                        // to get total number of behaviour
                        $getValueIndex = $CriteriaModel->getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName);

                        $quantity = '';
                        if ($getValueIndex[$threshold] > 1) {
                            $quantity = ' ( x'. $getValueIndex[$threshold]. ' )';
                        }

                        $riskValue = '<div style="color : red">' . $obj->risk_criteria->risk_value . $quantity  .'</div>';

                        // for reference tooltip
                        $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);

                        // for threshold name
                        $thresholdName = $LookupModel->get($threshold)->name;
                        $threshold = $thresholdName;
                        if ($thresholdName == 'Repeated') {
                            $threshold = $this->Risks->getCriteriasDetails($criteriaName)['threshold']['value']; // 'Yes'
                        }
                    } else {
                        // numeric value come here (absence quantity, results)
                        // for value
                        $riskValue = '<div style="color : red">'.$obj->risk_criteria->risk_value.'</div>';

                        // for the reference tooltip
                        $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);
                    }

                    // blue info tooltip
                    $tooltipReference = '<i class="fa fa-info-circle fa-lg icon-blue" data-placement="left" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="'.$reference.'"></i>';

                    if (!is_numeric($threshold)) {
                        $threshold = __($threshold);
                    }

                    if (!is_numeric($value)) {
                        $value = __($value);
                    }

                    // to put in the table
                    $rowData = [];
                    $rowData[] = __($this->Risks->getCriteriasDetails($criteriaName)['name']);
                    $rowData[] = __($operator);
                    $rowData[] = $threshold;
                    $rowData[] = $value;
                    $rowData[] = $riskValue;
                    $rowData[] = $tooltipReference;

                    $tableCells [] = $rowData;
                }
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Risk.Risks/' . $fieldKey, ['attr' => $attr]);
    }


    public function getStudentId($criteriaTable, $afterSaveOrDeleteEntity)
    {
        switch ($criteriaTable->alias()) {
            case 'Students': // The student_id is the Id
                $studentId = $afterSaveOrDeleteEntity->id;
                break;

            case 'SpecialNeedsAssessments': // The security_user_id is the Id
                $studentId = $afterSaveOrDeleteEntity->security_user_id;
                break;
        }

        return $studentId;
    }

    public function getAcademicPeriodId($criteriaTable, $afterSaveOrDeleteEntity)
    {
        // afterDelete $afterSaveOrDeleteEntity doesnt have academicPeriodId, every model also have different date
        switch ($criteriaTable->alias()) {
            case 'InstitutionStudentAbsences': // have start date and end date
                $startDate = $afterSaveOrDeleteEntity->date;
                $endDate = $afterSaveOrDeleteEntity->date;
                $academicPeriodId = $this->AcademicPeriods->getAcademicPeriodId($startDate, $endDate);
                break;

            case 'StudentBehaviours': // have date of behaviours
                $date = $afterSaveOrDeleteEntity->date_of_behaviour;
                $academicPeriodId = $this->AcademicPeriods->getAcademicPeriodIdByDate($date);
                break;

            case 'Students': // no date, will get the current academic period Id
                $academicPeriodId = $this->AcademicPeriods->getCurrent();
                break;

            case 'SpecialNeedsAssessments': // have special need date
                $date = $afterSaveOrDeleteEntity->date;
                $academicPeriodId = $this->AcademicPeriods->getAcademicPeriodIdByDate($date);
                break;
        }

        return $academicPeriodId;
    }

    public function getInstitutionId($criteriaTable, $afterSaveOrDeleteEntity, $academicPeriodId)
    {
        $institutionId = null;
        switch ($criteriaTable->alias()) {
            case 'Students':
                // guardian will have student_id, while gender only have id
                $studentId = !empty($afterSaveOrDeleteEntity->student_id) ? $afterSaveOrDeleteEntity->student_id : $afterSaveOrDeleteEntity->id;

                $Students = TableRegistry::get('Institution.Students');
                $institutionId = $Students->getInstitutionIdByUser($studentId, $academicPeriodId);

                break;

            case 'SpecialNeedsAssessments':
                $studentId = $afterSaveOrDeleteEntity->security_user_id;
                $Students = TableRegistry::get('Institution.Students');
                $institutionId = $Students->getInstitutionIdByUser($studentId, $academicPeriodId);

                break;
        }

        return $institutionId;
    }
}
