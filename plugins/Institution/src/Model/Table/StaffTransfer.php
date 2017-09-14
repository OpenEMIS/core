<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

// This file serves as an abstract class for StaffTransferRequests and StaffTransferApprovals

class StaffTransfer extends ControllerActionTable {
    // Type for application
    const PENDING = 0;
    const APPROVED = 1;
    const REJECTED = 2;
    const CLOSED = 3;

    // Type status for transfer / assignment
    const TRANSFER = 2;
    const ASSIGNMENT = 1;

    public function initialize(array $config) {
        $this->table('institution_staff_assignments');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);
        return $validator->requirePresence('previous_institution_id');
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];

        $this->field('status');
        $this->field('previous_institution_id');
        $this->field('institution_id', ['after' => 'previous_institution_id', 'visible' => ['index' => true, 'edit' => true, 'view' => true]]);
        $this->field('staff_id');
        $this->field('type', ['visible' => false]);
        $this->field('staff_type_id', ['after' => 'institution_position_id', 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
        $this->field('FTE', ['type' => 'select', 'options' => $fteOptions, 'after' => 'staff_type_id', 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
        $this->field('comment', ['after' => 'start_date', 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
        $this->field('end_date', ['visible' => false]);
        $this->field('update', ['visible' => false]);
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'Staff', 'index'];
    }

    public function indexBeforeAction(Event $event, $extra) {
        $toolbarButtons = $extra['toolbarButtons'];
        if (isset($toolbarButtons['add'])) {
            unset($toolbarButtons['add']);
        }
    }

    public function editBeforeQuery(Event $event, Query $query, $extra) {
        $query->contain(['Users', 'Institutions', 'PreviousInstitutions', 'Positions']);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $institution_name_with_code = $entity->institution->code . " - " . $entity->institution->name;

        $this->field('status', ['type' => 'readonly']);
        $this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $entity->user->name_with_id]]);
        $this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $entity->previous_institution->name]]);
        $this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $institution_name_with_code]]);
    }

    public function viewAfterAction(Event $event, Entity $entity, $extra) {
        $toolbarButtons = $extra['toolbarButtons'];
        if (in_array($entity->status, [self::APPROVED, self::REJECTED, self::CLOSED])) {
            if (isset($toolbarButtons['edit'])) {
                unset($toolbarButtons['edit']);
            }
            if (isset($toolbarButtons['remove'])) {
                unset($toolbarButtons['remove']);
            }
        }
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
        $statusOptions = [
            self::APPROVED => __('Approved'),
            self::REJECTED => __('Rejected'),
            self::CLOSED => __('Closed'),
            self::PENDING => __('Pending Approval')
        ];

        $attr['options'] = $statusOptions;

        if ($action == 'edit' || $action == 'add') {
            $attr['options'] = $statusOptions;
        }
        return $attr;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if ($entity->status_id != self::PENDING) {
            if (isset($buttons['edit'])) {
                unset($buttons['edit']);
            }
            if (isset($buttons['remove'])) {
                unset($buttons['remove']);
            }
        }
        return $buttons;
    }

    public function onGetStatus(Event $event, Entity $entity) {
        $name = '';
        $statusOptions = [
            self::APPROVED => __('Approved'),
            self::REJECTED => __('Rejected'),
            self::CLOSED => __('Closed'),
            self::PENDING => __('Pending Approval')
        ];
        if (array_key_exists($entity->status, $statusOptions)) {
            $name = $statusOptions[$entity->status];
        }

        $entity->status_id = $entity->status;
        return '<span class="status highlight">' . $name . '</span>';
    }

    public function onGetFTE(Event $event, Entity $entity) {
        $value = '100%';
        if ($entity->FTE < 1) {
            $value = ($entity->FTE * 100) . '%';
        }
        return $value;
    }

    public function getPendingRecords($institutionId = null)
    {
        $count = $this
            ->find()
            ->where([
                $this->aliasField('status') => self::PENDING,
                $this->aliasField('institution_id') => $institutionId,
            ])
            ->count()
        ;

        return $count;
    }
}
