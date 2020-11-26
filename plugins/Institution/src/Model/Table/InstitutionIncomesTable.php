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

class InstitutionIncomesTable extends ControllerActionTable
{
    use MessagesTrait;

    public function initialize(array $config)
    {
        $this->table('institution_incomes');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('IncomeSources', ['className' => 'FieldOption.IncomeSources', 'foreignKey' => 'income_source_id']);
        $this->belongsTo('IncomeTypes', ['className' => 'FieldOption.IncomeTypes', 'foreignKey' => 'income_type_id']);
    }

    public function beforeAction($event) {
        $this->field('academic_period_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('income_source_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('income_type_id', ['type' => 'select', 'visible' => ['index'=>true, 'view'=>true, 'edit'=>true]]);
        $this->field('attachment', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setFieldOrder(['academic_period_id', 'income_source_id', 'income_type_id', 'amount', 'attachment', 'description']);
    }

	public function beforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$entity->institution_id = $this->request->session()->read('Institution.Institutions.id');
    }
	
    public function indexBeforeAction($event) { 
        unset($this->fields['academic_period_id']);
        unset($this->fields['description']);
        $this->setFieldOrder(['date', 'income_source_id', 'income_type_id', 'amount']);
    }

    public function viewBeforeAction($event) { 
        unset($this->fields['attachment']);
        unset($this->fields['description']);
        $this->setFieldOrder(['date', 'income_source_id', 'income_type_id', 'amount']);
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'income_source_id') {
            return __('Source');
        } else if ($field == 'income_type_id') {
            return  __('Type');
        } else if ($field == 'amount' && $this->action == 'index') {
            return  __('Amount (PM)');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
