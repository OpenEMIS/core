<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2015-01-27

OpenEMIS
Open Education Management Information System

Copyright © 2015 OPENEMIS.  This program is free software: you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope 
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should 
have received a copy of the GNU General Public License along with this program.  If not, see 
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

App::uses('AppHelper', 'View/Helper');

class ModelHelper extends AppHelper {

	protected static function getNameDefaults(){
		/* To create option field for Administration to set these default values for system wide use */
		return array(
			'middle' 	=> true,
			'third'  	=> true,
			'preferred' => false,
		);
	}

	protected static function getNameKeys($otherNames){
		$defaults = ModelHelper::getNameDefaults();
		$middle = (isset($otherNames['middle'])&&is_bool($otherNames['middle'])&&$otherNames['middle']) ? $otherNames['middle'] : $defaults['middle'];
		$third = (isset($otherNames['third'])&&is_bool($otherNames['third'])&&$otherNames['third']) ? $otherNames['third'] : $defaults['third'];
		$preferred = (isset($otherNames['preferred'])&&is_bool($otherNames['preferred'])&&$otherNames['preferred']) ? $otherNames['preferred'] : $defaults['preferred'];
		return array(
			'first_name'	=>	true,
			'middle_name'	=>	$middle,
			'third_name'	=>	$third,
			'last_name'		=>	true,
			'preferred_name'=>	$preferred
		);
	}

	/*
	*	$obj 		= a single staff or student data 			@array
	*	$options 	= option to include other names such as middle_name, third_name or/and preferred_name.
	*				  Accepts keys defined as middle, third or/and preferred with boolean as their values.
	*				  To include OpenEMISid or separator.
	*				  @array(
	*						'openEmisId' => @boolean,
	*						'separator' => @string,
	*						'middle' => @boolean,
	*						'third' => @boolean,
	*						'preferred' => @boolean
	*					)
	*	
	*	return @string
	*/
	public static function getName($obj, $options=array()){
		$name = '';
		$separator = (isset($options['separator'])&&strlen($options['separator'])>0) ? $options['separator'] : ' ';
		$keys = ModelHelper::getNameKeys($options);
		foreach($keys as $k=>$v){
			if(isset($obj[$k])&&$v){
				if($k!='last_name'){
					if($k=='preferred_name'){
						$name .= $separator . '('. $obj[$k] .')';
					} else {
						$name .= $obj[$k] . $separator;
					}
				} else {
					$name .= $obj[$k];
				}
			}
		}
		return (isset($options['openEmisId'])&&is_bool($options['openEmisId'])&&$options['openEmisId']) ? trim(sprintf('%s - %s', $obj['identification_no'], $name)) : trim(sprintf('%s', $name));
	}

	public static function getNameWithHistory($obj, $options=array()){
		$name = '';
		$separator = (isset($options['separator'])&&strlen($options['separator'])>0) ? $options['separator'] : ' ';
		$keys = ModelHelper::getNameKeys($options);
		foreach($keys as $k=>$v){
			if(isset($obj[$k])&&$v){
				if($k!='last_name'){
					if($k=='preferred_name'){
						$name .= $separator . '('. $obj[$k] . ((isset($obj['history_'.$k])) ? '<br>'.$obj['history_'.$k] .')' : ')');
					} else {
						$name .= $obj[$k] . ((isset($obj['history_'.$k])) ? '<br>'.$obj['history_'.$k] . $separator : $separator);
					}
				} else {
					$name .= $obj[$k] . ((isset($obj['history_'.$k])) ? '<br>'.$obj['history_'.$k] : '');
				}
			}
		}
		return (isset($options['openEmisId'])&&is_bool($options['openEmisId'])&&$options['openEmisId']) ? trim(sprintf('%s - %s', $obj['identification_no'], $name)) : trim(sprintf('%s', $name));
	}

}