<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class CommentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('user_comments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('CommentTypes', ['className' => 'User.CommentTypes', 'foreignKey' => 'comment_type_id']);
	}

	public function beforeAction()
	{
		$this->ControllerAction->field('comment_type_id', ['type' => 'select']);
		$this->ControllerAction->setFieldOrder('comment_type_id', 'title', 'comment', 'comment_date');
	}

	public function indexBeforeAction(Event $event)
	{
		$order = 0;
		$this->ControllerAction->setFieldOrder('comment_date', $order++);
		$this->ControllerAction->setFieldOrder('comment_type_id', $order++);
		$this->ControllerAction->setFieldOrder('title', $order++);
		$this->ControllerAction->setFieldOrder('comment', $order++);
	}

	private function setupTabElements() {
		$options = [
			'userRole' => '',
		];

		switch ($this->controller->name) {
			case 'Students':
				$options['userRole'] = 'Students';
				break;
			case 'Staff':
				$options['userRole'] = 'Staff';
				break;
		}

		$tabElements = $this->controller->getUserTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event) {
		$this->setupTabElements();
	}

}