<?php
namespace Institution\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class InstitutionHistoriesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_activities');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey'=>'institution_id']);
        $this->belongsTo('CreatedUser',  ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);

        // $this->addBehavior('Activity');
    }
}
