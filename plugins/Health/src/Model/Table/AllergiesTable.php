<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;

class AllergiesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('user_health_allergies');
        parent::initialize($config);

        $this->belongsTo('AllergyTypes', ['className' => 'Health.AllergyTypes', 'foreignKey' => 'health_allergy_type_id']);
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

        $this->addBehavior('ClassExcel', ['excludes' => ['security_group_id'], 'pages' => ['view']]);
    }
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
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

    public function onUpdateFieldSevere(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('severe', ['after' => 'description']);
        $this->field('health_allergy_type_id', ['type' => 'select', 'after' => 'comment']);
        $this->field('file_content', ['after' => 'health_allergy_type_id','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator->allowEmpty('file_content');
        return $validator;
    }


    // cod pocor-6131
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();
        $newFields = [];
        foreach ($cloneFields as $key => $value) {
            $newFields[] = $value;
            if($value['field'] == 'homeroom_teacher'){

                $newFields[] = [
                    'key' => 'InstitutionClasses.total_male_students',
                    'field' => 'total_male_students',
                    'type' => 'string',
                    'label' => 'Total Male Student'
                ];

                $newFields[] = [
                    'key' => 'InstitutionClasses.total_female_students',
                    'field' => 'total_female_students',
                    'type' => 'string',
                    'label' => 'Total Female Student'
                ];
            }

        }
        //print_r($newFields); exit;
        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $extra, Query $query)
    {
        $query
        ->select(['total_male_students' => 'InstitutionClasses.total_male_students','total_female_students' => 'InstitutionClasses.total_female_students']);
    }
    // end POCOR-6131
}
