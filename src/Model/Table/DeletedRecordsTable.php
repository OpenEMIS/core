<?php
namespace App\Model\Table;

class DeletedRecordsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'created_user_id']);
    }
}
