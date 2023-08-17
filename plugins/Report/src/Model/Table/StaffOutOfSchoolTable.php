<?php
namespace Report\Model\Table;
use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use App\Model\Traits\MessagesTrait;
// POCOR -4827 starts
class StaffOutOfSchoolTable extends AppTable  {
    use MessagesTrait;
    public function initialize(array $config) {
        $this->table('security_users');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => ['super_admin', 'is_student', 'is_staff', 'is_guardian', 'photo_name', 'date_of_death', 'last_login', 'status', 'username'], 
            'pages' => false
        ]);
    }
    public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        //POCOR-7661::Start
        $join=[];
        $conditions=[];

        $join['areas'] = [
            'type' => 'left',
            'conditions'=>[ 'areas.id = StaffOutOfSchool.address_area_id']
            ]; 
        $join['area_administratives'] = [
            'type' => 'left',
            'conditions'=>[ 'area_administratives.id = StaffOutOfSchool.birthplace_area_id']
            ]; 
        $join['genders'] = [
            'type' => 'inner',
            'conditions'=>[ 'genders.id = StaffOutOfSchool.gender_id']
            ]; 
    
        $join['school_staff'] = [
        'type' => 'left',
        'table' => "(SELECT  institution_staff.staff_id
        FROM institution_staff
        INNER JOIN academic_periods
        ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
        WHERE academic_periods.id = $academicPeriodId
        AND institution_staff.staff_status_id = 1
        GROUP BY  institution_staff.staff_id)",
        'conditions'=>[ 'school_staff.staff_id = StaffOutOfSchool.id']
        ]; 

        $join['contact_info'] = [
            'type' => 'left',
            'table' => "(SELECT  user_contacts.security_user_id
            ,GROUP_CONCAT(CONCAT(' ',contact_options.name,' (',contact_types.name,'): ',user_contacts.value)) contacts
     FROM user_contacts
     INNER JOIN contact_types
     ON contact_types.id = user_contacts.contact_type_id
     INNER JOIN contact_options
     ON contact_options.id = contact_types.contact_option_id
     WHERE user_contacts.preferred = 1
     GROUP BY  user_contacts.security_user_id)",
            'conditions'=>[ 'contact_info.security_user_id = StaffOutOfSchool.id']
            ]; 

        $join['staff_nationalities'] = [
            'type' => 'left',
            'table' => "(SELECT  user_nationalities.security_user_id
            ,nationalities.name nationality_name
     FROM user_nationalities
     INNER JOIN nationalities
     ON nationalities.id = user_nationalities.nationality_id
     WHERE user_nationalities.preferred = 1
     GROUP BY  user_nationalities.security_user_id)",
            'conditions'=>[ 'staff_nationalities.security_user_id = StaffOutOfSchool.id']
            ]; 

        $join['staff_identities'] = [
            'type' => 'left',
            'table' => "(SELECT  user_identities.security_user_id
            ,GROUP_CONCAT(identity_types.name) identity_type
            ,GROUP_CONCAT(user_identities.number) identity_number
     FROM user_identities
     INNER JOIN identity_types
     ON identity_types.id = user_identities.identity_type_id
     WHERE identity_types.default = 1
     GROUP BY  user_identities.security_user_id)",
            'conditions'=>[ 'staff_identities.security_user_id = StaffOutOfSchool.id']
            ]; 

            $query
            ->select([
                'openemisID' => 'StaffOutOfSchool.openemis_no',
                'StaffName' => 'CONCAT_WS(" ", StaffOutOfSchool.first_name, StaffOutOfSchool.middle_name, StaffOutOfSchool.third_name, StaffOutOfSchool.last_name)',
                'PreferredName' => 'IFNULL(StaffOutOfSchool.preferred_name, "")',
                'Contacts' => 'IFNULL(contact_info.contacts, "")',
                'Address' => 'IFNULL(StaffOutOfSchool.address, "")',
                'PostalCode' => 'IFNULL(StaffOutOfSchool.postal_code, "")',
                'AddressArea' => 'IFNULL(areas.name, "")',
                'BirthplaceArea' => 'IFNULL(area_administratives.name, "")',
                'Gender' => 'genders.name',
                'DateofBirth' => 'CONCAT(DAY(StaffOutOfSchool.date_of_birth), " ", MONTHNAME(StaffOutOfSchool.date_of_birth), ", ", YEAR(StaffOutOfSchool.date_of_birth))',
                'Nationality' => 'IFNULL(staff_nationalities.nationality_name, "")',
                'IdentityType' => 'IFNULL(staff_identities.identity_type, "")',
                'IdentityNumber' => 'IFNULL(staff_identities.identity_number, "")'
                ])
            ->where([
                            'StaffOutOfSchool.is_staff' => 1,
                            'StaffOutOfSchool.status' => 1,
                            'school_staff.staff_id IS NULL'
                ]);

                $query->join($join);
        //POCOR-7661::End
           
    }
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
        
        $extraField[] = [
            'key' => 'openemis_no',
            'field' => 'openemisID',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $extraField[] = [
            'key' => 'staff_name',
            'field' => 'StaffName',
            'type' => 'string',
            'label' => __('Full Name')
        ];
        $extraField[] = [
            'key' => 'preferred_name',
            'field' => 'PreferredName',
            'type' => 'string',
            'label' => __('Preferred Name')
        ];
        
        $extraField[] = [
            'key' => 'contacts',
            'field' => 'Contacts',
            'type' => 'string',
            'label' => __('Contact')
            
        ];
       
        $extraField[] = [
            'key' => 'address',
            'field' => 'Address',
            'type' => 'string'
        ];
        $extraField[] = [
            'key' => 'postal_code',
            'field' => 'PostalCode',
            'type' => 'string',
            'label' => __('Postal Code')
        ];
        $extraField[] = [
            'key' => 'address_area_id',
            'field' => 'AddressArea',
            'type' => 'string',
            'label' => __('Address Area')
        ];
        $extraField[] = [
            'key' => 'birthplace_area_id',
            'field' => 'BirthplaceArea',
            'type' => 'string',
            'label' => __('Birthplace Area')
        ];
        $extraField[] = [
            'key' => 'gender_id',
            'field' => 'Gender',
            'type' => 'string'
        ];
        $extraField[] = [
            'key' => 'date_of_birth',
            'field' => 'DateofBirth',
            'type' => 'string',
            'label' => __('Date Of Birth')
        ];
        $extraField[] = [
            'key' => 'nationality_id',
            'field' => 'Nationality',
            'type' => 'string'
        ];
        
        $extraField[] = [
            'key' => 'identity_type_id',
            'field' => 'IdentityType',
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        $extraField[] = [
            'key' => 'identity_number',
            'field' => 'IdentityNumber',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $fields->exchangeArray($extraField);
    }
    
}
// POCOR -4827 ends