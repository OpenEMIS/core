<?php
namespace User\Model\Table;

use App\Model\Table\ControllerActionTable;

class CommentTypesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('comment_types');
        parent::initialize($config);

        $this->hasMany('Comments', ['className' => 'User.Comments', 'foreignKey' => 'comment_type_id']);
        $this->behaviors()->get('ControllerAction')->setConfig('actions.remove', 'transfer');

        $this->addBehavior('FieldOption.FieldOption');
    }
}
