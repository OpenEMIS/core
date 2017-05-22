<?php
namespace Infrastructure\Model\Behavior;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class PagesBehavior extends Behavior
{
    private $modules = ['Land', 'Building', 'Floor', 'Room'];

    protected $_defaultConfig = [
        'module' => null
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function getModules()
    {
        return $this->modules;
    }
}
