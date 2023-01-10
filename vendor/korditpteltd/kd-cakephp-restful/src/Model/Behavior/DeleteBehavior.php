<?php
namespace Restful\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use Cake\ORM\Entity;

class DeleteBehavior extends Behavior {
    public function implementedEvents()
    {
        $eventKey = 'Restful.Model.';
        $eventMap = [
            'Restful.CRUD.delete.beforeAction' => 'deleteBeforeAction',
            'Restful.Model.onBeforeProcessQueryString' => 'onBeforeProcessQueryString',
            'Restful.Model.onSetupFieldAttributes' => ['callable' => 'onSetupFieldAttributes', 'priority' => '11']
        ];

        $events = parent::implementedEvents();

        foreach ($eventMap as $event => $method) {
        	if (is_array($method) && isset($method['callable'])) {
        		if (!method_exists($this, $method['callable'])) {
                	continue;
            	}
        	} else if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

    public function onBeforeProcessQueryString(Event $event, $requestQueries, Query $query = null, ArrayObject $extra)
    {
    	$action = $extra['action'];
    	$model = $this->_table;
    	if ($action == 'delete') {
    		$displayField = $model->displayField();
	    	$event = $model->dispatchEvent('Restful.CRUD.delete.onChangeDisplayField', [$displayField, $extra], $model);
	    	if ($event->result) {
	    		$displayField = $event->result;
	    	}

	    	$extra['displayField'] = $displayField;
    	}
    }

    public function deleteBeforeAction(Event $event, ArrayObject $columns, ArrayObject $extra)
    {
    	$model = $this->_table;
    	$extra['contain'] = [];
    	$primaryKey = $model->primaryKey();
    	if (!is_array($primaryKey)) {
    		$primaryKey = [$primaryKey];
    	}
    	$primaryKey[] = $extra['displayField'];
    	$columns->exchangeArray($primaryKey);
    }

    public function onSetupFieldAttributes(Event $event, $col, ArrayObject $attr, ArrayObject $extra)
    {
    	$action = $extra['action'];
    	$model = $this->_table;
    	if ($action == 'delete') {
    		if ($col == $extra['displayField']) {
    			$attr['label'] = __('To Be Deleted');
    			$attr['readonly'] = true;
    			$event = $model->dispatchEvent('Restful.CRUD.delete.onChangeDisplayFieldAttribute', [$col, $attr, $extra], $model);
    		}
    	}
    }


   /**
     * Event to control the restrict deletion of an entity
     *
     * @return Entity with errors if it does not meet the delete requirement. If no errors, nothing is returned.
     */
    public function beforeDelete(Event $event, Entity $entity)
    {

    }
 }
