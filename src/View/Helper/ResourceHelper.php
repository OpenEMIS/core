<?php
namespace App\View\Helper;

use Cake\View\Helper;

class ResourceHelper extends Helper {
	public $helpers = ['Html'];

	public function plugin($folder, $file) {
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		$path = '..' . '/' . 'plugins' . '/' . $folder . '/';

		$html = '';
		switch ($ext) {
			case 'css':
				$html = $this->Html->css($path . 'css' . '/' . $file, array('media' => 'screen'));
				break;
			case 'js':
				$html = $this->Html->script($path . 'js' . '/' . $file);
				break;
		}
		return $html;
	}
}
