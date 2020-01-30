<?php
namespace OpenEmis\View\Helper;

use Cake\View\Helper;
use Cake\Utility\Security;
use Cake\Controller\Exception\SecurityException;

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

	public function getCodeVersion() {
		$path = 'version';
		$version = '1.0';

		if (file_exists($path)) {
			$version = file_get_contents($path);
		}
		return $version;
	}

	private function getFileExtension($path, $fileExt){
		$file = explode("/", $path);
		$ext = explode(".", $file[count($file) -1]);
		$ext = $ext[count($ext) -1];
		$ext = ($ext == $fileExt)? "": ".".$fileExt;
		return $ext;
	}

	private function generatePath($path, $fileExt){
		$ext = $this->getFileExtension($path,$fileExt);
		$version = $this->getCodeVersion();
		$path = $path . $ext.'?v=' . $version;
		return $path;
	}

	public function css($path, $options=[]) {
		$path = $this->generatePath($path, 'css');
		return $this->Html->css($path, $options);
	}

	public function script($path) {
		$path = $this->generatePath($path, 'js');
		return $this->Html->script($path);
	}
        
        public function urlsafeB64Encode($input)
        {
            return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
        }
        
        public function paramsEncode($params = [])
        {
            $sessionId = Security::hash('session_id', 'sha256');
            $params[$sessionId] = session_id();
            $jsonParam = json_encode($params);
            $base64Param = $this->urlsafeB64Encode($jsonParam);
            $signature = Security::hash($jsonParam, 'sha256', true);
            $base64Signature = $this->urlsafeB64Encode($signature);
            return "$base64Param.$base64Signature";
        }
        
}
