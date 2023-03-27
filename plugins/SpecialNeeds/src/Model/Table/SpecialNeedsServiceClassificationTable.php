<?php
namespace SpecialNeeds\Model\Table;

use App\Model\Table\ControllerActionTable;

/**
 * Class for classification in special need > Services > classification dropdown
 * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
 * @ticket POCOR-6873
 */

class SpecialNeedsServiceClassificationTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->addBehavior('FieldOption.FieldOption');
    }
    // Start POCOR-7286
    public function beforeAction() {
        $this->field('name', ['length' => 75]);
    }
    // End POCOR-7286
}
