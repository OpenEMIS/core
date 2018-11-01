<?php
namespace MoodleApi\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;

class MoodleApiCreatedUsersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'core_user_id']);
    }
}