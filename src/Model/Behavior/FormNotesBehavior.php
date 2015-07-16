<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class FormNotesBehavior extends Behavior {

    public function onGetFormNotesElement(Event $event, $action, $entity, $attr, $options=[]) {
		$fieldLabel = Inflector::humanize($attr['field']);
		if (array_key_exists('label', $attr)) {
			$fieldName = $attr['label'];
		}
		$fieldName = strtolower($attr['model'] . '-' . $attr['field']);
		if (array_key_exists('fieldName', $attr)) {
			$fieldName = $attr['fieldName'];
		}
		if (!array_key_exists('value', $attr)) {
			$attr['value'] = '* Please set the note in your model *';
		}
		$value = '<div class="input text"><label for="'.$fieldName.'">'.$fieldLabel.'</label><div class="button-label" style="width: 65%;">'.$attr['value'].'</div></div>';
		return $value;
    }

}
