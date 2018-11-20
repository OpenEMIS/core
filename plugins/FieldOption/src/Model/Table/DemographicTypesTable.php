<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use ArrayObject;

class DemographicTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('demographic_types');
        parent::initialize($config);

        $this->hasMany('UserDemographics', ['className' => 'Student.StudentDemographics']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->setDeleteStrategy('restrict');

        $this->fields['name']['required'] = true;

        $this->toggle('remove', false);
        $this->toggle('add', false);
    }

    public function afterAction(Event $event, ArrayObject $extra) 
    {
        $this->field('description', [
            'after' => 'name',
        ]);
    }
}
