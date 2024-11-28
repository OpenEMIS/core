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
            $tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes, $row, 'others');
            $tempRow['username'] = $tempRow['openemis_no'];
            return false;
        }

        $user = $this->Users->find()->where(['openemis_no' => $openemisNo])->first();
        if (!$user) {
            try{
                $tempRow['entity'] = $this->Users->newEntity(['openemis_no' => $openemisNo]);
                $tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes, $row, $tempRow['account_type']);
                $tempRow['username'] = $tempRow['openemis_no'];
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
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
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
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
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
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
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

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
        $isStudentIdentityMandatory = $ConfigItems->value('StudentIdentities');
        $isStaffIdentityMandatory = $ConfigItems->value('StaffIdentities');
        $isStaffNationalitiesMandatory = $ConfigItems->value('StaffNationalities');
        $isStudentNationalitiesMandatory = $ConfigItems->value('StudentNationalities');
        // POCOR-7973:start
        $isStaff = ($tempRow['account_type'] == self::IS_STAFF);
        $isStudent = ($tempRow['account_type'] == self::IS_STUDENT);
        $identity_type_id = isset($tempRow['identity_type_id']) ? $tempRow['identity_type_id'] : false;
        $identity_number = isset($tempRow['identity_number']) ? $tempRow['identity_number'] : false;
        $nationality_id = isset($tempRow['nationality_id']) ? $tempRow['nationality_id'] : false;
        $have_error = false;
        // identity number mandatory
        if ($isStaff) {
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
        }

        if ($isStudent) {
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
        }

        // Nationalities Mandatory

        //if identity type selected, then need to specify identity number
        if ($identity_type_id) {
            if (!$identity_number) {
                $rowInvalidCodeCols['identity_number'] = $this->getExcelLabel('Import', 'identity_number_for_type_required');
                $have_error = true;
            }
        }

        //if identity number is not empty, need to ensure it has identity type selected, it has to be unique and following the validation patter (if there is)
        if ($identity_number) {
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
        }
        if($have_error == true){
            return false;
        }
        // POCOR-7973:end
        //Validation of contact_type and contact
        if ($tempRow->offsetExists('contact_type') && !empty($tempRow['contact_type'])) {

            if (!$tempRow->offsetExists('contact') || empty($tempRow['contact'])) {
                $rowInvalidCodeCols['contact'] = $this->getExcelLabel('Import', 'contact_required');
                $tempRow['contact_error'] = true;
                return false;
            } else {
                //use contact_type_id to get contact_options id to save.
                $ContactTypesTable = self::getDynamicTableInstance('User.ContactTypes');
                $ContactTable = self::getDynamicTableInstance('User.Contacts');

                $contactOptionId = $ContactTypesTable->find()
                    ->select([$ContactTypesTable->aliasField('contact_option_id')])
                    ->where([$ContactTypesTable->aliasField('id') => $tempRow['contact_type']])
                    ->first();

                if ($contactOptionId) {
                    $contactEntity = null; // POCOR-7973

                    $securityUserId = $this->Users->find()
                        ->select([$this->Users->aliasField('id')])
                        ->where([$this->Users->aliasField('openemis_no') => $tempRow['openemis_no']])
                        ->first();

                    $data = [
                        'contact_type_id' => $tempRow['contact_type'],
                        'value' => $tempRow['contact'],
                        'contact_option_id' => $contactOptionId['contact_option_id'],
                    ];

                    if ($securityUserId) {  //if is existing user validation will be different
                        $data['security_user_id'] = $securityUserId->id;
                        $data['preferred'] = 0;
                        $contactEntity = $ContactTable->newEntity($data);
                    } else {
                        $contactEntity = $ContactTable->newEntity($data,
//                            ['validate' => 'importType'] // todo removed validations for further check
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

                        return false;
                    } elseif (!$contactEntity) {
                        $rowInvalidCodeCols['contact'] = $this->getExcelLabel('Import', 'value_not_in_list');
                        $tempRow['contact_error'] = true;

                        return false;
                    }

                } else {
                    $rowInvalidCodeCols['contact'] = $this->getExcelLabel('Import', 'value_not_in_list');
                    $tempRow['contact_error'] = true;
                    return false;
                }
            }
        }

        //add identifier that later will be used on User afterSave
        $tempRow['record_source'] = 'import_user';

        return true;
    }

    public function onImportPopulateNationalitiesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

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

        $customTable = TableRegistry::get($customDataSource);

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

}
