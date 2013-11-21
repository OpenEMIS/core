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

//App::import('Core', 'Controller'); 
//App::import('Component', 'Email'); 
App::uses('AppTask', 'Console/Command/Task');
class KmlTask extends AppTask {
	public $limit = 100;
	public $fileFP;
	public $tasks = array('Common');
	  
	public $uses = array(
            'ConfigItem'
        );
	/****
	 * KML Starts
	 */
	public function prepareKML($settings){
		
        $header = $settings['header'];
        $name = $settings['name'];
        $module = $settings['module'];
        $category = $settings['category'];
		$batchReportId = $settings['batchProcessId'];
		$reportId = $settings['reportId'];
		
        
        $line = '';
        $filename = $reportId."_".$batchReportId."_".str_replace(' ', '_', $name).'.kml';
        
		$path = $this->Common->getResultPath().str_replace(' ','_',$category).DS.$module.DS;
		
		$type = 'w+';
		
        $this->fileFP = fopen($path.$filename, $type);
		fputs ($this->fileFP, $header."\n");
       
		
	}
        
        public function checkLongitudeLatitude($check,$type='long'){

            $isValid = 0;
            $check = trim($check);
            if($type == 'long'){
                if(is_numeric($check) && floatval($check) >= -180.00 && floatval($check <= 180.00)){
                    $isValid = $check;
                }
            }else{
                if(is_numeric($check) && floatval($check) >= -90.00 && floatval($check <= 90.00)){
                    $isValid = $check;
                }
            }
            
            return $isValid;
        }

        public function checkLatitude($check){
            $isValid = 0;
            $latitude = trim($check);
            return $isValid;
        }


	private function formatSchoolDescription($arrData){ 
            $address = $this->Common->cleanContent($arrData['InstitutionSite']['Address']);
            $site_id  = $this->Common->cleanContent($arrData['InstitutionSite']['SiteId']);
            $url = $this->ConfigItem->getValue('where_is_my_school_url');
            $data  = '<div>'.$address.'</div><div><br>Institution Site Details: <a href='.$url.'/InstitutionSites/siteProfile/'.$site_id.'>Click here</a></div>';
            return $data; 
        }
	public function writeKML($data,$settings){ 
		//$batch = $settings['batch'];
		
        $tpl = $settings['tpl'];
		
        foreach($data as $k => $arrv){
			$line = str_replace('{InstitutionName}', $this->Common->cleanContent($arrv['Institution']['Name'].'-'.$arrv['InstitutionSite']['SiteName']), $tpl);
			$line = str_replace('{Longitude}', $this->checkLongitudeLatitude($arrv['InstitutionSite']['Longitude']), $line);
			$line = str_replace('{Latitude}', $this->checkLongitudeLatitude($arrv['InstitutionSite']['Latitude'], 'lat'), $line);
			$line = str_replace('{school_description}', $this->formatSchooldescription($arrv), $line);		
					
			fputs ($this->fileFP, $line);
        }
       
		
	}
	
	public function closeKML($settings){
		 $footer = $settings['footer'];
		 fputs ($this->fileFP, $footer."\n");
		 fclose ($this->fileFP);
	}
	
	public function genKML($settings){
		$childrenIds = array();
		
		$countryInfo = $this->Area->find('first',array('fields'=>array('Area.name as AreaName','Area.id as AreaId'),'conditions'=>array('Area.parent_id'=>'-1')));
                
		$oneSite = $this->InstitutionSite->find('first',array('fields'=>array('InstitutionSite.longitude','InstitutionSite.latitude'),'conditions'=>array('InstitutionSite.longitude >' => '0','InstitutionSite.latitude >' => '0'),'limit'=>1));
                
                $title = $this->ConfigItem->getValue('where_is_my_school_title');
                $long = $this->ConfigItem->getValue('where_is_my_school_start_long');
                $lat = $this->ConfigItem->getValue('where_is_my_school_start_lat');
                $range = $this->ConfigItem->getValue('where_is_my_school_start_range');
                
                
		$settings['header'] = str_replace('{description}', ($title != ''?$title:$countryInfo).' - '.date('Y-m-d'),$settings['header']); 
		$settings['header'] = str_replace('{start_longitude}', ($long == 0?$oneSite['InstitutionSite']['longitude']:$long),$settings['header']); 
		$settings['header'] = str_replace('{start_latitude}', ($lat == 0?$oneSite['InstitutionSite']['latitude']:$lat),$settings['header']); 
                $settings['header'] = str_replace('{start_range}', ($range == 0?'2000000':$range),$settings['header']); 
		$this->prepareKML($settings);
                $countofLevel3 = $res = $this->AreaLevel->find('count',array('conditions'=>array('AreaLevel.level >='=>3)));
                $initlevel = ($countofLevel3 > 0)?2:1;
                $res = $this->Area->find('all',array('fields'=>array('Area.name as AreaName','Area.id as AreaId'),'conditions'=>array('AreaLevel.level'=>$initlevel)));
                //$res = $this->Area->find('all',array('fields'=>array('Area.name as AreaName','Area.id as AreaId'),'conditions'=>array('AreaLevel.level'=>2)));
                   
		$this->Common->formatData($res);
		$ids = array();
		foreach($res as $arr){
			$ids[$arr['AreaName']] = array();
			$childrenIds = array($arr['AreaId']);
			
			do{
				$childrenIds = $this->Area->find('list',array('conditions'=>array('parent_id'=>$childrenIds)));
				$childrenIds = array_keys($childrenIds) ;
				$ids[$arr['AreaName']] = array_merge($ids[$arr['AreaName']],$childrenIds);
				
			}while(count($childrenIds)>0);
		}
		$count2=0;
		foreach($ids as $place => $areaIds){
			
			$folderHeadTpl = '<Folder><name>'.$place.'</name>';
			fputs ($this->fileFP, $folderHeadTpl);
					   
			$count = $this->getKMLCount($settings['reportId'],'array("conditions"=>array("InstitutionSite.area_id" => array('.implode(",",$areaIds).')))');
			$recusive = ceil($count / $this->limit);
			
			for($i=0;$i < $recusive;$i++){
				$offset = ($this->limit*$i + ($i!=0?1:0));
				$offsetStr = $offset;
				$offsetStr = (string)$offsetStr;
				$cond = 'array("fields"=>array(
                                        "InstitutionSite.id AS SiteId",
					"InstitutionSite.name AS SiteName",
					"InstitutionSite.longitude AS Longitude",
					"InstitutionSite.latitude AS Latitude",
					"InstitutionSite.address AS Address",
					"Institution.name AS Name"),
					"conditions"=>array("InstitutionSite.area_id" => array('.implode(",",$areaIds).')),"offset"=>'.$offsetStr.',"limit"=>'.$this->limit.')';
				
				$sql = str_replace('{cond}',$cond,$settings['sql']);//die;
				try{
					eval($sql);
				} catch (Exception $e) {
					// Update the status for the Processed item to (-1) ERROR
					$errLog = $e->getMessage();
					$this->Common->updateStatus($procId,'-1');
					$this->Common->createLog($this->getLogPath().$procId.'.log',$errLog);
				}
				
				$this->writeKML($data,$settings);
			}
			$folderFootTpl = '</Folder>';
			fputs ($this->fileFP, $folderFootTpl);
		}
		$this->closeKML($settings);
		
	}
	
    
	
	public function getKMLCount($id,$ExtraCond){
        $this->autoRender = false;
        $res = $this->Report->find('first',array('conditions'=>array('id'=>$id)));
        $sql = $res['BatchReport'][0]['query'];
        $sql = str_replace('{cond}',$ExtraCond,$sql);
		$countSql = str_replace("'all'","'count'",$sql);
		
        eval($countSql);
		
        return (isset($data)?$data:0);
    }
	
}
	
?>
