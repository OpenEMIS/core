<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class PotentialStudentDuplicatesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
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

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $Students = TableRegistry::get('Institution.Students');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');

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
                'education_grade' => $EducationGrades->aliasField('name')
            ])
            ->innerJoin(['DuplicateStudents' => $duplicateStudentSubquery], [
                'DuplicateStudents.first_name = ' . $this->aliasField('first_name'),
                'DuplicateStudents.last_name = ' . $this->aliasField('last_name'),
                'DuplicateStudents.gender_id = ' . $this->aliasField('gender_id'),
                'DuplicateStudents.date_of_birth = ' . $this->aliasField('date_of_birth')
            ])
            ->leftJoin([$Students->alias() => $Students->table()], [
                $Students->aliasField('student_id') . ' = ' . $this->aliasField('id'),
                $Students->aliasField('created') => $latestStudentSubquery
            ])
            ->leftJoin([$Institutions->alias() => $Institutions->table()], [
                $Institutions->aliasField('id') . ' = ' . $Students->aliasField('institution_id')
            ])
            ->leftJoin([$EducationGrades->alias() => $EducationGrades->table()], [
                $EducationGrades->aliasField('id') . ' = ' . $Students->aliasField('education_grade_id')
            ])
            ->where([$this->aliasField('is_student') => 1])
            ->group([$this->aliasField('id')]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        // set formatting to string for certain columns
        $cloneFields = $fields->getArrayCopy();
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

        $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $newFields = array_merge($cloneFields, $extraFields);
        $fields->exchangeArray($newFields);
    }
}
