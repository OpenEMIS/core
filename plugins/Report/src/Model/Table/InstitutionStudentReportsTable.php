<?php
namespace Report\Model\Table;

use ArrayObject;
use ZipArchive;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use PDOException;


/** 
* @author Rishabh
*POCOR-6970
*/

class InstitutionStudentReportsTable extends AppTable
{
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;
    private $_dynamicFieldName = 'custom_field_data';
    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);
        
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        
        $this->addBehavior('Excel', [
           // 'excludes' => ['is_student', 'photo_name', 'is_staff', 'is_guardian',  'super_admin', 'status'],
            'pages' => ['index'],
        ]);
        $this->addBehavior('Report.ReportList');
        
    }

    public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $InstitutionStudentTable = TableRegistry::get('Institution.InstitutionStudents');
        $InstitutionTypesTable = TableRegistry::get('Institution.InstitutionTypes');
        $InstitutionTable = TableRegistry::get('Institution.Institutions');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

       
        $AreaT = TableRegistry::get('areas');                    
        //Level-1
        $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $areaId])->toArray();
        $childArea =[];
        $childAreaMain = [];
        $childArea3 = [];
        $childArea4 = [];
        foreach($AreaData as $kkk =>$AreaData11 ){
            $childArea[$kkk] = $AreaData11->id;
        }
        //level-2
        foreach($childArea as $kyy =>$AreaDatal2 ){
            $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
            foreach($AreaDatas as $ky =>$AreaDatal22 ){
                $childAreaMain[$ky] = $AreaDatal22->id;
            }
        }
        //level-3
        if(!empty($childAreaMain)){
            foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                foreach($AreaDatass as $ky =>$AreaDatal222 ){
                    $childArea3[$ky] = $AreaDatal222->id;
                }
            }
        }
        
        //level-4
        if(!empty($childAreaMain)){
            foreach($childArea3 as $kyy =>$AreaDatal4 ){
                $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                    $childArea4[$ky] = $AreaDatal44->id;
                }
            }
        }
        $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
        array_push($mergeArr,$areaId);
        $mergeArr = array_unique($mergeArr);
        $finalIds = implode(',',$mergeArr);
        $finalIds = explode(',',$finalIds);
        

        $conditions = [];
        if ($areaId != -1) {
            $conditions['Institution.area_id IN'] = $finalIds;
        }
        if (!empty($academicPeriodId)) {
            $conditions['academic_period_id'] = $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['institution_id'] = $institutionId;
        }
        if (!empty($enrolled)) {
            $conditions['student_status_id'] = $enrolled;
        }

        $query->select([
                    'student_id' => 'Students.id',
                    'username' => 'Students.username',
                    'openemis_no' => 'Students.openemis_no',
                    'first_name' => 'Students.first_name',
                    'middle_name' => 'Students.middle_name',
                    'third_name' => 'Students.third_name',
                    'last_name' => 'Students.last_name',
                    'preferred_name' => 'Students.preferred_name',
                    'email' => 'Students.email',
                    'address' => 'Students.address',
                    'postal_code' => 'Students.postal_code',
                    'address_area' => 'AddressAreas.name',
                    'birthplace_area' => 'BirthplaceAreas.name',
                    'gender' => 'Genders.name',
                    'date_of_birth' => 'Students.date_of_birth',
                    'date_of_death' => 'Students.date_of_death',
                    'nationality_name' => 'MainNationalities.name',
                    'identity_type' => 'MainIdentityTypes.name',
                    'identity_number' => 'Students.identity_number',
                    'external_reference' => 'Students.external_reference',
                    'last_login' => 'Students.last_login',
                    'preferred_language' => 'Students.preferred_language',
                    'EndDate' => 'end_date',
                    'institution_name' => 'Institutions.name',
                    'institution_type_id' => 'Institutions.institution_type_id',
                    'institution_id' => 'Institutions.id',
                    'institution_localities' => 'Localities.name',
                    'area_administratives'=> 'AreaAdministratives.name',
                    'area_education'=> 'Areas.name',
                     'external_reference' => 'Students.external_reference'
                ])
                ->where([
                    $conditions
                ])
                ->contain([
                    'Students',
                    'Students.Genders',
                    'Students.AddressAreas', 
                    'Students.BirthplaceAreas', 
                    'Students.MainNationalities', 
                    'Students.MainIdentityTypes',
                    'Institutions',
                    'Institutions.Localities',
                    'Institutions.AreaAdministratives',
                    'Institutions.Areas',
                   
                ])
                ->group([$this->aliasField('student_id')]);
               
                
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $InstitutionTypesTable = TableRegistry::get('institution_types');
                $institution_type = $InstitutionTypesTable->find('all',['conditions'=>['id'=>$row['institution_type_id']]])->first();
                $row['institution_type'] = $institution_type->name;

                $Users = TableRegistry::get('security_users');
                $institutionStudents = TableRegistry::get('institution_students');
               
                $Guardians = TableRegistry::get('student_custom_field_values');
                $studentCustomFieldOptions = TableRegistry::get('student_custom_field_options');
                $studentCustomFields = TableRegistry::get('student_custom_fields');

                $guardianData = $Guardians->find()
                ->select([
                    'id'                             => $Guardians->aliasField('id'),
                    'student_id'                     => $Guardians->aliasField('student_id'),
                    'student_custom_field_id'        => $Guardians->aliasField('student_custom_field_id'),
                    'text_value'                     => $Guardians->aliasField('text_value'),
                    'number_value'                   => $Guardians->aliasField('number_value'),
                    'decimal_value'                  => $Guardians->aliasField('decimal_value'),
                    'textarea_value'                 => $Guardians->aliasField('textarea_value'),
                    'date_value'                     => $Guardians->aliasField('date_value'),
                    'time_value'                     => $Guardians->aliasField('time_value'),
                    'checkbox_value_text'            => 'studentCustomFieldOptions.name',
                    'question_name'                  => 'studentCustomField.name',
                    'field_type'                     => 'studentCustomField.field_type',
                    'field_description'              => 'studentCustomField.description',
                    'question_field_type'            => 'studentCustomField.field_type',
                ])->leftJoin(
                    ['studentCustomField' => 'student_custom_fields'],
                    [
                        'studentCustomField.id = '.$Guardians->aliasField('student_custom_field_id')
                    ]
                )->leftJoin(
                    ['studentCustomFieldOptions' => 'student_custom_field_options'],
                    [
                        'studentCustomFieldOptions.id = '.$Guardians->aliasField('number_value')
                    ]
                )
                ->where([
                    $Guardians->aliasField('student_id') => $row['student_id'],
                ])->toArray();

                $existingCheckboxValue = '';
                foreach ($guardianData as $guadionRow) {
                    $fieldType = $guadionRow->field_type;
                    if ($fieldType == 'TEXT') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->text_value;
                    } else if ($fieldType == 'CHECKBOX') {
                        $existingCheckboxValue = trim($row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id], ',') .','. $guadionRow->checkbox_value_text;
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = trim($existingCheckboxValue, ',');
                    } else if ($fieldType == 'NUMBER') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->number_value;
                    } else if ($fieldType == 'DECIMAL') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->decimal_value;
                    } else if ($fieldType == 'TEXTAREA') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->textarea_value;
                    } else if ($fieldType == 'DROPDOWN') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->checkbox_value_text;
                    } else if ($fieldType == 'DATE') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = date('Y-m-d', strtotime($guadionRow->date_value));
                    } else if ($fieldType == 'TIME') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = date('h:i A', strtotime($guadionRow->time_value));
                    } else if ($fieldType == 'COORDINATES') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->text_value;
                    } else if ($fieldType == 'NOTE') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->field_description;
                    }
                }
                return $row;
            });
        });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $settings['identity'] = $identity;

        $extraField[] = [
            'key' => 'Students.username',
            'field' => 'username',
            'type' => 'string',
            'label' => 'Username',
        ];

        $extraField[] = [
            'key' => 'Students.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID',
        ];

        $extraField[] = [
            'key' => 'Students.first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => 'First Name',
        ];

        $extraField[] = [
            'key' => 'Students.middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => 'Middle Name',
        ];

        $extraField[] = [
            'key' => 'Students.third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' => 'Third Name',
        ];

        $extraField[] = [
            'key' => 'Students.last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => 'Last Name',
        ];

        $extraField[] = [
            'key' => 'Students.preferred_name',
            'field' => 'preferred_name',
            'type' => 'string',
            'label' => 'Preferred Name',
        ];

        $extraField[] = [
            'key' => 'Students.email',
            'field' => 'email',
            'type' => 'string',
            'label' => 'Email',
        ];

        $extraField[] = [
            'key' => 'Students.address',
            'field' => 'address',
            'type' => 'string',
            'label' => 'Address',
        ];

        $extraField[] = [
            'key' => 'Students.postal_code',
            'field' => 'postal_code',
            'type' => 'string',
            'label' => 'Postal Code',
        ];

        $extraField[] = [
            'key' => 'AddressAreas.name',
            'field' => 'address_area',
            'type' => 'string',
            'label' => 'Address Area',
        ];


        $extraField[] = [
            'key' => 'BirthplaceAreas.name',
            'field' => 'birthplace_area',
            'type' => 'string',
            'label' => 'Birthplace Area',
        ];

        $extraField[] = [
            'key' => 'Genders.name',
            'field' => 'gender',
            'type' => 'string',
            'label' => 'Gender',
        ];

        $extraField[] = [
            'key' => 'Students.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => 'Date Of Birth',
        ];

        $extraField[] = [
            'key' => 'Students.date_of_death',
            'field' => 'date_of_death',
            'type' => 'string',
            'label' => 'Date Of Death',
        ];

        $extraField[] = [
            'key' => 'MainNationalities.name',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => 'Main Nationality',
        ];

        $extraField[] = [
            'key' => 'MainIdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => 'Main Identity Type',
        ];

        $extraField[] = [
            'key' => 'Students.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => 'Identity Number',
        ];

        $extraField[] = [
            'key' => 'Institution.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => 'Institution Name',
        ];

        $extraField[] = [
            'key' => 'InstitutionTypes.name',
            'field' => 'institution_type',
            'type' => 'string',
            'label' => 'Institution Type',
        ];

        $extraField[] = [
            'key' => 'Localities.name',
            'field' => 'institution_localities',
            'type' => 'string',
            'label' => 'Institution Locality',
        ];

        $extraField[] = [
            'key' => 'Areas.name',
            'field' => 'area_education',
            'type' => 'string',
            'label' => 'Area Education',
        ];

        $extraField[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administratives',
            'type' => 'string',
            'label' => 'Area Administration',
        ];


        $extraField[] = [
            'key' => 'Students.external_reference',
            'field' => 'external_reference',
            'type' => 'string',
            'label' => 'External Reference',
        ];

        $extraField[] = [
            'key' => 'Students.last_login',
            'field' => 'external_reference',
            'type' => 'string',
            'label' => 'Last Login',
        ];

        $extraField[] = [
            'key' => 'Students.preferred_language',
            'field' => 'preferred_language',
            'type' => 'string',
            'label' => 'Preferred Language',
        ];
        $InfrastructureCustomFields = TableRegistry::get('student_custom_fields');
        $customFieldData = $InfrastructureCustomFields->find()->select([
            'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
            'custom_field' => $InfrastructureCustomFields->aliasfield('name')
        ])->group($InfrastructureCustomFields->aliasfield('id'))->toArray();


        if(!empty($customFieldData)) {
            foreach($customFieldData as $data) {
                $custom_field_id = $data->custom_field_id;
                $custom_field = $data->custom_field;
                $extraField[] = [
                    'key' => '',
                    'field' => $this->_dynamicFieldName.'_'.$custom_field_id,
                    'type' => 'string',
                    'label' => __($custom_field)
                ];
            }
        }
        $fields->exchangeArray($extraField);
    }
}