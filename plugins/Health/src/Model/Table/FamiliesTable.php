<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class FamiliesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('user_health_families');
        parent::initialize($config);

        $this->belongsTo('Relationships', ['className' => 'Health.Relationships', 'foreignKey' => 'health_relationship_id']);
        $this->belongsTo('Conditions', ['className' => 'Health.Conditions', 'foreignKey' => 'health_condition_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        $this->addBehavior('Excel',[
            'excludes' => [],
            'pages' => ['index'],
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        
        // Start POCOR-5188
        if($this->request->getParam('controller') == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Families','Staff - Health');       
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
        }elseif($this->request->getParam('controller') == 'Students'){
            $is_manual_exist = $this->getManualUrl('Institutions','Families','Students - Health');       
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

        }elseif($this->request->getParam('controller') == 'Directories'){ 
            $is_manual_exist = $this->getManualUrl('Directory','Families','Health');       
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

        }elseif($this->request->getParam('controller') == 'Profiles'){ 
            $is_manual_exist = $this->getManualUrl('Personal','Families','Health');       
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

        }
        // End POCOR-5188
    }

    public function onGetCurrent(Event $event, Entity $entity)
    {
        $currentOptions = $this->getSelectOptions('general.yesno');
        return $currentOptions[$entity->current];
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);

        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setupFields($entity);
    }

    // public function onUpdateFieldCurrent(Event $event, array $attr, $action, Request $request)
    public function onUpdateFieldCurrent(Event $event, array $attr, $action)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('current');
        $this->field('health_relationship_id', ['type' => 'select', 'after' => 'comment']);
        $this->field('health_condition_id', ['type' => 'select', 'after' => 'health_relationship_id']);
        $this->field('file_content', ['after' => 'health_condition_id','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->allowEmpty('file_content');
        return $validator;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'current_new',
            'field' => 'current_new',
            'type'  => 'string',
            'label' => __('Current')
        ];

        $extraField[] = [
            'key'   => 'comment',
            'field' => 'comment',
            'type'  => 'string',
            'label' => __('Comment')
        ];

        $extraField[] = [
            'key'   => 'health_relationship_id',
            'field' => 'health_relationship_id',
            'type'  => 'string',
            'label' => __('Health Relationship')
        ];

        $extraField[] = [
            'key'   => 'health_condition_id',
            'field' => 'health_condition_id',
            'type'  => 'string',
            'label' => __('Health Condition')
        ];

        $extraField[] = [
            'key'   => 'file_name',
            'field' => 'file_name',
            'type'  => 'string',
            'label' => __('File Name')
        ];

        $fields->exchangeArray($extraField);
    }

    // POCOR-6131
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query){
    $userID = $this->getUserID();
        $query
        ->select([
            'current_new' => "(CASE WHEN current = 1 THEN 'Yes'
            ELSE 'No' END)"
        ])
        ->where([
            // $this->aliasField('security_user_id = ').$staffUserId
            $this->aliasField('security_user_id') => $userID
        ]);
    }


    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'file_content') {
            return __('Attachment');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $buttons = $this->fixProfileActionButtons($entity, $buttons);
        return $buttons;
    }

    /**
     * @return |null
     */
    private function getUserID()
    {
        $queryString = $this->getQueryString();
        $userId = null;
        if (!$userId && isset($queryString['security_user_id'])) {
            $userId = $queryString['security_user_id'];
        }
        if (!$userId && isset($queryString['user_id'])) {
            $userId = $queryString['user_id'];
        }
        if (!$userId) {
            $userId = $this->request->getSession()->read('Auth.User.id');
        }
        return $userId;
    }


    /**
     * @param Entity $entity
     * @param array $buttons
     * @return array
     */
    private function fixProfileActionButtons(Entity $entity, array $buttons): array
    {
        $userID = $this->getUserID();
        $actions = ['view', 'edit'];
        foreach ($actions as $action) {
            if (isset($buttons[$action])) {
                $url = $buttons[$action]['url'];
                if ($url['plugin'] == 'Profile' && $url['controller'] == 'Profiles' && $url['action'] == 'HealthFamilies') {
                    if (isset($url[2])) {
                        unset($url[2]);
                    }
                    $queryString = $this->getQueryString();
                    $queryString['id'] = $entity->id;
                    $queryString['user_id'] = $userID;
                    $queryString['health_relationship_id'] = $entity->health_relationship_id;
                    $queryString['health_condition_id'] = $entity->health_condition_id;
                    $queryString['security_user_id'] = $userID;
                    $url[1] = $this->paramsEncode($queryString);
                    $buttons[$action]['url'] = $url;
                }
            }
        }
//        die('<pre>' . print_r($entity, true));
//        die('<pre>' . print_r($buttons, true));

        return $buttons;
    }

    public function deleteBeforeAction(Event $event, ArrayObject $extra)
    {
        $url = $this->url('index');
        $userId = $this->getUserID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        $queryString['id'] = $userId;
        $queryString['user_id'] = $userId;
        $url[1] = $this->paramsEncode($queryString);
        $extra['redirect'] = $url;
    }


    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $url = $this->url('index');
        $userId = $this->getUserID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        $queryString['id'] = $userId;
        $queryString['user_id'] = $userId;
        $url[1] = $this->paramsEncode($queryString);
        $extra['redirect'] = $url;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];

        if ($this->action == 'edit') {
            $toolbarButtons = $this->addEditBackButton($toolbarButtons);
        }

        if ($this->action == 'view') {
            $toolbarButtons = $this->addViewBackButton($toolbarButtons);
        }

        $extra['toolbarButtons'] = $toolbarButtons;
    }

    /**
     * @param $toolbarButtons
     * @return mixed
     */
    private function addEditBackButton($toolbarButtons)
    {
        $queryString = $this->getQueryString();
        $queryString = $this->paramsEncode($queryString);
        if ($toolbarButtons->offsetExists('back')) {
            $toolbarButtons['back']['url'][0] = 'view';
            $toolbarButtons['back']['url'][1] = $queryString;
        }
        return $toolbarButtons;
    }

    /**
     * @param $toolbarButtons
     * @return mixed
     */
    private function addViewBackButton($toolbarButtons)
    {
        $userID = $this->getUserID();
        $params = ['user_id' => $userID];
        $queryString = $this->paramsEncode($params);
        if ($toolbarButtons->offsetExists('back')) {
            $toolbarButtons['back']['url'][0] = 'index';
            $toolbarButtons['back']['url'][1] = $queryString;
        }
        return $toolbarButtons;
    }

}
