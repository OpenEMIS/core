<?php
namespace Page\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\RulesChecker;
use Page\Model\Rule\RestrictDelete;

class RestrictDeleteBehavior extends Behavior
{
    protected $_defaultConfig = [
        'excludedModels' => ['ModifiedUsers', 'CreatedUsers'],
        'dependent' => [true, false],
        'message' => 'Delete operation is not allowed as there are other information linked to this record.'
    ];


    public function buildRules(Event $event, RulesChecker $rules)
    {
        $rules->addDelete(new RestrictDelete('associated_records', ['excludedModels' => $this->config('excludedModels'), 'dependent' => $this->config('dependent')]), 'restrictDelete', [
            'errorField' => 'associated_records',
            'message' => __($this->config('message'))
        ]);
    }
}
