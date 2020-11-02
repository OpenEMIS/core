<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

use Institution\Model\Table\Institutions;

class InstitutionStaffShiftsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ClassStudents' => ['index'],
            'Staff' => ['index', 'add']
        ]);
        
        $this->setDeleteStrategy('restrict');
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //POCOR-5444 - start
        $staff = TableRegistry::get('Institution.Staff');
        $bodyData = $staff->find('all',
                                [ 'contain' => [
                                    'Institutions',
                                    'StaffTypes',
                                    'StaffPositionProfiles',
                                    'Positions',
                                    'Positions.StaffPositionTitles',
                                    'Users',
                                    'Users.Genders',
                                    'Users.MainNationalities',
                                    'Users.Identities.IdentityTypes',
                                    'Users.AddressAreas',
                                    'Users.BirthplaceAreas',
                                    'Users.Contacts.ContactTypes'                   
                                ],
                    ])->where([
                        $staff->aliasField('staff_id') => $entity->staff_id
                    ]);


            if (!empty($bodyData)) { 
                foreach ($bodyData as $key => $value) {
                    $institutionStaffId = $value->id; 
                    $user_id = $value->user->id;
                    $openemis_no = $value->user->openemis_no;
                    $first_name = $value->user->first_name;
                    $middle_name = $value->user->middle_name;
                    $third_name = $value->user->third_name;
                    $last_name = $value->user->last_name;
                    $preferred_name = $value->user->preferred_name;
                    $gender = $value->user->gender->name;
                    $nationality = $value->user->main_nationality->name;
                    $dateOfBirth = $value->user->date_of_birth;
                    
                    $address = $value->user->address;
                    $postalCode = $value->user->postal_code;
                    $addressArea = $value->user->address_area->name;
                    $birthplaceArea = $value->user->birthplace_area->name;
                    
                    $contactValue = [];
                    $contactType = [];
                    if(!empty($value->user['contacts'])) {
                        foreach ($value->user['contacts'] as $key => $contact) {
                            $contactValue[] = $contact->value;
                            $contactType[] = $contact->contact_type->name;
                        }
                    }
                    
                    $identityNumber = [];
                    $identityType = [];
                    if(!empty($value->user['identities'])) {
                        foreach ($value->user['identities'] as $key => $identity) {
                            $identityNumber[] = $identity->number;
                            $identityType[] = $identity->identity_type->name;
                        }
                    }
                    
                    $username = $value->user->username;
                    $institution_id = $value->institution->id;
                    $institutionName = $value->institution->name;
                    $institutionCode = $value->institution->code;

                    $position_no = $value->position->position_no;
                    $staff_position_titles_type = $value->position->staff_position_title->type;
                    $staff_types_name = $value->staff_type->name;
                    
                    if($staff_position_titles_type == 1 ){
                        $class= 'Teaching';
                    } else {
                        $class = 'Non-Teaching';
                    }
                    $staff_position_titles_name = $value->position->staff_position_title->name;
                    
                    $startDate = $value->start_date;
                    $endDate = $value->end_date;
                    
                }
            }
            $shift =  TableRegistry::get('Institution.InstitutionShifts');
            $shiftData = $shift->find('all',
                                [ 'contain' => [
                                    'ShiftOptions'                   
                                ],
                    ])->where([
                        $shift->aliasField('id') => $entity->shift_id
                    ]);
            if (!empty($shiftData)) {
                foreach ($shiftData as $k => $val) {
                    $shiftName =  $val->shift_option->name;
                }
            }
            $body = array();
                   
            $body = [   
                'security_users_id' => !empty($user_id) ? $user_id : NULL,
                'security_users_openemis_no' => !empty($openemis_no) ? $openemis_no : NULL,
                'security_users_first_name' =>  !empty($first_name) ? $first_name : NULL,
                'security_users_middle_name' => !empty($middle_name) ? $middle_name : NULL,
                'security_users_third_name' => !empty($third_name) ? $third_name : NULL,
                'security_users_last_name' => !empty($last_name) ? $last_name : NULL,
                'security_users_preferred_name' => !empty($preferred_name) ? $preferred_name : NULL,
                'security_users_gender' => !empty($gender) ? $gender : NULL,
                'security_users_date_of_birth' => !empty($dateOfBirth) ? date("d-m-Y", strtotime($dateOfBirth)) : NULL,
                'security_users_address' => !empty($address) ? $address : NULL,
                'security_users_postal_code' => !empty($postalCode) ? $postalCode : NULL,
                'area_administrative_name_birthplace' => !empty($addressArea) ? $addressArea : NULL,
                'area_administrative_name_address' => !empty($birthplaceArea) ? $birthplaceArea : NULL,
                'contact_type_name' => !empty($contactType) ? $contactType : NULL,
                'user_contact_type_value' => !empty($contactValue) ? $contactValue : NULL,
                'nationality_name' => !empty($nationality) ? $nationality : NULL,
                'identity_type_name' => !empty($identityType) ? $identityType : NULL,
                'user_identities_number' => !empty($identityNumber) ? $identityNumber : NULL,
                'security_user_username' => !empty($username) ? $username : NULL,
                'institutions_id' => !empty($institution_id) ? $institution_id : NULL,
                'institutions_code' => !empty($institutionCode) ? $institutionCode : NULL,
                'institutions_name' => !empty($institutionName) ? $institutionName : NULL,
                'institution_staff_id' => !empty($institutionStaffId) ? $institutionStaffId : NULL,
                'institution_staff_start_date' => !empty($startDate) ? date("d-m-Y", strtotime($startDate)) : NULL,
                'institution_staff_end_date' => !empty($endDate) ? date("d-m-Y", strtotime($endDate)) : NULL, 
                'institution_positions_position_no'=>!empty($position_no) ? $position_no : NULL,
                'staff_position_titles_type'=>!empty($class) ? $class : NULL,
                'staff_position_titles_name'=>!empty($staff_position_titles_name) ? $staff_position_titles_name : NULL,
                'staff_types_name'=>!empty($staff_types_name) ? $staff_types_name : NULL,
                'shift_options_name' => !empty($shiftName) ? $shiftName : NULL
            ];
        
            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            $Webhooks->triggerShell('staff_create', ['username' => ''], $body);
        //POCOR-5444 - End
    }

}
