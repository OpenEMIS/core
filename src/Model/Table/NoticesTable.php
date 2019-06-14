<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use ArrayObject;

class NoticesTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
    }
    
}
