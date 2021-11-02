<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class ConsultationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_health_consultations');
        parent::initialize($config);

        $this->belongsTo('ConsultationTypes', ['className' => 'Health.ConsultationTypes', 'foreignKey' => 'health_consultation_type_id']);
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
    }
    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'health_consultation_type_id','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('health_consultation_type_id', ['type' => 'select', 'after' => 'treatment']);
        $this->field('file_content', ['after' => 'health_consultation_type_id','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
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
            'key'   => 'date',
            'field' => 'date',
            'type'  => 'date',
            'label' => __('Date')
        ];

        $extraField[] = [
            'key'   => 'description',
            'field' => 'description',
            'type'  => 'string',
            'label' => __('Description')
        ];

        $extraField[] = [
            'key'   => 'treatment',
            'field' => 'treatment',
            'type'  => 'string',
            'label' => __('Treatment')
        ];

        $extraField[] = [
            'key'   => 'health_consultation_type_id',
            'field' => 'health_consultation_type_id',
            'type'  => 'string',
            'label' => __('Health Consultation Type')
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
}
