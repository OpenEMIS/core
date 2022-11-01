<?php
namespace Email\Model\Table;

use App\Model\Table\AppTable;

class EmailProcessAttachmentsTable extends AppTable
{
	public function initialize(array $config)
    {
		parent::initialize($config);

		$this->belongsTo('EmailProcesses', ['className' => 'Email.EmailProcesses', 'foreignKey' => 'email_process_id']);
    }
}
