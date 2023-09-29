<?php
namespace User\Controller\Component;

use Cake\Controller\Component;
use Page\Model\Entity\PageElement;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class UserComponent extends Component
{
    private $defaultStudentProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-students'></i></div></div>";
    private $defaultStaffProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-staff'></i></div></div>";
    private $defaultGuardianProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-guardian'></i></div></div>";
    private $defaultUserProfileIndex = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='fa fa-user'></i></div></div>";

    private $controller = null;

    public $components = ['Area.Areapicker'];
    
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->controller = $this->_registry->getController();
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderPhotoContent'] = 'onRenderPhotoContent';
        $event['Controller.Page.onRenderAddressAreaId'] = 'onRenderAddressAreaId';
        $event['Controller.Page.onRenderBirthplaceAreaId'] = 'onRenderBirthplaceAreaId';

        return $event;
    }

    public function beforeFilter(Event $event)
    {   
        $request = $this->request;
        $action = $request->action;

        $this->controller->Page->exclude(['username', 'password', 'last_login', 'is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'date_of_death', 'external_reference', 'status', 'preferred_language']);

        if($action == 'index') {
            $this->renderIndex();
        } else if ($action == 'view') {
            $this->renderView();
       
        }
    }

    public function renderIndex() 
    {
        $this->controller->Page->exclude(['first_name', 'middle_name', 'third_name', 'last_name', 'preferred_name', 'email','address', 'postal_code', 'address_area_id', 'birthplace_area_id', 'nationality_id', 'photo_content']);
    }

    public function renderView() 
    {
        $this->controller->Page->get('photo_content')
                        ->setAttributes('fileNameField', 'photo_name')
                        ->setAttributes('type', 'image');
                
        $this->controller->Page->addNew('information')
                        ->setControlType('section');
        $this->controller->Page->addNew('location')
                        ->setControlType('section');
        $this->controller->Page->addNew('address_area')
                        ->setControlType('section');
        $this->controller->Page->addNew('birthplace_area')
                        ->setControlType('section');
        $this->controller->Page->addNew('other_information')
                        ->setControlType('section');


        $this->controller->Page->move('information')->first();            
        $this->controller->Page->move('photo_content')->after('information');
        $this->controller->Page->move('email')->after('identity_number');
        $this->controller->Page->move('location')->after('email'); 
        $this->controller->Page->move('address')->after('location'); 
        $this->controller->Page->move('postal_code')->after('address'); 
       
        $this->controller->Page->move('address_area')->after('postal_code');
        $this->controller->Page->move('address_area_id')->after('address_area');
        $this->controller->Page->move('birthplace_area')->after('address_area_id');
        $this->controller->Page->move('birthplace_area_id')->after('birthplace_area');
        $this->controller->Page->move('other_information')->after('birthplace_area_id');
    }

    public function onRenderAddressAreaId(Event $event, Entity $entity, PageElement $element)
    {
        $params = [
            'targetModel' => 'Area.AreaAdministratives',
            'areaKey' => $element->getKey()
        ];
        $this->Areapicker->renderAreaId($entity, $params);
    }

    public function onRenderBirthplaceAreaId(Event $event, Entity $entity, PageElement $element)
    {
        $params = [
            'targetModel' => 'Area.AreaAdministratives',
            'areaKey' => $element->getKey()
        ];
        $this->Areapicker->renderAreaId($entity, $params);
    }

    public function onRenderPhotoContent(Event $event, Entity $entity, PageElement $element)
    { 
        $fileContent = $entity->photo_content;
        $userEntity = $entity;
        
        if (empty($fileContent) && is_null($fileContent)) {
            
            $element->setControlType('string');

            if (($userEntity) && $userEntity->is_student) {
                 $value = $this->defaultStudentProfileIndex;
            } elseif (($userEntity) && $userEntity->is_staff) {
                $value = $this->defaultStaffProfileIndex;
            } elseif (($userEntity) && $userEntity->is_guardian) {
                 $value = $this->defaultGuardianProfileIndex;
            } else {
                   $value = $this->defaultUserProfileIndex;
            }
        } 
        return $value;
    }
}
