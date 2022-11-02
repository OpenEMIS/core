<?php
namespace Historical\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class HistoricalStaffPositionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('historical_staff_positions');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);

        $this->toggle('index', false);

        $this->addBehavior('ControllerAction.Image');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        $this->addBehavior('Historical.Historical', [
            'originUrl' => [
                'action' => 'StaffPositions',
                'type' => 'staff'
            ],
            'model' => 'Historical.HistoricalStaffPositions'
        ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        if ($this->controller->name === 'Staff') {
            $this->behaviors()->get('Historical')->config([
                'originUrl' => [
                    'action' => 'Positions',
                    'index'
                ]
            ]);
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content')
            ->add('end_date', 'ruleCompareDateReverse', [
                'rule' => ['compareDateReverse', 'start_date', false]
            ])
            ->add('end_date', 'validDate', [
                'rule' => ['lessThanToday', false]
            ])
            ->add('start_date', 'validDate', [
                'rule' => ['lessThanToday', false]
            ]);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'staff_position_title_id':
                return __('Position');
            case 'fte': 
                return __('FTE');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('start_date');
        $this->field('end_date');
        $this->field('institution_type_id');
        $this->field('institution_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('staff_position_title_id', ['type' => 'select']);
        $this->field('staff_type_id', ['type' => 'select']);
        $this->field('comments');
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('staff_status_id', ['visible' => false]);

        $this->setFieldOrder(['start_date', 'end_date', 'institution_type_id', 'institution_id', 'staff_position_title_id', 'staff_type_id', 'comments', 'file_name', 'file_content']);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'Users' => [
                    'fields' => [
                        'Users.id',
                        'Users.openemis_no',
                        'Users.first_name',
                        'Users.middle_name',
                        'Users.third_name',
                        'Users.last_name',
                        'Users.preferred_name',
                        'Users.photo_name',
                        'Users.photo_content',
                    ]
                ]
            ]);
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'Institutions'
            ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $options = ['type' => 'staff'];
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Positions');

        $this->field('photo', ['type' => 'image']);
        $this->field('openemis_no');
        $this->field('staff_type_id');
        $this->field('staff_status_id');
        $this->field('staff');
        $this->field('staff_position_title_id');
        $this->field('fte');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('institution_id');
        $this->field('comments');
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);

        $this->setFieldOrder(['photo', 'openemis_no', 'staff_type_id', 'staff_status_id', 'staff', 'staff_position_title_id', 'fte', 'start_date', 'end_date', 'institution_id', 'comments', 'file_name', 'file_content']);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onGetStaff(Event $event, Entity $entity)
    {
        return $entity->user->name;
    }

    public function onGetFte(Event $event, Entity $entity)
    {
        return '-';
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        return $entity->institution->code_name;
    }

    public function onGetPhoto(Event $event, Entity $entity)
    {
        $fileContent = $entity->user->photo_content;

        if (empty($fileContent) && is_null($fileContent)) {
            // staff default pic
            $value = "<div class='table-thumb'><div class='profile-image-thumbnail'><i class='kd-staff'></i></div></div>";
        } else {
            $value = base64_encode(stream_get_contents($fileContent));
        }
        return $value;
    }

    public function onUpdateFieldInstitutionTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $TypesTable = TableRegistry::get('Institution.Types');
            $typeOptions = $TypesTable
                ->find('list')
                ->find('visible')
                ->find('order')
                ->toArray();

            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['options'] = $typeOptions;
            $attr['attr']['required'] = true;
            return $attr;
        } elseif ($action == 'edit') {
            $attr['visible'] = false;
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $institutionList = [];
            if (isset($request->data[$this->alias()]) && array_key_exists('institution_type_id', $request->data[$this->alias()]) && !empty($request->data[$this->alias()]['institution_type_id'])) {
                $institutionTypeId = $request->data[$this->alias()]['institution_type_id'];
                $institutionList = $this->Institutions
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'code_name'
                    ])
                    ->where([
                        $this->Institutions->aliasField('institution_type_id') => $institutionTypeId
                    ])
                    ->order([
                        $this->Institutions->aliasField('code') => 'ASC',
                        $this->Institutions->aliasField('name') => 'ASC'
                    ])
                    ->toArray();
            }

            if (empty($institutionList)) {
                $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];
                $attr['type'] = 'select';
                $attr['options'] = $institutionOptions;
                $attr['attr']['required'] = true;
            } else {
                $institutionOptions = ['' => '-- '.__('Select').' --'] + $institutionList;
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                $attr['options'] = $institutionOptions;
                $attr['attr']['required'] = true;
            }
            return $attr;
        } elseif ($action == 'edit') {
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $entity->institution_id;
            $attr['attr']['value'] = $entity->institution->code_name;
            return $attr;
        }
        
    } 
}
