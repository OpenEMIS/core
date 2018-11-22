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
    public function initialize(array $config)
    {
        $this->table('historial_staff_positions');
        parent::initialize($config);

        $this->toggle('index', false);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        $historialUrl = [
            'plugin' => 'Directory',
            'controller' => 'Directories',
            'action' => 'StaffPositions',
            'type' => 'staff'
        ];
        $toolbarButtonsArray['back']['url'] = $historialUrl;

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }
}
