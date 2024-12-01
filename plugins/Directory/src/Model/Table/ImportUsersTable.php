<?php

namespace Directory\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use Cake\Utility\Text;

class ImportUsersTable extends AppTable
{
    const IS_STAFF = "is_staff";
    const IS_STUDENT = "is_student";
    private $Users;
    private $ConfigItems;
//    private $Nationalities;
    private $IdentityTypes;
    private $UserIdentities;
    private $accountTypes;
    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
                'plugin' => 'User',
                'model' => 'Users',
                'row_heights' => [75, 25, 25],
                'header_font_size' => 16,
                'headings' => [
                    [
                        'title' => 'Import Users Data',
                        'title_range' => 'C1:R1',
                        'subtitle' => '* Mandatory for User Import',
                        'subtitle_range' => 'D2:R2'
                    ],
                    [
                        'title' => 'Import User into an Institution',
                        'title_range' => 'S1:W1',
                        'subtitle' => '** Mandatory for Institution Import',
                        'subtitle_range' => 'S2:W2'
                    ],
                    [
                        'title' => 'Import Guardian for the User',
                        'title_range' => 'X1:AN1',
                        'subtitle' => '*** Mandatory for Guardian Import',
                        'subtitle_range' => 'X2:AN2'
                    ]
                ]
            ]
        );

        // register table once
        $this->Users = self::getDynamicTableInstance('User.Users');
        $this->ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
//        $this->Nationalities = self::getDynamicTableInstance('FieldOption.Nationalities');
        $this->IdentityTypes = self::getDynamicTableInstance('FieldOption.IdentityTypes');
        $this->UserIdentities = self::getDynamicTableInstance('User.Identities');

        $prefix = $this->ConfigItems->value('openemis_id_prefix');
        $prefix = explode(",", $prefix);
        $prefix = (isset($prefix[1]) && $prefix[1] > 0) ? $prefix[0] : '';

        //when add the accountTypes, please add in User.UsersTable validationDefault function
        $this->accountTypes = [
            'is_student' => [
                'id' => 'is_student',
                'code' => 'STU',
                'name' => __('Students'),
                'model' => 'Student',
                'prefix' => $prefix,
            ],
            'is_staff' => [
                'id' => 'is_staff',
                'code' => 'STA',
                'name' => __('Staff'),
                'model' => 'Staff',
                'prefix' => $prefix,
            ],
            'is_guardian' => [
                'id' => 'is_guardian',
                'code' => 'GUA',
                'name' => __('Guardians'),
                'model' => 'Guardian',
                'prefix' => $prefix,
            ],
            'others' => [
                'id' => 'others',
                'code' => 'OTH',
                'name' => __('Others'),
                'model' => '',
                'prefix' => $prefix,
            ]
        ];
        $this->addBehavior('ControllerAction.FileUpload');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportPopulateAreaAdministrativesData' => 'onImportPopulateAreaAdministrativesData',
            'Model.import.onImportPopulateGendersData' => 'onImportPopulateGendersData',
            'Model.import.onImportPopulateAccountTypesData' => 'onImportPopulateAccountTypesData',
            'Model.import.onImportPopulateContactTypesData' => 'onImportPopulateContactTypesData',
            'Model.import.onImportGetAccountTypesId' => 'onImportGetAccountTypesId',
            'Model.import.onImportPopulateAcademicPeriodsData' => 'onImportPopulateAcademicPeriodsData',
            'Model.import.onImportPopulateEducationGradesData' => 'onImportPopulateEducationGradesData',
            'Model.import.onImportPopulateGuardianRelationsData' => 'onImportPopulateGuardianRelationsData',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.import.onImportCustomHeader' => 'onImportCustomHeader',
            'Model.import.onImportCheckIdentityConfig' => 'onImportCheckIdentityConfig',
            'Model.import.onImportGetContact' => 'onImportGetContact'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onImportCheckUnique(Event $event,
                                        $sheet,
                                        $row,
                                        $columns,
                                        ArrayObject $tempRow,
                                        ArrayObject $importedUniqueCodes,
                                        ArrayObject $rowInvalidCodeCols) //POCOR-8082
    {
        $columns = new Collection($columns);
        $extractedOpenemisNo = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'openemis_no';
        });

        $openemisNoIndex = key($extractedOpenemisNo->toArray()) + 1;
        $openemisNo = $sheet->getCellByColumnAndRow($openemisNoIndex, $row)->getValue();

        if (in_array($openemisNo, $importedUniqueCodes->getArrayCopy())) {
            $rowInvalidCodeCols['openemis_no'] = $this->getExcelLabel('Import', 'duplicate_unique_key');
            return false;
        }

        $accountType = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'account_type';
        });
        $accountTypeIndex = key($accountType->toArray()) + 1;
        $accountType = $sheet->getCellByColumnAndRow($accountTypeIndex, $row)->getValue();
        $accountTypeId = $this->getAccountTypeId($accountType);
        $tempRow['account_type'] = $accountTypeId;
        if (empty($tempRow['account_type'])) {
            $tempRow['duplicates'] = __('Account type cannot be empty');
            $rowInvalidCodeCols['account_type'] = $tempRow['duplicates'];
//            $tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes, $row, 'others');
//            $tempRow['username'] = $tempRow['openemis_no'];
            return false;
        }
        if ($openemisNo) {
            $user = $this->Users->find()->where(['openemis_no' => $openemisNo])->first();
        }
        if (!$user) {
            try{
                $username = "";
                $tempRow['entity'] = $this->Users->newEntity(['openemis_no' => $openemisNo]);
                if(strlen($openemisNo) > 1){
                    $username = Text::slug($openemisNo);
                }
                if(strlen($username) < 6){
                    $username = $username . $this->getNewOpenEmisNo($importedUniqueCodes, $row, $tempRow['account_type']);
                    $tempRow['openemis_no'] = $username;
                }
                $tempRow['username'] = $username;
            } catch (\Exception $exception) {
                $rowInvalidCodeCols['openemis_no'] = __($exception->getMessage());
                return false;
            }
        } else {
            $tempRow['entity'] = $user;
        }

        if (!empty($tempRow['account_type'])) {
            // setting is_student = 1, or is_staff = 1, or is_guardian = 1
            $tempRow[$tempRow['account_type']] = 1;
        }
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity)
    {
        $importedUniqueCodes[] = $entity->openemis_no;
    }

    public function onImportGetAccountTypesId(Event $event, $cellValue)
    {
        return $this->getAccountTypeId($cellValue);
    }

    public function onImportGetAccountTypesName(Event $event, $value)
    {
        $name = '';
        foreach ($this->accountTypes as $key => $type) {
            if ($type['code'] == $value) {
                $name = $type['name'];
                break;
            }
        }
        return $name;
    }

    public function onImportPopulateAccountTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $translatedReadableCol = $this->getExcelLabel('Imports', 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        $modelData = $this->accountTypes;
        foreach ($modelData as $row) {
            $data[$columnOrder]['data'][] = [
                $row['name'],
                $row[$lookupColumn]
            ];
        }
    }

    public function onImportPopulateContactTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        //Join contact type and contact options for displaying the name of contact type and its contact option name at excel for user to see
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all', [
            'contain' => ['ContactOptions']
        ])
            ->select(['ContactOptions.name', 'name', $lookupColumn])
            ->order($lookupModel . '.order');

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->contact_option->name . ' - ' . $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateAreaAdministrativesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
            ->select(['name', $lookupColumn])
                                ->order($lookupModel.'.area_administrative_level_id', $lookupModel.'.order')
                                ;

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateGendersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')
            ->select(['name', $lookupColumn])
                                ->order([$lookupModel.'.order'])
                                ;

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        //            Log::debug(print_r($tempRow, true));

        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $isStaff = ($tempRow['account_type'] == self::IS_STAFF);
        $isStudent = ($tempRow['account_type'] == self::IS_STUDENT);
        $identity_type_id = isset($tempRow['identity_type_id']) ? $tempRow['identity_type_id'] : false;
        $identity_number = isset($tempRow['identity_number']) ? $tempRow['identity_number'] : false;
        $contact_type = $tempRow['contact_type'];
        $have_error = false;
        // identity number mandatory
        if ($isStaff) {
            $have_error = $have_error || $this->checkStaffIdentityNationality($tempRow, $rowInvalidCodeCols);
        }

        if ($isStudent) {
            $have_error = $have_error || $this->checkStudentIdentityNationality($tempRow, $rowInvalidCodeCols);

        }

        //if identity type selected, then need to specify identity number
        if ($identity_type_id) {
            $have_error = $have_error ||  $this->checkIdentityNumber($tempRow, $rowInvalidCodeCols);
        }

        //if identity number is not empty, need to ensure it has identity type selected, it has to be unique and following the validation patter (if there is)
        if ($identity_number) {
            $have_error = $have_error ||  $this->checkIdentityTypeId($tempRow, $rowInvalidCodeCols);
        }
        if (isset($contact_type)) {
            $have_error = $have_error ||  $this->checkContact($tempRow, $rowInvalidCodeCols);
        }
        if ($isStudent) {
            $have_error = $have_error || $this->checkInstitution($tempRow, $rowInvalidCodeCols);
            $institution_id = $tempRow['institution_id'];
            $have_error = $have_error || $this->checkStartDate($tempRow, $rowInvalidCodeCols);
            $start_date = $tempRow['start_date'];
            $have_error = $have_error || $this->checkAcademicPeriodId($tempRow, $rowInvalidCodeCols);
            $academicPeriodId = $tempRow['academic_period_id'];
            $have_error = $have_error || $this->checkInstitution($tempRow, $rowInvalidCodeCols);
            $institution_id = $tempRow['institution_id'];
            $have_error = $have_error || $this->checkInstitution($tempRow, $rowInvalidCodeCols);
            $institution_id = $tempRow['institution_id'];

//            if($institution_id){
//                $user = $tempRow['entity'];
//                if($user){
//                    $security_user_id = $user->id;
//                }
//                $currentUserId = $this->Auth->user('id');
//                $admission = [
//                    'institution_id' => $institution_id,
//                    'assignee_id' => $currentUserId,
//                ];
//                if($security_user_id){
//                  $admission['student_id'] = $security_user_id;
//                }
//                $admissionEntity = $StudentAdmissions->newEntity($admission);
//                if ($admissionEntity && $admissionEntity->getErrors()) { // POCOR-7973
//                    $errorMessages = array_reduce(
//                        $admissionEntity->getErrors(),
//                        function ($carry, $errors) {
//                            return array_merge($carry, $errors);
//                        },
//                        []
//                    );
//
//                    $rowInvalidCodeCols['institution_code'] = implode(',', $errorMessages);
//                    $have_error = true;
//                }
//                $tempRow['admission_entity'] = $admissionEntity;
//                $tempRow['end_date'] = false;
//                $tempRow[] =; //POCOR-7282
//                // Optional fields which will be validated should be set with a default value on initialisation
//                $tempRow['institution_class_id'] = null;
//            }
        }
        Log::debug(print_r($tempRow, true));
        // Nationalities Mandatory

        if($have_error){
            return false;
        }

        //add identifier that later will be used on User afterSave
        $tempRow['record_source'] = 'import_user';

        return true;
    }
    private static function getInstitutionGradeIds($academic_period_id, $institution_id): array
    {
        $InstitutionGrades = self::getDynamicTableInstance('Institution.InstitutionGrades');

        return $InstitutionGrades->find('list', [
            'keyField' => 'id',
            'valueField' => 'education_grade_id'
        ])
            ->LeftJoin(['EducationGrades' => 'education_grades'], [
                'EducationGrades.id = ' . $InstitutionGrades->aliasField('education_grade_id')
            ])
            ->LeftJoin(['EducationProgrammes' => 'education_programmes'], [
                'EducationProgrammes.id = EducationGrades.education_programme_id'
            ])
            ->LeftJoin(['EducationCycles' => 'education_cycles'], [
                'EducationCycles.id = EducationProgrammes.education_cycle_id'
            ])
            ->LeftJoin(['EducationLevels' => 'education_levels'], [
                'EducationLevels.id = EducationCycles.education_level_id'
            ])
            ->LeftJoin(['EducationSystems' => 'education_systems'], [
                'EducationSystems.id = EducationLevels.education_system_id'
            ])
            ->LeftJoin(['AcademicPeriods' => 'academic_periods'], [
                'AcademicPeriods.id = EducationSystems.academic_period_id'
            ])
            ->where([
                $InstitutionGrades->aliasField('institution_id') => $institution_id,
                'AcademicPeriods.id' => $academic_period_id
            ])
            ->toArray();
    }

    private static function checkInstitutionGrade(ArrayObject &$tempRow, ArrayObject &$rowInvalidCodeCols)
    {
        $academic_period_id = $tempRow['academic_period_id'];
        $institution_id = $tempRow['institution_id'];
        $gradesInInstitution = self::getInstitutionGradeIds($academic_period_id, $institution_id);
        $education_grade_id = $tempRow['education_grade_id'];
        $InstitutionGrades = self::getDynamicTableInstance('Institution.InstitutionGrades');
        if (!in_array($education_grade_id, $gradesInInstitution)) {
            $rowInvalidCodeCols['education_grade_id'] = __('Selected education grade is not being offered in this institution');
            return false;
        }
        $institutionGrade = $InstitutionGrades
            ->find()
            ->contain('EducationGrades.EducationProgrammes.EducationCycles')
            ->where([
                $InstitutionGrades->aliasField('education_grade_id') => $education_grade_id,
                $InstitutionGrades->aliasField('institution_id') => $institution_id
            ]);
        if ($institutionGrade->isEmpty()) {
            $rowInvalidCodeCols['education_grade_id'] = __('No matching education grade.');
            return false;
        }

        $institutionGrade = $institutionGrade->first();
        return $institutionGrade;
    }
    public function onImportPopulateNationalitiesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel);

        $modelData = $lookedUpTable->find()
            ->contain('IdentityTypes')
            ->select([
                $lookedUpTable->aliasField($lookupColumn),
                $lookedUpTable->aliasField('name'),
                'IdentityTypes.name'
            ])
            ->order($lookedUpTable->aliasField('order'));

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol, __('Identity Types')];
        if (!empty($modelData)) {
            foreach ($modelData->toArray() as $row) {
                $identityTypeName = !empty($row->identity_type) ? $row->identity_type->name : '';
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn},
                    $identityTypeName
                ];
            }
        }
    }

    protected function getNewOpenEmisNo(ArrayObject $importedUniqueCodes, $row, $accountType)
    {
        $notUnique = true;
        $val = $this->Users->getUniqueOpenemisId();
        while ($notUnique) {
            $user = $this->Users->find()->select(['id'])->where([
                $this->Users->aliasField('openemis_no') => $val,
                $this->Users->aliasField('username') => $val
            ])->first();
            if ($user) {
                $val = $this->Users->getUniqueOpenemisId();
            } else {
                $notUnique = false;
            }
        }
        return $val;
    }

    protected function getAccountTypeId($cellValue)
    {
        $accountType = '';
        foreach ($this->accountTypes as $key => $type) {
            if ($type['code'] == $cellValue) {
                $accountType = $type['id'];
                break;
            }
        }
        return $accountType;
    }

    public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow)
    {
        $flipped = array_flip($columns);
        $key = $flipped['openemis_no'];
        $tempPassedRecord['data'][$key] = $clonedEntity->openemis_no;
    }

    public function onImportCustomHeader(Event $event, $customDataSource, ArrayObject $customHeaderData)
    {

        $customTable = self::getDynamicTableInstance($customDataSource);

        switch ($customDataSource) { //this is for specify column name based on the data
            case 'FieldOption.IdentityTypes':
                $customTableRecords = $customTable
                    ->find()
                    ->where([
                        $customTable->aliasField('default') => 1
                    ])
                    ->toArray();

                if (count($customTableRecords)) { //if default found

                    $column = $customTableRecords[0]['name'];
                    $customHeaderData[] = true; //show descriptions
                } else { //no default defined, then put warning on header

                    $column = "Please Define Default Identity Type";
                    $customHeaderData[] = false; //dont show descriptions
                }

                break;
        }

        $customHeaderData[] = $column;
    }

    public function onImportCheckIdentityConfig(Event $event, $tempRow, $cellValue)
    {
        $result = true;

        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $isStudentIdentityMandatory = $ConfigItems->value('StudentIdentities');
        $isStaffIdentityMandatory = $ConfigItems->value('StaffIdentities');

        if (($tempRow['account_type'] == self::IS_STAFF) && ($isStaffIdentityMandatory) && (empty($cellValue))) {
            $result = 'Staff identity is mandatory';
        };

        if (($tempRow['account_type'] == self::IS_STUDENT) && ($isStudentIdentityMandatory) && (empty($cellValue))) {
            $result = 'Student identity is mandatory';
        };

        if ($result === true) { //if checking mandatory is ok, then check the uniqueness of the Identity

            if (!empty($cellValue)) { //if Identity Number is not empty

                $userIdentitiesTable = $this->Users->Identities;

                $defaultIdentityType = $userIdentitiesTable->IdentityTypes->getDefaultValue();

                if ($defaultIdentityType) { //if has default identity

                    $countIdentity = $userIdentitiesTable->find()
                        ->where([
                            'number' => $cellValue,
                            'identity_type_id' => $defaultIdentityType
                        ])
                        ->count(); //get the record which has same identity number and type

                    if ($countIdentity) {
                        $result = "Identity number must be unique";
                    }
                } else {
                    $result = "No default identity type set";
                }
            }
        }
        return $result;
    }
    // POCOR-7973:start

    /**
     * @param $identity_number
     * @param $identity_type_id
     * @param null $nationality_id
     * @return bool|string
     */

    private function alreadyPresentIdentityTypeName($identity_number, $identity_type_id, $nationality_id = null)
    {
        $identityTypeName = false;
//        $this->log("$identity_number, $identity_type_id", 'debug');
        $where = [
            $this->UserIdentities->aliasField('number') => $identity_number,
            $this->UserIdentities->aliasField('identity_type_id') => $identity_type_id
        ];
        if($nationality_id){
            $where[$this->UserIdentities->aliasField('nationality_id')] = $nationality_id;
        }
        $query = $this->UserIdentities
            ->find()
            ->contain('IdentityTypes')
            ->where($where)
            ->first();
//        $this->log($query, 'debug');
        if (!empty($query)) {
            $identityTypeName = strval($query->identity_type->name);
        }
        return $identityTypeName;
    }

    /**
     * @param $identity_type_id
     * @param $identity_number
     * @return bool|false|int
     */
    private function checkIdentityNumberPattern($identity_type_id, $identity_number)
    {
        $isValidIdentityNumber = true;
        $query = $this->IdentityTypes->find()
            ->where([
                $this->IdentityTypes->aliasField('id') => $identity_type_id
            ])
            ->first();
        $validationPattern = $query->validation_pattern;
        if (!empty($validationPattern)) {
            $validationPattern = '/' . $validationPattern . '/';
            $isValidIdentityNumber = preg_match($validationPattern, $identity_number);
        }
        return $isValidIdentityNumber;
    }
    // POCOR-7973:end
    public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData as $row) {
                if ($row->academic_period_level_id == 1) { //validate that only period level "year" will be shown
                    $date = $row->start_date;
                    $data[$columnOrder]['data'][] = [
                        $row->name,
                        $row->start_date->format('d/m/Y'),
                        $row->end_date->format('d/m/Y'),
                        $row->{$lookupColumn}
                    ];
                }
            }
        }
    }

    public function onImportPopulateEducationGradesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel);
        $programmeHeader = $this->getExcelLabel($lookedUpTable, 'education_programme_id');
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$programmeHeader, $translatedReadableCol, $translatedCol];
        $modelData = $lookedUpTable->find('visible')
            ->contain(['EducationProgrammes'])
            ->select(['code', 'name', 'EducationProgrammes.name'])
            ->order([
                'EducationProgrammes.order',
                $lookupModel.'.order'
            ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->education_programme->name,
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }
    public function onImportPopulateGuardianRelationsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel);

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'Relation');

        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        $modelData = $lookedUpTable->find('all')
            ->select([
                'name',
                'id'
            ]);

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {

                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->id,
                ];
            }
        }

    }

    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkStaffIdentityNationality(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $isStaffIdentityMandatory = $ConfigItems->value('StaffIdentities');
        $isStaffNationalitiesMandatory = $ConfigItems->value('StaffNationalities');
        $identity_type_id = isset($tempRow['identity_type_id']) ? $tempRow['identity_type_id'] : false;
        $identity_number = isset($tempRow['identity_number']) ? $tempRow['identity_number'] : false;
        $nationality_id = isset($tempRow['nationality_id']) ? $tempRow['nationality_id'] : false;

        if ($isStaffIdentityMandatory == 1) {
            if (!$identity_type_id || !$identity_number) {
                //POCOR-7973
                $rowInvalidCodeCols['identity_number'] = $this->getExcelLabel('Import', 'identity_number_required');
                $have_error = true;
            }
        }
        if ($isStaffNationalitiesMandatory == 1) {
            if (!$nationality_id) {
                $rowInvalidCodeCols['nationality_id'] = $this->getExcelLabel('Import', 'nationality_required');
                $have_error = true;
            }
        }
        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkStudentIdentityNationality(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $isStudentIdentityMandatory = $ConfigItems->value('StudentIdentities');
        $isStudentNationalitiesMandatory = $ConfigItems->value('StudentNationalities');
        $identity_type_id = isset($tempRow['identity_type_id']) ? $tempRow['identity_type_id'] : false;
        $identity_number = isset($tempRow['identity_number']) ? $tempRow['identity_number'] : false;
        $nationality_id = isset($tempRow['nationality_id']) ? $tempRow['nationality_id'] : false;

        if ($isStudentIdentityMandatory == 1) {
            if (!$identity_type_id || !$identity_number) {
                $rowInvalidCodeCols['identity_number'] = $this->getExcelLabel('Import', 'identity_number_required');
                $have_error = true;
            }
        }
        if ($isStudentNationalitiesMandatory == 1) {
            if (!$nationality_id) {
                $rowInvalidCodeCols['nationality_id'] = $this->getExcelLabel('Import', 'nationality_required');
                $have_error = true;
            }
        }
        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkIdentityNumber(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        $identity_number = isset($tempRow['identity_number']) ? $tempRow['identity_number'] : false;

        if (!$identity_number) {
            $rowInvalidCodeCols['identity_number'] = $this->getExcelLabel('Import', 'identity_number_for_type_required');
            $have_error = true;
        }
        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkIdentityTypeId(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        $identity_type_id = isset($tempRow['identity_type_id']) ? $tempRow['identity_type_id'] : false;
        $identity_number = isset($tempRow['identity_number']) ? $tempRow['identity_number'] : false;
        $nationality_id = isset($tempRow['nationality_id']) ? $tempRow['nationality_id'] : false;

        if (!$identity_type_id) {
            $rowInvalidCodeCols['identity_type'] = $this->getExcelLabel('Import', 'identity_type_for_number_required');
            $have_error = true;
        }
        if ($identity_type_id) {
            // check whether same identity number exist for the selected identity type
            $identityTypeName = $this->alreadyPresentIdentityTypeName($identity_number, $identity_type_id, $nationality_id);
            if ($identityTypeName) {
                $rowInvalidCodeCols['identity_number'] = $this->getMessage('Import.identity_number_exist', ['sprintf' => [$identityTypeName]]);
                $have_error = true;
            }
            // following validation pattern.
            $isValidIdentityNumber = $this->checkIdentityNumberPattern($identity_type_id, $identity_number);
            if (!$isValidIdentityNumber) {
                $rowInvalidCodeCols['identity_number'] = $this->getExcelLabel('Import', 'identity_number_invalid_pattern');
                $have_error = true;
            }
        }
        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkContact(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        $contact = $tempRow['contact'];
        $contactType = $tempRow['contact_type'];
        if (!isset($contact)) {
            $rowInvalidCodeCols['contact'] = $this->getExcelLabel('Import', 'contact_required');
            $tempRow['contact_error'] = true;
            $have_error = true;
        }
        if (!isset($contact)) {
            //use contact_type_id to get contact_options id to save.
            $ContactTypesTable = self::getDynamicTableInstance('User.ContactTypes');
            $ContactTable = self::getDynamicTableInstance('User.Contacts');

            $contactOptionId = $ContactTypesTable->find()
                ->select([$ContactTypesTable->aliasField('contact_option_id')])
                ->where([$ContactTypesTable->aliasField('id') => $contactType])
                ->first();

            if ($contactOptionId) {
                $contactEntity = null; // POCOR-7973
                $securityUserId = null;
                $openemis_no = $tempRow['openemis_no'];
                if($openemis_no){
                $securityUserId = $this->Users->find()
                    ->select([$this->Users->aliasField('id')])
                    ->where([$this->Users->aliasField('openemis_no') => $openemis_no])
                    ->first();
                }
                $data = [
                    'contact_type_id' => $tempRow['contact_type'],
                    'value' => $contact,
                    'contact_option_id' => $contactOptionId['contact_option_id'],
                ];

                if ($securityUserId) {  //if is existing user validation will be different
                    $data['security_user_id'] = $securityUserId->id;
                    $data['preferred'] = 0;
                    $contactEntity = $ContactTable->newEntity($data);
                } else {
                    $contactEntity = $ContactTable->newEntity($data,
                    );
                }

                //Display all the error msgs
                // Display all the error messages
                if ($contactEntity && $contactEntity->getErrors()) { // POCOR-7973
                    $errorMessages = array_reduce(
                        $contactEntity->getErrors(),
                        function ($carry, $errors) {
                            return array_merge($carry, $errors);
                        },
                        []
                    );

                    $rowInvalidCodeCols['contact'] = implode(',', $errorMessages);
                    $tempRow['contact_error'] = true;

                    $have_error = true;
                } elseif (!$contactEntity) {
                    $rowInvalidCodeCols['contact'] = $this->getExcelLabel('Import', 'value_not_in_list');
                    $tempRow['contact_error'] = true;

                    $have_error = true;
                }

            } else {
                $rowInvalidCodeCols['contact'] = $this->getExcelLabel('Import', 'value_not_in_list');
                $tempRow['contact_error'] = true;
                $have_error = true;
            }
        }
        return $have_error;
    }

   /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkInstitution(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        $institution_code = $tempRow['institution_code'];
        $institution = null;
        $Institutions = self::getDynamicTableInstance('Institution.Institutions');
        if (strlen($institution_code) > 1) {
            $institution = $Institutions->find()->where([$Institutions->aliasField('code') => $institution_code])->first();
            $tempRow['institution_id'] = $institution->id;
        }
        if (!$institution) {
            $rowInvalidCodeCols['institution_code'] = __('Institution With This Code Not Found');
            $have_error = true;
        }

        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     * @throws \Exception
     */
    private function checkStartDate(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        $timeZone = $this->getTimeZone();
        $dateTimeZone = new \DateTimeZone($timeZone);

//        $this->log(__FUNCTION__, 'debug');
//        $this->log($tempRow, 'debug');
//        $this->log($rowInvalidCodeCols, 'debug');
        // from string to dateObject
        $start_date = $tempRow['start_date'];
        if (empty($start_date)) {
            $rowInvalidCodeCols['start_date'] = __('No start date specified');
            $have_error = true;
            $tempRow['start_date'] = null;
            return $have_error;
        }
        try {
            $formattedDate = DateTime::createFromFormat('d/m/Y', $start_date, $dateTimeZone);
        } catch (\Exception $exception) {
            $rowInvalidCodeCols['start_date'] = $exception->getMessage() . ": " . $start_date;
            $have_error = true;
            $tempRow['start_date'] = null;
            return $have_error;
        }
        if (!($formattedDate instanceof DateTimeInterface)) {
            $rowInvalidCodeCols['start_date'] = __('Unknown date format') . __('Date format should be d/m/Y');
            $have_error = true;
            $tempRow['start_date'] = null;
            return $have_error;
        }
        $tempRow['start_date'] = $formattedDate;
        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     * @throws \Exception
     */
    private function checkAcademicPeriodId(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        $academic_period_id = null;

        $tempRow['academic_period_id'] = $academic_period_id;
        return $have_error;
    }

    /**
     * @return string
     */
    private function getTimeZone(): string
    {
        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $setTimeZone = $ConfigItems->value("time_zone");
        $timeZone = !empty($setTimeZone) ? $setTimeZone : 'UTC'; //POCOR-6732
        date_default_timezone_set($timeZone);
        return $timeZone;
    }


}
