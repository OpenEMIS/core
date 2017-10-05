<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use Institution\Model\Table\InstitutionStaffTransfersTable;

class StaffTransferOutTable extends InstitutionStaffTransfersTable
{
    // Transfer Type
    const FULL_TRANSFER = 1;
    const PARTIAL_TRANSFER = 2;

    public function initialize(array $config)
    {
        parent::initialize($config);
    }
}
