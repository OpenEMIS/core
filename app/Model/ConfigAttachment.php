<?php
App::uses('AppModel', 'Model');

class ConfigAttachment extends AppModel {
	public $useTable = 'config_attachments';
       // public $belongsTo = array('Institution');
       
    public $virtualFields = array(
		'blobsize' => "OCTET_LENGTH(file_content)"
	);

	public function modelName() {
		return $this->name;
	}

	public function updateAttachmentCoord($id=0, $x=null, $y=null){
		$isUpdated = false;
		if(is_null($x) || is_null($y)){
			return false;
		}

		$timestamp = '';
		$newX = $x;
		$newY = $y;

		$results = $this->find('all', array('fields' => array('id', 'file_name'), 'conditions' => array('ConfigAttachment.id' => $id)));
		$row = array_pop($results);
		$row = array_merge($row['ConfigAttachment']);
		$fileExtension = $this->findExtension($row['file_name']);
		// $imageName = str_ireplace('.'.$fileExtension, '', $row['file_name']);

		// $filenameSections = explode('_', $imageName);
		$timestamp = $this->getFileTimestamp($row['file_name']);
		$resolution = $this->getResolution($row['file_name']);
		// list($timestamp, $fileX, $fileY, $width, $height) = explode('_', $imageName);
		$row['file_name'] = "{$timestamp}_{$x}_{$y}_{$resolution['width']}_{$resolution['height']}.{$fileExtension}";

		if($this->save($row)){
			$isUpdated = true;
		}
		return $isUpdated;

	}

	public function getFileTimestamp($filename=null){

		$resolution = array('width' => 0, 'height' => 0);
		if(is_null($filename) || empty($filename)){
			return time();
		}

		$fileExtension = $this->findExtension($filename);
		$imageName = str_ireplace('.'.$fileExtension, '', $filename);
		// $timestamp = '';
		// $x = 0;
		// $y = 0;

		$filenameSections = explode('_', $imageName);
		$timestamp = (sizeof($filenameSections) > 0)? array_shift($filenameSections): time();
		$x = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;
		$y = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;
		$width = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;
		$height = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;

		return $timestamp;

	}

	public function getResolution($filename=null){

		$resolution = array('width' => 0, 'height' => 0);
		if(is_null($filename)){
			return false;
		}

		if(empty($filename)){
			$resolution['width'] = 0;
			$resolution['height'] = 0;
			return $resolution;
		}

		$fileExtension = $this->findExtension($filename);
		$imageName = str_ireplace('.'.$fileExtension, '', $filename);
		// $timestamp = '';
		// $x = 0;
		// $y = 0;

		$filenameSections = explode('_', $imageName);
		$timestamp = (sizeof($filenameSections) > 0)? array_shift($filenameSections): time();
		$x = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;
		$y = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;
		$width = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;
		$height = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;

		// $filenameSections = explode('_', $imageName);
		// if(count($filenameSections)>0){
		// 	$timestamp = array_shift($filenameSections);
		// }
		// if(count($filenameSections)>0){
		// 	$x = array_shift($filenameSections);
		// }
		// if(count($filenameSections)>0){
		// 	$y = array_shift($filenameSections);
		// }

		$resolution['width'] = $width;
		$resolution['height'] = $height;

		return $resolution;

	}

	public function getCoordinates($filename=null){

		$coordinates = array('x' => 0, 'y' => 0);
		if(is_null($filename)){
			return false;
		}

		if(empty($filename)){
			$coordinates['x'] = 0;
			$coordinates['y'] = 0;
			return $coordinates;
		}

		$fileExtension = $this->findExtension($filename);
		$imageName = str_ireplace('.'.$fileExtension, '', $filename);
		$timestamp = '';
		$x = 0;
		$y = 0;

		$filenameSections = explode('_', $imageName);
		$timestamp = (sizeof($filenameSections) > 0)? array_shift($filenameSections): time();
		$x = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;
		$y = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;
		// $width = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;
		// $height = (sizeof($filenameSections) > 0)? array_shift($filenameSections): 0;

		$coordinates['x'] = $x;
		$coordinates['y'] = $y;

		return $coordinates;

	}

	private function findExtension ($filename)
	{
	   $filename = strtolower($filename) ;
	   $exts = explode(".", $filename) ;
	   $n = count($exts)-1;
	   $exts = $exts[$n];
	   return $exts;
	}

}
?>