<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class HealthsTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('user_healths');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        // $this->addBehavior('ClassExcel', ['excludes' => ['security_group_id'], 'pages' => ['view']]);
        
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
            'excludes' => ['security_user_id'],
            'pages' => ['index','view'],
        ]);
    }

    public function onGetBloodType(Event $event, Entity $entity)
    {
        $bloodTypeOptions = $this->getSelectOptions('Health.blood_types');
        return $bloodTypeOptions[$entity->blood_type];
    }

    public function onGetHealthInsurance(Event $event, Entity $entity)
    {
        $healthInsuranceOptions = $this->getSelectOptions('general.yesno');
        return $healthInsuranceOptions[$entity->health_insurance];
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        // always redirect to view page if got record
        if ($data->count() == 1) {
            $entity = $data->first();
            $action = $this->url('view');
            $action[1] = $this->paramsEncode(['id' => $entity->id]);
            $event->stopPropagation();
            return $this->controller->redirect($action);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setupFields($entity);

        // Remove back toolbarButton from directory>health>overview (POCOR-3358)
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        unset($toolbarButtonsArray['back']);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end POCOR-3358
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setupFields($entity);
    }

    public function onUpdateFieldBloodType(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('Health.blood_types');
        return $attr;
    }

    public function onUpdateFieldHealthInsurance(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('blood_type');
        $this->field('health_insurance', ['after' => 'medical_facility']);
        $this->field('file_content', ['after' => 'health_insurance','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
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
            'key'   => 'blood_type',
            'field' => 'blood_type',
            'type'  => 'string',
            'label' => __('Blood Type')
        ];

        $extraField[] = [
            'key'   => 'doctor_name',
            'field' => 'doctor_name',
            'type'  => 'string',
            'label' => __('Doctor Name')
        ];

        $extraField[] = [
            'key'   => 'doctor_contact',
            'field' => 'doctor_contact',
            'type'  => 'string',
            'label' => __('Doctor Contact')
        ];

        $extraField[] = [
            'key'   => 'medical_facility',
            'field' => 'medical_facility',
            'type'  => 'string',
            'label' => __('Medical Facility')
        ];

        $extraField[] = [
            'key'   => 'health_insurance_new',
            'field' => 'health_insurance_new',
            'type'  => 'integer',
            'label' => __('Health Insurance')
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
        $session = $this->request->session();
        // $staffUserId = $session->read('Institution.StaffUser.primaryKey.id');
        $studentUserId = $session->read('Student.Students.id');

        $query
        ->select([
            'health_insurance_new' => "(CASE WHEN health_insurance = 1 THEN 'Yes'
            ELSE 'No' END)"
        ])
        ->where([
            // $this->aliasField('security_user_id = ').$staffUserId
            $this->aliasField('security_user_id') => $studentUserId
        ]);
    }
}
