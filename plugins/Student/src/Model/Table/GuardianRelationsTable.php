<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;

class GuardianRelationsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        $this->addBehavior('FieldOption.FieldOption');
        $this->table('guardian_relations');
        parent::initialize($config);

        $this->hasMany('StudentGuardians', ['className' => 'Student.StudentGuardians']);

        $this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
    }
}
