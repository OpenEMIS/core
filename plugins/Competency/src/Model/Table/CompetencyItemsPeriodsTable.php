<?php
namespace Competency\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;

use App\Model\Table\AppTable;

class CompetencyItemsPeriodsTable extends AppTable {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('Items',       ['className' => 'Competency.CompetencyItems', 'dependent' => true, 'foreignKey' => ['competency_item_id', 'academic_period_id', 'competency_template_id'], 'bindingKey' => ['id', 'academic_period_id', 'competency_template_id']]);
        $this->belongsTo('Periods',     ['className' => 'Competency.CompetencyPeriods', 'dependent' => true, 'foreignKey' => ['competency_period_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->addBehavior('CompositeKey');
    }
}