<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class StudentContactsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);
		
		//$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		//$this->belongsTo('ContactTypes', ['className' => 'User.ContactTypes']);
		
		$this->addBehavior('Excel');
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

    //POCOR-7491:: Start ---Changes in query remove extra function
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');
        $institutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $institutionIds = TableRegistry::get('Institution.Institutions');
        $educationGrades = TableRegistry::get('Education.EducationGrades');
        $userIdentity = TableRegistry::get('User.Identities');
        $identityType = TableRegistry::get('FieldOption.IdentityTypes');
        $userContacts = TableRegistry::get('User.Contacts');
        $contactsOptions = TableRegistry::get('User.ContactOptions');
        $contactsType = TableRegistry::get('User.ContactTypes');
        $academicPeriods = TableRegistry::get('academic_periods');

        $UserIdentities = TableRegistry::get('user_identities');
        $IdentityTypes = TableRegistry::get('identity_types');

        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions['InstitutionStudents.academic_period_id'] = $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InstitutionStudents.institution_id'] = $institutionId;
        }
        if (!empty($enrolled)) {
            $conditions['InstitutionStudents.student_status_id'] = $enrolled;
        }
        $query
            ->select([
                'institution_code' => $institutionIds->aliasField('code'),
                'institution_name' => $institutionIds->aliasField('name'),
                'student_id' => $institutionStudents->aliasField('student_id'),
                'education_grade_id' => $institutionStudents->aliasField('education_grade_id'),
                'education_name' => $educationGrades->aliasField('name'),
                'education_code' => $educationGrades->aliasField('code'),
                'openemis_no' => $this->aliasField('openemis_no'),
                'security_user_id' => $this->aliasField('id'),
                'identity_numberr' => $UserIdentities->aliasField('number'),
                'identity_type' => $IdentityTypes->aliasField('name'),
                'user_name' => $query->func()->concat([
                    $this->aliasField('first_name') => 'literal',
                    " ",
                    $this->aliasField('last_name') => 'literal'
                ]),
            ])
            ->leftJoin([$institutionStudents->alias() => $institutionStudents->table()],
                [
                $institutionStudents->aliasField('student_id') . ' = '. $this->aliasField('id')
                ])
            ->leftJoin([$institutionStudents->alias() => $institutionStudents->table()],
                [
                $institutionStudents->aliasField('student_id') . ' = '. $this->aliasField('id')
                ])
           ->leftJoin([$institutionStudents->alias() => $institutionStudents->table()],
                [
                $institutionStudents->aliasField('student_id') . ' = '. $this->aliasField('id')
                ])
            ->leftJoin([$UserIdentities->alias() => $UserIdentities->table()],
                [
                $UserIdentities->aliasField('security_user_id') . ' = '. $this->aliasField('id')
                ])
            ->leftJoin([$IdentityTypes->alias() => $IdentityTypes->table()],
                [
                $IdentityTypes->aliasField('id') . ' = '. $UserIdentities->aliasField('identity_type_id')
                ])
            ->innerJoin([$institutionIds->alias() => $institutionIds->table()],
                [
                $institutionIds->aliasField('id') . ' = '. $institutionStudents->aliasField('institution_id')
                ])
            ->innerJoin([$educationGrades->alias() => $educationGrades->table()],
                [
                $educationGrades->aliasField('id') . ' = '. $institutionStudents->aliasField('education_grade_id')
                ])
            ->leftJoin([$academicPeriods->alias() => $academicPeriods->table()],
                [
                $academicPeriods->aliasField('id') . ' = '. $institutionStudents->aliasField('academic_period_id')
                ])
            ->where([$institutionStudents->aliasField('student_status_id') => 1, $conditions])
            ->group(['InstitutionStudents.student_id']);

            $query
                ->select([
                    'contacts' => $userContacts->aliasField('security_user_id'),
                    'contact_name' => $query->func()->group_concat([
                    $this->aliasField('ContactOptions.name') => 'literal',
                    " ",
                    '(',
                    $this->aliasField('ContactTypes.name') => 'literal',
                    " ", '): ',
                    $this->aliasField('Contacts.value') => 'literal',
                    " "
                    ]),
                   'description' => $contactsType->aliasField('name'),
                   'preferred' => $userContacts->aliasField('preferred'),
                ])
                ->innerJoin([$userContacts->alias() => $userContacts->table()],
                [
                    $userContacts->aliasField('security_user_id') . ' = '. $this->aliasField('id')
                ])
                ->innerJoin([$contactsType->alias() => $contactsType->table()],
                [
                    $contactsType->aliasField('id') . ' = '. $userContacts->aliasField('contact_type_id')
                ])
                ->innerJoin([$contactsOptions->alias() => $contactsOptions->table()],
                [
                    $contactsOptions->aliasField('id') . ' = '. $contactsType->aliasField('contact_option_id')
                ])
                ->where([$userContacts->aliasField('preferred') => 1, $conditions])
                ->group(['Contacts.security_user_id']);
    }
    //POCOR-7491:: End

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $extraFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

		$extraFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
		
        $extraFields[] = [
            'key' => 'education_code',
            'field' => 'education_code',
            'type' => 'string',
            'label' => __('Education Code')
        ];

		$extraFields[] = [
            'key' => 'education_name',
            'field' => 'education_name',
            'type' => 'string',
            'label' => __('Education Name')
        ];
		
        $extraFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];    

        $extraFields[] = [
            'key' => 'user_name',
            'field' => 'user_name',
            'type' => 'string',
            'label' => __('Student Name')
        ];    

        $extraFields[] = [
            'key' => 'identity_type',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type') //POCOR-7108
        ]; 

        $extraFields[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number') 
        ];

        $extraFields[] = [
            'key' => 'contact_name',
            'field' => 'contact_name',
            'type' => 'string',
            'label' => __('Contact') //POCOR-7108
        ];

        $fields->exchangeArray($extraFields);
    }


}
