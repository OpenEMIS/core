<?php
namespace App\Model\Table;

// use ArrayObject;
// use Cake\I18n\Time;
// use Cake\ORM\Table;
// use Cake\ORM\Query;
// use Cake\ORM\Entity;
// use Cake\ORM\TableRegistry;
// use Cake\Event\Event;
// use Cake\Network\Request;
// use Cake\Utility\Inflector;
// use Cake\Validation\Validator;
// use App\Model\Traits\OptionsTrait;

class DeletedRecordsTable extends AppTable {
    public function initialize(array $config) 
    {
        parent::initialize($config);
        // $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'created_user_id']);
    }
}
