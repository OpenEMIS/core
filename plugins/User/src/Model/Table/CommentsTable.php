<?php
namespace User\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class CommentsTable extends ControllerActionTable
{
	public function initialize(array $config) {
		$this->table('user_comments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('CommentTypes', ['className' => 'User.CommentTypes', 'foreignKey' => 'comment_type_id']);
	}

	public function beforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('comment_type_id', ['type' => 'select', 'sort' => ['field' => 'CommentTypes.name']]);
		$this->setFieldOrder('comment_type_id', 'title', 'comment', 'comment_date');
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->setFieldOrder('comment_date', 'comment_type_id', 'title', 'comment');
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		if (!empty($this->request->query['sort']) && ($this->request->query['sort'] == $this->fields['comment_type_id']['sort']['field'])) {
			$sortList = [
				$this->fields['comment_type_id']['sort']['field']
			];
			if (array_key_exists('sortWhitelist', $extra['options'])) {
				$sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
			}
			$extra['options']['sortWhitelist'] = $sortList;
		}
	}

	private function setupTabElements()
	{
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

	public function afterAction(Event $event)
	{
		$this->setupTabElements();
	}

}