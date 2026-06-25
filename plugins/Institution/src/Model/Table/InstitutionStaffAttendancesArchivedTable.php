<?php

namespace Institution\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;
use Cake\Log\Log;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ResultSetInterface;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Utility\Hash;


class InstitutionStaffAttendancesArchivedTable extends ControllerActionTable
{


    public function initialize(array $config): void
    {
        $this->setTable('institution_staff_attendances_archived');
        parent::initialize($config);

    }


}
