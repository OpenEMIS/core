<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Converter
 *
 * @author vincent
 */
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');
App::uses('CakeLog', 'Log');
class Converter {
	//put your code here
	
	public static function convertToMo($source, $destination) {
		$shellCmd = 'msgfmt -cv -o ' . $destination . ' ' . $source. ' 2>&1';
		$result = shell_exec($shellCmd);
		CakeLog::write('debug','Translation : '.$result .'Path : '.$destination);
    }
}
