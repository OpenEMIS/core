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
        $subquery1 = "(SELECT institution_staff.staff_id
                FROM institution_staff
                INNER JOIN academic_periods
                    ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date)
                            OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date)
                            OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date))
                        OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                WHERE academic_periods.id = $academicPeriodId
                    AND institution_staff.staff_status_id = 1
                GROUP BY institution_staff.staff_id
            )";
        $subquery2 = '(SELECT user_contacts.security_user_id
                ,GROUP_CONCAT(CONCAT(" ", contact_options.name, " (", contact_types.name, "): ", user_contacts.value)) contacts
                FROM user_contacts
                INNER JOIN contact_types
                ON contact_types.id = user_contacts.contact_type_id
                INNER JOIN contact_options
                ON contact_options.id = contact_types.contact_option_id
                WHERE user_contacts.preferred = 1
                GROUP BY user_contacts.security_user_id
            )';
        $subquery3 = '(SELECT user_nationalities.security_user_id
                ,nationalities.name nationality_name
                FROM user_nationalities
                INNER JOIN nationalities
                    ON nationalities.id = user_nationalities.nationality_id
                WHERE user_nationalities.preferred = 1
                GROUP BY user_nationalities.security_user_id
            )';
        
        $subquery4 = '(SELECT user_identities.security_user_id
                ,GROUP_CONCAT(identity_types.name) identity_type
                ,GROUP_CONCAT(user_identities.number) identity_number
                FROM user_identities
                INNER JOIN identity_types
                ON identity_types.id = user_identities.identity_type_id
                WHERE identity_types.default = 1
                GROUP BY user_identities.security_user_id
            )';
        $query->select([
            'openemisID' => 'openemis_no',
            'StaffName' => 'CONCAT_WS(" ", first_name, middle_name, third_name, last_name)',
            'PreferredName' => 'IFNULL(preferred_name, "")',
            'Contacts' => 'IFNULL(contact_info.contacts, "")',
            'Address' => 'IFNULL(address, "")',
            'PostalCode' => 'IFNULL(postal_code, "")',
            'AddressArea' => 'IFNULL(areas.name, "")',
            'BirthplaceArea' => 'IFNULL(area_administratives.name, "")',
            'Gender' => "IF(gender_id = 1, 'Male', 'Female')",
            'DateofBirth' => 'CONCAT(DAY(date_of_birth), " ", MONTHNAME(date_of_birth), ", ", YEAR(date_of_birth))',
            'Nationality' => 'IFNULL(staff_nationalities.nationality_name, "")',
            'IdentityType' => 'IFNULL(staff_identities.identity_type, "")',
            'IdentityNumber' => 'IFNULL(staff_identities.identity_number, "")'
        ]);
        $query->join([
            'table' => 'areas',
            'alias' => 'areas',
            'type' => 'LEFT',
            'conditions' => 'areas.id =' .  $this->aliasField('address_area_id')
        ]);
        $query->join([
            'table' => 'area_administratives',
            'alias' => 'area_administratives',
            'type' => 'LEFT',
            'conditions' => 'area_administratives.id =' . $this->aliasField('birthplace_area_id')
        ]);
        $query->join([
            'table' => $subquery1,
            'alias' => 'school_staff',
            'type' => 'LEFT',
            'conditions' => 'school_staff.staff_id =' . $this->aliasField('id')
        ]);
        $query->join([
                    'table' => $subquery2,
                    'alias' => 'contact_info',
                    'type' => 'LEFT',
                    'conditions' => 'contact_info.security_user_id ='. $this->aliasField('id')
        ]);
        $query->join([
                    'table' => $subquery3,
                    'alias' => 'staff_nationalities',
                    'type' => 'LEFT',
                    'conditions' => 'staff_nationalities.security_user_id ='. $this->aliasField('id')
        ]);
        $query->join([
                    'table' => $subquery4,
                    'alias' => 'staff_identities',
                    'type' => 'LEFT',
                    'conditions' => 'staff_identities.security_user_id ='. $this->aliasField('id')
        ]);
        $query->where([
                    $this->aliasField('is_staff') => 1,
                    $this->aliasField('status') => 1,
                    'school_staff.staff_id IS NULL'
        ]);
           
    }
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
        
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.openemis_no',
            'field' => 'openemisID',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.staff_name',
            'field' => 'StaffName',
            'type' => 'string',
            'label' => __('Full Name')
        ];
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.preferred_name',
            'field' => 'PreferredName',
            'type' => 'string',
            'label' => __('Preferred Name')
        ];
        
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.contacts',
            'field' => 'Contacts',
            'type' => 'string',
            'label' => __('Contact')
            
        ];
       
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.address',
            'field' => 'Address',
            'type' => 'string'
        ];
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.postal_code',
            'field' => 'PostalCode',
            'type' => 'string',
            'label' => __('Postal Code')
        ];
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.address_area_id',
            'field' => 'AddressArea',
            'type' => 'string',
            'label' => __('Address Area')
        ];
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.birthplace_area_id',
            'field' => 'BirthplaceArea',
            'type' => 'string',
            'label' => __('Birthplace Area')
        ];
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.gender_id',
            'field' => 'Gender',
            'type' => 'string'
        ];
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.date_of_birth',
            'field' => 'DateofBirth',
            'type' => 'string',
            'label' => __('Date Of Birth')
        ];
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.nationality_id',
            'field' => 'Nationality',
            'type' => 'string'
        ];
        
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.identity_type_id',
            'field' => 'IdentityType',
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        $extraField[] = [
            'key' => 'StaffOutOfSchoolTable.identity_number',
            'field' => 'IdentityNumber',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $fields->exchangeArray($extraField);
    }
    
}
// POCOR -4827 ends