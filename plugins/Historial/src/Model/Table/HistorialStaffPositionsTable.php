<?php
namespace Historial\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class HistorialStaffPositionsTable extends ControllerActionTable
{
    const ORIGIN = [
        'plugin' => 'Directory',
        'controller' => 'Directories',
        'action' => 'StaffPositions',
        'type' => 'staff'
    ];

    public function initialize(array $config)
    {
        $this->table('historial_staff_positions');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);

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
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content');
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'institution_name':
                return __('Institution');
            case 'institution_position_name':
                return __('Position');
            case 'fte': 
                return __('FTE');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->updateBackButton($extra);

        // For afterSave redirection
        $extra['redirect'] = self::ORIGIN;
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('start_date');
        $this->field('end_date');
        $this->field('institution_name');
        $this->field('institution_position_name');
        $this->field('staff_type_id', ['type' => 'select']);
        $this->field('comments');
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('staff_status_id', ['visible' => false]);

        $this->setFieldOrder(['start_date', 'end_date', 'institution_name', 'institution_position_name', 'staff_type_id', 'comments', 'file_name', 'file_content']);
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

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->updateBackButton($extra);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $options = ['type' => 'staff'];
        $tabElements = $this->controller->getCareerTabElements($options);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Positions');

        $this->field('photo_content', ['type' => 'image']);
        $this->field('openemis_no');
        $this->field('staff_type_id');
        $this->field('staff_status_id');
        $this->field('staff');
        $this->field('institution_position_name');
        $this->field('fte');
        $this->field('start_date');
        $this->field('end_date');
        $this->field('institution_name');
        $this->field('comments');
        $this->field('file_name', ['type' => 'hidden', 'visible' => ['view' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);

        $this->setFieldOrder(['photo_content','openemis_no','staff_type_id','staff_status_id','staff','institution_position_name','fte','start_date','end_date','institution_name','comments','file_name','file_content']);
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

    private function updateBackButton(ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarButtonsArray['back']['url'] = self::ORIGIN;
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }
}
