<?php
namespace OpenEmis\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class AutocompleteBehavior extends Behavior {
	public function initialize(array $config): void {
		parent::initialize($config);
	}

	public function onGetAutocompleteElement(Event $event, $action, $entity, $attr, $options=[]) {
		$value = '';

		if ($action == 'edit') {
			$subject = $event->getSubject();
			$Form = $subject->Form;
			$url = $subject->Url->build($attr['url']);
			$label = isset($attr['label']) ? $attr['label'] : $attr['field'];
			$target = $attr['target'];

			$subject->includes['autocomplete'] = [
				'include' => true,
				'css' => ['OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.../plugins/autocomplete/js/autocomplete']
			];

			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (isset($attr['fieldName'])) {
				$fieldName = $attr['fieldName'];
			}

			$options['type'] = 'text';

			// POCOR-4062 maxLength auto set by database type value, so extend the maxLength to 30 char by default, unless specified.
			$options['maxlength'] = isset($attr['maxlength']) ? $attr['maxlength'] : 30;
			// end POCOR-4062

			$options['class'] = 'autocomplete';
			$options['autocomplete-url'] = $url;
			// text to show for no results
			if (isset($attr['noResults'])) {
				$options['autocomplete-no-results'] = $attr['noResults'];
			}
			// action for no results
			if (isset($attr['onNoResults'])) {
				$options['autocomplete-on-no-results'] = $attr['onNoResults'];
			}
			// action before search happens
			if (isset($attr['onBeforeSearch'])) {
				$options['autocomplete-before-search'] = $attr['onBeforeSearch'];
			}
			// action when selected
			if (isset($attr['onSelect'])) {
				$options['autocomplete-submit'] = $attr['onSelect'];
			}
			$options['autocomplete-class'] = 'error-message';
			$options['autocomplete-target'] = $target['key'];

			$value .= $Form->input($fieldName, $options);
			$value .= $Form->hidden($target['name'], ['autocomplete-value' => $target['key']]);
			$Form->unlockField($target['name']);
		}
		return $value;
	}
}
