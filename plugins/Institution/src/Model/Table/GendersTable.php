<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class GendersTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_genders');
        parent::initialize($config);

        $this->hasMany('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_gender_id']);
	}
}
