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

class HistoriesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('user_health_histories');
        parent::initialize($config);

        $this->belongsTo('Conditions', ['className' => 'Health.Conditions', 'foreignKey' => 'health_condition_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['HealthHistories' =>
                ['health_condition_id']
            ]
        ]);
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
            $is_manual_exist = $this->getManualUrl('Institutions','Histories','Staff - Health');       
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
            $is_manual_exist = $this->getManualUrl('Institutions','Histories','Students - Health');       
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
            $is_manual_exist = $this->getManualUrl('Directory','Histories','Health');       
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
            $is_manual_exist = $this->getManualUrl('Personal','Histories','Health');       
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
        $this->field('health_condition_id', ['type' => 'select', 'after' => 'comment']);
        $this->field('file_content', ['after' => 'health_condition_id','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $userID = $this->getUserID();
        $this->field('security_user_id', ['after' => 'file_content', 'attr' => ['value' => $userID], 'type' => 'hidden']);
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'current') {
            return __('Current');
        } elseif ($field == 'comment') {
            return __('Comment');
        }elseif ($field == 'health_condition_id') {
            return __('Health Condition');
        }elseif ($field == 'health_condition_id') {
            return __('Health Condition');
        }elseif ($field == 'file_content') {
            return __('Attachment');
        }elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        }elseif ($field == 'created_user_id') {
            return __('Modified By');
        } elseif ($field == 'created') {
            return __('Created On');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-8293
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $userId = $this->getUserID();
        $query->where([ $this->aliasField('security_user_id') => $userId]);
        return $query;
    }
}
