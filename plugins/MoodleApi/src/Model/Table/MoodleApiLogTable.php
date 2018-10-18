<?php
namespace MoodleApi\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;

class MoodleApiLogTable extends ControllerActionTable
{
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    public function initialize(array $config)
    {
        parent::initialize($config);
    }
}