<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class PotentialStudentDuplicatesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('security_users'); 
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'is_staff', 'is_guardian', 'external_reference', 'super_admin', 'status', 'last_login', 'photo_name', 'photo_content', 'preferred_language'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $Students = TableRegistry::getTableLocator()->get('Institution.Students');
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');

        $duplicateStudentSubquery = $this->find()
            ->select([
                'first_name' => 'first_name',
                'last_name' => 'last_name',
                'gender_id' => 'gender_id',
                'date_of_birth' => 'date_of_birth'
            ])
            ->where(['is_student' => 1])
            ->group(['first_name', 'last_name', 'gender_id', 'date_of_birth'])
            ->having(['COUNT(*) > ' => 1]);

        $latestStudentSubquery = $this->find()
            ->select([$this->find()->func()->max('latest_student.created')])
            ->from(['latest_student' => 'institution_students'])
            ->where(['latest_student.student_id = ' . $Students->aliasField('student_id')])
            ->group('latest_student.student_id');

        $query
            ->select([
                $this->aliasField('id'), //POCOR-6069
                $this->aliasField('username'),
                $this->aliasField('openemis_no'),
                $this->aliasField('first_name'),
                $this->aliasField('middle_name'),
                $this->aliasField('third_name'),
                $this->aliasField('last_name'),
                $this->aliasField('preferred_name'),
                $this->aliasField('email'),
                $this->aliasField('address'),
                $this->aliasField('postal_code'),
                $this->aliasField('address_area_id'),
                $this->aliasField('birthplace_area_id'),
                $this->aliasField('gender_id'),
                $this->aliasField('date_of_birth'),
                $this->aliasField('date_of_death'),
                $this->aliasField('nationality_id'),
                $this->aliasField('identity_type_id'),
                $this->aliasField('identity_number'),
                'institution_code' => $Institutions->aliasField('code'),
                'institution_name' => $Institutions->aliasField('name'),
                'education_grade' => $EducationGrades->aliasField('name'),
                'education_programme_id' => $EducationGrades->aliasField('education_programme_id') //POCOR-6069
            ])
            ->innerJoin(['DuplicateStudents' => $duplicateStudentSubquery], [
                'DuplicateStudents.first_name = ' . $this->aliasField('first_name'),
                'DuplicateStudents.last_name = ' . $this->aliasField('last_name'),
                'DuplicateStudents.gender_id = ' . $this->aliasField('gender_id'),
                'DuplicateStudents.date_of_birth = ' . $this->aliasField('date_of_birth')
            ])
            ->leftJoin([$Students->getAlias() => $Students->getTable()], [
                $Students->aliasField('student_id') . ' = ' . $this->aliasField('id'),
                $Students->aliasField('created') => $latestStudentSubquery
            ])
            ->leftJoin([$Institutions->getAlias() => $Institutions->getTable()], [
                $Institutions->aliasField('id') . ' = ' . $Students->aliasField('institution_id')
            ])
            ->leftJoin([$EducationGrades->getAlias() => $EducationGrades->getTable()], [
                $EducationGrades->aliasField('id') . ' = ' . $Students->aliasField('education_grade_id')
            ])
            ->where([$this->aliasField('is_student') => 1])
            ->group([$this->aliasField('id')]);

            //Start:POCOR-6069
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) { 
                return $results->map(function ($row) { 
                    //For Education Programme
                    $EducationProgramTable = TableRegistry::getTableLocator()->get('Education.EducationProgrammes');
                    if(!empty($row->education_programme_id)){
                        $EducationProgram = $EducationProgramTable->find()->where(['id'=> $row->education_programme_id])->first();
                        $row['education_programme'] = $EducationProgram->name;
                    }
                    //For Student Status
                    $InstitutionStudentsTable = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
                    $InstitutionStudent = $InstitutionStudentsTable->find()->where(['student_id'=> $row->id])->order(['id'=>'ASC'])->first();
                    $student_status_id = $InstitutionStudent->student_status_id;
                    $StudentStatusTable = TableRegistry::getTableLocator()->get('student_statuses');
                    if(!empty($student_status_id)){
                        $StudentStatus = $StudentStatusTable->find()->where(['id'=> $student_status_id])->first();
                        $row['student_status'] = $StudentStatus->name;
                    }

                    return $row;
                });
            });
            //End:POCOR-6069
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        // set formatting to string for certain columns
        $cloneFields = $fields->getArrayCopy();

        $cloneFields[1]['label'] = 'OpenEMIS ID'; //POCOR-6069
        $stringFormatColumns = ['username', 'openemis_no', 'email', 'postal_code', 'identity_number'];
        foreach ($cloneFields as $key => $value) {
            if (in_array($value['field'], $stringFormatColumns)) {
                $cloneFields[$key]['formatting'] = 'string';
            }
        }

        $extraFields = [];

        $extraFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $extraFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution')
        ];
        //Start:POCOR-6069
        $extraFields[] = [
            'key' => '',
            'field' => 'education_programme',
            'type' => 'string',
            'label' => __('Education Programme')
        ];
        //End:POCOR-6069
        $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];
        //Start:POCOR-6069
        $extraFields[] = [
            'key' => '',
            'field' => 'student_status',
            'type' => 'string',
            'label' => __('Student Status')
        ];
        //End:POCOR-6069
        $newFields = array_merge($cloneFields, $extraFields);
        $fields->exchangeArray($newFields);
    }
}
