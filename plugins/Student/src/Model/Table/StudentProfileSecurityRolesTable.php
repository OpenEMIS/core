<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use App\Model\Table\ControllerActionTable;

class StudentProfileSecurityRolesTable extends ControllerActionTable
{
	public function initialize(array $config): void {
		parent::initialize($config);
	}

}
