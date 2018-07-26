<?php
namespace ReportCard\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;


class EmailProcessAttachmentsTable extends ControllerActionTable
{
	public function initialize(array $config)
    {
		parent::initialize($config);

		$this->belongsTo('email_processes', ['className' => 'ReportCard.EmailProcesses', 'foreignKey' => 'email_processes_id']);
    }
}