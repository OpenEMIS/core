<?php
namespace Indexes\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;


class IndexesCriteriasTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->hasMany('StudentIndexesCriteria', ['className' => 'Indexes.StudentIndexesCriteria']);

        $this->setDeleteStrategy('restrict');
    }
}
