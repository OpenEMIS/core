<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use Institution\Model\Table\InstitutionStaffTransfersTable;

class StaffTransferInTable extends InstitutionStaffTransfersTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Staff' => ['index', 'add']
        ]);

        $this->toggle('add', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('staff_id', 'ruleTransferRequestExists', [
                'rule' => ['checkPendingStaffTransferIn'],
                'on' => 'create'
            ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('FTE', ['type' => 'hidden']);
        $this->field('staff_type_id', ['type' => 'hidden']);
        $this->field('previous_end_date', ['type' => 'hidden']);
        $this->field('comments', ['type' => 'hidden']);
        $this->setFieldOrder(['status_id', 'assignee_id', 'initiated_by', 'staff_id', 'previous_institution_id', 'institution_id', 'start_date', 'institution_position_id']);
    }
}
