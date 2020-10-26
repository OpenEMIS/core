<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class StaffDutiesTable extends AppTable  {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('institution_staff_duties');
		parent::initialize($config);
	
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		
		$this->addBehavior('Excel', ['excludes' => ['id', 'comment', 'start_year', 'end_year', 'institution_programme_id']]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
       
        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        
        if (!empty($institutionId)) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }
        

         $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'academic_period' => 'AcademicPeriods.name',
				'openemis_no' => 'Users.openemis_no',
				'user_name' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                ]),
				'contact_value' => 'Contacts.value',
				'contact_type' => 'ContactTypes.name',
             ])
             ->leftJoin(['Users' => 'security_users'], [
                            $this->aliasfield('staff_id') . ' = '.'Users.id'
                        ])
             ->leftJoin(['Institutions' => 'institutions'], [
                           $this->aliasfield('institution_id') . ' = '.'Institutions.id'
                        ])
             ->leftJoin(['AcademicPeriods' => 'academic_periods'], [
                           $this->aliasfield('academic_period_id') . ' = AcademicPeriods.id'
                        ])
			->leftJoin(['Contacts' => 'user_contacts'], [
                           'Users.id = Contacts.security_user_id'
                        ])
			->leftJoin(['ContactTypes' => 'contact_types'], [
                           'Contacts.contact_type_id = ContactTypes.id'
                        ])			
               ->where($conditions);
    }

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions('Institutions');
		return $attr;
	}

	 public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
    {   
         $cloneFields = $fields->getArrayCopy();

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
            'label' => __('Institution Name')
        ];

        $extraFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'integer',
            'label' => __('Academic Period')
        ];

        $extraFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'integer',
            'label' => __('OpenEMIS ID')
        ];
		
		$extraFields[] = [
            'key' => '',
            'field' => 'user_name',
            'type' => 'string',
            'label' => __('Name')
        ];
		
		$extraFields[] = [
            'key' => 'ContactTypes.name',
            'field' => 'contact_type',
            'type' => 'string',
            'label' => __('Contact Type')
        ];
		
		$extraFields[] = [
            'key' => 'Contacts.value',
            'field' => 'contact_value',
            'type' => 'string',
            'label' => __('Contact Value')
        ];
		
       $newFields = $extraFields;
       $fields->exchangeArray($newFields);
       
   }
	
}
