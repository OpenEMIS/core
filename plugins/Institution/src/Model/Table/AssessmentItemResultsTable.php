<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class AssessmentItemResultsTable extends ControllerActionTable {
	use OptionsTrait;
    
    public function initialize(array $config)
    {
        $this->table('assessment_item_results');
        parent::initialize($config);

        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportAssessmentItemResults']);

        // register the target table once
        $this->Institutions = TableRegistry::get('Institution.Institutions');
    }
}