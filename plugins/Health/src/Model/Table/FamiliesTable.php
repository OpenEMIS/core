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

    public function initialize(array $config)
    {
        $this->table('user_health_families');
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
        if($this->request->params['controller'] == 'Staff'){
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
        }elseif($this->request->params['controller'] == 'Students'){
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

    public function onUpdateFieldCurrent(Event $event, array $attr, $action, Request $request)
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

    public function validationDefault(Validator $validator)
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
        $session = $this->request->session();
        // $staffUserId = $session->read('Institution.StaffUser.primaryKey.id');
        $studentUserId = $session->read('Student.Students.id');

        $query
        ->select([
            'current_new' => "(CASE WHEN current = 1 THEN 'Yes'
            ELSE 'No' END)"
        ])
        ->where([
            // $this->aliasField('security_user_id = ').$staffUserId
            $this->aliasField('security_user_id') => $studentUserId
        ]);
    }
}
