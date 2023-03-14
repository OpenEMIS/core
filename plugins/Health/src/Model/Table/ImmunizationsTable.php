<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class ImmunizationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_health_immunizations');
        parent::initialize($config);

        $this->belongsTo('ImmunizationTypes', ['className' => 'Health.ImmunizationTypes', 'foreignKey' => 'health_immunization_type_id']);
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

    //POCOR-5890 starts remain work
    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'Vaccination Type'], 'type' => 'select', 'before' => 'comment']);
        $this->field('dosage',['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function indexAfterAction(Event $event, $data)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'Vaccination Type'], 'before' => 'comment']);
        $this->field('dosage',['visible' => false]);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'health_immunization_type_id':
                return __('Vaccination Type');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {   
        $this->field('dosage', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function viewBeforeAction(Event $event)
    {
        $this->field('health_immunization_type_id', ['attr'=>['label'=>'Vaccination Type'], 'before' => 'comment']);
        $this->field('dosage', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }
    //POCOR-5890 ends

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator->allowEmpty('file_content');
        return $validator;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'date',
            'field' => 'date',
            'type'  => 'date',
            'label' => __('Date')
        ];

        $extraField[] = [
            'key'   => 'health_immunization_type_id',
            'field' => 'health_immunization_type_id',
            'type'  => 'string',
            'label' => __('Vaccination Type')
        ];

        $extraField[] = [
            'key'   => 'comment',
            'field' => 'comment',
            'type'  => 'string',
            'label' => __('Comment')
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
        ->where([
            // $this->aliasField('security_user_id = ').$staffUserId
            $this->aliasField('security_user_id') => $studentUserId
        ]);
    }

    // Start POCOR-5188
    public function beforeAction(Event $event, ArrayObject $extra)
    {
		$is_manual_exist = $this->getManualUrl('Personal','Vaccinations','Health');       
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
