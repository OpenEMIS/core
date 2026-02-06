<?php

namespace Directory\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use Cake\Utility\Text; // POCOR-8683
use DateTime; // POCOR-8683

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
    private $generatedUsername; // POCOR-9364
    private $generatedPassword; // POCOR-9364
    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);
        // POCOR-8683 start
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
        // POCOR-8683 end

        // register table once
        $this->Users = self::getDynamicTableInstance('User.Users');
        $this->ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
//        $this->Nationalities = self::getDynamicTableInstance('FieldOption.Nationalities');
        $this->IdentityTypes = self::getDynamicTableInstance('FieldOption.IdentityTypes');
        $this->UserIdentities = self::getDynamicTableInstance('User.Identities');

        $prefix = $this->ConfigItems->value('openemis_no_prefix'); // POCOR-8683 start
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
            // POCOR-8683 start
            'Model.import.onImportPopulateAcademicPeriodsData' => 'onImportPopulateAcademicPeriodsData',
            'Model.import.onImportPopulateEducationGradesData' => 'onImportPopulateEducationGradesData',
            'Model.import.onImportPopulateGuardianRelationsData' => 'onImportPopulateGuardianRelationsData',
            // POCOR-8683 end
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.import.onImportCustomHeader' => 'onImportCustomHeader',
            'Model.import.onImportCheckIdentityConfig' => 'onImportCheckIdentityConfig',
            'Model.import.onImportGetContact' => 'onImportGetContact'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onImportCheckUnique(EventInterface $event,
                                        $sheet,
                                        $row,
                                        $columns,
                                        $tempRow,
                                        $importedUniqueCodes,
                                        $rowInvalidCodeCols) //POCOR-8082
    {

        $tempRow['columns'] = $columns; // POCOR-8683 start
        $columns = new Collection($columns);
        // POCOR-8835 start

        // POCOR-9364 start
        $extractedUsername = $columns->filter(fn($v) => $v === 'username');
        $usernameNoIndex   = key($extractedUsername->toArray()) + 1;
        $username          = $sheet->getCellByColumnAndRow($usernameNoIndex, $row)->getValue();
        $username          = is_string($username) ? trim($username) : $username;

        $extractedPassword = $columns->filter(fn($v) => strtolower(trim($v)) === 'password');
        $passwordColIndex  = key($extractedPassword->toArray()) + 1;
        $password          = $sheet->getCellByColumnAndRow($passwordColIndex, $row)->getValue();
        $password          = trim((string)$password);
        $generatedUsername = null;
        $generatedPassword = null;
        $this->generatedPassword = $generatedPassword;
        $this->generatedUsername = $generatedUsername;
        $newOpenemisNo = "";

        if ($username === null || $username === '') {
            $newOpenemisNo = $this->Users->nextOpenEmisNo();
            $tempRow['openemis_no'] = $newOpenemisNo;
            $candidate = $newOpenemisNo;
            $username = $this->Users->ensureUniqueUsername($candidate);
            $generatedUsername = $username;           // remember for report/export
            $this->generatedUsername = $generatedUsername;
        } else {
            // Validate format (your existing POCOR-9327 rule)
            if (strpos($username, '@') !== false && strpos($username, '.') !== false) {
                $validUserName = true;
            } elseif (preg_match('/^\w+$/', $username)) {
                $validUserName = true;
            } else {
                $validUserName = false;
            }
            if (!$validUserName) {
                $rowInvalidCodeCols['username'] = 'The Username has invalid characters';
                return false;
            }
            // Enforce uniqueness if provided
            $userCount = $this->Users->find()->where(['username' => $username])->count();
            if ($userCount > 0) {
                $rowInvalidCodeCols['username'] = 'This username is already in use';
                return false;
            }
            $newOpenemisNo = $this->Users->nextOpenEmisNo();
            $tempRow['openemis_no'] = $newOpenemisNo;
        }
        $tempRow['username'] = $username;
//        Log::debug(print_r($tempRow, true));
        if ($password === null || $password === '') {
//            // Use UsersTable::autoPassword() wrapper (which calls ConfigItems::getAutoGeneratedPassword)
            $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');

            $password = $ConfigItems->getAutoGeneratedPassword();
            $generatedPassword = $password;           // remember for report/export
            $tempRow['password'] = $password;
            $this->generatedPassword = $generatedPassword;
        } else {
            if (strlen($password) < 4) {
                $rowInvalidCodeCols['password'] = 'Invalid password: Must be at least 4 characters';
                return false;
            }
            $this->generatedPassword = $password;
        }
        // POCOR-9364 end
        // POCOR-8683 end
        $accountType = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'account_type';
        });
        $accountTypeIndex = key($accountType->toArray()) + 1;
        $accountType = $sheet->getCellByColumnAndRow($accountTypeIndex, $row)->getValue();
        $accountTypeId = $this->getAccountTypeId($accountType);
// POCOR-8835 start
//        if (!$user) {
//            if ($openemisNo) {
//                $rowInvalidCodeCols['openemis_no'] = __('No Such User 1');
//                return false;
//            }
        // POCOR-8835 end
            try{
                // POCOR-8683 start
                // POCOR-8835 start
//                $newOpenemisNo = "";

//                $newOpenemisNo = $this->Users->nextOpenEmisNo();;
                $tempRow['openemis_no'] = $newOpenemisNo;
                $tempRow['username'] = $username ?? $newOpenemisNo;
                //POCOR-9327 start
                if (empty($tempRow['username'])) {
                    // Empty username is allowed
                    $validUserName = true;
                } else {
                    $username = $tempRow['username'];

                    if (strpos($username, '@') !== false && strpos($username, '.') !== false) {
                        // Has both @ and .
                        $validUserName = true;
                    } elseif (preg_match('/^\w+$/', $username)) {
                        // Plain alphanumeric with underscores only
                        $validUserName = true;
                    } else {
                        $validUserName = false;
                    }
                }

                if (!$validUserName && !empty($username)) {
                    $rowInvalidCodeCols['username'] = 'The Username has invalid characters';
                    return false;
                } //POCOR-9327 end
            } catch (\Exception $exception) {

                $tempRow['duplicates'] = 'New User Creation Error: ' . __($exception->getMessage());
                $rowInvalidCodeCols['username'] = $tempRow['duplicates'];

                return false;
            }

        // POCOR-8835 end
        $tempRow['account_type'] = $accountTypeId;

        if (empty($tempRow['account_type'])) {
            $tempRow['duplicates'] = __('Account type cannot be empty');
            $rowInvalidCodeCols['account_type'] = $tempRow['duplicates'];
            return false;
            // POCOR-8683 end
        }

        if (!empty($tempRow['account_type'])) {
            // setting is_student = 1, or is_staff = 1, or is_guardian = 1
            $tempRow[$tempRow['account_type']] = 1;
        }
        // POCOR-8835 start
//        if (in_array($openemisNo, $importedUniqueCodes->getArrayCopy())) {
//            $rowInvalidCodeCols['openemis_no'] = __('This OpenEMIS No is Already Present');//$this->getExcelLabel('Import', 'duplicate_unique_key');
//            $tempRow['duplicates'] = $rowInvalidCodeCols['openemis_no'] ;
//            return false;
//        }
        // POCOR-8835 end

    }

    public function onImportUpdateUniqueKeys(EventInterface $event, ArrayObject $importedUniqueCodes, Entity $entity)
    {
        $importedUniqueCodes[] = $entity->openemis_no;
    }

    public function onImportGetAccountTypesId(EventInterface $event, $cellValue)
    {
        return $this->getAccountTypeId($cellValue);
    }
    /**
     * POCOR-8683
     * Set default value for empty or unset array fields
     *
     * @param array  $targetArray  The array to check and update
     * @param string $field        The key to check
     * @param mixed  $defaultValue The default value to set if the field is empty
     */
    private static function setIfEmpty(&$targetArray, $field, $defaultValue) {
        if (empty($targetArray[$field])) {
            $targetArray[$field] = $defaultValue;
        }
    }

    public function onImportGetAccountTypesName(EventInterface $event, $value)
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

    public function onImportPopulateAccountTypesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
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

    public function onImportPopulateContactTypesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        //Join contact type and contact options for displaying the name of contact type and its contact option name at excel for user to see
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel); // POCOR-8683 start
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

    public function onImportPopulateAreaAdministrativesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel); // POCOR-8683 start
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

    public function onImportPopulateGendersData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel); // POCOR-8683 start
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
     * POCOR-8683 refactured
     * @throws \Exception
     */
    public function onImportModelSpecificValidation(EventInterface $event, $references, $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {

        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $isStaff = ($tempRow['account_type'] == self::IS_STAFF);
        $isStudent = ($tempRow['account_type'] == self::IS_STUDENT);
        $identity_type_id = $tempRow['identity_type_id'] ??  false;
        $identity_number = $tempRow['identity_number'] ?? false;
        $contact_type = $tempRow['contact_type'];
        $have_error = false;
        // identity number mandatory
        if ($isStaff) {
            $tempRow['staff_id'] = $tempRow['security_user_id'] ?? null;
            $have_error = $have_error || $this->checkStaffIdentityNationality($tempRow, $rowInvalidCodeCols);
        }

        if ($isStudent) {
            $tempRow['student_id'] = $tempRow['security_user_id'] ?? null;
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

        $tempRow['record_source'] = 'import_user';
        if (0 == $rowInvalidCodeCols->count()) {
            if ($isStudent) {
                if (!$have_error) {

                    list($tempRow, $rowInvalidCodeCols, $have_error) = $this->checkNewAdmission($have_error, $tempRow, $rowInvalidCodeCols, $originalRow);

                }
                if (!$have_error) {
                    list($tempRow, $rowInvalidCodeCols, $have_error) = $this->checkNewGuardian($have_error, $tempRow, $rowInvalidCodeCols, $originalRow);
                }
            }
        }
        if($have_error){
            return false;
        }

        //add identifier that later will be used on User afterSave
        $tempRow['record_source'] = 'import_user';

        return true;
    }

    public function onImportPopulateNationalitiesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel); // POCOR-8683

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

    // POCOR-9364 removed getNewOpenEmisNo
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

    public function onImportSetModelPassedRecord(EventInterface $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow)
    {
        $flipped = array_flip($columns);
        $key = $flipped['openemis_no'];
        // POCOR-8835 start

//        if ($clonedEntity->openemis_no != $clonedEntity->username) {
//            $tempPassedRecord['data'][$key] =
//                "Openemis No: {$clonedEntity->openemis_no}\nUsername: {$clonedEntity->username}"; // POCOR-8835
//        }else{
            $tempPassedRecord['data'][$key] = $clonedEntity->username;
//        }
        // POCOR-8835 end
//        if ($this->generatedUsername) {
//            $key = $flipped['openemis_no'];
//            // IMPORTANT: use "\n" (LF), not "<li>" or "<br>"
//            $tempPassedRecord['data'][$key] =
//                "Openemis No: {$clonedEntity->openemis_no}\nGenerated Username: {$clonedEntity->username}";
//        }
        // POCOR-9364 start
        if ($this->generatedPassword) {
            $key = $flipped['password'];
            // IMPORTANT: use "\n" (LF), not "<li>" or "<br>"
            $tempPassedRecord['data'][$key] = $this->generatedPassword;
        }
        // POCOR-9364 end
        // POCOR-8835 end
        $key = $flipped['guardian_openemis_no']; // POCOR-8683
        $tempPassedRecord['data'][$key] = $clonedEntity->guardian_openemis_no; // POCOR-8683
    }

    public function onImportCustomHeader(EventInterface $event, $customDataSource, ArrayObject $customHeaderData)
    {

        $customTable = self::getDynamicTableInstance($customDataSource); // POCOR-8683

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

    public function onImportCheckIdentityConfig(EventInterface $event, $tempRow, $cellValue)
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
    /*
     * POCOR-8683
     */
    public function onImportPopulateAcademicPeriodsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
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
                if ($row->academic_period_level_id == 1 && $row->current == 1) { // POCOR-9358 validate that only current period level "year" will be shown
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

    /*
     * POCOR-8683
     */
    public function onImportPopulateEducationGradesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {

        $lookedUpTable = self::getDynamicTableInstance($lookupPlugin . '.' . $lookupModel);
        $programmeHeader = $this->getExcelLabel($lookedUpTable, 'education_programme_id');
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [$programmeHeader, $translatedReadableCol, $translatedCol];
        $AcademicPeriod = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods'); // POCOR-9358 start
        $academicPeriodId = $AcademicPeriod->getCurrent();
        $modelData = $lookedUpTable->find('visible')
            ->contain(['EducationProgrammes',
                'EducationProgrammes.EducationCycles',
                'EducationProgrammes.EducationCycles.EducationLevels',
                'EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
            ->where(['EducationSystems.academic_period_id' => $academicPeriodId])  // POCOR-9358 end
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

    /*
     * POCOR-8683
     */
    public function onImportPopulateGuardianRelationsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
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
     *  POCOR-8683
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
        $contactTypeID = $tempRow['contact_type'];
        if (!isset($contact)) {
            $rowInvalidCodeCols['contact'] = $this->getExcelLabel('Import', 'contact_required');
            $tempRow['contact_error'] = true;
            $have_error = true;
        }

        if (isset($contact)) {
            //use contact_type_id to get contact_options id to save.
            $ContactTypesTable = self::getDynamicTableInstance('User.ContactTypes');
            $ContactTable = self::getDynamicTableInstance('User.Contacts');

            $contactOption = $ContactTypesTable->find()
                ->select(['contact_option_id' => $ContactTypesTable->aliasField('contact_option_id')])
                ->where([$ContactTypesTable->aliasField('id') => $contactTypeID])
                ->first();
            if ($contactOption) {
                $contact_option_id = $contactOption['contact_option_id'];
                $securityUserId = $tempRow['security_user_id'] ?? null;
                $data = [
                    'contact_type_id' => $contactTypeID,
                    'value' => $contact,
                    'contact_option_id' => $contact_option_id,
                ];

                if ($securityUserId) {  //if is existing user validation will be different
                    $has_preferred = $ContactTable->find()
                        ->where([
                            'contact_type_id' => $contactTypeID,
                            'preferred' => 1,
                            'security_user_id' => $securityUserId,
                        ])->count();
                    $data['security_user_id'] = $securityUserId;
                    $data['preferred'] = $has_preferred ? 0 : 1;
                    $contactEntity = $ContactTable->newEntity($data);
                } else {
                    $contactEntity = $ContactTable->newEntity($data,
                    );
                }

                //Display all the error msgs
                // Display all the error messages
                if ($contactEntity->getErrors()) { // POCOR-7973
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
                } else {
                    $tempRow['contact_entity'] = $contactEntity;
                }

            } else {
                $rowInvalidCodeCols['contact'] = $this->getExcelLabel('Import', 'value_not_in_list');
                $tempRow['contact_error'] = true;
                $have_error = true;
            }
        }


        return $have_error;
    }

    private function checkGuardianContact(&$tempRow, &$rowInvalidCodeCols, &$guardian): bool
    {
        $have_error = false;
        if (isset($guardian['mobile_number']) || isset($guardian['email'])) {
            $ContactTypesTable = self::getDynamicTableInstance('User.ContactTypes');
            $ContactOptionsTable = self::getDynamicTableInstance('User.ContactOptions');
            $ContactTable = self::getDynamicTableInstance('User.Contacts');

            $fields = ['mobile_number' => 'MOB', 'email' => 'EMA'];

            foreach ($fields as $field => $code) {
                if (!empty($guardian[$field])) {
                    // Find contact_option_id by joining ContactTypes and ContactOptions
                    $contactOption = $ContactTypesTable->find()
                        ->select(['contact_option_id' => $ContactTypesTable->aliasField('contact_option_id'),
                            'contact_type_id' => $ContactTypesTable->aliasField('id')])
                        ->innerJoinWith('ContactOptions', function ($q) use ($code, $ContactOptionsTable) {
                            return $q->where([$ContactOptionsTable->aliasField('code') => $code]);
                        })
                        ->first();

                    if ($contactOption) {
                        $contact_type_id = $contactOption->contact_type_id;
                        $contact_option_id = $contactOption->contact_option_id;
                        $securityUserId = $tempRow['guardian_id'] ?? null;

                        $data = [
                            'contact_type_id' => $contact_type_id,
                            'value' => $guardian[$field],
                            'contact_option_id' => $contact_option_id,
                        ];

                        if ($securityUserId) {
                            $has_preferred = $ContactTable->find()
                                ->where([
                                    'contact_type_id' => $contact_type_id,
                                    'preferred' => 1,
                                    'security_user_id' => $securityUserId,
                                ])
                                ->count();

                            $data['security_user_id'] = $securityUserId;
                            $data['preferred'] = $has_preferred ? 0 : 1;
                        }

                        // Create new contact entity
                        $contactEntity = $ContactTable->newEntity($data);
                        // Error handling
                        if ($contactEntity->getErrors()) {
                            $errorMessages = array_reduce(
                                $contactEntity->getErrors(),
                                function ($carry, $errors) {
                                    return array_merge($carry, $errors);
                                },
                                []
                            );

                            $rowInvalidCodeCols[$field] = implode(',', $errorMessages);
                            $tempRow['contact_error'] = true;
                            $have_error = true;
                        } else {
                            $guardian['contact_entity'][] = $contactEntity;
                        }
                    } else {
                        // Error if no matching contact_option_id found
                        $rowInvalidCodeCols[$field] = $this->getExcelLabel('Import', 'value_not_in_list');
                        $tempRow['contact_error'] = true;
                        $have_error = true;
                    }
                }
            }
        }

        return $have_error;
    }

   private function checkAdmission(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;

        list($tempRow, $rowInvalidCodeCols, $have_error) = $this->checkCreateNewStudent($tempRow, $rowInvalidCodeCols, $have_error);

        if ($have_error) {
            return true;
        }
        // Required fields for admission
        $requiredFields = [
            'institution_id',
            'start_date',
            'assignee_id',
            'student_id',
            'education_grade_id',
//            'institution_class_id' // POCOR-9476
        ];

        // Validate required fields
        foreach ($requiredFields as $field) {
            if (empty($tempRow[$field])) {
                $rowInvalidCodeCols[$field] = $field . $this->getExcelLabel('Import', "{$field}_required") . ":" . $field;
                $tempRow["{$field}_error"] = true;
                $have_error = true;
            }
        }

        // If there are errors, stop further processing
        if ($have_error) {
            return true;
        }
        if(!is_array($tempRow)) {
            $tempRowArray = $tempRow->getArrayCopy();
        }else{
            $tempRowArray = $tempRow;
        }

        // Extract relevant fields for admission
        $admissionData = array_intersect_key($tempRowArray, array_flip([
            'institution_id',
            'academic_period_id',
            'start_date',
            'end_date',
            'assignee_id',
            'student_id',
            'education_grade_id',
            'institution_class_id'
        ]));

        // Add additional default values
        $admissionData['action_type'] = 'imported';

        // Create a new admission entity
        $StudentAdmission = self::getDynamicTableInstance('Institution.StudentAdmission');
        $newAdmission = $StudentAdmission->newEntity($admissionData);

        // Validate the new entity and handle errors
        $newErrors = $newAdmission->getErrors();
        if ($newAdmission && $newErrors) {
            $errorMessages = array_reduce(
                $newErrors,
                function ($carry, $errors) {
                    return array_merge($carry, $errors);
                },
                []
            );

            $rowInvalidCodeCols['admission'] = implode(',', $errorMessages);
            $tempRow['admission_error'] = true;
            $have_error = true;
        } elseif (!$newAdmission) {
            $rowInvalidCodeCols['admission'] = $this->getExcelLabel('Import', 'value_not_in_list');
            $tempRow['admission_error'] = true;
            $have_error = true;
        } elseif($newAdmission) {
            // Save the admission
            $newAdmission = $StudentAdmission->save($newAdmission);
            if (!$newAdmission) {
                $rowInvalidCodeCols['admission'] = $this->getExcelLabel('Import', 'save_failed');
                $tempRow['admission_error'] = true;
                $have_error = true;
            }
            $tempRow['admission'] = $newAdmission;
        }
        return $have_error;
    }
    private function addError(array|ArrayObject &$rowInvalidCodeCols, string|array $fields, string $message): void
    {
        $fields = (array) $fields; // Always convert to array for consistent handling
        foreach ($fields as $field) {
            $rowInvalidCodeCols[$field] = $message;
        }
    }

    private function checkInstitution(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $institution_code = $tempRow['institution_code'] ?? '';
        $gender_id = $tempRow['gender_id'] ?? '';

        if (trim($institution_code) === '') {
//            $this->addError($rowInvalidCodeCols, 'institution_code', __('No Institution Code Provided'));
            unset($tempRow['institution_code']);
            unset($tempRow['institution_id']);
            unset($tempRow['academic_period']);
            unset($tempRow['education_grade']);
            unset($tempRow['class_name']);
            unset($tempRow['start_date']);
            return false;
        }

        $institution = $this->getInstitutionByCodeAndGender($institution_code, $gender_id);

        if (empty($institution)) {
            $this->addError($rowInvalidCodeCols, 'institution_code', __('Institution With This Code Not Found'));
            return true;
        }
        if (isset($institution['id'])) {
            $tempRow['institution_id'] = $institution['id'];
            return false;
        }
        if (isset($institution['error'])) {
            $this->addError($rowInvalidCodeCols, 'institution_code', $institution['error']);
            return true;
        }
        return true;
    }
    private function getInstitutionByCodeAndGender(string $code, string $gender_id): array
    {
        $Institutions = self::getDynamicTableInstance('institutions');
        $query = $Institutions->find()
            ->select(['id' => $Institutions->aliasField('id'),
                $Institutions->aliasField('institution_gender_id'),
                'gender_id' => 'real_genders.id',
                'gender_code' => 'institution_genders.code',
                'gender_name' => 'institution_genders.name'])
            ->leftJoin(['institution_genders' => 'institution_genders'],
                [$Institutions->aliasField('institution_gender_id') . ' = institution_genders.id'])
            ->leftJoin(['real_genders' => 'genders'],
                ['institution_genders.code = real_genders.code'])
            ->where([
                $Institutions->aliasField('code') => $code,
            ])
            ->first();
        if(empty($query)){
            return [];
        }
        $institution_id = $query->id;
        $institution_gender = $query->gender_name;
        $institution_gender_id = $query->gender_id;
        $institution_gender_code = $query->gender_code;
        if ($institution_gender_code == 'X') { //if mixed then always true
            return ['id' => $institution_id];
        } else {
            if ($gender_id != $institution_gender_id) {
                return ['error' => __(sprintf('Institution only accepts %s student.', $institution_gender))];
            } else {
                return ['id' => $institution_id];
            }
        }
    }



    private function checkAcademicPeriodId( &$tempRow,  &$rowInvalidCodeCols): bool
    {
        $institution_id = $tempRow['institution_id'];
        $academic_period_id = $tempRow['academic_period_id'];

        if (empty($institution_id) || empty($academic_period_id)) {
            $this->addError($rowInvalidCodeCols, 'academic_period_id', __('No academic period specified'));
            $tempRow['academic_period'] = $tempRow['academic_period_id'] = null;
            return true;
        }

        $education_grades = $this->getAcademicPeriodGrades($institution_id, $academic_period_id);
        if (empty($education_grades)) {
            $this->addError($rowInvalidCodeCols, 'education_grade_id', __('No education grades in this academic period'));
            $tempRow['education_grade_id'] = $tempRow['academic_period_id'] = null;
            return true;
        }
        $tempRow['education_grades'] = $education_grades;
        return false;
    }

    private function getAcademicPeriodGrades($institution_id, $academic_period_id): array
    {
        $InstitutionGrades = self::getDynamicTableInstance('institution_grades');
        $educationsGrades = $InstitutionGrades
            ->find()
            ->select(['academic_period_id' => $InstitutionGrades->aliasField('academic_period_id'),
                'education_grade_id' => $InstitutionGrades->aliasField('education_grade_id'),
                'education_grade_code' => 'education_grades.code',
                ])
            ->leftJoin(['education_grades' => 'education_grades'],
                [$InstitutionGrades->aliasField('education_grade_id') . ' = education_grades.id'])
            ->leftJoin(['academic_periods' => 'academic_periods'],
                [$InstitutionGrades->aliasField('academic_period_id') . ' = academic_periods.id'])
            ->leftJoin(['academic_period_levels' => 'academic_period_levels'],
                ['academic_periods.academic_period_level_id = academic_period_levels.id'])
            ->where([
                $InstitutionGrades->aliasField('institution_id') => $institution_id,
                'academic_periods.id' => $academic_period_id,
                'academic_periods.editable' => 1,
                'academic_periods.visible' => 1,
                'academic_period_levels.level' => 1,
            ])->toArray();
        if (empty($educationsGrades)) {
            return [];
        }
        $education_grades = [];
        foreach ($educationsGrades as $educationsGrade){
            $education_grades[] = ['id' => $educationsGrade->education_grade_id,
                'code' => $educationsGrade->education_grade_code];
        }
        return $education_grades;
    }

    private function checkClassName(&$tempRow, &$rowInvalidCodeCols): bool
    {

        $class_name = $tempRow['class_name'] ?? '';

        if (trim($class_name) === '') {
//            $this->addError($rowInvalidCodeCols, 'class_name', __('No class name specified')); // POCOR-9476
            $tempRow['class_name'] = null;
            $tempRow['institution_class_id'] = null;
            return false; // POCOR-9476
        }

        $institution_class_id = $this->getInstitutionClass(
            $class_name,
            $tempRow['institution_id'],
            $tempRow['academic_period_id'],
            $tempRow['education_grade_id'],
        );

        if ($institution_class_id > 0) {
            $tempRow['institution_class_id'] = $institution_class_id;
            return false;
        }

        $tempRow['class_name'] = null;
        $tempRow['institution_class_id'] = null;
        $this->addError($rowInvalidCodeCols, 'class_name', __('Institution class not found/full'));
        return true;
    }

    private function getInstitutionClass($class_name, $institution_id, $academic_period_id, $education_grade_id)
    {
            $availableClasses = $this->getInstitutionClasses($institution_id,
                $academic_period_id,
                $education_grade_id);
            foreach ($availableClasses as $id => $name) {
                if ($class_name == $name) {
                    return $id;
                }
            }
        return 0;
    }


    private function getInstitutionClasses($institution_id, $academic_period_id, $education_grade_id): array
    {
        $InstitutionClasses = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $institutionClassesList = $InstitutionClasses->getClassOptions($academic_period_id, $institution_id, $education_grade_id);
        $resultList = [];
        foreach ($institutionClassesList as $id => $className) {
            $InstitutionClassStudents = self::getDynamicTableInstance('Institution.InstitutionClassStudents');
            $countStudent = $InstitutionClassStudents->getStudentCountByClass($id);
            $classCapacity = $InstitutionClasses->get($id)->capacity;
            if ($countStudent + 1 <= $classCapacity) {
                $resultList[$id] = $className;
            }
        }
        return $resultList;
    }

    private function checkStartDate( &$tempRow,  &$rowInvalidCodeCols): bool
    {
        $start_date = $tempRow['start_date'] ?? null;

        if (!$start_date) {
            $this->addError($rowInvalidCodeCols, 'start_date', __('No start date specified'));
            $tempRow['start_date'] = null;
            return true;
        }

        $formattedDate = $this->parseDate($start_date, 'd/m/Y');
        if (!$formattedDate) {
            $this->addError($rowInvalidCodeCols, 'start_date', __('Unknown date format. Date format should be d/m/Y.'));
            $tempRow['start_date'] = null;
            return true;
        }
        $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $AcademicPeriods->getAcademicPeriodIdByDate($formattedDate);
        if(!$academicPeriodId){
            $this->addError($rowInvalidCodeCols, 'start_date', __('The Date is not within valid Academic Period'));
            $tempRow['start_date'] = null;
            return true;
        }
        $academic_period_id = $tempRow['academic_period_id'];
        if($academic_period_id != $academicPeriodId){
            $this->addError($rowInvalidCodeCols, 'start_date', __('The Date is not within given Academic Period'));
            $tempRow['start_date'] = null;
            return true;
        }
        $tempRow['start_date'] = $formattedDate;
        $period = $AcademicPeriods->get($academic_period_id);
        if (!$period) {
            $this->addError($rowInvalidCodeCols, 'start_date', __('The given Academic Period is Invalid'));
            $tempRow['start_date'] = null;
            return true;
        }

        //$periodStartDay = $period->start_date->format('d/m/Y');
        // $periodStartDate = DateTime::createFromFormat('d/m/Y', $periodStartDay, $dateTimeZone);

        $periodEndDay = $period->end_date->format('d/m/Y');
        try { // POCOR-9423 start
            $dateTimeZone = new \DateTimeZone($this->getTimeZone());
        } catch (\Exception $e) {
            $dateTimeZone = new \DateTimeZone('GMT');
        } // POCOR-9423 end
        $periodEndDate = DateTime::createFromFormat('d/m/Y', $periodEndDay, $dateTimeZone);

        $tempRow['end_date'] = $periodEndDate;
        return false;
    }

    private function parseDate($date, string $format): ?DateTime
    {
        try { // POCOR-9423 start
            $dateTimeZone = new \DateTimeZone($this->getTimeZone());
        } catch (\Exception $e) {
            $dateTimeZone = new \DateTimeZone('GMT');
        } // POCOR-9423 end

        // If the input is already a DateTime object, return it
        if ($date instanceof \DateTime) {
            return $date;
        }

        // If the input is a string, try parsing it with the given format
        if (is_string($date)) {
            return DateTime::createFromFormat($format, $date, $dateTimeZone) ?: null;
        }

        // If it's neither a string nor a DateTime object, return null
        return null;
    }

    private function checkEducationGrade( &$tempRow,  &$rowInvalidCodeCols): bool
    {

        $education_grade = $tempRow['education_grade_id'] ?? '';
        if (trim($education_grade) === '') {
            $this->addError($rowInvalidCodeCols, 'education_grade_id', __('No education grade specified'));
            $tempRow['education_grade_id'] = null;
            return true;
        }

        $education_grades = $tempRow['education_grades'];
        $education_grade_code = $tempRow['education_grade_code'];
        $education_grade_id = 0;
        foreach ($education_grades as $education_grade){
            if($education_grade['code'] == $education_grade_code){
                $education_grade_id = $education_grade['id'];
                break;
            }
        }

        if ($education_grade_id > 0) {
            $tempRow['education_grade_id'] = $education_grade_id;
            return false;
        }

        $tempRow['education_grade_id'] = null;
        $this->addError($rowInvalidCodeCols, 'education_grade', __('Education grade not found/not present'));
        return true;
    }

    private function getEducationGrade($education_grade, $institution_id, $academic_period_id)
    {
        $InstitutionGrades = self::getDynamicTableInstance('Institution.InstitutionGrades');
        $institutionGrade = $InstitutionGrades
            ->find()
            ->select(['education_grade_id' => $InstitutionGrades->aliasField('education_grade_id')])
            ->contain('EducationGrades')
            ->where([
                $InstitutionGrades->aliasField('academic_period_d') => $academic_period_id,
                $InstitutionGrades->aliasField('institution_id') => $institution_id,
                'EducationGrades.code' => $education_grade
            ])->first();
        if (empty($institutionGrade)) {
            return 0;
        }
        $education_grade_id = $institutionGrade->education_grade_id;
        return $education_grade_id;
    }

    /**
     * @return string
     */
    private function getTimeZone()
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $setTimeZone = $ConfigItems->value("time_zone");
        $timeZone = !empty($setTimeZone) ? $setTimeZone : 'UTC'; //POCOR-6732
        date_default_timezone_set($timeZone);
        return $timeZone;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @param $have_error
     * @return array
     */
    private function checkCreateNewStudent($tempRow, $rowInvalidCodeCols, $have_error): array
    {
        if (!$tempRow['student_id']) {
            $tempRowArray = $tempRow->getArrayCopy();
            try {
                $this->Users->setImportValidationPassed();
                $newEntity = $this->Users->newEntity($tempRowArray);

                if ($this->Users->save($newEntity)) {
                    $newId = $newEntity->id;  // Get the ID after save
                    $tempRow['student_id'] = $newId;
                    $tempRow['security_user_id'] = $newId;
                    $tempRow['entity'] = $newEntity;
                }else{
                    $rowInvalidCodeCols['openemis_no'] = 'New Student Creation Error';
                    $have_error = true;
                }

            } catch (\Exception $exception) {
                $rowInvalidCodeCols['openemis_no'] = 'New Student Creation Error: ' . __($exception->getMessage());
                $have_error = true;
            }
        }
        return array($tempRow, $rowInvalidCodeCols, $have_error);
    }
   /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @param $have_error
     * @return array
     */
    private function checkCreateNewGuardian($tempRow, $rowInvalidCodeCols, $have_error): array
    {
        if($have_error){
            return [$tempRow, $rowInvalidCodeCols, $have_error];
        }

        if (!$tempRow['guardian_id']) {
            $tempRowArray = $tempRow->getArrayCopy();

            $tempRowArray['guardian_mobile_number'] = $tempRowArray['guardian_contact_cell_phone'] ?? null;
            $tempRowArray['guardian_email'] = $tempRowArray['guardian_contact_email'] ?? null;
            $tempRowArray['guardian_identity_type_id'] = $tempRowArray['guardian_identity_type'] ?? null;
            $guardianFields = [
                'guardian_id',
                'guardian_openemis_no',
                'guardian_username',
                'guardian_first_name',
                'guardian_middle_name',
                'guardian_third_name',
                'guardian_last_name',
                'guardian_preferred_name',
                'guardian_gender_id',
                'guardian_date_of_birth',
                'guardian_address',
                'guardian_postal',
                'guardian_address_area_id',
                'guardian_birthplace_area_id',
                'guardian_nationality_id',
                'guardian_identity_type',
                'guardian_identity_type_id',
                'guardian_identity_number',
                'guardian_contact_email',
                'guardian_contact_cell_phone',
                'guardian_email',
                'guardian_mobile_number'
            ];
            foreach ($guardianFields as $field) {
                $cleanField = str_replace('guardian_', '', $field);
                $guardian[$cleanField] = !empty($tempRowArray[$field]) ? $tempRowArray[$field] : null;
            }
            $guardian['action_type'] = 'imported';
            $guardian['is_guardian'] = 1;
            $have_error = self::checkGuardianContact($tempRowArray, $rowInvalidCodeCols, $guardian);
            if($have_error){
                return array($tempRow, $rowInvalidCodeCols, $have_error);
            }
            try {
                if ($tempRow['guardian_entity']) {
//                    $newGuardian = $this->Users->patchEntity($tempRow['guardian_entity'], $guardian); // POCOR-8835
                } else {
                    $newGuardian = $this->Users->newEntity($guardian);
                    if ($newGuardian->getErrors()) { // POCOR-7973

                        $errorMessages = array_reduce(
                            $newGuardian->getErrors(),
                            function ($carry, $errors) {
                                return array_merge($carry, $errors);
                            },
                            []
                        );

                        $rowInvalidCodeCols['guardian_openemis_no'] = implode(',', $errorMessages);
                        $tempRow['guardian_error'] = true;
                        $have_error = true;
                    }
                    if ($this->Users->save($newGuardian)) {
                        $newId = $newGuardian->id;  // Get the ID after save
                        $tempRow['guardian_id'] = $newId;
                        $tempRow['guardian_entity'] = $newGuardian;
                    }
                }
//                    Log::debug(print_r(['$newGuardian' => $newGuardian], true));


            } catch (\Exception $exception) {
                $rowInvalidCodeCols['guardian_openemis_no'] = 'New Guardian Creation Error: ' . __($exception->getMessage());
                $have_error = true;
            }
        }
        return array($tempRow, $rowInvalidCodeCols, $have_error);
    }

    /**
     * @param bool $have_error
     * @param $tempRow
     * @param ArrayObject $rowInvalidCodeCols
     * @param ArrayObject $originalRow
     * @return array
     */
    private function checkNewGuardian(bool $have_error, $tempRow, ArrayObject $rowInvalidCodeCols, ArrayObject $originalRow): array
    {
        $hasGuardianData = false;

        foreach ($tempRow as $key => $value) {

            foreach ($tempRow as $key => $value) {
                if (strpos($key, 'guardian_') === 0) {  // Check if the key starts with 'guardian_'
                    // Handle different data types:
                    if (is_null($value)) {
                        continue; // Null is considered invalid
                    }

                    if (is_string($value) && trim($value) === '') {
                        continue; // Empty or whitespace-only string is invalid
                    }

                    if (is_numeric($value) && (int)$value === 0) {
                        continue; // Numeric zero (0) is invalid
                    }

                    // If we reach here, the value is valid
                    $hasGuardianData = true;
                    break;
                }
            }
        }

        if (!$hasGuardianData) {
            // No valid guardian data at all, return false (no error added)
            return array($tempRow, $rowInvalidCodeCols, $have_error);
        }
        $have_error = $have_error || $this->checkGuardianRelationId($tempRow, $rowInvalidCodeCols);
        if ($have_error) {
            return array($tempRow, $rowInvalidCodeCols, $have_error);
        }
        $have_error = $have_error || $this->checkGuardianOpenemisID($tempRow, $rowInvalidCodeCols);
        if ($have_error) {
            return array($tempRow, $rowInvalidCodeCols, $have_error);
        }
        $have_error = $have_error || $this->checkNewRelationship($tempRow, $rowInvalidCodeCols);
        return array($tempRow, $rowInvalidCodeCols, $have_error);
    }
    /**
     * @param bool $have_error
     * @param $tempRow
     * @param ArrayObject $rowInvalidCodeCols
     * @param ArrayObject $originalRow
     */
    private function checkNewAdmission(bool $have_error, $tempRow, ArrayObject $rowInvalidCodeCols, ArrayObject $originalRow): array
    {

        $have_error = $have_error || $this->checkInstitution($tempRow, $rowInvalidCodeCols);


        $institution_id = $tempRow['institution_id'] ?? null;
        if (!$institution_id) {
            return array($tempRow, $rowInvalidCodeCols, $have_error);
        }
        $columns = $tempRow['columns'];
        $keys = array_flip($columns);
        $education_grade_key = $keys['education_grade_id'];
        $have_error = $have_error || $this->checkAcademicPeriodId($tempRow, $rowInvalidCodeCols);


        $academic_period_id = $tempRow['academic_period_id'] ?? null;
//                Log::debug(print_r(['$academic_period_id' => $tempRow], true));
        if (!empty($academic_period_id)) {
            $education_grade_code = $originalRow[$education_grade_key];
            $tempRow['education_grade_code'] = $education_grade_code;
            $have_error = $have_error || $this->checkEducationGrade($tempRow, $rowInvalidCodeCols);


            $education_grade_id = $tempRow['education_grade_id'] ?? null;
//                    Log::debug(print_r(['$education_grade_id' => $tempRow], true));

            if (!empty($education_grade_id)) {
                $have_error = $have_error || $this->checkClassName($tempRow, $rowInvalidCodeCols);


                $have_error = $have_error || $this->checkStartDate($tempRow, $rowInvalidCodeCols);


                $tempRow['assignee_id'] = $this->Auth->user('id'); // Assignee as current user

                $have_error = $have_error || $this->checkAdmission($tempRow, $rowInvalidCodeCols); // TODO check


            }
        }

//            Log::debug(print_r(['$institution_id' => $tempRow], true));
        return array($tempRow, $rowInvalidCodeCols, $have_error);
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkGuardianRelationId(&$tempRow, &$rowInvalidCodeCols): bool
    {


// Check individual guardian_relation_id field for specific error handling
        $guardian_relation_id = $tempRow['guardian_relation_id'] ?? null;
        if (!$guardian_relation_id) {
            $this->addError($rowInvalidCodeCols, 'guardian_relation_id', __('No Relation Type'));
            return true;
        }
        $have_error = false;
        $lookedUpTable = self::getDynamicTableInstance('Student.GuardianRelations');
        $relations = $lookedUpTable->find('all')
            ->select([
                'id'
            ])
            ->where([$lookedUpTable->aliasField('id') => $guardian_relation_id])
            ->disableHydration()
            ->first();
        if(empty($relations)){
            $this->addError($rowInvalidCodeCols, 'guardian_relation_id', __('No Relation Types'));
            return true;
        }
        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkGuardianOpenemisId(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        $openemisNo = $tempRow['guardian_openemis_no'] ?? null;
        $user = null;
        if ($openemisNo) {
            if(isset($tempRow['entity'])){
                $userOpenemisNo = $tempRow['entity']->openemis_no;
                if ($userOpenemisNo == $openemisNo) {
                    $this->addError($rowInvalidCodeCols, 'guardian_openemis_no', __('Same student and guardian id'));
                    return true;
                }
            }

            $user = $this->Users->find()->where(['openemis_no' => $openemisNo])->first();

        }
        if (!$user) {
            if ($openemisNo) {
                $rowInvalidCodeCols['openemis_no'] = __('No Such User');
                return false;
            }
            try{
                $username = "";
                if(strlen($openemisNo) > 1){
                    $username = Text::slug($openemisNo);
                }
                if(strlen($username) < 6){
                    $username = $username . $this->Users->nextOpenEmisNo();;

                    $tempRow['guardian_openemis_no'] = $username;
                }
                $tempRow['guardian_username'] = $username;
            } catch (\Exception $exception) {
                $rowInvalidCodeCols['guardian_openemis_no'] = 'New User Creation Error: ' . __($exception->getMessage());
                return true;
            }
        } else {
            $tempRow['guardian_entity'] = $user;
            $tempRow['guardian_id'] = $user->id;
        }
        return $have_error;
    }


    private function checkNewRelationship(&$tempRow, &$rowInvalidCodeCols): bool
    {
        $have_error = false;
        list($tempRow, $rowInvalidCodeCols, $have_error) = $this->checkCreateNewStudent($tempRow, $rowInvalidCodeCols, $have_error);
        if ($have_error) {
            return true;
        }
        list($tempRow, $rowInvalidCodeCols, $have_error) = $this->checkCreateNewGuardian($tempRow, $rowInvalidCodeCols, $have_error);
        if ($have_error) {
            return true;
        }
        if (!$tempRow['student_id']) {
            $rowInvalidCodeCols['guardian_openemis_no'] = __('No Present Student');
            return true;
        }
        if (!$tempRow['guardian_id']) {
            $rowInvalidCodeCols['guardian_openemis_no'] = __('No Present Guardian');
            return true;
        }
        $student_id = $tempRow['student_id'];
        $guardian_id = $tempRow['guardian_id'];
        $guardian_relation_id = $tempRow['guardian_relation_id'];
        $StudentGuardians = self::getDynamicTableInstance('student_guardians');
        $existingEntity = $StudentGuardians->find()
            ->where([$StudentGuardians->aliasField('student_id') => $student_id,
                $StudentGuardians->aliasField('guardian_id') => $guardian_id,
                $StudentGuardians->aliasField('guardian_relation_id') => $guardian_relation_id,])
            ->first();
        if ($existingEntity) {
            return $have_error;
        }
        $entityGuardiansData = [
            'id' => Text::uuid(),
            'student_id' => $student_id,
            'guardian_id' => $guardian_id,
            'guardian_relation_id' => $guardian_relation_id,
            'created_user_id' => $this->Auth->user('id'), // Assignee as current user,
            'created' => date('Y-m-d H:i:s')
        ];
//            Log::debug(print_r($entityGuardiansData, true));
// Check for an existing entity based on unique fields

        $newRelationship = $StudentGuardians->newEntity($entityGuardiansData);
        if ($newRelationship->getErrors()) { // POCOR-7973
            $errorMessages = array_reduce(
                $newRelationship->getErrors(),
                function ($carry, $errors) {
                    return array_merge($carry, $errors);
                },
                []
            );

            $rowInvalidCodeCols['guardian_openemis_no'] = implode(',', $errorMessages);
            $tempRow['guardian_error'] = true;
            $have_error = true;
            return $have_error;
        }
        // If the entity does not exist, create a new one

        try {
            $newRelationship = $StudentGuardians->save($newRelationship);
        } catch (\Exception $e) {
            // Handle save error
            $rowInvalidCodeCols['guardian_openemis_no'] = $e->getMessage();
            $have_error = true;
        }
        try {
            $guardian_entity = $tempRow['guardian_entity'];

            $guardian_entity = $this->patchEntity($guardian_entity, ['is_guardian' => 1], ['validate' =>false]);

            $this->Users->save($guardian_entity);
        } catch (\Exception $e) {
            // Handle save error
            $rowInvalidCodeCols['guardian_openemis_no'] = $e->getMessage();
            $have_error = true;
        }
        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkGuardianFirstLastName($tempRow, $rowInvalidCodeCols): bool
    {
        $have_error = false;
        $something = $tempRow['something'];
        if(empty($something)){
            $this->addError($rowInvalidCodeCols, 'something', __('No Something'));
            return true;
        }
        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkGuardianGender($tempRow, $rowInvalidCodeCols): bool
    {
        $have_error = false;
        $something = $tempRow['something'];
        if(empty($something)){
            $this->addError($rowInvalidCodeCols, 'something', __('No Something'));
            return true;
        }
        return $have_error;
    }

    /**
     * @param $tempRow
     * @param $rowInvalidCodeCols
     * @return bool
     */
    private function checkGuardianDOB($tempRow, $rowInvalidCodeCols): bool
    {
        $have_error = false;
        $something = $tempRow['something'];
        if(empty($something)){
            $this->addError($rowInvalidCodeCols, 'something', __('No Something'));
            return true;
        }
        return $have_error;
    }



}
