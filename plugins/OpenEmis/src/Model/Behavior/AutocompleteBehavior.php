<?php
namespace OpenEmis\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class AutocompleteBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function onGetAutocompleteElement(Event $event, $action, $entity, $attr, $options=[]) {
		$value = '';
		// pr($attr);
		if ($action == 'edit') {
			$subject = $event->subject();
			$Form = $subject->Form;
			$url = $subject->Url->build($attr['url']);
			$label = isset($attr['label']) ? $attr['label'] : $attr['field'];
			$target = $attr['target'];

			$subject->includes['autocomplete'] = [
				'include' => true, 
				'css' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/js/autocomplete']
			];

			$fieldName = $attr['model'] . '.' . $attr['field'];
			if (array_key_exists('fieldName', $attr)) {
				$fieldName = $attr['fieldName'];
			}
			
			$options['type'] = 'text';
			$options['class'] = 'autocomplete';
			$options['autocomplete-url'] = $url;
			// text to show for no results
			if (array_key_exists('noResults', $attr)) {
				$options['autocomplete-no-results'] = $attr['noResults'];
			}
			// action for no results
			if (array_key_exists('onNoResults', $attr)) {
				$options['autocomplete-on-no-results'] = $attr['onNoResults'];
			}
			// action before search happens
			if (array_key_exists('onBeforeSearch', $attr)) {
				$options['autocomplete-before-search'] = $attr['onBeforeSearch'];
			}
			$options['autocomplete-class'] = 'error-message';
			$options['autocomplete-target'] = $target['key'];

			$value .= $Form->input($fieldName, $options);
			$value .= $Form->hidden($target['name'], ['autocomplete-value' => $target['key']]);
		}
		return $value;
	}
}
