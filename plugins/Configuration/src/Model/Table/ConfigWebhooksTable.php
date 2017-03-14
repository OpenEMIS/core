<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Network\Session;
use Cake\ORM\Entity;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;
use ArrayObject;

class ConfigWebhooksTable extends ControllerActionTable {

	public function initialize(array $config) {
		$this->table('webhooks');
		parent::initialize($config);
        $this->hasMany('WebhookEvents', ['className' => 'Webhook.WebhookEvents', 'dependent' => true, 'cascadeCallBack' => true, 'saveStrategy' => 'replace']);
        $this->addBehavior('Configuration.ConfigItems');
	}

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->requirePresence('url')
            ->requirePresence('event_key')
            ->notEmpty('event_key')
            ->add('url', 'invalidUrl', [
                'rule' => ['url', true]
            ])
            ;
        return $validator;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('url', ['type' => 'string']);
        $this->field('file_name', ['visible' => false]);
        $this->fields['deletable']['visible'] = false;
    }
}
