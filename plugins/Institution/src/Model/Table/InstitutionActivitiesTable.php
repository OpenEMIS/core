<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionActivitiesTable extends AppTable {
	public function initialize(array $config) {
        parent::initialize($config);

		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey'=>'institution_id']);
		$this->belongsTo('CreatedUser',  ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);

        $this->addBehavior('Activity');
    }
}
