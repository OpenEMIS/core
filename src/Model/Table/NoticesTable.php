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
   
    
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        foreach ($data as $key => $value) {
            if (is_string($value) &&  'message' === $key) {
                $data[$key] = htmlspecialchars($value, ENT_QUOTES);
            }
        }
    }
}
