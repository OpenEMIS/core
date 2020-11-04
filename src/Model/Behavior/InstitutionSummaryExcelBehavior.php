<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\I18n\Time;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\EventTrait;
use Cake\I18n\I18n;
use Cake\Utility\Hash;
use XLSXWriter;
use Cake\ORM\TableRegistry;

// Events
// public function onExcelBeforeGenerate(Event $event, ArrayObject $settings) {}
// public function onExcelGenerate(Event $event, $writer, ArrayObject $settings) {}
// public function onExcelGenerateComplete(Event $event, ArrayObject $settings) {}
// public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {}
// public function onExcelStartSheet(Event $event, ArrayObject $settings, $totalCount) {}
// public function onExcelEndSheet(Event $event, ArrayObject $settings, $totalProcessed) {}
// public function onExcelGetLabel(Event $event, $column) {}

class InstitutionSummaryExcelBehavior extends Behavior
{
    use EventTrait;

    private $events = [];

    protected $_defaultConfig = [
        'folder' => 'export',
        'default_excludes' => ['modified_user_id', 'modified', 'created', 'created_user_id', 'password'],
        'excludes' => [],
        'limit' => 100000,
        'pages' => [],
        'autoFields' => true,
        'orientation' => 'landscape', // or portrait
        'sheet_limit' =>  1000000, // 1 mil rows and header row
        'auto_contain' => true
    ];

    public function initialize(array $config)
    {
        $this->config('excludes', array_merge($this->config('default_excludes'), $this->config('excludes')));
        if (!array_key_exists('filename', $config)) {
            $this->config('filename', $this->_table->alias());
        }
        $folder = WWW_ROOT . $this->config('folder');

        if (!file_exists($folder)) {
            umask(0);
            mkdir($folder, 0777);
        } else {
            // $delete = true;
            // if (array_key_exists('delete', $settings) &&  $settings['delete'] == false) {
            //  $delete = false;
            // }
            // if ($delete) {
            //  $this->deleteOldFiles($folder, $format);
            // }
        }
        $pages = $this->config('pages');
        if ($pages !== false && empty($pages)) {
            $this->config('pages', ['index', 'view']);
        }
    }

    private function eventMap($method)
    {
        $exists = false;
        if (in_array($method, $this->events)) {
            $exists = true;
        } else {
            $this->events[] = $method;
        }
        return $exists;
    }

    public function excel($id = 0)
    {
        $ids = empty($id) ? [] : $this->_table->paramsDecode($id);
        $this->generateXLXS($ids);
    }

    public function excelV4(Event $mainEvent, ArrayObject $extra)
    {
        $id = 0;
        $break = false;
        $action = $this->_table->action;
        $pass = $this->_table->request->pass;
        if (in_array($action, $pass)) {
            unset($pass[array_search($action, $pass)]);
            $pass = array_values($pass);
        }
        if (isset($pass[0])) {
            $id = $pass[0];
        }
        $ids = empty($id) ? [] : $this->_table->paramsDecode($id);
        $this->generateXLXS($ids);
        return true;
    }

    private function eventKey($key)
    {
        return 'Model.excel.' . $key;
    }
	
	public function generateXLXS($settings = [])
    {
        $_settings = [
            'file' => $this->config('filename') . '_' . date('Ymd') . 'T' . date('His') . '.xlsx',
            'path' => WWW_ROOT . $this->config('folder') . DS,
            'download' => true,
            'purge' => true
        ];
        $_settings = new ArrayObject(array_merge($_settings, $settings));

        $this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeGenerate'), 'onExcelBeforeGenerate', [$_settings]);

        $writer = new XLSXWriter();
        $excel = $this;

        $_settings['writer'] = $writer;

		$data = $this->getData();
		
		$row1 = array("Area Education"," "," ","Area Administrative"," "," ","Locality"," "," ","Type"," "," ","Ownership"," "," ","Sector"," "," ","Provider"," "," ","First Shift Gender"," "," ","Second Shift Gender"," "," ","Third Shift Gender"," "," ","Fourth Shift Gender"," "," ","Total Gender");
		
		$j = 0; //start column
		for ($i=0; $i<12; $i++) {
			$writer->markMergedCell('Summary', $start_row = 0, $start_col = $j, $end_row = 0, $end_col = $j+2);  //merge cells
			$j+=3;
		}
		$writer->writeSheetRow('Summary', $row1);
		foreach($data as $row) {
			if(array_filter($row)) {
				$writer->writeSheetRow('Summary', $row );
			}
		}
		$blankRow[] = [];
		$footer = $this->getFooter();
		$writer->writeSheetRow('Summary', $blankRow);
		$writer->writeSheetRow('Summary', $footer);
		
		$filepath = $_settings['path'] . $_settings['file'];
        $_settings['file_path'] = $filepath;
        $writer->writeToFile($_settings['file_path']);
        $this->dispatchEvent($this->_table, $this->eventKey('onExcelGenerateComplete'), 'onExcelGenerateComplete', [$_settings]);


        if ($_settings['download']) {
            $this->download($filepath);
        }

        if ($_settings['purge']) {
            $this->purge($filepath);
        }
        return $_settings;
    }
	
    public function getData()
    {
		$Institutions = TableRegistry::get('Institution.Institutions');
		$institutionData = $Institutions->find()
                    ->leftJoinWith('Ownerships')
                    ->leftJoinWith('Sectors')
                    ->leftJoinWith('Areas')
                    ->leftJoinWith('AreaAdministratives')
                    ->leftJoinWith('Providers')
                    ->leftJoinWith('Types')
                    ->leftJoinWith('Localities')
                    ->select([
                        'ownership_name' => 'Ownerships.name',
                        'ownership_id' => 'Ownerships.id',
                        'sector_name' => 'Sectors.name',
                        'sector_id' => 'Sectors.id',
                        'provider_name' => 'Providers.name',
                        'provider_id' => 'Providers.id',
                        'type_name' => 'Types.name',
                        'type_id' => 'Types.id',
                        'area_id' => 'Areas.id',
                        'area_name' => 'Areas.name',
                        'area_code' => 'Areas.code',
                        'area_administrative_name' => 'AreaAdministratives.name',
                        'area_administrative_id' => 'AreaAdministratives.id',
                        'area_administrative_code' => 'AreaAdministratives.code',
                        'locality_name' => 'Localities.name',
                        'locality_id' => 'Localities.id'
                    ]);
					
		$areaArray = $sectorArray = $sectorData = $ownershipArray = $localityArray = $typeArray = $providerArray = $areaAdministrativeArray = [];	
		$resultArray = array();
		$i = 0;
		foreach($institutionData as $key => $value) {
			
			if($i == 0) {
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
				$resultArray[$key][] = 'Code';
				$resultArray[$key][] = 'Name';
				$resultArray[$key][] = 'Count';
			} else {
				if(!empty($value->area_id)) {
					
					if (!in_array($value->area_id, $areaArray)) {
						$resultArray[$key]['area_code'] = $value->area_code;
						$resultArray[$key]['area_name'] = $value->area_name;
						$areaCount = $Institutions->find()
							->where([$Institutions->aliasField('area_id') => $value->area_id])
							->count();
							
						$resultArray[$key]['area_count'] = $areaCount;	
					} else {
						$resultArray[$key]['area_code'] = '';
						$resultArray[$key]['area_name'] = '';
						$resultArray[$key]['area_count'] = '';
					}
					$areaArray[] = $value->area_id;
					
				} else {
					$resultArray[$key]['area_code'] = '';
					$resultArray[$key]['area_name'] = '';
					$resultArray[$key]['area_count'] = '';
				}
				
				if(!empty($value->area_administrative_id)) {
					
					if (!in_array($value->area_administrative_id, $areaAdministrativeArray)) {
						$resultArray[$key]['area_administrative_code'] = $value->area_administrative_code;
						$resultArray[$key]['area_administrative_name'] = $value->area_administrative_name;
						$areaAdministrativeCount = $Institutions->find()
							->where([$Institutions->aliasField('area_administrative_id') => $value->area_administrative_id])
							->count();
							
						$resultArray[$key]['area_administrative_count'] = $areaAdministrativeCount;	
					} else {
						$resultArray[$key]['area_administrative_code'] = '';
						$resultArray[$key]['area_administrative_name'] = '';
						$resultArray[$key]['area_administrative_count'] = '';
					}
					$areaAdministrativeArray[] = $value->area_administrative_id;
					
				} else {
					$resultArray[$key]['area_administrative_code'] = '';
					$resultArray[$key]['area_administrative_name'] = '';
					$resultArray[$key]['area_administrative_count'] = '';
				}
				
				if(!empty($value->locality_id)) {
					
					if (!in_array($value->locality_id, $localityArray)) {
						$resultArray[$key]['locality_code'] = '';
						$resultArray[$key]['locality_name'] = $value->locality_name;
						$localityCount = $Institutions->find()
							->where([$Institutions->aliasField('institution_locality_id') => $value->locality_id])
							->count();
							
						$resultArray[$key]['locality_count'] = $localityCount;	
					} else {
						$resultArray[$key]['locality_code'] = '';
						$resultArray[$key]['locality_name'] = '';
						$resultArray[$key]['locality_count'] = '';
					}
					$localityArray[] = $value->locality_id;
					
				} else {
					$resultArray[$key]['locality_code'] = '';
					$resultArray[$key]['locality_name'] = '';
					$resultArray[$key]['locality_count'] = '';
				}
				
				if(!empty($value->type_id)) {
					
					if (!in_array($value->type_id, $typeArray)) {
						$resultArray[$key]['type_code'] = '';
						$resultArray[$key]['type_name'] = $value->type_name;
						$typeCount = $Institutions->find()
							->where([$Institutions->aliasField('institution_type_id') => $value->type_id])
							->count();
							
						$resultArray[$key]['type_count'] = $typeCount;	
					} else {
						$resultArray[$key]['type_code'] = '';
						$resultArray[$key]['type_name'] = '';
						$resultArray[$key]['type_count'] = '';
					}
					$typeArray[] = $value->type_id;
					
				} else {
					$resultArray[$key]['type_code'] = '';
					$resultArray[$key]['type_name'] = '';
					$resultArray[$key]['type_count'] = '';
				}
				
				if(!empty($value->ownership_id)) {
					
					if (!in_array($value->ownership_id, $ownershipArray)) {
						$resultArray[$key]['ownership_code'] = '';
						$resultArray[$key]['ownership_name'] = $value->ownership_name;
						$ownershipCount = $Institutions->find()
							->where([$Institutions->aliasField('institution_ownership_id') => $value->ownership_id])
							->count();
							
						$resultArray[$key]['ownership_count'] = $ownershipCount;	
					} else {
						$resultArray[$key]['ownership_code'] = '';
						$resultArray[$key]['ownership_name'] = '';
						$resultArray[$key]['ownership_count'] = '';
					}
					$ownershipArray[] = $value->ownership_id;
					
				} else {
					$resultArray[$key]['ownership_code'] = '';
					$resultArray[$key]['ownership_name'] = '';
					$resultArray[$key]['ownership_count'] = '';
				}
				if(!empty($value->sector_id)) {
					
					if (!in_array($value->sector_id, $sectorArray)) {
						$resultArray[$key]['sector_code'] = '';
						$resultArray[$key]['sector_name'] = $value->sector_name;
						$sectorCount = $Institutions->find()
							->where([$Institutions->aliasField('institution_sector_id') => $value->sector_id])
							->count();
							
						$resultArray[$key]['sector_count'] = $sectorCount;	
					} else {
						$resultArray[$key]['sector_code'] = '';
						$resultArray[$key]['sector_name'] = '';
						$resultArray[$key]['sector_count'] = '';
					}
					$sectorArray[] = $value->sector_id;
					
				} else {
					$resultArray[$key]['sector_code'] = '';
					$resultArray[$key]['sector_name'] = '';
					$resultArray[$key]['sector_count'] = '';
				}
				
				if(!empty($value->provider_id)) {
					
					if (!in_array($value->provider_id, $providerArray)) {
						$resultArray[$key]['provider_code'] = '';
						$resultArray[$key]['provider_name'] = $value->provider_name;
						$providerCount = $Institutions->find()
							->where([$Institutions->aliasField('institution_provider_id') => $value->provider_id])
							->count();
							
						$resultArray[$key]['provider_count'] = $providerCount;	
					} else {
						$resultArray[$key]['provider_code'] = '';
						$resultArray[$key]['provider_name'] = '';
						$resultArray[$key]['provider_count'] = '';
					}
					$providerArray[] = $value->provider_id;
					
				} else {
					$resultArray[$key]['provider_code'] = '';
					$resultArray[$key]['provider_name'] = '';
					$resultArray[$key]['provider_count'] = '';
				}
			}
			$i++;	
		}
		
		$shiftOptions = $Institutions->find()
                    ->select([
                        'gender' => 'genders.name',
                        'code' => 'genders.code',
                        'shift_option' => 'ShiftOptions.name',
                        'shift_option_id' => 'ShiftOptions.id'
                    ])
					->join([
						 "InstitutionShifts" => [
							 "table" => "institution_shifts",
							 "type" => "inner",
							 "conditions" => "Institutions.id = InstitutionShifts.institution_id"
						  ]
					])
					->join([
						 "InstitutionStudents" => [
							 "table" => "institution_students",
							 "type" => "inner",
							 "conditions" => "Institutions.id = InstitutionStudents.institution_id"
						  ]
					])
					->join([
						 "ShiftOptions" => [
							 "table" => "shift_options",
							 "type" => "inner",
							 "conditions" => "ShiftOptions.id = InstitutionShifts.shift_option_id"
						  ]
					])
					->join([
						 "Users" => [
							 "table" => "security_users",
							 "type" => "inner",
							 "conditions" => "Users.id = InstitutionStudents.student_id"
						  ]
					])
					->join([
						 "Genders" => [
							 "table" => "genders",
							 "type" => "inner",
							 "conditions" => "Genders.id = Users.gender_id"
						  ]
					]);
					
		foreach($shiftOptions as $key => $value) {
			if($value->code == 'M') {
				$shift_gender[$value->shift_option_id][$value->code][] = $value->gender;
			} 
			if($value->code == 'F') {
				$shift_gender[$value->shift_option_id][$value->code][] = $value->gender;
			}
		}
		$totalMale = $totalFemale = 0;
		$ShiftOptions = TableRegistry::get('ShiftOptions');
		$shiftOptionData = $ShiftOptions->find();
		if(!empty($shiftOptionData)) {
			foreach($shiftOptionData as $key => $value) {
				$genderArray[$value->name]['male_name'] = 'Male';
				$genderArray[$value->name]['male_code'] = 'M';
				$genderArray[$value->name]['male_count'] = count($shift_gender[$value->id]['M']);
				$totalMale = $genderArray[$value->name]['male_count'] + $totalMale;
				$genderArray[$value->name]['female_name'] = 'Female';
				$genderArray[$value->name]['female_code'] = 'F';
				$genderArray[$value->name]['female_count'] = count($shift_gender[$value->id]['F']);
				$totalFemale = $genderArray[$value->name]['female_count'] + $totalFemale;		
			}
		}
		$genderArray['total_gender'] = array('male_name'=> 'Male','male_code'=> 'M','male_count'=> $totalMale, 'female_name'=> 'Female','female_code'=> 'F','female_count'=> $totalFemale);
		
		$data = $area = $locality = $areaAdministrative = $sector = $ownership = $provider = $type = array();
		$areaIndex = $areaAdministrativeIndex = $localityIndex = $sectorIndex = $providerIndex = $ownershipIndex = $typeIndex = NULL;
		
		if(!empty($resultArray)) {
			foreach($resultArray as $key => $result) { 
				if(array_filter($result)) {
					if($key == 1) {
						foreach($result as $key1 => $value1) {
							$data[$key][$key1] = $value1;
							if($key1 === 'area_name'|| $key1 === 'area_code'|| $key1 === 'area_count') {
								if(!empty($value1)) {
									$areaIndex = key($area);
									if(!empty($areaIndex)) {
										$data[$key][$key1] = '';
									}
									$data[$areaIndex][$key1] = $value1;
								} else {
									$area[$key] = $key;
								}
							}
							if($key1 === 'area_administrative_name'|| $key1 === 'area_administrative_code'|| $key1 === 'area_administrative_count') {
								if(!empty($value1)) {
									$areaAdministrativeIndex = key($areaAdministrative);
									if(!empty($areaAdministrativeIndex)) {
										$data[$key][$key1] = '';
									}
									$data[$areaAdministrativeIndex][$key1] = $value1;
								} else {
									$areaAdministrative[$key] = $key;
								}
							}
							if($key1 === 'locality_name'|| $key1 === 'locality_code'|| $key1 === 'locality_count') {
								if(!empty($value1)) {
									$localityIndex = key($locality);
									if(!empty($localityIndex)) {
										$data[$key][$key1] = '';
									}
									$data[$localityIndex][$key1] = $value1;
								} else {
									$locality[$key] = $key;
								}
							}
							if($key1 === 'sector_name'|| $key1 === 'sector_code'|| $key1 === 'sector_count') {
								if(!empty($value1)) {
									$sectorIndex = key($sector);
									if(!empty($sectorIndex)) {
										$data[$key][$key1] = '';
									}
									$data[$sectorIndex][$key1] = $value1;
								} else {
									$sector[$key] = $key;
								}
							}
							if($key1 === 'ownership_name'|| $key1 === 'ownership_code'|| $key1 === 'ownership_count') {
								if(!empty($value1)) {
									$ownershipIndex = key($ownership);
									if(!empty($ownershipIndex)) {
										$data[$key][$key1] = '';
									}
									$data[$ownershipIndex][$key1] = $value1;
								} else {
									$ownership[$key] = $key;
								}
							}
							if($key1 === 'provider_name'|| $key1 === 'provider_code'|| $key1 === 'provider_count') {
								if(!empty($value1)) {
									$providerIndex = key($provider);
									if(!empty($providerIndex)) {
										$data[$key][$key1] = '';
									}
									$data[$providerIndex][$key1] = $value1;
								} else {
									$provider[$key] = $key;
								}
							}
							if($key1 === 'type_name'|| $key1 === 'type_code'|| $key1 === 'type_count') {
								if(!empty($value1)) {
									$typeIndex = key($type);
									if(!empty($typeIndex)) {
										$data[$key][$key1] = '';
									}
									$data[$typeIndex][$key1] = $value1;
								} else {
									$type[$key] = $key;
								}
							}
						} 
						foreach($genderArray as $maleGender) {
							$data[$key][] = $maleGender['male_code'];
							$data[$key][] = $maleGender['male_name'];
							$data[$key][] = $maleGender['male_count'];
						}
					}
					if($key == 2) {
						foreach($result as $key2 => $value2) {
							$data[$key][$key2] = $value2;
							if($key2 === 'sector_name'|| $key2 === 'sector_code'|| $key2 === 'sector_count') {
								if(!empty($value2)) {
									$sectorIndex = key($sector);
									if(!empty($sectorIndex)) {
										$data[$key][$key2] = '';
									}
									$data[$sectorIndex][$key2] = $value2;
								} else {
									$sector[$key] = $key;
								}
							}
							if($key2 === 'area_name'|| $key2 === 'area_code'|| $key2 === 'area_count') {
								if(!empty($value2)) {
									$areaIndex = key($area);
									if(!empty($areaIndex)) {
										$data[$key][$key2] = '';
									}
									$data[$areaIndex][$key2] = $value2;
								} else {
									$area[$key] = $key;
								}
							}
							if($key2 === 'area_administrative_name'|| $key2 === 'area_administrative_code'|| $key2 === 'area_administrative_count') {
								if(!empty($value2)) {
									$areaAdministrativeIndex = key($areaAdministrative);
									if(!empty($areaAdministrativeIndex)) {
										$data[$key][$key2] = '';
									}
									$data[$areaAdministrativeIndex][$key2] = $value2;
								} else {
									$areaAdministrative[$key] = $key;
								}
							}
							if($key2 === 'locality_name'|| $key2 === 'locality_code'|| $key2 === 'locality_count') {
								if(!empty($value2)) {
									$localityIndex = key($locality);
									if(!empty($localityIndex)) {
										$data[$key][$key2] = '';
									}
									$data[$localityIndex][$key2] = $value2;
								} else {
									$locality[$key] = $key;
								}
							}
							if($key2 === 'ownership_name'|| $key2 === 'ownership_code'|| $key2 === 'ownership_count') {
								if(!empty($value2)) {
									$ownershipIndex = key($ownership);
									if(!empty($ownershipIndex)) {
										$data[$key][$key2] = '';
									}
									$data[$ownershipIndex][$key2] = $value2;
								} else {
									$ownership[$key] = $key;
								}
							}
							if($key2 === 'provider_name'|| $key2 === 'provider_code'|| $key2 === 'provider_count') {
								if(!empty($value2)) {
									$providerIndex = key($provider);
									if(!empty($providerIndex)) {
										$data[$key][$key2] = '';
									}
									$data[$providerIndex][$key2] = $value2;
								} else {
									$provider[$key] = $key;
								}
							}
							if($key2 === 'type_name'|| $key2 === 'type_code'|| $key2 === 'type_count') {
								if(!empty($value2)) {
									$typeIndex = key($type);
									if(!empty($typeIndex)) {
										$data[$key][$key2] = '';
									}
									$data[$typeIndex][$key2] = $value2;
								} else {
									$type[$key] = $key;
								}
							}
						} 
						foreach($genderArray as $femaleGender) {
							$data[$key][] = $femaleGender['female_code'];
							$data[$key][] = $femaleGender['female_name'];
							$data[$key][] = $femaleGender['female_count'];
						}
					}
					foreach($result as $key3 => $value3) {
						$data[$key][$key3] = $value3;
						if(($key != 0) && ($key3 === 'area_name'|| $key3 === 'area_code'|| $key3 === 'area_count')) {
							if(!empty($value3)) {
								$areaIndex = key($area);
								if(!empty($areaIndex)) {
									$data[$key][$key3] = '';
								}
								$data[$areaIndex][$key3] = $value3;
							} else {
								$area[$key] = $key;
							}
						}
						if(($key != 0) && $key3 === 'area_administrative_name'|| $key3 === 'area_administrative_code'|| $key3 === 'area_administrative_count') {
							if(!empty($value3)) {
								$areaAdministrativeIndex = key($areaAdministrative);
								if(!empty($areaAdministrativeIndex)) {
									$data[$key][$key3] = '';
								}
								$data[$areaAdministrativeIndex][$key3] = $value3;
							} else {
								$areaAdministrative[$key] = $key;
							}
						}
						if(($key != 0) && $key3 === 'locality_name'|| $key3 === 'locality_code'|| $key3 === 'locality_count') {
							if(!empty($value3)) {
								$localityIndex = key($locality);
								if(!empty($localityIndex)) {
									$data[$key][$key3] = '';
								}
								$data[$localityIndex][$key3] = $value3;
							} else {
								$locality[$key] = $key;
							}
						}
						if(($key != 0) && ($key3 === 'sector_name'|| $key3 === 'sector_code'|| $key3 === 'sector_count')) {
							if(!empty($value3)) {
								$sectorIndex = key($sector);
								if(!empty($sectorIndex)) {
									$data[$key][$key3] = '';
								}
								$data[$sectorIndex][$key3] = $value3;
							} else {
								$sector[$key] = $key;
							}
						}
						if(($key != 0) && ($key3 === 'provider_name'|| $key3 === 'provider_code'|| $key3 === 'provider_count')) {
							if(!empty($value3)) {
								$providerIndex = key($provider);
								if(!empty($providerIndex)) {
									$data[$key][$key3] = '';
								}
								$data[$providerIndex][$key3] = $value3;
							} else {
								$provider[$key] = $key;
							}
						}
						if(($key != 0) && ($key3 === 'ownership_name'|| $key3 === 'ownership_code'|| $key3 === 'ownership_count')) {
							if(!empty($value3)) {
								$ownershipIndex = key($sector);
								if(!empty($ownershipIndex)) {
									$data[$key][$key3] = '';
								}
								$data[$ownershipIndex][$key3] = $value3;
							} else {
								$ownership[$key] = $key;
							}
						}
						if(($key != 0) && $key3 === 'type_name'|| $key3 === 'type_code'|| $key3 === 'type_count') {
							if(!empty($value3)) {
								$typeIndex = key($type);
								if(!empty($typeIndex)) {
									$data[$key][$key3] = '';
								}
								$data[$typeIndex][$key3] = $value3;
							} else {
								$type[$key] = $key;
							}
						}
					}
					unset($area[$areaIndex]);
					unset($areaAdministrative[$areaAdministrativeIndex]);
					unset($locality[$localityIndex]);
					unset($sector[$sectorIndex]);
					unset($provider[$providerIndex]);
					unset($ownership[$ownershipIndex]);
					unset($type[$typeIndex]);
				}
				
			}
		}		
		$finalArray = array();
		if(!empty($data)) {
			foreach($data as $data_key => $data_row) {
				if($data_key === 0) {
					$finalArray[$data_key] = $data_row;
				}
				if(!empty($data_key)) {
					$finalArray[$data_key] = $data_row;
				}
			}
		}
		return $finalArray;
	}

    public function generate($settings = [])
    {
        $writer = $settings['writer'];
        $sheets = new ArrayObject();

        // Event to get the sheets. If no sheet is specified, it will be by default one sheet
        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelBeforeStart'), 'onExcelBeforeStart', [$settings, $sheets], true);

        if (count($sheets->getArrayCopy())==0) {
            $sheets[] = [
                'name' => $this->_table->alias(),
                'table' => $this->_table,
                'query' => $this->_table->find(),
            ];
        }

        $sheetNameArr = [];

        foreach ($sheets as $sheet) {
            $table = $sheet['table'];
            // sheet info added to settings to avoid adding more parameters to event
            $settings['sheet'] = $sheet;
            $this->getFields($table, $settings);
            $fields = $settings['sheet']['fields'];

            $footer = $this->getFooter();
            $query = $sheet['query'];

            $this->dispatchEvent($table, $this->eventKey('onExcelBeforeQuery'), 'onExcelBeforeQuery', [$settings, $query], true);
            $sheetName = $sheet['name'];

            // Check to make sure the string length does not exceed 31 characters
            $sheetName = (strlen($sheetName) > 31) ? substr($sheetName, 0, 27).'....' : $sheetName;

            // Check to make sure that no two sheets has the same name
            $counter = 1;
            $initialLength = 0;
            while (in_array($sheetName, $sheetNameArr)) {
                if ($counter > 1) {
                    $sheetName = substr($sheetName, 0, $initialLength);
                } else {
                    $initialLength = strlen($sheetName);
                }
                if (strlen($sheetName) > 23) {
                    $sheetName = substr($sheetName, 0, 23).'('.$counter++.')';
                } else {
                    $sheetName = $sheetName.'('.$counter++.')';
                }
            }
            $sheetNameArr[] = $sheetName;
            $baseSheetName = $sheetName;

            // if the primary key of the record is given, only generate that record
            if (array_key_exists('id', $settings)) {
                $id = $settings['id'];
                if ($id != 0) {
                    $primaryKey = $table->primaryKey();
                    $query->where([$table->aliasField($primaryKey) => $id]);
                }
            }

            if ($this->config('auto_contain')) {
                $this->contain($query, $fields, $table);
            }

            // To auto include the default fields. Using select will turn off autoFields by default
            // This is set so that the containable data will still be in the array.
            $autoFields = $this->config('autoFields');

            if (!isset($autoFields) || $autoFields == true) {
                $query->autoFields(true);
            }

            $count = $query->count();
            $rowCount = 0;
            $sheetCount = 1;
            $sheetRowCount = 0;
            $percentCount = intval($count / 100);
            $pages = ceil($count / $this->config('limit'));

            // Debugging 
            $pages = 1;

            if (isset($sheet['orientation'])) {
                if ($sheet['orientation'] == 'landscape') {
                    $this->config('orientation', 'landscape');
                } else {
                    $this->config('orientation', 'portrait');
                }
            } elseif ($count == 1) {
                $this->config('orientation', 'portrait');
            }

            $this->dispatchEvent($table, $this->eventKey('onExcelStartSheet'), 'onExcelStartSheet', [$settings, $count], true);
            $this->onEvent($table, $this->eventKey('onExcelBeforeWrite'), 'onExcelBeforeWrite');
            if ($this->config('orientation') == 'landscape') {
                $headerRow = [];
                $headerStyle = [];
                $headerFormat = [];

                // Handling of Group field for merging cells for first 2 row
                $groupList = Hash::extract($fields, '{n}.group');
                $hasGroupRow = (!empty($groupList));

                if ($hasGroupRow) {
                    $subjectsHeaderRow = [];
                    $subjectsColWidth = [];
                    $groupStartingIndex = 0;
                    $groupName = '';
                    $subjectHeaderstyle = ['halign' => 'center'];

                    foreach ($fields as $index => $attr) {
                        $subjectsHeaderRow[$index] = "";

                        if (array_key_exists('group', $attr)) {
                            if ($groupName !== $attr['group']) {
                                $groupStartingIndex = $index;
                                $groupName = $attr['group'];
                            }

                            $groupKey = $groupName . $groupStartingIndex;

                            if (!array_key_exists($groupKey, $subjectsColWidth)) {
                                $subjectsColWidth[$groupKey] = [];
                                $subjectsColWidth[$groupKey]['start_col'] = $index;
                                $subjectsHeaderRow[$index]  = $attr['group'];
                            }

                            $subjectsColWidth[$groupKey]['end_col'] = $index;

                        } else {
                            $groupName = '';
                        }
                    }

                    $writer->writeSheetRow($sheetName, $subjectsHeaderRow, $subjectHeaderstyle);

                    foreach ($subjectsColWidth as $obj) {
                        $writer->markMergedCell($sheetName, $start_row=0, $start_col=$obj['start_col'], $end_row=0, $end_col=$obj['end_col']);
                    }
                }
                // End of handling of Group field for merging cells

                foreach ($fields as $attr) {
                    $headerRow[] = $attr['label'];
                    $headerStyle[] = isset($attr['style']) ? $attr['style'] : [];
                    $headerFormat[] = isset($attr['formatting']) ? $attr['formatting'] : 'GENERAL';
                }

                // Any additional custom headers that require to be appended on the right side of the sheet
                // Header column count must be more than the additional data columns
                if (isset($sheet['additionalHeader'])) {
                    $headerRow = array_merge($headerRow, $sheet['additionalHeader']);
                }

				$row1 = array("Area Education"," "," ","Area Administrative"," "," ","Locality"," "," ","Type"," "," ","Ownership"," "," ","Sector"," "," ","Provider"," "," ","First Shift Gender"," "," ","Second Shift Gender"," "," ","Third Shift Gender"," "," ","Fourth Shift Gender"," "," ","Fifth Shift Gender"," "," ","Total Gender");

                $writer->writeSheetHeader($sheetName, $headerFormat, true);
				
				$j = 0; //start column
				for ($i=0; $i<13; $i++) {
					$writer->markMergedCell($sheetName, $start_row = 0, $start_col = $j, $end_row = 0, $end_col = $j+2);  //merge cells
					$j+=3;
				}

                $writer->writeSheetRow($sheetName, $row1, $headerStyle);

                $this->dispatchEvent($table, $this->eventKey('onExcelAfterHeader'), 'onExcelAfterHeader', [$settings], true);

                // process every page based on the limit
                for ($pageNo=0; $pageNo<$pages; $pageNo++) {
                    $resultSet = $query
                    ->limit($this->config('limit'))
                    ->page($pageNo+1)
                    ->all();

                    // Data to be appended on the right of spreadsheet
                    $additionalRows = [];
                    if (isset($sheet['additionalData'])) {
                        $additionalRows = $sheet['additionalData'];
                    }

                    // process each row based on the result set
                    foreach ($resultSet as $entity) {
                        if ($sheetRowCount >= $this->config('sheet_limit')) {
                            $sheetCount++;
                            $sheetName = $baseSheetName . '_' . $sheetCount;

                            // rewrite header into new sheet
                            $writer->writeSheetRow($sheetName, $headerRow, $headerStyle);

                            $sheetRowCount= 0;
                        }

                        $settings['entity'] = $entity;

                        $row = [];
                        $rowStyle = [];
                        foreach ($fields as $attr) {
                            $rowDataWithStyle = $this->getValue($entity, $table, $attr);
                            $row[] = $rowDataWithStyle['rowData'];
                            $rowStyle[] = $rowDataWithStyle['style'];
                        }

                        $sheetRowCount++;
                        $rowCount++;
                        $event = $this->dispatchEvent($table, $this->eventKey('onExcelBeforeWrite'), null, [$settings, $rowCount, $percentCount]);
                        if (!$event->result) {
                            $writer->writeSheetRow($sheetName, $row, $rowStyle);
                        }
                    }
                }
            } else {
                $entity = $query->first();
                foreach ($fields as $attr) {
                    $row = [$attr['label']];
                    $rowStyle = [[]];
                    $rowDataWithStyle = $this->getValue($entity, $table, $attr);
                    $row[] = $rowDataWithStyle['rowData'];
                    $rowStyle[] = $rowDataWithStyle['style'];
                    $writer->writeSheetRow($sheetName, $row, $rowStyle);
                }

                // Any additional custom headers that require to be appended on the left column of the sheet
                $additionalHeader = [];
                if (isset($sheet['additionalHeader'])) {
                    $additionalHeader = $sheet['additionalHeader'];
                }
                // Data to be appended on the right column of spreadsheet
                $additionalRows = [];
                if (isset($sheet['additionalData'])) {
                    $additionalRows = $sheet['additionalData'];
                }

                for ($i = 0; $i < count($additionalHeader); $i++) {
                    $row = [$additionalHeader[$i]];
                    $row[] = $additionalRows[$i];
                    $rowStyle = [[], []];
                    $writer->writeSheetRow($sheetName, $row, $rowStyle);
                }
                $rowCount++;
            }
            $writer->writeSheetRow($sheetName, ['']);
            $writer->writeSheetRow($sheetName, $footer);
            $this->dispatchEvent($table, $this->eventKey('onExcelEndSheet'), 'onExcelEndSheet', [$settings, $rowCount], true);
        }
    }

    private function getFields($table, $settings)
    {
        $schema = $table->schema();
        $columns = $schema->columns();
        $excludes = $this->config('excludes');

        if (!is_array($table->primaryKey())) { //if not composite key
            $excludes[] = $table->primaryKey();
        }

        $fields = new ArrayObject();
        $module = $table->alias();
        $language = I18n::locale();
        $excludedTypes = ['binary'];
        $columns = array_diff($columns, $excludes);

        foreach ($columns as $col) {
            $field = $schema->column($col);
            if (!in_array($field['type'], $excludedTypes)) {
                $label = $table->aliasField($col);

                $event = $this->dispatchEvent($table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $col, $language], true);
                if (strlen($event->result)) {
                    $label = $event->result;
                }

                $fields[] = [
                    'key' => $table->aliasField($col),
                    'field' => $col,
                    'type' => $field['type'],
                    'label' => $label,
                    'style' => [],
                    'formatting' => 'GENERAL'
                ];
            }
        }
        // Event to add or modify the fields to fetch from the table
        $event = $this->dispatchEvent($table, $this->eventKey('onExcelUpdateFields'), 'onExcelUpdateFields', [$settings, $fields], true);

        $newFields = [];
        foreach ($fields->getArrayCopy() as $field) {
            if (empty($field['label'])) {
                $key = explode('.', $field['key']);
                $module = $key[0];
                $column = $key[1];
                // Redispatch get label
                $event = $this->dispatchEvent($table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $column, $language], true);
                if (strlen($event->result)) {
                    $field['label'] = $event->result;
                }
            }
            $newFields[] = $field;
        }

        // Replace the ArrayObject with the new fields
        $fields->exchangeArray($newFields);

        // Add the fields into the sheet
        $settings['sheet']['fields'] = $fields;
    }

    private function getFooter()
    {
        $footer = [__("Report Generated") . ": "  . date("Y-m-d H:i:s")];
        return $footer;
    }

    private function getValue($entity, $table, $attr)
    {
        $value = '';
        $field = $attr['field'];
        $type = $attr['type'];
        $style = [];

        if (!empty($entity)) {
            if (!in_array($type, ['string', 'integer', 'decimal', 'text'])) {
                $method = 'onExcelRender' . Inflector::camelize($type);
                if (!$this->eventMap($method)) {
                    $event = $this->dispatchEvent($table, $this->eventKey($method), $method, [$entity, $attr]);
                } else {
                    $event = $this->dispatchEvent($table, $this->eventKey($method), null, [$entity, $attr]);
                }
                if ($event->result) {
                    $returnedResult = $event->result;
                    if (is_array($returnedResult)) {
                        $value = isset($returnedResult['value']) ? $returnedResult['value'] : '';
                        $style = isset($returnedResult['style']) ? $returnedResult['style'] : [];
                    } else {
                        $value = $returnedResult;
                    }
                }
            } else {
                $method = 'onExcelGet' . Inflector::camelize($field);
                $event = $this->dispatchEvent($table, $this->eventKey($method), $method, [$entity], true);
                if ($event->result) {
                    $returnedResult = $event->result;
                    if (is_array($returnedResult)) {
                        $value = isset($returnedResult['value']) ? $returnedResult['value'] : '';
                        $style = isset($returnedResult['style']) ? $returnedResult['style'] : [];
                    } else {
                        $value = $returnedResult;
                    }
                } elseif ($entity->has($field)) {
                    if ($this->isForeignKey($table, $field)) {
                        $associatedField = $this->getAssociatedKey($table, $field);
                        if ($entity->has($associatedField)) {
                            $value = $entity->{$associatedField}->name;
                        }
                    } else {
                        $value = $entity->{$field};
                    }
                }
            }
        }

        $specialCharacters = ['=', '@'];
        $firstCharacter = substr($value, 0, 1);
        if (in_array($firstCharacter, $specialCharacters)) {
            // append single quote to escape special characters
            $value = "'" . $value;
        }

        return ['rowData' => __($value), 'style' => $style];
    }

    private function isForeignKey($table, $field)
    {
        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAssociatedTable($table, $field)
    {
        $relatedModel = null;

        foreach ($table->associations() as $assoc) {
            if ($assoc->type() == 'manyToOne') { // belongsTo associations
                if ($field === $assoc->foreignKey()) {
                    $relatedModel = $assoc;
                    break;
                }
            }
        }
        return $relatedModel;
    }

    public function getAssociatedKey($table, $field)
    {
        $tableObj = $this->getAssociatedTable($table, $field);
        $key = null;
        if (is_object($tableObj)) {
            $key = Inflector::underscore(Inflector::singularize($tableObj->alias()));
        }
        return $key;
    }

    private function contain(Query $query, $fields, $table)
    {
        $contain = [];
        foreach ($fields as $attr) {
            $field = $attr['field'];
            if ($this->isForeignKey($table, $field)) {
                $contain[] = $this->getAssociatedTable($table, $field)->alias();
            }
        }
        $query->contain($contain);
    }

    private function download($path)
    {
        $filename = basename($path);

        header("Pragma: public", true);
        header("Expires: 0"); // set expiration time
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=".$filename);
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($path));
        echo file_get_contents($path);
    }

    private function purge($path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 0];

        if ($this->isCAv4()) {
            $events['ControllerAction.Model.excel'] = 'excelV4';
            $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
        }
        return $events;
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $action = $this->_table->action;
        if (in_array($action, $this->config('pages'))) {
            $toolbarButtons = isset($extra['toolbarButtons']) ? $extra['toolbarButtons'] : [];
            $toolbarAttr = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Export')
            ];

            $toolbarButtons['export'] = [
                'type' => 'button',
                'label' => '<i class="fa kd-export"></i>',
                'attr' => $toolbarAttr,
                'url' => ''
            ];

            $url = $this->_table->url($action);
            $url[0] = 'excel';
            $toolbarButtons['export']['url'] = $url;
            $extra['toolbarButtons'] = $toolbarButtons;
        }
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if ($buttons->offsetExists('view')) {
            $export = $buttons['view'];
            $export['type'] = 'button';
            $export['label'] = '<i class="fa kd-export"></i>';
            $export['attr'] = $attr;
            $export['attr']['title'] = __('Export');

            if ($isFromModel) {
                $export['url'][0] = 'excel';
            } else {
                $export['url']['action'] = 'excel';
            }

            $pages = $this->config('pages');
            if (in_array($action, $pages)) {
                $toolbarButtons['export'] = $export;
            }
        } elseif ($buttons->offsetExists('back')) {
            $export = $buttons['back'];
            $export['type'] = 'button';
            $export['label'] = '<i class="fa kd-export"></i>';
            $export['attr'] = $attr;
            $export['attr']['title'] = __('Export');

            if ($isFromModel) {
                $export['url'][0] = 'excel';
            } else {
                $export['url']['action'] = 'excel';
            }

            $pages = $this->config('pages');
            if ($pages != false) {
                if (in_array($action, $pages)) {
                    $toolbarButtons['export'] = $export;
                }
            }
        }
    }
}
