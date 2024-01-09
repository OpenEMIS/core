<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Network\Request;
use Cake\Event\Event;

class InfrastructureCustomFormsFieldsTable extends CustomFormsTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_custom_forms_fields');
        parent::initialize($config);
    }
}
