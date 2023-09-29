<?php
namespace FieldOption\Model\Table;
/* POCOR-7376 */
use App\Model\Table\ControllerActionTable;

class IndustriesTable extends ControllerActionTable {

	public function initialize(array $config): void
    {
        $this->setTable('industries');
        parent::initialize($config);
    
        $this->addBehavior('FieldOption.FieldOption');
    }
}
?>