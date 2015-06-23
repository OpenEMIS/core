<?php
namespace ControllerAction\Model\Traits;

use Cake\Utility\Inflector;

trait UtilityTrait {
	public function endsWith($haystack, $needle) {
		return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
	}

	/**
	 * Converts the class alias to a label.
	 * 
	 * Usefull for class names or aliases that are more than a word.
	 * Converts the camelized word to a sentence.
	 * If null, this function will used the default alias of the current model.
	 * 
	 * @param  string $camelizedString the camelized string [optional]
	 * @return string                  the converted string
	 */
	public function getHeader($camelizedString = null) {
		if ($camelizedString) {
		    return Inflector::humanize(Inflector::underscore($camelizedString));
		} else {
	        return Inflector::humanize(Inflector::underscore($this->alias()));
		}
	}

	public function queryString($key, $options=[], $request=null) {
		$value = 0;
		if (is_null($request) && isset($this->request)) {
			$request = $this->request;
		}

		if (!is_null($request)) {
			$query = $request->query;
			if (isset($query[$key])) {
				$value = $query[$key];
				if (!array_key_exists($value, $options)) {
					$value = key($options);
				}
			} else {
				$value = key($options);
				$request->query[$key] = $value;
			}
		}
		return $value;
	}

	public function advancedSelectOptions(&$options, &$selected, $params=[]) {
		$callable = array_key_exists('callable', $params) ? $params['callable'] : null;
		$message = array_key_exists('message', $params) ? $params['message'] : '';

		foreach ($options as $id => $label) {
			$options[$id] = ['value' => $id, 'text' => $label];

			if (is_callable($callable)) {
				$count = $callable($id);

				if ($count == 0) {
					if (!empty($message)) {
						$options[$id]['text'] = str_replace('{{label}}', $label, $message);
					}
					$options[$id][] = 'disabled';

					if ($selected == $id) $selected = 0;
				} else {
					if ($selected == 0) $selected = $id;
				}
			}

			if ($selected == $id) {
				$options[$id][] = 'selected';
			}
		}
		return $selected;
	}
}
