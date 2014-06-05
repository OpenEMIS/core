<?php
class Licenses
{
	protected $filetypes='\.php|\.js';
    protected $rootpath=""; //defaults to current directory which file is run , if not specified
	protected $paths = array('Controller','Console','Plugin','Model','View','webroot/js'); //include desired directories within rootpath
	protected $exclude_files=array('routes.php','cake.php');
    protected $exclude_patterns=array('jquery','^app');


/* @LICENSEDATE is replaced to @OPENEMIS_LICENSE YYYY-MM-DD 
 also used as the anchor to replace the old license text */
    protected $newTxt = '/*
@LICENSEDATE
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
';

	protected $filetypes_regex ="";
    protected $replace_locations = array();



    function licensesForDir($path){
        foreach(glob($path.'/*') as $eachPath)
        {
            if(is_dir($eachPath))
            {
                $this->licensesForDir($eachPath);
            }
            if(preg_match('/'. $this->filetypes.'/',$eachPath))
            {
                $this->replace_locations[] = $eachPath;
            }
        }
    }

    function exec(){
        if ($this->rootpath == ""){
            $this->rootpath = dirname(__FILE__);
        }

        foreach ($this->paths as $path){
             $this->licensesForDir($this->rootpath.'/'.$path);
        }
       
        foreach($this->replace_locations as $path)
        {
            $this->handleFile($path);
        }
    }

    function handleFile($path){
        $path_parts = pathinfo($path);

        foreach ($this->exclude_files as $exclude){
            if  (trim($path_parts['basename']) == trim($exclude)) {
                return;
            }
        }
        foreach ($this->exclude_patterns as $exclude_pattern){
           if (preg_match("/". $exclude_pattern ."/i",$path_parts['basename'])) {
                return;
            }
        }

        $source = file_get_contents($path);
		$source = preg_replace('/\/\*[^@]*\@OPENEMIS LICENSE LAST UPDATED ON[^*]*\*\/[\n]*/m','',$source,1);

		$licenseText = str_replace("@LICENSEDATE", "@OPENEMIS LICENSE LAST UPDATED ON " . date("Y-m-d") . "\n"  , $this->newTxt);
        
		switch($path_parts['extension']){
			case "ctp" :
			case "php" :
				$source = preg_replace('/\<\?php/',"<?php"."\n".$licenseText,$source,1);
				break;
			default:
				$source = $licenseText."\n".$source;
				break;
		}		
        $source = str_replace( "\r", "", $source);

        file_put_contents($path,$source);
        echo $path."\n";
    }
}

$licenses = new Licenses;
$licenses->exec();

