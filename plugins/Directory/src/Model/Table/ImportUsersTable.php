<?php
namespace Directory\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;

class ImportUsersTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'User', 'model'=>'Users']);

        // register table once
        $this->Users = TableRegistry::get('User.Users');
        $this->ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $this->Nationalities = TableRegistry::get('FieldOption.Nationalities');
        $this->IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $this->UserIdentities = TableRegistry::get('User.Identities');

        $prefix = $this->ConfigItems->value('openemis_id_prefix');
        $prefix = explode(",", $prefix);
        $prefix = (isset($prefix[1]) && $prefix[1]>0) ? $prefix[0] : '';

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
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportPopulateAreaAdministrativesData' => 'onImportPopulateAreaAdministrativesData',
            'Model.import.onImportPopulateGendersData' => 'onImportPopulateGendersData',
            'Model.import.onImportPopulateAccountTypesData' => 'onImportPopulateAccountTypesData',
            'Model.import.onImportGetAccountTypesId' => 'onImportGetAccountTypesId',
            'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
            'Model.import.onImportCustomHeader' => 'onImportCustomHeader',
            'Model.import.onImportCheckIdentityConfig' => 'onImportCheckIdentityConfig',
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols)
    {
        $columns = new Collection($columns);
        $extractedOpenemisNo = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'openemis_no';
        });
        $openemisNoIndex = key($extractedOpenemisNo->toArray());
        $openemisNo = $sheet->getCellByColumnAndRow($openemisNoIndex, $row)->getValue();

        if (in_array($openemisNo, $importedUniqueCodes->getArrayCopy())) {
            $rowInvalidCodeCols['openemis_no'] = $this->getExcelLabel('Import', 'duplicate_unique_key');
            return false;
        }

        $accountType = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'account_type';
        });
        $accountTypeIndex = key($accountType->toArray());
        $accountType = $sheet->getCellByColumnAndRow($accountTypeIndex, $row)->getValue();
        $tempRow['account_type'] = $this->getAccountTypeId($accountType);
        if (empty($tempRow['account_type'])) {
            $tempRow['duplicates'] = __('Account type cannot be empty');
            $rowInvalidCodeCols['account_type'] = $tempRow['duplicates'];
            $tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes, $row, 'others');
            $tempRow['username'] = $tempRow['openemis_no'];
            return false;
        }

        $user = $this->Users->find()->where(['openemis_no'=>$openemisNo])->first();
        if (!$user) {
            $tempRow['entity'] = $this->Users->newEntity();
            $tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes, $row, $tempRow['account_type']);
            $tempRow['username'] = $tempRow['openemis_no'];
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
            if ($type['code']==$value) {
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
                    $row->$lookupColumn
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
                    $row->$lookupColumn
                ];
            }
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        //check combination of nationality and identity whether according to the setting on nationality field options
        if ($tempRow->offsetExists('nationality_id') && !empty($tempRow['nationality_id'])) {
            if ($tempRow->offsetExists('identity_type_id') && !empty($tempRow['identity_type_id'])) {
                $query = $this->Nationalities
                        ->find()
                        ->contain('IdentityTypes')
                        ->where([
                            $this->Nationalities->aliasField('id') => $tempRow['nationality_id'],
                        ])
                        ->first();

                $identityTypeId = $query->identity_type_id;
                $identityTypeName = $query->identity_type->name;

                if ($identityTypeId != $tempRow['identity_type_id']) {
                    $rowInvalidCodeCols['identity_type_id'] = $this->getMessage('Import.identity_type_doesnt_match', ['sprintf' => [$identityTypeName]]);
                    return false;
                }
            }
        }

        //if identity type selected, then need to specify identity number
        if ($tempRow->offsetExists('identity_type_id') && !empty($tempRow['identity_type_id'])) {
            if (!$tempRow->offsetExists('identity_number') || empty($tempRow['identity_number'])) {
                $rowInvalidCodeCols['identity_number'] = $this->getExcelLabel('Import', 'identity_number_required');
                return false;
            }
        }

        //if identity number is not empty, need to ensure it has identity type selected, it has to be unique and following the validation patter (if there is)
        if ($tempRow->offsetExists('identity_number') && !empty($tempRow['identity_number'])) {
            if (!$tempRow->offsetExists('identity_type_id') || empty($tempRow['identity_type_id'])) {
                $rowInvalidCodeCols['identity_type'] = $this->getExcelLabel('Import', 'identity_type_required');
                return false;
            } else {
                // check whether same identity number exist for the selected identity type
                $query = $this->UserIdentities
                        ->find()
                        ->contain('IdentityTypes')
                        ->where([
                            $this->UserIdentities->aliasField('number') => $tempRow['identity_number'],
                            $this->UserIdentities->aliasField('identity_type_id') => $tempRow['identity_type_id']
                        ])
                        ->first();
                if (!empty($query)) {
                    $identityTypeName = $query->identity_type->name;
                    $rowInvalidCodeCols['identity_number'] = $this->getMessage('Import.identity_number_exist', ['sprintf' => [$identityTypeName]]);
                    return false;
                } else {
                    // following validation pattern.
                    $query = $this->IdentityTypes->find()
                            ->where([
                                $this->IdentityTypes->aliasField('id') => $tempRow['identity_type_id']
                            ])
                            ->first();
                    $validationPattern = $query->validation_pattern;
                    if (!empty($validationPattern)) {
                        $validationPattern = '/' . $validationPattern . '/';
                        if (!preg_match($validationPattern, $tempRow['identity_number'])) {
                            $rowInvalidCodeCols['identity_number'] = $this->getExcelLabel('Import', 'identity_number_invalid_pattern');
                            return false;
                        }
                    }
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
                    $row->$lookupColumn,
                    $identityTypeName
                ];
            }
        }
    }

    protected function getNewOpenEmisNo(ArrayObject $importedUniqueCodes, $row, $accountType)
    {
        $model = $this->accountTypes[$accountType]['model'];
        $importedCodes = $importedUniqueCodes->getArrayCopy();
        if (count($importedCodes)>0) {
            if (empty($accountType)) {
                $prefix = '';
            } else {
                $prefix = $this->accountTypes[$accountType]['prefix'];
            }
            $val = reset($importedCodes);

            foreach ($this->accountTypes as $key => $value) {
                if (!empty($value['prefix']) && substr_count($val, $value['prefix'])>0) {
                    $val = substr($val, strlen($value['prefix']));
                }
            }
            $val = $prefix . (intval($val) + $row);
            $user = $this->Users->find()->select(['id'])->where(['openemis_no'=>$val])->first();
            if ($user) {
                $importedUniqueCodes[] = $val;
                $val = $this->Users->getUniqueOpenemisId(['model' => $model]);
            }
        } else {
            $val = $this->Users->getUniqueOpenemisId(['model' => $model]);
        }
        return $val;
    }

    protected function getAccountTypeId($cellValue)
    {
        $accountType = '';
        foreach ($this->accountTypes as $key => $type) {
            if ($type['code']==$cellValue) {
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

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $isStudentIdentityMandatory = $ConfigItems->value('StudentIdentities');
        $isStaffIdentityMandatory = $ConfigItems->value('StaffIdentities');

        if (($tempRow['account_type'] == "is_staff") && ($isStaffIdentityMandatory) && (empty($cellValue))) {
            $result = 'Staff identity is mandatory';
        };

        if (($tempRow['account_type'] == "is_student") && ($isStudentIdentityMandatory) && (empty($cellValue))) {
            $result = 'Student identity is mandatory';
        };

        if ($result === true) { //if checking mandatory is ok, then check the uniqueness of the Identity

            if (!empty($cellValue)) { //if Identity Number is not empty

                $userIdentitiesTable = $this->Users->Identities;

                $defaultIdentityType = $userIdentitiesTable->IdentityTypes->getDefaultValue();

                if ($defaultIdentityType) { //if has default identity

                    $countIdentity = $userIdentitiesTable->find()
                                        ->where([
                                            'number'=>$cellValue,
                                            'identity_type_id'=>$defaultIdentityType
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
}
