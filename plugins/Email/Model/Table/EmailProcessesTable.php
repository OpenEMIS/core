<?php
namespace Email\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\EntityInterface;

class EmailProcessesTable extends ControllerActionTable
{
	public function initialize(array $config)
    {
		parent::initialize($config);
		$this->hasMany('EmailProcessAttachments', ['className' => 'Email.EmailProcessAttachments']);
    }
}