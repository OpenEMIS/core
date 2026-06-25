<?php
namespace Infrastructure\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFormsTable;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;

class InfrastructureCustomFieldsTable extends CustomFormsTable
{
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_custom_fields');
        parent::initialize($config);
    }
}
