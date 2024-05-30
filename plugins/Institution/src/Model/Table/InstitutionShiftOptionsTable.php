<?php
namespace Institution\Model\Table;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class InstitutionShiftOptionsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_shifts');
        parent::initialize($config);
        $this->hasMany('Shifts', ['className' => 'Institution.InstitutionShifts', 'foreignKey' => 'shift_option_id']);
        $this->addBehavior('FieldOption.FieldOption');
        $this->toggle('add', true);
        $this->toggle('remove', true);//POCOR-7393 Case 3rd
    }

}
