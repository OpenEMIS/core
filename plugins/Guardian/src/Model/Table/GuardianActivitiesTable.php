<?php
namespace Guardian\Model\Table;

use App\Model\Table\AppTable;

class GuardianActivitiesTable extends AppTable {
	public function initialize(array $config) {
        parent::initialize($config);

		$this->belongsTo('Users', 		['className' => 'User.Users', 'foreignKey'=>'guardian_id']);
		$this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
		
        $this->addBehavior('Activity');
    }

}
