<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Validation\Validator;

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

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('min', [
                'ruleRange' => [
                    'rule' => ['range', 0, 100]
                ]
            ])
            ->add('max', [
                'ruleCompare' => [
                    'rule' => ['compareValues', 'min'],
                    'last' => true
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 100]
                ]
            ])
            ;
    }

    public function beforeAction(Event $event, arrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
        $this->field('min', ['type' => 'integer', 'after' => 'visible', 'attr'=>['min' => 0, 'max' => 100, 'step' => 1]]);
        $this->field('max', ['type' => 'integer', 'after' => 'min', 'attr'=>['min' => 0, 'max' => 100, 'step' => 1]]);
    }

    public function indexBeforeAction(Event $event, arrayObject $extra)
    {
        $this->field('default', ['visible' => false]);
        $this->field('editable', ['visible' => false]);
        $this->field('international_code', ['visible' => false]);
        $this->field('national_code', ['visible' => false]);
    }
}
