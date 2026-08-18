<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class FamiliesTable extends ControllerActionTable
{
    use OptionsTrait;
    use HealthLookupTrait; //POCOR-9718

    public function initialize(array $config): void
    {
        $this->setTable('user_health_families');
        parent::initialize($config);

        $this->belongsTo('Relationships', ['className' => 'Health.Relationships', 'foreignKey' => 'health_relationship_id']);
        $this->belongsTo('Conditions', ['className' => 'Health.Conditions', 'foreignKey' => 'health_condition_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['HealthFamilies' =>
                ['health_relationship_id',
                    'health_condition_id']
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

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
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

    public function onGetCurrent(EventInterface $event, Entity $entity)
    {
        $currentOptions = $this->getSelectOptions('general.yesno');
        return $currentOptions[$entity->current];
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);

        $this->setupFields($entity);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setupFields($entity);
    }

    // public function onUpdateFieldCurrent(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldCurrent(EventInterface $event, array $attr, $action)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    //POCOR-9718: populate Health Family selects from their lookup tables.
    public function onUpdateFieldHealthRelationshipId(EventInterface $event, array $attr, $action)
    {
        return $this->populateLookupSelect($attr, $action, 'Health.Relationships');
    }

    public function onUpdateFieldHealthConditionId(EventInterface $event, array $attr, $action)
    {
        return $this->populateLookupSelect($attr, $action, 'Health.Conditions');
    }

    private function setupFields(Entity $entity)
    {
       $this->field('health_condition_id', ['type' => 'select', 'attr' => ['required' => true]]); //POCOR-9507 
        $this->field('health_relationship_id', ['type' => 'select', 'before' => 'current', 'attr' => ['required' => true]]); //POCOR-9507
        

        $this->field('current');
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $userID = $this->getUserID();
        $this->field('security_user_id', ['after' => 'file_content', 'attr' => ['value' => $userID], 'type' => 'hidden']);
        $this->field('current', ['type' => 'select', 'after' => 'health_relationship_id', 'attr' => ['required' => true]]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->allowEmpty('file_content')
            ->notEmpty('health_relationship_id')
            ->notEmpty('health_condition_id')
            ->notEmpty('current');
        return $validator;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
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
            'label' => __('Relationship') //POCOR-9507
        ];

        $extraField[] = [
            'key'   => 'health_condition_id',
            'field' => 'health_condition_id',
            'type'  => 'string',
            'label' => __('Condition') //POCOR-9507
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
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query){
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


    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'file_content') {
            return __('Attachment');
        } elseif ($field == 'health_relationship_id') {
            return __('Relationship'); //POCOR-9507
        } elseif ($field == 'health_condition_id') {
            return __('Condition'); //POCOR-9507
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-8293
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra) {
        $userId = $this->getUserID();
        $query->where([ $this->aliasField('security_user_id') => $userId]);
        return $query;
    }

    //POCOR-9507
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $file = $this->request->getData('Families.file_content');

        if (!empty($file) && is_object($file) && method_exists($file, 'getClientFilename')) {

            $filename = $file->getClientFilename();
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($extension, ['exe', 'zip','mov'])) {
                $entity->setError(
                    'file_content',
                    __('This file is not allowed.')
                );
                $event->stopPropagation();
                return false;
            }
        }
    }

}
