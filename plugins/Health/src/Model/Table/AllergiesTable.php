<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Http\ServerRequest;
use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;

class AllergiesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('user_health_allergies');
        parent::initialize($config);

        $this->belongsTo('AllergyTypes', ['className' => 'Health.AllergyTypes', 'foreignKey' => 'health_allergy_type_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
      //  $this->addBehavior('User.UserTab');
        $this->addBehavior('Institution.InstitutionTab',  [
            'appliedAction' => ['Allergies' =>['id']
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
            $is_manual_exist = $this->getManualUrl('Institutions','Allergies','Staff - Health');
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
            $is_manual_exist = $this->getManualUrl('Institutions','Allergies','Students - Health');
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
            $is_manual_exist = $this->getManualUrl('Directory','Allergies','Health');
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
            if ($extra->offsetExists('toolbarButtons') && $extra['toolbarButtons']['add']) {
                unset($extra['toolbarButtons']['add']);
            }
            $is_manual_exist = $this->getManualUrl('Personal','Allergies','Health');
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

    public function onGetSevere(Event $event, Entity $entity)
    {
        $severeOptions = $this->getSelectOptions('general.yesno');
        return $severeOptions[$entity->severe];
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

    // public function onUpdateFieldSevere(Event $event, array $attr, $action, Request $request)
    public function onUpdateFieldSevere(Event $event, array $attr, $action)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('severe', ['after' => 'description']);
        $this->field('health_allergy_type_id', ['type' => 'select', 'after' => 'comment']);
        $this->field('file_content', ['after' => 'health_allergy_type_id','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
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
            'key'   => 'description',
            'field' => 'description',
            'type'  => 'string',
            'label' => __('Description')
        ];

        $extraField[] = [
            'key'   => 'severe_new',
            'field' => 'severe_new',
            'type'  => 'integer',
            'label' => __('Severe')
        ];

        $extraField[] = [
            'key'   => 'comment',
            'field' => 'comment',
            'type'  => 'string',
            'label' => __('Comment')
        ];

        $extraField[] = [
            'key'   => 'health_allergy_type_id',
            'field' => 'health_allergy_type_id',
            'type'  => 'string',
            'label' => __('Health Allergy Type')
        ];

        $extraField[] = [
            'key'   => 'file_name',
            'field' => 'file_name',
            'type'  => 'string',
            'label' => __('File Name')
        ];

        $fields->exchangeArray($extraField);
    }

    //POCOR-6131
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query){
        $userId = $this->getUserID();
        $query
        ->select([
            'severe_new' => "(CASE WHEN severe = 1 THEN 'Yes'
            ELSE 'No' END)"
        ])
        ->where([
            // $this->aliasField('security_user_id = ').$staffUserId
            $this->aliasField('security_user_id') => $userId
        ]);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'file_content') {
            return __('Attachment');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-8293s
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $userId = $this->getUserID();
        $query->where([ $this->aliasField('security_user_id') => $userId]);
        return $query;
    }



}
