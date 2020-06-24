<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;

use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;

class InstitutionBudgetsTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        $this->table('institution_budgets');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('BudgetTypes', ['className' => 'FieldOption.BudgetTypes', 'foreignKey' => 'budget_type_id']);
    }

    public function beforeAction($event) {
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('budget_type_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('attachment', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'budget_type_id', 'amount', 'attachment', 'description']);
    }
}
