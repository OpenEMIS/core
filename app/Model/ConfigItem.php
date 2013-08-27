<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppModel', 'Model');

class ConfigItem extends AppModel {
    public $validate = array(
        'image', array(
	        'rule'    => array('extension', array('gif', 'jpeg', 'png', 'jpg')),
	        'message' => 'Please supply a valid image.'
	    )
    );

    public function getYearbook() {
    	$yearbook = array();
    	$yearbook['yearbook_organization_name'] = $this->getValue('yearbook_organization_name');
    	$yearbook['yearbook_school_year'] = $this->getValue('yearbook_school_year');
    	$yearbook['yearbook_title'] = $this->getValue('yearbook_title');
    	$yearbook['yearbook_publication_date'] = $this->getValue('yearbook_publication_date');
    	$yearbook['yearbook_logo'] = $this->getValue('yearbook_logo');
    	$yearbook['yearbook_orientation'] = $this->getValue('yearbook_orientation');
    	return $yearbook;
    }
	public function getVersion() {
		return $this->getValue('version');
	}

	public function getTypes() {
		$types = array();
		$rawData = $this->find('all',array(
			'fields' => array('DISTINCT ConfigItem.type'),
			'recursive' => 0
		));

		$data = $this->formatArray($rawData);
		foreach ($data as $element) {
			$types[] = $element['type'];
		}
		return $types;
	}

	public function getSupport() {
		$supportInfo = array();
		$supportInfo['phone'] = $this->getValue('support_phone');
		$supportInfo['email'] = $this->getValue('support_email');
		$supportInfo['address'] = $this->getValue('support_address');
		return $supportInfo;
	}
	
	public function getAdaptation() {	
		// return $this->getValue('adaptation');
		$results = $this->find('all', array(
			'fields' => array('ConfigItem.id', 'ConfigItem.name', 'ConfigItem.value', 'ConfigItem.default_value'),
			'recursive' => 0,
			'limit' => 1,
			'conditions' => array('name' => 'adaptation')
		));

		$adaptation = array_shift($results);

		return (!is_null($adaptation['ConfigItem']['value']) && !empty($adaptation['ConfigItem']['value']))? $adaptation['ConfigItem']['value']: $adaptation['ConfigItem']['default_value'];
	}
	
	public function getCountry() {
		return $this->getValue('country');
	}

	public function getLabel($name) {
		return $this->field('label', array('name' => $name));
	}
	
	public function getValue($name) {
		$value = $this->field('value', array('name' => $name));
		return (strlen($value)==0)? $this->getDefaultValue($name):$value;
	}

	public function getDefaultValue($name) {
		return $this->field('default_value', array('name' => $name));
	}

	public function getNotice(){

		$notice = $this->getValue('dashboard_notice');
		$default_notice = $this->getDefaultValue('dashboard_notice');

		return (is_null($notice) || empty($notice))? $default_notice:$notice;
	}


	public function editDashboardImage($x=null, $y=null){
		$isUpdated = false;
		if(is_null($x) || is_null($y)){
			return false;
		}

		$timestamp = '';
		$newX = $x;
		$newY = $y;

		$imageFolder = $this->getValue('dashboard_img_folder');
		$path = IMAGES.$imageFolder.DS;

		$filenames = $this->getUserImageFiles($imageFolder);


		if(count($filenames) > 0){
			$filename = $filenames[0];
			$ext = $this->findExtension($filename);

			$spilt_filename = explode('_', $filename);
			
			if(isset($spilt_filename[0])){
				$timestamp = $spilt_filename[0];
			}
			$newFilename = $timestamp . '_' . $newX . '_' . $newY . '.' . $ext;
			
			$isUpdated = rename($path.$filename, $path.$newFilename);
			
		}else{
			return false;
		}

		return $isUpdated;

	}

	public function saveDashboardImage($newImage=null){
		// $fields = array('dashboard_img_orignal', 'dashboard_img_x_offset', 'dashboard_img_y_offset', 'dashboard_img_folder' );

		$returnResult = array();

		$isSave = false;

		$isFolderEmpty = false;

		if(is_null($newImage)){
			return false;
		}

		$image = $newImage;

		$folder = $this->getValue('dashboard_img_folder');
 		$filename = time()."_0_0.". $this->findExtension($image['name']); //str_ireplace(' ', '_', strtolower($image['name']));
 		try {
 			$isFolderEmpty = $this->emptyFolder($folder);
 			$isSave = move_uploaded_file($image['tmp_name'], IMAGES.$folder.DS.$filename);

			$returnResult['saved'] = $isSave;
 			
 		} catch (Exception $e) {
 			$returnResult['saved'] = $isSave;
 			
 		}

		return $isSave;

	}
	
	public function getDashboardMasthead(){
		$image = array(
			'imagePath' => '',
			'x' => 0,
			'y' => 0,
			'width' => 700,
			'height' => 320
		);

		$imageFolder = '';
		$imageFilename = '';
		$isDefault = false;

		list($image, $defaultIamge, $x, $y, $width, $height) = array('','',0,0,700,200);

		$imageFolder = $this->getValue('dashboard_img_folder');

		$filenames = $this->getUserImageFiles($imageFolder);

		$filename = (count($filenames)> 0)?$filenames[0]: $this->getDefaultImageFile();

		$width = $this->getValue('dashboard_img_width');

		$height = $this->getValue('dashboard_img_height');

		$coordinates = $this->getCoordinates($filename);

		$image['imagePath'] = $imageFolder.DS.$filename;

		$image['x'] = $coordinates['x'];
		$image['y'] = $coordinates['y'];
		$image['width'] = $width;
		$image['height'] = $height;

		return $image;
	}

	public function getImageConfItem()
	{

		$rawImageConfig = $this->find('all', array(
			'fields' => array('ConfigItem.name', 'ConfigItem.value', 'ConfigItem.default_value'),
			'conditions' => array('ConfigItem.name' => array( 'dashboard_img_default', 'dashboard_img_width', 'dashboard_img_height', 'dashboard_img_size_limit'))
		));


		// $imageFolder = $this->getValue('dashboard_img_folder');
		$defaultImageId = $this->getValue('dashboard_img_default');
		// $filenames = $this->getUserImageFiles($imageFolder);
		// $filename = (count($filenames)>0)?$filenames[0]:$this->getDefaultImageFile();
		$width = $this->getValue('dashboard_img_width');
		$height = $this->getValue('dashboard_img_height');
		// $coordinates = $this->getCoordinates($filename);
		$size_limit = $this->getValue('dashboard_img_size_limit');
		// (int)(ini_get('upload_max_filesize'));

		$imageConfig = array();

		$isDefault = false;

		// $imageConfig['dashboard_img_x_offset'] = (isset($coordinates['x']))? $coordinates['x']:0;
		// $imageConfig['dashboard_img_y_offset'] = (isset($coordinates['y']))? $coordinates['y']:0;
		// $imageConfig['dashboard_img_folder'] = $imageFolder;
		// $imageConfig['dashboard_img_file'] = $filename;
		$imageConfig['dashboard_img_default'] = $defaultImageId;
		$imageConfig['dashboard_img_width'] = $width;
		$imageConfig['dashboard_img_height'] = $height;
		$imageConfig['dashboard_img_size_limit'] = (int) ($size_limit)? $size_limit: ini_get('upload_max_filesize');

		// $imageFilenames = $this->getUserImageFiles();
		
		// $imageOrignalPath = '';
		

		// $imageOrignalPath = $imageFolder.DS.$filename;

		// list($orignalWidth, $orignalHeight) = getimagesize(IMAGES.$imageOrignalPath);
		
		// $imageConfig['orignal_width'] = $orignalWidth;
		// $imageConfig['orignal_height'] = $orignalHeight;

		return $imageConfig;

	}

	private function findExtension ($filename)
	{
	   $filename = strtolower($filename) ;
	   $exts = explode(".", $filename) ;
	   $n = count($exts)-1;
	   $exts = $exts[$n];
	   return $exts;
	}

	private function getUserImageFiles($folder=null)
	{
		$filenames = array();
		$path = IMAGES;

		$path .= (is_null($folder))? $this->getValue('dashboard_img_folder'): $folder;

		foreach (new DirectoryIterator($path) as $fileInfo){
		    if(!$fileInfo->isDot()){
		        if(!stristr($fileInfo->getFilename(), 'default')) {
		        	$filenames[] = $fileInfo->getFilename();
		        }
		    }
		}
		return $filenames;
	}

	private function getDefaultImageFile($folder=null)
	{
		$filename = '';
		$path = IMAGES;

		$path .= (is_null($folder))? $this->getValue('dashboard_img_folder'): $folder;

		foreach (new DirectoryIterator($path) as $fileInfo){
		    if(!$fileInfo->isDot()){
		        if(stristr($fileInfo->getFilename(), 'default')) {
		        	$filename = $fileInfo->getFilename();
		        	break;
		        }
		    }
		}
		return $filename;
	}

	private function getUserImageFile($folder=null)
	{
		$filenames = array();
		$path = IMAGES;
		$path .= (is_null($folder))? $this->getValue('dashboard_img_folder'): $folder;

		foreach (new DirectoryIterator($path) as $fileInfo){
		    if(!$fileInfo->isDot()){
		        if(!stristr($fileInfo->getFilename(), 'default')) {
		        	$filenames[] = $fileInfo->getFilename();
		        }
		    }
		}


		return $filenames;
	}

	private function emptyFolder($folder=null)
	{
		$isFolderEmpty = false;
		$path = IMAGES.$folder;

		if(is_null($folder)){
			return $isFolderEmpty;
		}
		
		$filenames = $this->getUserImageFiles($folder);

		if(count($filenames) < 0){
			$isFolderEmpty = true;
		}else{

			foreach ($filenames as $filename){
		        unlink($path.DS.$filename);
			}

			$checkFiles = new DirectoryIterator($path);

			if(count($checkFiles) < 1){
				$isFolderEmpty = true;
			}
			
		}

		return $isFolderEmpty;
	}

	private function getCoordinates($filename=null){

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
		if(count($filenameSections)>0){
			$timestamp = array_shift($filenameSections);
		}
		if(count($filenameSections)>0){
			$x = array_shift($filenameSections);
		}
		if(count($filenameSections)>0){
			$y = array_shift($filenameSections);
		}

		$coordinates['x'] = $x;
		$coordinates['y'] = $y;

		return $coordinates;

	}
	
	public function getAllCustomValidation(){
		$data = $this->findAllByType('custom validation');
		$newArr = array();
		foreach($data as $arrVal){
			if($arrVal['ConfigItem']['value'] != '')
			$newArr[$arrVal['ConfigItem']['name']] = str_replace("[a[\\-]zA[\\-]Z]", "[a-zA-Z]",str_replace (array("N","A","_", " ", "(", ")", "-"), array("\\d","[a-zA-Z]", "[_]", "\\s", "[(]", "[)]", "[\\-]"), $arrVal['ConfigItem']['value']));
		}
		return $newArr;
	}
	
	
	
	public function getAllLDAPConfig(){
		$tmp = array();
		$data = $this->findAllByType('LDAP Configuration');

		foreach($data as $k => $arrV){
			foreach($arrV as $arrVal){
				$tmp[$arrVal['name']] = ($arrVal['value'] != '')?$arrVal['value']:$arrVal['default_value'];
			}
		}
		
		return $tmp;
	}

}
