<?php
namespace App\Model\Traits;

trait OptionsTrait {
	public function getSelectOptions($code) {
		$options = [
			'general' => [
				'active' => [1 => __('Active'), 0 => __('Inactive')],
				'yesno' => [1 => __('Yes'), 0 => __('No')],
			],
			'Staff' => [
				'position_types' => [1 => __('Teaching'), 0 => __('Non-Teaching')]
			],
			'Absence' => [
				'types' => ['EXCUSED' => __('Excused'), 'UNEXCUSED' => __('Unexcused')]
			]
		];

		$index = explode('.', $code);
		foreach ($index as $i) {
			if (isset($options[$i])) {
				$options = $options[$i];
			} else {
				$options = false;
				break;
			}
		}
		return $options;
	}
}
