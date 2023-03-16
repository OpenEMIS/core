<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use ArrayObject;
use Cake\ORM\TableRegistry;
use App\Model\Traits\OptionsTrait;

class ConfigWebhooksTable extends ControllerActionTable
{
    use OptionsTrait;

    private $eventKeyOptions = [
        'logout' => 'Logout',
        'institutions_create' => 'Institution Create',
        'class_create'  	  => 'Class Create',
        'class_update'    	  => 'Class Update',
        'subject_create'      => 'Subject Create',
        'student_create'      => 'Student Create',
        'student_update'      => 'Student Update',
        'subject_update'      => 'Subject Update',
        'staff_create'    	  => 'Staff Create',
        'staff_update'        => 'Staff Update',
        'institutions_update' => 'Institution Update',
        'institutions_delete' => 'Institutions Delete',
        'programme_create'    => 'Programme Create',
        'programme_update'    => 'Programme Update',
        'programme_delete'    => 'Programme Delete',
        'class_delete'        => 'Class Delete',
        'programme_delete'    => 'Programme Delete',
        'subject_delete'      => 'Subject Delete',
        'programme_delete'    => 'Programme Delete',
        'student_delete'      => 'Student Delete',
        'staff_delete'        => 'Staff Delete',
        'security_user_delete' => 'Delete Security User',
        'academic_period_create' => 'Academic Period Create',
        'academic_period_update' => 'Academic Period Update',
        'academic_period_delete' => 'Academic Period Delete', 
        'education_cycle_create' => 'Education Structure Cycle Create',
        'education_cycle_update' => 'Education Structure Cycle Update',
        'education_cycle_delete' => 'Education Structure Cycle Delete',
        'education_programme_create' => 'Education Programme Create',
        'education_programme_update' => 'Education Programme Update',
        'education_programme_delete' => 'Education Programme Delete',
        'education_grade_create' => 'Education Grade Create',
        'education_grade_update' => 'Education Grade Update',
        'education_grade_delete' => 'Education Grade Delete',
        'education_subject_create' => 'Education Subject Create',
        'education_subject_update' => 'Education Subject Update',
        'education_subject_delete' => 'Education Subject Delete',
        'education_grade_subject_create' => 'Education Grade Subject Create',
        'education_grade_subject_update' => 'Education Grade Subject Update',
        'education_grade_subject_delete' => 'Education Grade Subject Delete',
        'area_education_create' => 'Area Education Create',
        'area_education_update' => 'Area Education Update',
        'area_education_delete' => 'Area Education Delete',
        'education_level_create' => 'Education Structure Level Create',
        'education_level_update' => 'Education Structure Level Update',
        'education_level_delete' => 'Education Structure Level Delete',
        'role_update'           => 'Role Update',
        'education_structure_system_update' => 'Education Structure System Update',
        'role_create'           => 'Role Create',
        'role_delete'           => 'Role Delete',
        'education_structure_system_delete' => 'Education Structure System Delete',
    ];


    public function initialize(array $config)
    {
        $this->table('webhooks');
        parent::initialize($config);
        $this->hasMany('WebhookEvents', ['className' => 'Webhook.WebhookEvents', 'dependent' => true, 'cascadeCallBack' => true, 'saveStrategy' => 'replace', 'foreignKey' => 'webhook_id', 'joinType' => 'INNER']);
        $this->addBehavior('Configuration.ConfigItems');

        foreach ($this->eventKeyOptions as $key => $value) {
            $this->eventKeyOptions[$key] = __($value);
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->requirePresence('url')
            ->add('triggered_event', 'ruleNotEmpty', [
                'rule' => function ($value, $context) {
                    if (empty($value)) {
                        return false;
                    } elseif (isset($value['_ids']) && empty($value['_ids'])) {
                        return false;
                    }
                    return true;
                }
            ])
            ;
        return $validator;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['WebhookEvents']);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Webhooks','System Configurations');       
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

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['WebhookEvents']);
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $this->request->data[$this->alias()]['triggered_event']['_ids'] = [];
        foreach ($entity->webhook_events as $event) {
            $this->request->data[$this->alias()]['triggered_event']['_ids'][] = $event->event_key;
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $supportedMethod = TableRegistry::get('Webhook.Webhooks')->supportedMethod;
        $this->fields['description']['visible']['index'] = false;
        $this->field('name');
        $this->field('url', ['type' => 'string']);
        $this->field('status', ['options' => $this->getSelectOptions('general.active')]);
        $this->field('method', ['options' => $supportedMethod]);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('triggered_event', [
            'type' => 'chosenSelect',
            'options' => $this->eventKeyOptions,
            'before' => 'description',
            'attr' => ['required' => true]
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('triggered_event', [
            'before' => 'description'
        ]);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $data['webhook_events'] = [];
        if (is_array($data['triggered_event']['_ids'])) {
            foreach ($data['triggered_event']['_ids'] as $event) {
                $data['webhook_events'][] = ['event_key' => $event];
            }
        }
        $options['associated'] = [
            'WebhookEvents' => [
                'validate' => false
            ]
        ];
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('triggered_event');
        $this->setFieldOrder(['triggered_event', 'name', 'url', 'status', 'method']);
    }

    public function onGetTriggeredEvent(Event $event, Entity $entity)
    {
        $returnString = '';
        foreach ($entity->webhook_events as $event) {
            $returnString = $returnString . ', ' . __($this->eventKeyOptions[$event->event_key]);
        }
        return ltrim($returnString, ', ');
    }
}
