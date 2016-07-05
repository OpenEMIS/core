<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;

class CommentTypesTable extends AppTable {
    public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        parent::initialize($config);
        $this->hasMany('Comments', ['className' => 'User.Comment', 'foreignKey' => 'comment_type_id']);
    }
}
