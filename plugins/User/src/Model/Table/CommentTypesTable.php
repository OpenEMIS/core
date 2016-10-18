<?php
namespace User\Model\Table;

use App\Model\Table\ControllerActionTable;

class CommentTypesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('comment_types');
        parent::initialize($config);

        $this->hasMany('Comments', ['className' => 'User.Comments', 'foreignKey' => 'comment_type_id']);
        $this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');

        $this->addBehavior('FieldOption.FieldOption');
    }
}
