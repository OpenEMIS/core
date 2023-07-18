<?php
namespace Textbook\Model\Table;

use App\Model\Table\ControllerActionTable;

// POCOR-7362

class TextbookDimensionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        
        parent::initialize($config);
        $this->table('textbook_dimensions');

        $this->addBehavior('FieldOption.FieldOption');
        
    }
}
