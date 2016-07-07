<?php
namespace User\Model\Table;

use App\Model\Table\ControllerActionTable;

class CommentTypesTable extends ControllerActionTable {
    public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        $this->table('comment_types');
        parent::initialize($config);
        $this->hasMany('Comments', ['className' => 'User.Comment', 'foreignKey' => 'comment_type_id']);
    }
}
