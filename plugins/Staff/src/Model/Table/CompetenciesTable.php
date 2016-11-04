<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Query;

use App\Model\Table\ControllerActionTable;

class CompetenciesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('competencies');
        parent::initialize($config);
        $this->hasMany('CompetencySetsCompetencies', ['className' => 'Staff.CompetencySetsCompetencies']);
        $this->hasMany('StaffAppraisalsCompetencies', ['className' => 'Staff.StaffAppraisalsCompetencies']);

        $this->addBehavior('FieldOption.FieldOption');
    }

    public function beforeAction(Event $event, arrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
        $this->field('min', ['type' => 'readonly', 'after' => 'visible', 'attr' => ['value' => $this->fields['min']['default']]]);
        $this->field('max', ['type' => 'readonly', 'after' => 'min', 'attr' => ['value' => $this->fields['max']['default']]]);
    }

    public function indexBeforeAction(Event $event, arrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
    }
}
