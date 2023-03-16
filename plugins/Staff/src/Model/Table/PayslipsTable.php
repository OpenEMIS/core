<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Traits\MessagesTrait;

class PayslipsTable extends ControllerActionTable
{
    use MessagesTrait;
    public function initialize(array $config)
    {
        $this->table('staff_payslips');
        
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StaffPayslips' => ['add']
        ]);

        $this->addBehavior('ControllerAction.FileUpload', [
            'size' => '2MB',
            'contentEditable' => false,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        if ($this->behaviors()->has('ControllerAction')) {
            $this->behaviors()->get('ControllerAction')->config([
                'actions' => [
                    'download' => ['show' => true] // to show download on toolbar
                ]
            ]);
        }

    } 

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('file_type', ['visible' => false]);
       
        $this->field('created', [
            'type' => 'datetime',
            'visible' => true
        ]);

        $this->setFieldOrder([
            'name',
            'description',
            'created'
        ]);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Payslips','Staff - Finance');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    { 
        if ($entity->isNew()) {
            $emptyFields = 0;
            if(!isset($entity->openemis_id)){
                if(( !isset($entity->user_identity_number) && !isset($entity->user_identity_type_id))){
                    $emptyFields = 1;
                }
            }
            $path_uri = '/Staff/Payslips/add';
            if(!isset($entity->name)){
                $response["name"][] ="Field Can not be empty";
                $entity->errors($response);
                    return false;
            }else if(!isset($entity->file_name)){
                $response["file_name"][] ="Field Can not be empty";
                $entity->errors($response);
                    return false;
            }else if(!isset($entity->file_content)){
                $response["file_content"][] ="Field Can not be empty";
                $entity->errors($response);
                    return false;
            }else if($emptyFields == 1 && !is_int(strpos($_SERVER['REQUEST_URI'], $path_uri))){
                echo $_SERVER['REQUEST_URI'];die;
                $response = array('error'=> 'Please enter either OpenEMIS ID or identity number');
                $entity->errors($response);    
                return false;
            }else{
                $apiSecuritiesScopes = TableRegistry::get('AcademicPeriod.ApiSecuritiesScopes');
                $apiSecurities = TableRegistry::get('AcademicPeriod.ApiSecurities');
                $apiSecuritiesData = $apiSecurities->find('all')
                    ->select([
                        'ApiSecurities.id','ApiSecurities.name','ApiSecurities.add'
                    ])
                    ->where([
                        'ApiSecurities.name' => 'Payslips',
                        'ApiSecurities.model' => 'Staff.StaffPayslips'
                    ])
                    ->first();
                $apiSecuritiesScopesData = $apiSecuritiesScopes->find('all')
                    ->select([
                        'ApiSecuritiesScopes.add'
                    ])
                    ->where([
                        'ApiSecuritiesScopes.api_security_id' => $apiSecuritiesData->id
                    ])
                    ->first();
                if($apiSecuritiesScopesData->add == 0){
                    $response["message"][] ="Api is disabled";
                    $entity->errors($response);
                    return false;
                }else{
                    if (!empty($entity->openemis_id)) {
                        $openemis_payload_exist = 1;
                        $Users = TableRegistry::get('security_users');
                        $user_data= $Users
                                    ->find()
                                    ->where(['security_users.openemis_no' => $entity->openemis_id])
                                    ->first();
                        if ((!empty($user_data)  && $user_data->is_staff)) {
                            $entity->staff_id = $user_data['id'];
                        }else{
                            $response["openemis_id"][] ="Record not found";
                            $entity->errors($response);
                            return false;
                        } 
                    }

                    if(( !empty($entity->user_identity_number) && !empty($entity->user_identity_type_id))){
                        $UsersIdentity = TableRegistry::get('user_identities');
                        $user_identity_data= $UsersIdentity
                                    ->find()
                                    ->where(['user_identities.number' => $entity->user_identity_number,
                                             'user_identities.identity_type_id' => $entity->user_identity_type_id,
                                            ])
                                    ->first();
                        if ((!empty($user_identity_data))) {
                            $entity->staff_id = $user_identity_data['security_user_id'];
                        }else{
                            if($openemis_payload_exist == 0){
                                $response["user_identity_number"][] ="Record not found";
                                $entity->errors($response);
                                return false;
                            }
                        } 
                    }else{
                        if(($openemis_payload_exist == 0 && !is_int(strpos($_SERVER['REQUEST_URI'], $path_uri)) )){
                            if(empty($entity->user_identity_number)){
                                $response["user_identity_number"][] ="Field Can not be empty";
                                $entity->errors($response);
                                return false;
                            }else if(empty($entity->user_identity_type_id)){
                                $response["user_identity_type_id"][] ="Field Can not be empty";
                                $entity->errors($response);
                                return false;
                            }
                        }
                    }
                } 
            }
        }

    }



    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('identity_number', ['visible' => false]);
    }

    public function onGetFileType(Event $event, Entity $entity)
    {
        return $this->getFileTypeForView($entity->file_name);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $user = $this->Auth->user();
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];

        if ($user['super_admin'] == 1 && $buttons['view']['url']['plugin'] == 'Staff') {
            $downloadUrl = [
                'plugin' => 'Staff',
                'controller' => 'Staff',
                'action' => $this->alias,
                'staffId' => $this->paramsEncode(['id' => $entity->staff_id]),
                '0' => 'download',
                '1' => $this->paramsEncode(['id' => $entity->id])
            ];

             $buttons['download'] = [
                'label' => '<i class="fa kd-download"></i>'.__('Download'),
                'attr' => $indexAttr,
                'url' => $downloadUrl
            ];
        }

        else{
             $downloadUrl = [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => $this->alias,
                'staffId' => $this->paramsEncode(['id' => $entity->staff_id]),
                '0' => 'download',
                '1' => $this->paramsEncode(['id' => $entity->id])
            ]; 

            if ($buttons['view']['url']['plugin'] == 'Profile' || $user['super_admin'] == 1){
                $buttons['download'] = [
                    'label' => '<i class="fa kd-download"></i>'.__('Download'),
                    'attr' => $indexAttr,
                    'url' => $downloadUrl
                ];
            }
        }
        


        return $buttons;
    } 

    public function afterAction(Event $event)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $nonSchoolController = ['Directories', 'Profiles'];
        if (in_array($this->controller->name, $nonSchoolController)) {
            $options = [
                'type' => 'staff'
            ];
            $tabElements = $this->controller->getStaffFinanceTabElements($options);
        } else {
            $tabElements = $this->controller->getFinanceTabElements();
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

   
}
