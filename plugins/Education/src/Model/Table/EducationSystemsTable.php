<?php
namespace Education\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class EducationSystemsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('EducationLevels', ['className' => 'Education.EducationLevels']);
        $this->setDeleteStrategy('restrict');
    }
}
