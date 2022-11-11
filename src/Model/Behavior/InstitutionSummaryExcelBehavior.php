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
use Cake\Log\Log;


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
		
		$generate = function ($settings) {
            $generate = $this->generate($settings);
        };
		
        $_settings['writer'] = $writer;

        $event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGenerate'), 'onExcelGenerate', [$_settings]);
       
        $generate($_settings);

		// institution_providers institution_types
		$InstitutionProvidersTable = TableRegistry::get('institution_providers');
		$InstitutionProviders = $InstitutionProvidersTable->find('all')->toArray();
		$providerArr = [];
		foreach($InstitutionProviders as $keyy => $InstitutionProvider){
			$providerArr[$keyy] = $InstitutionProvider->name;
		}
		$labelArray = $providerArr;
		//$labelArray = array("area_education","area_administrative","locality","type","ownership","sector","provider","first_shift_gender","second_shift_gender","third_shift_gender","fourth_shift_gender","total_gender");
		$headerRow1[] = 'Area Education';

		$InstitutionTypesTable = TableRegistry::get('institution_types');
		$InstitutionTypes = $InstitutionTypesTable->find('all')->toArray();

		foreach($labelArray as $label) {
			
			foreach($InstitutionTypes as $kkey=>$its){
				if($kkey == 0){
				$headerRow2[] = $this->getFields($this->_table, $settings, $label);
				
				}else{
					$headerRow2[] = '';
				}	
			}
		}
		
		$ShiftOptionTable = TableRegistry::get('shift_options');
		$ShiftOptions = $ShiftOptionTable->find('all')->toArray();
		$shiftArr = [];
		foreach($ShiftOptions as $keyy => $ShiftOption){
			$shiftArr[$keyy] = $ShiftOption->name;
		}
		foreach($shiftArr as $shiftObj) {
			$headerRow3[] = $this->getFields($this->_table, $settings, $shiftObj);
			$headerRow3[] = ' ';
		}
		$headerRow4[] = 'Total';
		$headerRow = array_merge($headerRow1,$headerRow2,$headerRow3,$headerRow4);
		
		$requestData = json_decode($settings['process']['params']);
		
		if($requestData->area_level_id == 1){
			$data = $this->getCountryData($settings);
		}elseif($requestData->area_level_id == 2){
			$data = $this->getReagionData($settings);
		}elseif($requestData->area_level_id == 3){
			$AreaLevelTable = TableRegistry::get('area_levels');
			$AreaLevel = $AreaLevelTable->find('all',['conditions'=>['id'=>3]])->first();
		
			if($AreaLevel->name == 'Atoll'){
				$data = $this->getReagionAtollData($settings);
			}else{
				$data = $this->getDistrictData($settings);
			}
			
		}else{
			$data = $this->getDistrictData($settings);
		}
		
		$InstitutionTypesTable = TableRegistry::get('institution_types');
		$InstitutionTypesCount = $InstitutionTypesTable->find('all')->count();
		
		$writer->writeSheetRow('Summary', $headerRow);
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
	

	public function getCountryData($settings)
    {
	$Institutions = TableRegistry::get('Institutions');
    	$requestData = json_decode($settings['process']['params']);
    	$institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
	$areaLevelId = $requestData->area_level_id;
	$academic_period_id = $requestData->academic_period_id;
	
	$AcademicPriodT = TableRegistry::get('academic_periods'); 
	$AcademicPeriodDataforYear = $AcademicPriodT->find('all')->where(['id' => $academic_period_id])->first();
	$startDate = $AcademicPeriodDataforYear->start_date->format('Y-m-d');;
	$endDate = $AcademicPeriodDataforYear->end_date->format('Y-m-d');;
		
	$currDate = date('Y-m-d');
	if($currDate >= $startDate && $currDate <= $endDate){
		$stuStatussArr = [1];
	}else{
		$stuStatussArr = [1,6,7,8];
	}
	
		
	$AreaLvlT = TableRegistry::get('area_levels'); 
	$AreaLvlData = $AreaLvlT->find('all')->where(['id' => $areaLevelId])->first();
        $AreaT = TableRegistry::get('areas');       
	$ShiftOptionTable = TableRegistry::get('shift_options');
	$InsStudentTable = TableRegistry::get('institution_students');
	$InsStudentTable1 = TableRegistry::get('institution_students');             
        //Level-1
        $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['area_level_id' => $areaLevelId])->toArray();
        $childArea =[];
        $childAreaMain = [];
        $childArea3 = [];
        $childArea4 = [];
        foreach($AreaData as $kkk =>$AreaData11 ){
            $childArea[$kkk] = $AreaData11->id;
        }
        //level-2
        foreach($childArea as $kyy =>$AreaDatal2 ){
            $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
            foreach($AreaDatas as $ky =>$AreaDatal22 ){
                $childAreaMain[$kyy.$ky] = $AreaDatal22->id;
            }
        }
        //level-3
        if(!empty($childAreaMain)){
            foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                foreach($AreaDatass as $ky =>$AreaDatal222 ){
                    $childArea3[$kyy.$ky] = $AreaDatal222->id;
                }
            }
        }   
        //level-4
        if(!empty($childAreaMain)){
            foreach($childArea3 as $kyy =>$AreaDatal4 ){
                $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                    $childArea4[$kyy.$ky] = $AreaDatal44->id;
                }
            }
        }
        $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
        array_push($mergeArr,$areaId);
        $mergeArr = array_unique($mergeArr);
        $finalIds = implode(',',$mergeArr);
        $finalIds = explode(',',$finalIds);
		
        $where = [];
        if ($areaId != -1) {
            $where[$Institutions->aliasField('area_id in')] = $finalIds;
        }
        /*if ($academic_period_id) {
            $where[$Institutions->aliasField('academic_period_id')] = $academic_period_id;
        }*/
	if ($institution_id != 0) {
            $where[$Institutions->aliasField('id')] = $institution_id;
        }
		$institutionData = $Institutions->find()
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
                    ])
					->leftJoin(
					['Ownerships' => 'institution_ownerships'],
					[
						'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
					]
					)
					->leftJoin(
					['Sectors' => 'institution_sectors'],
					[
						'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
					]
					)
					->leftJoin(
					['Areas' => 'areas'],
					[
						'Areas.id = '. $Institutions->aliasField('area_id')
					]
					)
					->leftJoin(
					['AreaAdministratives' => 'area_administratives'],
					[
						'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
					]
					)
					->leftJoin(
					['Providers' => 'institution_providers'],
					[
						'Providers.id = '. $Institutions->aliasField('institution_provider_id')
					]
					)
					->leftJoin(
					['Types' => 'institution_types'],
					[
						'Types.id = '. $Institutions->aliasField('institution_type_id')
					]
					)
					->leftJoin(
					['Localities' => 'institution_localities'],
					[
						'Localities.id = '. $Institutions->aliasField('institution_locality_id')
					]
					)
					->where([$where])
					;
					$areaArray = $sectorArray = $sectorData = $ownershipArray = $localityArray = $typeArray = $providerArray = $areaAdministrativeArray = [];	
					$resultArray = array();
					$i = 0;
					foreach($institutionData as $key => $value) { 
						if($i == 0) { 
							$InstitutionTypesTable = TableRegistry::get('institution_types');
							$InstitutionTypes = $InstitutionTypesTable->find('all')->toArray();
							$InstitutionProvidersTable = TableRegistry::get('institution_providers');
							$InstitutionProviders = $InstitutionProvidersTable->find('all')->toArray();
							$resultArray[0][] = $AreaLvlData->name;
							$keyy = 0;
							$ki = 1;
							foreach($InstitutionProviders as $keyy => $InstitutionProvider){ 
								foreach($InstitutionTypes as $ki => $InstitutionType){ 

						//**************************************************************************** */
						//********************  Section For Country  Start     *****************************
						//**************************************************************************** */
						// $institut = $Institutions->find()
						// 			->select([
						// 				'ownership_name' => 'Ownerships.name',
						// 				'ownership_id' => 'Ownerships.id',
						// 				'sector_name' => 'Sectors.name',
						// 				'sector_id' => 'Sectors.id',
						// 				'provider_name' => 'Providers.name',
						// 				'provider_id' => 'Providers.id',
						// 				'type_name' => 'Types.name',
						// 				'type_id' => 'Types.id',
						// 				'area_id' => 'Areas.id',
						// 				'area_name' => 'Areas.name',
						// 				'area_code' => 'Areas.code',
						// 				'area_administrative_name' => 'AreaAdministratives.name',
						// 				'area_administrative_id' => 'AreaAdministratives.id',
						// 				'area_administrative_code' => 'AreaAdministratives.code',
						// 				'locality_name' => 'Localities.name',
						// 				'locality_id' => 'Localities.id'
						// 			])
						// 			->leftJoin(
						// 			['Ownerships' => 'institution_ownerships'],
						// 			[
						// 				'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
						// 			]
						// 			)
						// 			->leftJoin(
						// 			['Sectors' => 'institution_sectors'],
						// 			[
						// 				'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
						// 			]
						// 			)
						// 			->leftJoin(
						// 			['Areas' => 'areas'],
						// 			[
						// 				'Areas.id = '. $Institutions->aliasField('area_id')
						// 			]
						// 			)
						// 			->leftJoin(
						// 			['AreaAdministratives' => 'area_administratives'],
						// 			[
						// 				'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
						// 			]
						// 			)
						// 			->leftJoin(
						// 			['Providers' => 'institution_providers'],
						// 			[
						// 				'Providers.id = '. $Institutions->aliasField('institution_provider_id')
						// 			]
						// 			)
						// 			->leftJoin(
						// 			['Types' => 'institution_types'],
						// 			[
						// 				'Types.id = '. $Institutions->aliasField('institution_type_id')
						// 			]
						// 			)
						// 			->leftJoin(
						// 			['Localities' => 'institution_localities'],
						// 			[
						// 				'Localities.id = '. $Institutions->aliasField('institution_locality_id')
						// 			]
						// 			)
						// 			->where(['institution_provider_id'=>$InstitutionProvider->id,'institution_type_id'=>$InstitutionType->id,$where])
						// 			;

								
									$institut =	$InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => $InsStudentTable->aliasField('student_id'),
									'academic_period_id' => $InsStudentTable->aliasField('academic_period_id'),
									'institution_id' => $InsStudentTable->aliasField('institution_id'),
									'student_status_id' => $InsStudentTable->aliasField('student_status_id'),
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)

									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)
									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										
								
								->where(['Institutions.institution_provider_id'=>$InstitutionProvider->id,'Institutions.institution_type_id'=>$InstitutionType->id ,'student_status_id in'=>$stuStatussArr,'academic_period_id'=>$academic_period_id])
								->distinct([$InsStudentTable->aliasField('student_id')])
								//->count()
								;
						
						//**************************************************************************** */
						//********************  Section For Counrty  END     *****************************
						//**************************************************************************** */
						$resultArray[$key][] = $InstitutionType->name;
						$resultArray['countryData'][]= $institut->count();
						$ki++;
					}
					
				}
				$totalValue = array_sum($resultArray['countryData']);
				//echo "<pre>";print_r($totalValue);die;
				$ShiftOptionTable = TableRegistry::get('shift_options');
				$InsStudentTable = TableRegistry::get('institution_students');
				$ShiftOptions = $ShiftOptionTable->find('all')->toArray();
				$shiftArr = [];
				$shiftArrResult = [];
				foreach($ShiftOptions as $keyy => $ShiftOption){  
					$shiftArr[$keyy] = 'male';
					$shiftArrResult[$keyy] = $InsStudentTable->find()
					->select([
						//'id','institution_id','student_id'
						'student_id' => 'student_id',
						'academic_period_id' => 'academic_period_id',
						'institution_id' => 'institution_id',
						'student_status_id' => 'student_status_id',
						'gender_id' => 'Students.gender_id',
					])
					
					->leftJoin(
						['Students' => 'security_users'],
						[
							'Students.id = '. $InsStudentTable->aliasField('student_id')
						]
						)

						->leftJoin(
							['Institutions' => 'institutions'],
							[
								'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
							]
							)

							->leftJoin(
								['InsShifts' => 'institution_shifts'],
								[
									'InsShifts.institution_id = '. $InsStudentTable->aliasField('institution_id')
								]
								)
					
					->where([$InsStudentTable->aliasField('institution_id in') => $finalIds,$InsStudentTable->aliasField('academic_period_id')=> $academic_period_id,'Students.gender_id'=>1,'InsShifts.shift_option_id'=>$ShiftOption->id])
					->count()
					;
					$shiftArr[$keyy.'1'] = 'female';
					$shiftArrResult[$keyy.'1'] = $InsStudentTable->find()
					->select([
						//'id','institution_id','student_id'
						'student_id' => 'student_id',
						'academic_period_id' => 'academic_period_id',
						'institution_id' => 'institution_id',
						'student_status_id' => 'student_status_id',
						'gender_id' => 'Students.gender_id',
					])
					
					->leftJoin(
						['Students' => 'security_users'],
						[
							'Students.id = '. $InsStudentTable->aliasField('student_id')
						]
						)
						->leftJoin(
							['Institutions' => 'institutions'],
							[
								'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
							]
							)

							->leftJoin(
								['InsShifts' => 'institution_shifts'],
								[
									'InsShifts.institution_id = '. $InsStudentTable1->aliasField('institution_id')
								]
								)
								->where([$InsStudentTable->aliasField('institution_id in') => $finalIds,$InsStudentTable->aliasField('academic_period_id')=> $academic_period_id,'Students.gender_id'=>2,'InsShifts.shift_option_id'=>$ShiftOption->id])
					->count()
					;
				}
				$arr = ['0'=>'male','1'=>'female'];
				array_merge($resultArray[0],$shiftArr);
				array_merge($resultArray[1],$shiftArrResult);
				$aa =(array_merge($resultArray[0],$shiftArr));//die;
				$resultArray[0] = $aa;
				$aa1 =(array_merge($resultArray['countryData'],$shiftArrResult));//die;
				$resultArray['countryData'] = $aa1;
				//echo "<pre>";print_r($AreaLvlData);die;
				array_unshift($resultArray['countryData'] , $AreaLvlData->name);
			    $AreasData1 = $AreaT->find('all',['conditions'=>['area_level_id'=> $areaLevelId]])->toArray();
				foreach($AreasData1 as $KEYY => $Area_insss){ 
					$resultArray[$KEYY+1] = $resultArray['DistrictData'];
					array_unshift($resultArray[$KEYY+1], $Area_ins->name);
				}
				
			} else {  

				if(!empty($value->area_id)) {
					//if (!in_array($value->area_id, $areaArray)) {
						$resultArray[$key]['area_name'] = $value->area_name; 
					$areaArray[] = $value->area_id;
				} else {
					$resultArray[$key]['area_name'] = '';
				}
			}
			$i++;	
		}
		
		$shift_gender = array();
		$totalMale = $totalFemale = 0;
		$genderArray = [];
		$ShiftOptions = TableRegistry::get('ShiftOptions');
		$shiftOptionData = $ShiftOptions->find();
		
		$data = $area = $locality = $areaAdministrative = $sector = $ownership = $provider = $type = array();
		$areaIndex = $areaAdministrativeIndex = $localityIndex = $sectorIndex = $providerIndex = $ownershipIndex = $typeIndex = NULL;
		
		if(!empty($resultArray)) {
			foreach($resultArray as $key => $result) { //echo "<pre>";print_r($result);die;
				if(array_filter($result)) {
					
					foreach($result as $key3 => $value3) { //echo "Key3";
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
		$AreaLevelT = TableRegistry::get('area_levels');
		$AreaT = TableRegistry::get('areas');
		$AreaLevel = $AreaLevelT->find('all',['conditions'=>['id'=>$areaLevelId]])->first();
		if(!empty($data)) {
			foreach($data as $data_keyy => $data_roww) { //echo "<pre>";print_r($arrayy);die;
				if($data_keyy === 0) { 
					$finalArray[$data_keyy] = $data_roww;
					$finalArray[$data_keyy+1] = $data['countryData'];
				}else{
					unset($data_row);
				}
			}
		}
		array_push($finalArray[1],$totalValue);
		//echo "<pre>";print_r($finalArray);die;
		return $finalArray;
		
	}
	
	public function getReagionData($settings)
    {
	$Institutions = TableRegistry::get('Institutions');
    	$requestData = json_decode($settings['process']['params']);
    	$institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
		$areaLevelId = $requestData->area_level_id;
		$academic_period_id = $requestData->academic_period_id;
		
		$AcademicPriodT = TableRegistry::get('academic_periods'); 
	$AcademicPeriodDataforYear = $AcademicPriodT->find('all')->where(['id' => $academic_period_id])->first();
	$startDate = $AcademicPeriodDataforYear->start_date->format('Y-m-d');;
	$endDate = $AcademicPeriodDataforYear->end_date->format('Y-m-d');;
		
	$currDate = date('Y-m-d');
	if($currDate >= $startDate && $currDate <= $endDate){
		$stuStatussArr = [1];
	}else{
		$stuStatussArr = [1,6,7,8];
	}
		
		
	$AreaLvlT = TableRegistry::get('area_levels'); 
	$AreaLvlData = $AreaLvlT->find('all')->where(['id' => $areaLevelId])->first();
        $AreaT = TableRegistry::get('areas');  
		$ShiftOptionTable = TableRegistry::get('shift_options');
		$InsStudentTable = TableRegistry::get('institution_students');
		$InsStudentTable1 = TableRegistry::get('institution_students');                  
        //Level-1
        $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['area_level_id' => $areaLevelId])->toArray();
        $childArea =[];
        $childAreaMain = [];
        $childArea3 = [];
        $childArea4 = [];
        foreach($AreaData as $kkk =>$AreaData11 ){
            $childArea[$kkk] = $AreaData11->id;
        }
        //level-2
        foreach($childArea as $kyy =>$AreaDatal2 ){
            $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
            foreach($AreaDatas as $ky =>$AreaDatal22 ){
                $childAreaMain[$kyy.$ky] = $AreaDatal22->id;
            }
        }
        //level-3
        if(!empty($childAreaMain)){
            foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                foreach($AreaDatass as $ky =>$AreaDatal222 ){
                    $childArea3[$kyy.$ky] = $AreaDatal222->id;
                }
            }
        }   
        //level-4
        if(!empty($childAreaMain)){
            foreach($childArea3 as $kyy =>$AreaDatal4 ){
                $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                    $childArea4[$kyy.$ky] = $AreaDatal44->id;
                }
            }
        }
        $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
        array_push($mergeArr,$areaId);
        $mergeArr = array_unique($mergeArr);
        $finalIds = implode(',',$mergeArr);
        $finalIds = explode(',',$finalIds);
		
		//echo "<pre>";print_r($requestData);die;
		
        $where = [];
        if ($areaId != -1) {
            $where[$Institutions->aliasField('area_id in')] = $finalIds;
        }
		if ($institution_id != 0) {
            $where[$Institutions->aliasField('id')] = $institution_id;
        }
		$institutionData = $Institutions->find()
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
                    ])
					->leftJoin(
					['Ownerships' => 'institution_ownerships'],
					[
						'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
					]
					)
					->leftJoin(
					['Sectors' => 'institution_sectors'],
					[
						'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
					]
					)
					->leftJoin(
					['Areas' => 'areas'],
					[
						'Areas.id = '. $Institutions->aliasField('area_id')
					]
					)
					->leftJoin(
					['AreaAdministratives' => 'area_administratives'],
					[
						'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
					]
					)
					->leftJoin(
					['Providers' => 'institution_providers'],
					[
						'Providers.id = '. $Institutions->aliasField('institution_provider_id')
					]
					)
					->leftJoin(
					['Types' => 'institution_types'],
					[
						'Types.id = '. $Institutions->aliasField('institution_type_id')
					]
					)
					->leftJoin(
					['Localities' => 'institution_localities'],
					[
						'Localities.id = '. $Institutions->aliasField('institution_locality_id')
					]
					)
					->where([$where])
					;
					$areaArray = $sectorArray = $sectorData = $ownershipArray = $localityArray = $typeArray = $providerArray = $areaAdministrativeArray = [];	
					$resultArray = array();
					$resultArray1 = array();
					$i = 0;
					foreach($institutionData as $key => $value) { 
						if($i == 0) {
							$InstitutionTypesTable = TableRegistry::get('institution_types');
							$InstitutionTypes = $InstitutionTypesTable->find('all')->toArray();
							$InstitutionTypesCount = $InstitutionTypesTable->find('all')->count();
							$InstitutionProvidersTable = TableRegistry::get('institution_providers');
							$InstitutionProviders = $InstitutionProvidersTable->find('all')->toArray();
							$InstitutionProvidersCount = $InstitutionProvidersTable->find('all')->count();
							$totalCount = $InstitutionProvidersCount*$InstitutionTypesCount;
							
							

							$resultArray[0][] = $AreaLvlData->name;
							$keyy = 0;
							$ki = 1;


							$AreasData1 = $AreaT->find('all',['conditions'=>['area_level_id'=> $areaLevelId]])->toArray();
							$abcArr =[];
							foreach($AreasData1 as $KEYY => $Area_insss){

								
								//Level-1
								$areaparentIds = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $Area_insss->id])->toArray();
								$childArea1 =[];
								$childAreaMain2 = [];
								$childArea33 = [];
								$childArea44 = [];
								foreach($areaparentIds as $kkk =>$AreaData111 ){
									$childArea1[$kkk] = $AreaData111->id;
								}
								//level-2
								foreach($childArea1 as $kyy =>$AreaDatal21 ){
									$AreaDatas11 = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal21])->toArray();
									
									
									foreach($AreaDatas11 as $ky =>$AreaDatal223 ){
										$childAreaMain2[$kyy.$ky] = $AreaDatal223->id;
									}
									
								}
								//echo "<pre>";print_r($childAreaMain2);die;
								//level-3
								if(!empty($childAreaMain2)){
									foreach($childAreaMain as $kyy =>$AreaDatal32 ){
										$AreaDatass11 = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal32])->toArray();
										foreach($AreaDatass11 as $ky =>$AreaDatal222 ){
											$childArea33[$kyy.$ky] = $AreaDatal222->id;
										}
									}
								}   
								/*//level-4
								if(!empty($childAreaMain2)){
									foreach($childArea33 as $kyy =>$AreaDatal4 ){
										$AreaDatasss11 = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
										foreach($AreaDatasss11 as $ky =>$AreaDatal44 ){
											$childArea44[$kyy.$ky] = $AreaDatal44->id;
										}
									}
								}*/
								/*$mergeArr1 = array_merge($childArea1,$childAreaMain2,$childArea33);
								array_push($mergeArr1,$areaId);
								$mergeArr1 = array_unique($mergeArr1);
								$finalIds1 = implode(',',$mergeArr1);
								$finalIds1 = explode(',',$finalIds1);*/
								$abcArr[$KEYY] = $finalIds1;
								
								//Log::write('debug', $finalIds1);
								//echo "qwerty"; echo "<pre>";print_r($childArea1);die;

							foreach($InstitutionProviders as $keyy => $InstitutionProvider){ 
								foreach($InstitutionTypes as $ki => $InstitutionType){ 

									//**************************************************************************** */
									//********************  Section For Country  Start     *****************************
									//**************************************************************************** */


									$institut1 =	$InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => $InsStudentTable->aliasField('student_id'),
									'academic_period_id' => $InsStudentTable->aliasField('academic_period_id'),
									'institution_id' => $InsStudentTable->aliasField('institution_id'),
									'student_status_id' => $InsStudentTable->aliasField('student_status_id'),
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)

									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										
								
								->where(['Institutions.institution_provider_id'=>$InstitutionProvider->id,'Institutions.institution_type_id'=>$InstitutionType->id,'Institutions.area_id in' => $childAreaMain2,'student_status_id in'=>$stuStatussArr,'academic_period_id'=>$academic_period_id,$where])
								->group([$InsStudentTable->aliasField('student_id')])
								//->count()
								;
//echo "<pre>";print_r($institut1->count());die;

									// $institut = $Institutions->find()
									// 			->select([
									// 				'ownership_name' => 'Ownerships.name',
									// 				'ownership_id' => 'Ownerships.id',
									// 				'sector_name' => 'Sectors.name',
									// 				'sector_id' => 'Sectors.id',
									// 				'provider_name' => 'Providers.name',
									// 				'provider_id' => 'Providers.id',
									// 				'type_name' => 'Types.name',
									// 				'type_id' => 'Types.id',
									// 				'area_id' => 'Areas.id',
									// 				'area_name' => 'Areas.name',
									// 				'area_code' => 'Areas.code',
									// 				'area_administrative_name' => 'AreaAdministratives.name',
									// 				'area_administrative_id' => 'AreaAdministratives.id',
									// 				'area_administrative_code' => 'AreaAdministratives.code',
									// 				'locality_name' => 'Localities.name',
									// 				'locality_id' => 'Localities.id'
									// 			])
									// 			->leftJoin(
									// 			['Ownerships' => 'institution_ownerships'],
									// 			[
									// 				'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Sectors' => 'institution_sectors'],
									// 			[
									// 				'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Areas' => 'areas'],
									// 			[
									// 				'Areas.id = '. $Institutions->aliasField('area_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['AreaAdministratives' => 'area_administratives'],
									// 			[
									// 				'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Providers' => 'institution_providers'],
									// 			[
									// 				'Providers.id = '. $Institutions->aliasField('institution_provider_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Types' => 'institution_types'],
									// 			[
									// 				'Types.id = '. $Institutions->aliasField('institution_type_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Localities' => 'institution_localities'],
									// 			[
									// 				'Localities.id = '. $Institutions->aliasField('institution_locality_id')
									// 			]
									// 			)
									// 			->where(['area_id' => $Area_insss->id,'institution_provider_id'=>$InstitutionProvider->id,'institution_type_id'=>$InstitutionType->id,$where])
									// 			;
									
									//**************************************************************************** */
									//********************  Section For Counrty  END     *****************************
									//**************************************************************************** */
									
									$resultArray[$key][] = $InstitutionType->name;
									$resultArray[$KEYY+1][]= $institut1->count();
									$ki++;
								}
								
							}
							
							$ShiftOptions = $ShiftOptionTable->find('all')->toArray();
							$shiftArr = [];
							$shiftArrResult = [];
							foreach($ShiftOptions as $keyy => $ShiftOption){  
								$shiftArr[$keyy] = 'male';
								$shiftArrResult[$keyy] = $InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => 'student_id',
									'academic_period_id' => 'academic_period_id',
									'institution_id' => 'institution_id',
									'student_status_id' => 'student_status_id',
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)

									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										->leftJoin(
											['InsShifts' => 'institution_shifts'],
											[
												'InsShifts.institution_id = '. $InsStudentTable->aliasField('institution_id')
											]
											)
								
								->where([$InsStudentTable->aliasField('institution_id in') => $finalIds,$InsStudentTable->aliasField('academic_period_id')=> $academic_period_id,'Students.gender_id'=>1,'Institutions.area_id'=>$Area_insss->id,'InsShifts.shift_option_id'=>$ShiftOption->id])
								->count()
								;
								$shiftArr[$keyy.'1'] = 'female';
								$shiftArrResult[$keyy.'1'] = $InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => 'student_id',
									'academic_period_id' => 'academic_period_id',
									'institution_id' => 'institution_id',
									'student_status_id' => 'student_status_id',
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)
									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										->leftJoin(
											['InsShifts' => 'institution_shifts'],
											[
												'InsShifts.institution_id = '. $InsStudentTable1->aliasField('institution_id')
											]
											)
											->where([$InsStudentTable->aliasField('institution_id in') => $finalIds,$InsStudentTable->aliasField('academic_period_id')=> $academic_period_id,'Students.gender_id'=>2,'Institutions.area_id'=>$Area_insss->id,'InsShifts.shift_option_id'=>$ShiftOption->id])
								->count()
								;
							}
							
								$abc = $shiftArrResult;
								array_unshift($resultArray[$KEYY+1], $Area_insss->name);
								 $mergeshift[] = array_merge($resultArray[$KEYY+1], $abc );
								 
						}
						//echo "<pre>";print_r($abcArr);die;
						unset($resultArray[0]);

						foreach($mergeshift as $e=>$ms){
							$mergeshift[$e][count($ms)-1] = array_sum($mergeshift[$e]);
						}
						
						//add level array start
												$institutionData1 = $Institutions->find()
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
													])
													->leftJoin(
													['Ownerships' => 'institution_ownerships'],
													[
														'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
													]
													)
													->leftJoin(
													['Sectors' => 'institution_sectors'],
													[
														'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
													]
													)
													->leftJoin(
													['Areas' => 'areas'],
													[
														'Areas.id = '. $Institutions->aliasField('area_id')
													]
													)
													->leftJoin(
													['AreaAdministratives' => 'area_administratives'],
													[
														'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
													]
													)
													->leftJoin(
													['Providers' => 'institution_providers'],
													[
														'Providers.id = '. $Institutions->aliasField('institution_provider_id')
													]
													)
													->leftJoin(
													['Types' => 'institution_types'],
													[
														'Types.id = '. $Institutions->aliasField('institution_type_id')
													]
													)
													->leftJoin(
													['Localities' => 'institution_localities'],
													[
														'Localities.id = '. $Institutions->aliasField('institution_locality_id')
													]
													)
													->where([$where])
													;
													$areaArray = $sectorArray = $sectorData = $ownershipArray = $localityArray = $typeArray = $providerArray = $areaAdministrativeArray = [];	
													$resultArray1 = array();
													$i = 0;
													foreach($institutionData as $key => $value) { 
														if($i == 0) { 
															$InstitutionTypesTable = TableRegistry::get('institution_types');
															$InstitutionTypess = $InstitutionTypesTable->find('all')->toArray();
															$InstitutionProvidersTable = TableRegistry::get('institution_providers');
															$InstitutionProviderss = $InstitutionProvidersTable->find('all')->toArray();
															$resultArray1[0][] = 'atoll';
															$keyy = 0;
															$ki = 1;
															foreach($InstitutionProviderss as $keyy => $InstitutionProvider1){ 
																foreach($InstitutionTypess as $ki => $InstitutionType1){ 
						
												//**************************************************************************** */
												//********************  Section For Country  Start     *****************************
												//**************************************************************************** */
												$institut11 = $Institutions->find()
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
															])
															->leftJoin(
															['Ownerships' => 'institution_ownerships'],
															[
																'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
															]
															)
															->leftJoin(
															['Sectors' => 'institution_sectors'],
															[
																'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
															]
															)
															->leftJoin(
															['Areas' => 'areas'],
															[
																'Areas.id = '. $Institutions->aliasField('area_id')
															]
															)
															->leftJoin(
															['AreaAdministratives' => 'area_administratives'],
															[
																'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
															]
															)
															->leftJoin(
															['Providers' => 'institution_providers'],
															[
																'Providers.id = '. $Institutions->aliasField('institution_provider_id')
															]
															)
															->leftJoin(
															['Types' => 'institution_types'],
															[
																'Types.id = '. $Institutions->aliasField('institution_type_id')
															]
															)
															->leftJoin(
															['Localities' => 'institution_localities'],
															[
																'Localities.id = '. $Institutions->aliasField('institution_locality_id')
															]
															)
															->where(['institution_provider_id'=>$InstitutionProvider1->id,'institution_type_id'=>$InstitutionType1->id,$where])
															;
						
												
												//**************************************************************************** */
												//********************  Section For Counrty  END     *****************************
												//**************************************************************************** */
						
												$resultArray1[$key][] = $InstitutionType1->name;
												//$resultArray['countryData'][]= $institut11->count();
						
												$ki++;
											}
											
										}
									}
								}
								//add level array end
							array_unshift($resultArray1[1],$AreaLvlData->name);
							$arrrr[]= $resultArray1[1];
							$resultfinalArr =	array_merge($arrrr,$mergeshift);
							$ShiftOptionTable = TableRegistry::get('shift_options');
							$InsStudentTable = TableRegistry::get('institution_students');
							$ShiftOptions = $ShiftOptionTable->find('all')->toArray();
							$shiftArr = [];
							$shiftArrResult = [];
							
							foreach($ShiftOptions as $keyy => $ShiftOption){  
								$shiftArr[$keyy] = 'male';
							$shiftArrResult[$keyy] = $InsStudentTable->find()
							->select([
								//'id','institution_id','student_id'
								'student_id' => 'student_id',
								'academic_period_id' => 'academic_period_id',
								'institution_id' => 'institution_id',
								'student_status_id' => 'student_status_id',
								'gender_id' => 'Students.gender_id',
							])
						
							->leftJoin(
								['Students' => 'security_users'],
								[
									'Students.id = '. $InsStudentTable->aliasField('student_id')
								]
								)

								->leftJoin(
									['Institutions' => 'institutions'],
									[
										'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
									]
									)
						
						->where(['institution_id in'=>$finalIds,'academic_period_id'=> $academic_period_id,'gender_id'=>1])
						->count();
						$shiftArr[$keyy.'1'] = 'female';
						$shiftArrResult[$keyy.'1'] = $InsStudentTable->find()
						->select([
							//'id','institution_id','student_id'
							'student_id' => 'student_id',
							'academic_period_id' => 'academic_period_id',
							'institution_id' => 'institution_id',
							'student_status_id' => 'student_status_id',
							'gender_id' => 'Students.gender_id',
						])
						
						->leftJoin(
							['Students' => 'security_users'],
							[
								'Students.id = '. $InsStudentTable->aliasField('student_id')
							]
							)
						
						->where(['institution_id in'=>$finalIds,'academic_period_id'=> $academic_period_id,'gender_id'=>2])
						->count();
					}
					$arr = ['0'=>'male','1'=>'female'];
					if(!empty($institution_id)){
						$resultfinalArr[0] =array_merge($resultArray1[0],$shiftArr);
					}else{
						$resultfinalArr[0] =array_merge($resultfinalArr[0],$shiftArr);
					}
					

			} else {  
				if(!empty($value->area_id)) {
						$resultArray[$key]['area_name'] = $value->area_name; 
					$areaArray[] = $value->area_id;
				} else {
					$resultArray[$key]['area_name'] = '';
				}
			}
			$i++;	
		}
		return $resultfinalArr;
		
	}


	public function getReagionAtollData($settings)
    {
    
	$Institutions = TableRegistry::get('Institutions');
    	$requestData = json_decode($settings['process']['params']);
    	$institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
		$areaLevelId = $requestData->area_level_id;
		$academic_period_id = $requestData->academic_period_id;
		
		$AcademicPriodT = TableRegistry::get('academic_periods'); 
	$AcademicPeriodDataforYear = $AcademicPriodT->find('all')->where(['id' => $academic_period_id])->first();
	$startDate = $AcademicPeriodDataforYear->start_date->format('Y-m-d');;
	$endDate = $AcademicPeriodDataforYear->end_date->format('Y-m-d');;
		
	$currDate = date('Y-m-d');
	if($currDate >= $startDate && $currDate <= $endDate){
		$stuStatussArr = [1];
	}else{
		$stuStatussArr = [1,6,7,8];
	}
	
	
	$AreaLvlT = TableRegistry::get('area_levels'); 
	$AreaLvlData = $AreaLvlT->find('all')->where(['id' => $areaLevelId])->first();
        $AreaT = TableRegistry::get('areas');  
		$ShiftOptionTable = TableRegistry::get('shift_options');
		$InsStudentTable = TableRegistry::get('institution_students');
		$InsStudentTable1 = TableRegistry::get('institution_students');                  
        //Level-1
        $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['area_level_id' => $areaLevelId])->toArray();
        $childArea =[];
        $childAreaMain = [];
        $childArea3 = [];
        $childArea4 = [];
        foreach($AreaData as $kkk =>$AreaData11 ){
            $childArea[$kkk] = $AreaData11->id;
        }
        //level-2
        foreach($childArea as $kyy =>$AreaDatal2 ){
            $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
            foreach($AreaDatas as $ky =>$AreaDatal22 ){
                $childAreaMain[$kyy.$ky] = $AreaDatal22->id;
            }
        }
        //level-3
        if(!empty($childAreaMain)){
            foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                foreach($AreaDatass as $ky =>$AreaDatal222 ){
                    $childArea3[$kyy.$ky] = $AreaDatal222->id;
                }
            }
        }   
        //level-4
        if(!empty($childAreaMain)){
            foreach($childArea3 as $kyy =>$AreaDatal4 ){
                $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                    $childArea4[$kyy.$ky] = $AreaDatal44->id;
                }
            }
        }
        $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
        array_push($mergeArr,$areaId);
        $mergeArr = array_unique($mergeArr);
        $finalIds = implode(',',$mergeArr);
        $finalIds = explode(',',$finalIds);
		
		//echo "<pre>";print_r($requestData);die;
		
        $where = [];
        if ($areaId != -1) {
            $where[$Institutions->aliasField('area_id in')] = $finalIds;
        }
		if ($institution_id != 0) {
            $where[$Institutions->aliasField('id')] = $institution_id;
        }
		$institutionData = $Institutions->find()
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
                    ])
					->leftJoin(
					['Ownerships' => 'institution_ownerships'],
					[
						'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
					]
					)
					->leftJoin(
					['Sectors' => 'institution_sectors'],
					[
						'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
					]
					)
					->leftJoin(
					['Areas' => 'areas'],
					[
						'Areas.id = '. $Institutions->aliasField('area_id')
					]
					)
					->leftJoin(
					['AreaAdministratives' => 'area_administratives'],
					[
						'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
					]
					)
					->leftJoin(
					['Providers' => 'institution_providers'],
					[
						'Providers.id = '. $Institutions->aliasField('institution_provider_id')
					]
					)
					->leftJoin(
					['Types' => 'institution_types'],
					[
						'Types.id = '. $Institutions->aliasField('institution_type_id')
					]
					)
					->leftJoin(
					['Localities' => 'institution_localities'],
					[
						'Localities.id = '. $Institutions->aliasField('institution_locality_id')
					]
					)
					->where([$where])
					;
					$areaArray = $sectorArray = $sectorData = $ownershipArray = $localityArray = $typeArray = $providerArray = $areaAdministrativeArray = [];	
					$resultArray = array();
					$resultArray1 = array();
					$i = 0;
					foreach($institutionData as $key => $value) { 
						if($i == 0) {
							$InstitutionTypesTable = TableRegistry::get('institution_types');
							$InstitutionTypes = $InstitutionTypesTable->find('all')->toArray();
							$InstitutionTypesCount = $InstitutionTypesTable->find('all')->count();
							$InstitutionProvidersTable = TableRegistry::get('institution_providers');
							$InstitutionProviders = $InstitutionProvidersTable->find('all')->toArray();
							$InstitutionProvidersCount = $InstitutionProvidersTable->find('all')->count();
							$totalCount = $InstitutionProvidersCount*$InstitutionTypesCount;
							
							

							$resultArray[0][] = $AreaLvlData->name;
							$keyy = 0;
							$ki = 1;


							$AreasData1 = $AreaT->find('all',['conditions'=>['area_level_id'=> $areaLevelId]])->toArray();
							$abcArr =[];
							foreach($AreasData1 as $KEYY => $Area_insss){

								
								//Level-1
								$areaparentIds = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $Area_insss->id])->toArray();
								$childArea1 =[];
								$childAreaMain2 = [];
								$childArea33 = [];
								$childArea44 = [];
								foreach($areaparentIds as $kkk =>$AreaData111 ){
									$childArea1[$kkk] = $AreaData111->id;
								}
								//level-2
								foreach($childArea1 as $kyy =>$AreaDatal21 ){
									$AreaDatas11 = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal21])->toArray();
									
									
									foreach($AreaDatas11 as $ky =>$AreaDatal223 ){
										$childAreaMain2[$kyy.$ky] = $AreaDatal223->id;
									}
									
								}
								//echo "<pre>";print_r($childAreaMain2);die;
								//level-3
								if(!empty($childAreaMain2)){
									foreach($childAreaMain as $kyy =>$AreaDatal32 ){
										$AreaDatass11 = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal32])->toArray();
										foreach($AreaDatass11 as $ky =>$AreaDatal222 ){
											$childArea33[$kyy.$ky] = $AreaDatal222->id;
										}
									}
								}   
								/*//level-4
								if(!empty($childAreaMain2)){
									foreach($childArea33 as $kyy =>$AreaDatal4 ){
										$AreaDatasss11 = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
										foreach($AreaDatasss11 as $ky =>$AreaDatal44 ){
											$childArea44[$kyy.$ky] = $AreaDatal44->id;
										}
									}
								}*/
								/*$mergeArr1 = array_merge($childArea1,$childAreaMain2,$childArea33);
								array_push($mergeArr1,$areaId);
								$mergeArr1 = array_unique($mergeArr1);
								$finalIds1 = implode(',',$mergeArr1);
								$finalIds1 = explode(',',$finalIds1);*/
								$abcArr[$KEYY] = $finalIds1;
								
								//Log::write('debug', $finalIds1);
								//echo "qwerty"; echo "<pre>";print_r($childArea1);die;

							foreach($InstitutionProviders as $keyy => $InstitutionProvider){ 
								foreach($InstitutionTypes as $ki => $InstitutionType){ 

									//**************************************************************************** */
									//********************  Section For Country  Start     *****************************
									//**************************************************************************** */


									$institut1 =	$InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => $InsStudentTable->aliasField('student_id'),
									'academic_period_id' => $InsStudentTable->aliasField('academic_period_id'),
									'institution_id' => $InsStudentTable->aliasField('institution_id'),
									'student_status_id' => $InsStudentTable->aliasField('student_status_id'),
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)

									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										
								
								->where(['Institutions.institution_provider_id'=>$InstitutionProvider->id,'Institutions.institution_type_id'=>$InstitutionType->id,'Institutions.area_id in' => $childArea1,'student_status_id in'=>$stuStatussArr,'academic_period_id'=>$academic_period_id,$where])
								->group([$InsStudentTable->aliasField('student_id')])
								//->count()
								;
//echo "<pre>";print_r($institut1->count());die;

									// $institut = $Institutions->find()
									// 			->select([
									// 				'ownership_name' => 'Ownerships.name',
									// 				'ownership_id' => 'Ownerships.id',
									// 				'sector_name' => 'Sectors.name',
									// 				'sector_id' => 'Sectors.id',
									// 				'provider_name' => 'Providers.name',
									// 				'provider_id' => 'Providers.id',
									// 				'type_name' => 'Types.name',
									// 				'type_id' => 'Types.id',
									// 				'area_id' => 'Areas.id',
									// 				'area_name' => 'Areas.name',
									// 				'area_code' => 'Areas.code',
									// 				'area_administrative_name' => 'AreaAdministratives.name',
									// 				'area_administrative_id' => 'AreaAdministratives.id',
									// 				'area_administrative_code' => 'AreaAdministratives.code',
									// 				'locality_name' => 'Localities.name',
									// 				'locality_id' => 'Localities.id'
									// 			])
									// 			->leftJoin(
									// 			['Ownerships' => 'institution_ownerships'],
									// 			[
									// 				'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Sectors' => 'institution_sectors'],
									// 			[
									// 				'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Areas' => 'areas'],
									// 			[
									// 				'Areas.id = '. $Institutions->aliasField('area_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['AreaAdministratives' => 'area_administratives'],
									// 			[
									// 				'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Providers' => 'institution_providers'],
									// 			[
									// 				'Providers.id = '. $Institutions->aliasField('institution_provider_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Types' => 'institution_types'],
									// 			[
									// 				'Types.id = '. $Institutions->aliasField('institution_type_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Localities' => 'institution_localities'],
									// 			[
									// 				'Localities.id = '. $Institutions->aliasField('institution_locality_id')
									// 			]
									// 			)
									// 			->where(['area_id' => $Area_insss->id,'institution_provider_id'=>$InstitutionProvider->id,'institution_type_id'=>$InstitutionType->id,$where])
									// 			;
									
									//**************************************************************************** */
									//********************  Section For Counrty  END     *****************************
									//**************************************************************************** */
									
									$resultArray[$key][] = $InstitutionType->name;
									$resultArray[$KEYY+1][]= $institut1->count();
									$ki++;
								}
								
							}
							
							$ShiftOptions = $ShiftOptionTable->find('all')->toArray();
							$shiftArr = [];
							$shiftArrResult = [];
							foreach($ShiftOptions as $keyy => $ShiftOption){  
								$shiftArr[$keyy] = 'male';
								$shiftArrResult[$keyy] = $InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => 'student_id',
									'academic_period_id' => 'academic_period_id',
									'institution_id' => 'institution_id',
									'student_status_id' => 'student_status_id',
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)

									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										->leftJoin(
											['InsShifts' => 'institution_shifts'],
											[
												'InsShifts.institution_id = '. $InsStudentTable->aliasField('institution_id')
											]
											)
								
								->where([$InsStudentTable->aliasField('institution_id in') => $finalIds,$InsStudentTable->aliasField('academic_period_id')=> $academic_period_id,'Students.gender_id'=>1,'Institutions.area_id'=>$Area_insss->id,'InsShifts.shift_option_id'=>$ShiftOption->id])
								->count()
								;
								$shiftArr[$keyy.'1'] = 'female';
								$shiftArrResult[$keyy.'1'] = $InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => 'student_id',
									'academic_period_id' => 'academic_period_id',
									'institution_id' => 'institution_id',
									'student_status_id' => 'student_status_id',
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)
									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										->leftJoin(
											['InsShifts' => 'institution_shifts'],
											[
												'InsShifts.institution_id = '. $InsStudentTable1->aliasField('institution_id')
											]
											)
											->where([$InsStudentTable->aliasField('institution_id in') => $finalIds,$InsStudentTable->aliasField('academic_period_id')=> $academic_period_id,'Students.gender_id'=>2,'Institutions.area_id'=>$Area_insss->id,'InsShifts.shift_option_id'=>$ShiftOption->id])
								->count()
								;
							}
							
								$abc = $shiftArrResult;
								array_unshift($resultArray[$KEYY+1], $Area_insss->name);
								 $mergeshift[] = array_merge($resultArray[$KEYY+1], $abc );
								 
						}
						//echo "<pre>";print_r($abcArr);die;
						unset($resultArray[0]);

						foreach($mergeshift as $e=>$ms){
							$mergeshift[$e][count($ms)-1] = array_sum($mergeshift[$e]);
						}
						
						//add level array start
												$institutionData1 = $Institutions->find()
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
													])
													->leftJoin(
													['Ownerships' => 'institution_ownerships'],
													[
														'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
													]
													)
													->leftJoin(
													['Sectors' => 'institution_sectors'],
													[
														'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
													]
													)
													->leftJoin(
													['Areas' => 'areas'],
													[
														'Areas.id = '. $Institutions->aliasField('area_id')
													]
													)
													->leftJoin(
													['AreaAdministratives' => 'area_administratives'],
													[
														'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
													]
													)
													->leftJoin(
													['Providers' => 'institution_providers'],
													[
														'Providers.id = '. $Institutions->aliasField('institution_provider_id')
													]
													)
													->leftJoin(
													['Types' => 'institution_types'],
													[
														'Types.id = '. $Institutions->aliasField('institution_type_id')
													]
													)
													->leftJoin(
													['Localities' => 'institution_localities'],
													[
														'Localities.id = '. $Institutions->aliasField('institution_locality_id')
													]
													)
													->where([$where])
													;
													$areaArray = $sectorArray = $sectorData = $ownershipArray = $localityArray = $typeArray = $providerArray = $areaAdministrativeArray = [];	
													$resultArray1 = array();
													$i = 0;
													foreach($institutionData as $key => $value) { 
														if($i == 0) { 
															$InstitutionTypesTable = TableRegistry::get('institution_types');
															$InstitutionTypess = $InstitutionTypesTable->find('all')->toArray();
															$InstitutionProvidersTable = TableRegistry::get('institution_providers');
															$InstitutionProviderss = $InstitutionProvidersTable->find('all')->toArray();
															$resultArray1[0][] = 'atoll';
															$keyy = 0;
															$ki = 1;
															foreach($InstitutionProviderss as $keyy => $InstitutionProvider1){ 
																foreach($InstitutionTypess as $ki => $InstitutionType1){ 
						
												//**************************************************************************** */
												//********************  Section For Country  Start     *****************************
												//**************************************************************************** */
												$institut11 = $Institutions->find()
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
															])
															->leftJoin(
															['Ownerships' => 'institution_ownerships'],
															[
																'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
															]
															)
															->leftJoin(
															['Sectors' => 'institution_sectors'],
															[
																'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
															]
															)
															->leftJoin(
															['Areas' => 'areas'],
															[
																'Areas.id = '. $Institutions->aliasField('area_id')
															]
															)
															->leftJoin(
															['AreaAdministratives' => 'area_administratives'],
															[
																'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
															]
															)
															->leftJoin(
															['Providers' => 'institution_providers'],
															[
																'Providers.id = '. $Institutions->aliasField('institution_provider_id')
															]
															)
															->leftJoin(
															['Types' => 'institution_types'],
															[
																'Types.id = '. $Institutions->aliasField('institution_type_id')
															]
															)
															->leftJoin(
															['Localities' => 'institution_localities'],
															[
																'Localities.id = '. $Institutions->aliasField('institution_locality_id')
															]
															)
															->where(['institution_provider_id'=>$InstitutionProvider1->id,'institution_type_id'=>$InstitutionType1->id,$where])
															;
						
												
												//**************************************************************************** */
												//********************  Section For Counrty  END     *****************************
												//**************************************************************************** */
						
												$resultArray1[$key][] = $InstitutionType1->name;
												//$resultArray['countryData'][]= $institut11->count();
						
												$ki++;
											}
											
										}
									}
								}
								//add level array end
							array_unshift($resultArray1[1],$AreaLvlData->name);
							$arrrr[]= $resultArray1[1];
							$resultfinalArr =	array_merge($arrrr,$mergeshift);
							$ShiftOptionTable = TableRegistry::get('shift_options');
							$InsStudentTable = TableRegistry::get('institution_students');
							$ShiftOptions = $ShiftOptionTable->find('all')->toArray();
							$shiftArr = [];
							$shiftArrResult = [];
							
							foreach($ShiftOptions as $keyy => $ShiftOption){  
								$shiftArr[$keyy] = 'male';
							$shiftArrResult[$keyy] = $InsStudentTable->find()
							->select([
								//'id','institution_id','student_id'
								'student_id' => 'student_id',
								'academic_period_id' => 'academic_period_id',
								'institution_id' => 'institution_id',
								'student_status_id' => 'student_status_id',
								'gender_id' => 'Students.gender_id',
							])
						
							->leftJoin(
								['Students' => 'security_users'],
								[
									'Students.id = '. $InsStudentTable->aliasField('student_id')
								]
								)

								->leftJoin(
									['Institutions' => 'institutions'],
									[
										'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
									]
									)
						
						->where(['institution_id in'=>$finalIds,'academic_period_id'=> $academic_period_id,'gender_id'=>1])
						->count();
						$shiftArr[$keyy.'1'] = 'female';
						$shiftArrResult[$keyy.'1'] = $InsStudentTable->find()
						->select([
							//'id','institution_id','student_id'
							'student_id' => 'student_id',
							'academic_period_id' => 'academic_period_id',
							'institution_id' => 'institution_id',
							'student_status_id' => 'student_status_id',
							'gender_id' => 'Students.gender_id',
						])
						
						->leftJoin(
							['Students' => 'security_users'],
							[
								'Students.id = '. $InsStudentTable->aliasField('student_id')
							]
							)
						
						->where(['institution_id in'=>$finalIds,'academic_period_id'=> $academic_period_id,'gender_id'=>2])
						->count();
					}
					$arr = ['0'=>'male','1'=>'female'];
					if(!empty($institution_id)){
						$resultfinalArr[0] =array_merge($resultArray1[0],$shiftArr);
					}else{
						$resultfinalArr[0] =array_merge($resultfinalArr[0],$shiftArr);
					}
					

			} else {  
				if(!empty($value->area_id)) {
						$resultArray[$key]['area_name'] = $value->area_name; 
					$areaArray[] = $value->area_id;
				} else {
					$resultArray[$key]['area_name'] = '';
				}
			}
			$i++;	
		}
		return $resultfinalArr;
		
	}

	public function getDistrictData($settings)
    {
		$Institutions = TableRegistry::get('Institutions');
    	$requestData = json_decode($settings['process']['params']);
    	$institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
		$areaLevelId = $requestData->area_level_id;
		$academic_period_id = $requestData->academic_period_id;
		
		$AcademicPriodT = TableRegistry::get('academic_periods'); 
	$AcademicPeriodDataforYear = $AcademicPriodT->find('all')->where(['id' => $academic_period_id])->first();
	$startDate = $AcademicPeriodDataforYear->start_date->format('Y-m-d');;
	$endDate = $AcademicPeriodDataforYear->end_date->format('Y-m-d');;
		
	$currDate = date('Y-m-d');
	if($currDate >= $startDate && $currDate <= $endDate){
		$stuStatussArr = [1];
	}else{
		$stuStatussArr = [1,6,7,8];
	}
		
		$AreaLvlT = TableRegistry::get('area_levels'); 
	$AreaLvlData = $AreaLvlT->find('all')->where(['id' => $areaLevelId])->first();
	//print_r($AreaLvlData);die;
        $AreaT = TableRegistry::get('areas');  
		$ShiftOptionTable = TableRegistry::get('shift_options');
		$InsStudentTable = TableRegistry::get('institution_students');
		$InsStudentTable1 = TableRegistry::get('institution_students');                  
        //Level-1
        $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['area_level_id' => $areaLevelId])->toArray();
        $childArea =[];
        $childAreaMain = [];
        $childArea3 = [];
        $childArea4 = [];
        foreach($AreaData as $kkk =>$AreaData11 ){
            $childArea[$kkk] = $AreaData11->id;
        }
        //level-2
        foreach($childArea as $kyy =>$AreaDatal2 ){
            $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
            foreach($AreaDatas as $ky =>$AreaDatal22 ){
                $childAreaMain[$kyy.$ky] = $AreaDatal22->id;
            }
        }
        //level-3
        if(!empty($childAreaMain)){
            foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                foreach($AreaDatass as $ky =>$AreaDatal222 ){
                    $childArea3[$kyy.$ky] = $AreaDatal222->id;
                }
            }
        }   
        //level-4
        if(!empty($childAreaMain)){
            foreach($childArea3 as $kyy =>$AreaDatal4 ){
                $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                    $childArea4[$kyy.$ky] = $AreaDatal44->id;
                }
            }
        }
        $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
        array_push($mergeArr,$areaId);
        $mergeArr = array_unique($mergeArr);
        $finalIds = implode(',',$mergeArr);
        $finalIds = explode(',',$finalIds);
		
        $where = [];
        if ($areaId != -1) {
            $where[$Institutions->aliasField('area_id in')] = $finalIds;
        }
		if ($institution_id != 0) {
            $where[$Institutions->aliasField('id')] = $institution_id;
        }
		$institutionData = $Institutions->find()
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
                    ])
					->leftJoin(
					['Ownerships' => 'institution_ownerships'],
					[
						'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
					]
					)
					->leftJoin(
					['Sectors' => 'institution_sectors'],
					[
						'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
					]
					)
					->leftJoin(
					['Areas' => 'areas'],
					[
						'Areas.id = '. $Institutions->aliasField('area_id')
					]
					)
					->leftJoin(
					['AreaAdministratives' => 'area_administratives'],
					[
						'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
					]
					)
					->leftJoin(
					['Providers' => 'institution_providers'],
					[
						'Providers.id = '. $Institutions->aliasField('institution_provider_id')
					]
					)
					->leftJoin(
					['Types' => 'institution_types'],
					[
						'Types.id = '. $Institutions->aliasField('institution_type_id')
					]
					)
					->leftJoin(
					['Localities' => 'institution_localities'],
					[
						'Localities.id = '. $Institutions->aliasField('institution_locality_id')
					]
					)
					->where([$where])
					;
					$areaArray = $sectorArray = $sectorData = $ownershipArray = $localityArray = $typeArray = $providerArray = $areaAdministrativeArray = [];	
					$resultArray = array();
					$resultArray1 = array();
					$i = 0;
					foreach($institutionData as $key => $value) { 
						if($i == 0) {
							$InstitutionTypesTable = TableRegistry::get('institution_types');
							$InstitutionTypes = $InstitutionTypesTable->find('all')->toArray();
							$InstitutionTypesCount = $InstitutionTypesTable->find('all')->count();
							$InstitutionProvidersTable = TableRegistry::get('institution_providers');
							$InstitutionProviders = $InstitutionProvidersTable->find('all')->toArray();
							$InstitutionProvidersCount = $InstitutionProvidersTable->find('all')->count();
							$totalCount = $InstitutionProvidersCount*$InstitutionTypesCount;

							$resultArray[0][] = $AreaLvlData->name;
							$keyy = 0;
							$ki = 1;


							$AreasData1 = $AreaT->find('all',['conditions'=>['area_level_id'=> $areaLevelId]])->toArray();
							//echo "<pre>";print_r($AreasData1);die;
							foreach($AreasData1 as $KEYY => $Area_insss){



							foreach($InstitutionProviders as $keyy => $InstitutionProvider){ 
								foreach($InstitutionTypes as $ki => $InstitutionType){ 

									//**************************************************************************** */
									//********************  Section For Country  Start     *****************************
									//**************************************************************************** */


									$institut =	$InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => $InsStudentTable->aliasField('student_id'),
									'academic_period_id' => $InsStudentTable->aliasField('academic_period_id'),
									'institution_id' => $InsStudentTable->aliasField('institution_id'),
									'student_status_id' => $InsStudentTable->aliasField('student_status_id'),
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)

									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										
								
								->where(['Institutions.institution_provider_id'=>$InstitutionProvider->id,'Institutions.institution_type_id'=>$InstitutionType->id,'Institutions.area_id' => $Area_insss->id,'student_status_id in'=>$stuStatussArr,'academic_period_id'=>$academic_period_id,$where])
								->group([$InsStudentTable->aliasField('student_id')])
								//->count()
								;


									// $institut = $Institutions->find()
									// 			->select([
									// 				'ownership_name' => 'Ownerships.name',
									// 				'ownership_id' => 'Ownerships.id',
									// 				'sector_name' => 'Sectors.name',
									// 				'sector_id' => 'Sectors.id',
									// 				'provider_name' => 'Providers.name',
									// 				'provider_id' => 'Providers.id',
									// 				'type_name' => 'Types.name',
									// 				'type_id' => 'Types.id',
									// 				'area_id' => 'Areas.id',
									// 				'area_name' => 'Areas.name',
									// 				'area_code' => 'Areas.code',
									// 				'area_administrative_name' => 'AreaAdministratives.name',
									// 				'area_administrative_id' => 'AreaAdministratives.id',
									// 				'area_administrative_code' => 'AreaAdministratives.code',
									// 				'locality_name' => 'Localities.name',
									// 				'locality_id' => 'Localities.id'
									// 			])
									// 			->leftJoin(
									// 			['Ownerships' => 'institution_ownerships'],
									// 			[
									// 				'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Sectors' => 'institution_sectors'],
									// 			[
									// 				'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Areas' => 'areas'],
									// 			[
									// 				'Areas.id = '. $Institutions->aliasField('area_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['AreaAdministratives' => 'area_administratives'],
									// 			[
									// 				'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Providers' => 'institution_providers'],
									// 			[
									// 				'Providers.id = '. $Institutions->aliasField('institution_provider_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Types' => 'institution_types'],
									// 			[
									// 				'Types.id = '. $Institutions->aliasField('institution_type_id')
									// 			]
									// 			)
									// 			->leftJoin(
									// 			['Localities' => 'institution_localities'],
									// 			[
									// 				'Localities.id = '. $Institutions->aliasField('institution_locality_id')
									// 			]
									// 			)
									// 			->where(['area_id' => $Area_insss->id,'institution_provider_id'=>$InstitutionProvider->id,'institution_type_id'=>$InstitutionType->id,$where])
									// 			;
									
									//**************************************************************************** */
									//********************  Section For Counrty  END     *****************************
									//**************************************************************************** */
									
									$resultArray[$key][] = $InstitutionType->name;
									$resultArray[$KEYY+1][]= $institut->count();
									$ki++;
								}
								
							}
							
							$ShiftOptions = $ShiftOptionTable->find('all')->toArray();
							$shiftArr = [];
							$shiftArrResult = [];
							foreach($ShiftOptions as $keyy => $ShiftOption){  
								$shiftArr[$keyy] = 'male';
								$shiftArrResult[$keyy] = $InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => 'student_id',
									'academic_period_id' => 'academic_period_id',
									'institution_id' => 'institution_id',
									'student_status_id' => 'student_status_id',
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)

									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										->leftJoin(
											['InsShifts' => 'institution_shifts'],
											[
												'InsShifts.institution_id = '. $InsStudentTable->aliasField('institution_id')
											]
											)
								
								->where([$InsStudentTable->aliasField('institution_id in') => $finalIds,$InsStudentTable->aliasField('academic_period_id')=> $academic_period_id,'Students.gender_id'=>1,'Institutions.area_id'=>$Area_insss->id,'InsShifts.shift_option_id'=>$ShiftOption->id])
								->count()
								;
								$shiftArr[$keyy.'1'] = 'female';
								$shiftArrResult[$keyy.'1'] = $InsStudentTable->find()
								->select([
									//'id','institution_id','student_id'
									'student_id' => 'student_id',
									'academic_period_id' => 'academic_period_id',
									'institution_id' => 'institution_id',
									'student_status_id' => 'student_status_id',
									'gender_id' => 'Students.gender_id',
								])
								
								->leftJoin(
									['Students' => 'security_users'],
									[
										'Students.id = '. $InsStudentTable->aliasField('student_id')
									]
									)
									->leftJoin(
										['Institutions' => 'institutions'],
										[
											'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
										]
										)

										->leftJoin(
											['InsShifts' => 'institution_shifts'],
											[
												'InsShifts.institution_id = '. $InsStudentTable1->aliasField('institution_id')
											]
											)
											->where([$InsStudentTable->aliasField('institution_id in') => $finalIds,$InsStudentTable->aliasField('academic_period_id')=> $academic_period_id,'Students.gender_id'=>2,'Institutions.area_id'=>$Area_insss->id,'InsShifts.shift_option_id'=>$ShiftOption->id])
								->count()
								;
							}
							
								$abc = $shiftArrResult;
								array_unshift($resultArray[$KEYY+1], $Area_insss->name);
								 $mergeshift[] = array_merge($resultArray[$KEYY+1], $abc );
								 
						}
						
						unset($resultArray[0]);

						foreach($mergeshift as $e=>$ms){
							$mergeshift[$e][count($ms)-1] = array_sum($mergeshift[$e]);
						}
						
						//add level array start
												$institutionData1 = $Institutions->find()
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
													])
													->leftJoin(
													['Ownerships' => 'institution_ownerships'],
													[
														'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
													]
													)
													->leftJoin(
													['Sectors' => 'institution_sectors'],
													[
														'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
													]
													)
													->leftJoin(
													['Areas' => 'areas'],
													[
														'Areas.id = '. $Institutions->aliasField('area_id')
													]
													)
													->leftJoin(
													['AreaAdministratives' => 'area_administratives'],
													[
														'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
													]
													)
													->leftJoin(
													['Providers' => 'institution_providers'],
													[
														'Providers.id = '. $Institutions->aliasField('institution_provider_id')
													]
													)
													->leftJoin(
													['Types' => 'institution_types'],
													[
														'Types.id = '. $Institutions->aliasField('institution_type_id')
													]
													)
													->leftJoin(
													['Localities' => 'institution_localities'],
													[
														'Localities.id = '. $Institutions->aliasField('institution_locality_id')
													]
													)
													->where([$where])
													;
													$areaArray = $sectorArray = $sectorData = $ownershipArray = $localityArray = $typeArray = $providerArray = $areaAdministrativeArray = [];	
													$resultArray1 = array();
													$i = 0;
													foreach($institutionData as $key => $value) { 
														if($i == 0) { 
															$InstitutionTypesTable = TableRegistry::get('institution_types');
															$InstitutionTypess = $InstitutionTypesTable->find('all')->toArray();
															$InstitutionProvidersTable = TableRegistry::get('institution_providers');
															$InstitutionProviderss = $InstitutionProvidersTable->find('all')->toArray();
															$resultArray1[0][] = 'atoll';
															$keyy = 0;
															$ki = 1;
															foreach($InstitutionProviderss as $keyy => $InstitutionProvider1){ 
																foreach($InstitutionTypess as $ki => $InstitutionType1){ 
						
												//**************************************************************************** */
												//********************  Section For Country  Start     *****************************
												//**************************************************************************** */
												$institut11 = $Institutions->find()
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
															])
															->leftJoin(
															['Ownerships' => 'institution_ownerships'],
															[
																'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
															]
															)
															->leftJoin(
															['Sectors' => 'institution_sectors'],
															[
																'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
															]
															)
															->leftJoin(
															['Areas' => 'areas'],
															[
																'Areas.id = '. $Institutions->aliasField('area_id')
															]
															)
															->leftJoin(
															['AreaAdministratives' => 'area_administratives'],
															[
																'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
															]
															)
															->leftJoin(
															['Providers' => 'institution_providers'],
															[
																'Providers.id = '. $Institutions->aliasField('institution_provider_id')
															]
															)
															->leftJoin(
															['Types' => 'institution_types'],
															[
																'Types.id = '. $Institutions->aliasField('institution_type_id')
															]
															)
															->leftJoin(
															['Localities' => 'institution_localities'],
															[
																'Localities.id = '. $Institutions->aliasField('institution_locality_id')
															]
															)
															->where(['institution_provider_id'=>$InstitutionProvider1->id,'institution_type_id'=>$InstitutionType1->id,$where])
															;
						
												
												//**************************************************************************** */
												//********************  Section For Counrty  END     *****************************
												//**************************************************************************** */
						
												$resultArray1[$key][] = $InstitutionType1->name;
												//$resultArray['countryData'][]= $institut11->count();
						
												$ki++;
											}
											
										}
									}
								}
								//add level array end
							array_unshift($resultArray1[1],$AreaLvlData->name);
							$arrrr[]= $resultArray1[1];
							$resultfinalArr =	array_merge($arrrr,$mergeshift);
							$ShiftOptionTable = TableRegistry::get('shift_options');
							$InsStudentTable = TableRegistry::get('institution_students');
							$ShiftOptions = $ShiftOptionTable->find('all')->toArray();
							$shiftArr = [];
							$shiftArrResult = [];
							
							foreach($ShiftOptions as $keyy => $ShiftOption){  
								$shiftArr[$keyy] = 'male';
							$shiftArrResult[$keyy] = $InsStudentTable->find()
							->select([
								//'id','institution_id','student_id'
								'student_id' => 'student_id',
								'academic_period_id' => 'academic_period_id',
								'institution_id' => 'institution_id',
								'student_status_id' => 'student_status_id',
								'gender_id' => 'Students.gender_id',
							])
						
							->leftJoin(
								['Students' => 'security_users'],
								[
									'Students.id = '. $InsStudentTable->aliasField('student_id')
								]
								)

								->leftJoin(
									['Institutions' => 'institutions'],
									[
										'Institutions.id = '. $InsStudentTable->aliasField('institution_id')
									]
									)
						
						->where(['institution_id in'=>$finalIds,'academic_period_id'=> $academic_period_id,'gender_id'=>1])
						->count();
						$shiftArr[$keyy.'1'] = 'female';
						$shiftArrResult[$keyy.'1'] = $InsStudentTable->find()
						->select([
							//'id','institution_id','student_id'
							'student_id' => 'student_id',
							'academic_period_id' => 'academic_period_id',
							'institution_id' => 'institution_id',
							'student_status_id' => 'student_status_id',
							'gender_id' => 'Students.gender_id',
						])
						
						->leftJoin(
							['Students' => 'security_users'],
							[
								'Students.id = '. $InsStudentTable->aliasField('student_id')
							]
							)
						
						->where(['institution_id in'=>$finalIds,'academic_period_id'=> $academic_period_id,'gender_id'=>2])
						->count();
					}
					$arr = ['0'=>'male','1'=>'female'];
					if(!empty($institution_id)){
						$resultfinalArr[0] =array_merge($resultArray1[0],$shiftArr);
					}else{
						$resultfinalArr[0] =array_merge($resultfinalArr[0],$shiftArr);
					}
					

			} else {  
				if(!empty($value->area_id)) {
						$resultArray[$key]['area_name'] = $value->area_name; 
					$areaArray[] = $value->area_id;
				} else {
					$resultArray[$key]['area_name'] = '';
				}
			}
			$i++;	
		}
		
		return $resultfinalArr;
		
	}


    public function getData($settings)
    {
    	$Institutions = TableRegistry::get('Institutions');
    	$requestData = json_decode($settings['process']['params']);
    	$institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
		$areaLevelId = $requestData->area_level_id;
		$academic_period_id = $requestData->academic_period_id;
		//echo "<pre>";print_r($requestData);die;
		//Start:POCOR-6818 Modified this for POCOR-6859
        $AreaT = TableRegistry::get('areas');                    
        //Level-1
        $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['area_level_id' => $areaLevelId])->toArray();
        $childArea =[];
        $childAreaMain = [];
        $childArea3 = [];
        $childArea4 = [];
        foreach($AreaData as $kkk =>$AreaData11 ){
            $childArea[$kkk] = $AreaData11->id;
        }
        //level-2
        foreach($childArea as $kyy =>$AreaDatal2 ){
            $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
            foreach($AreaDatas as $ky =>$AreaDatal22 ){
                $childAreaMain[$kyy.$ky] = $AreaDatal22->id;
            }
        }
        //level-3
        if(!empty($childAreaMain)){
            foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                foreach($AreaDatass as $ky =>$AreaDatal222 ){
                    $childArea3[$kyy.$ky] = $AreaDatal222->id;
                }
            }
        }   
        //level-4
        if(!empty($childAreaMain)){
            foreach($childArea3 as $kyy =>$AreaDatal4 ){
                $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                    $childArea4[$kyy.$ky] = $AreaDatal44->id;
                }
            }
        }
        $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
        array_push($mergeArr,$areaId);
        $mergeArr = array_unique($mergeArr);
        $finalIds = implode(',',$mergeArr);
        $finalIds = explode(',',$finalIds);
		
        $where = [];
        if ($areaId != -1) {
            $where[$Institutions->aliasField('area_id in')] = $finalIds;
        }
		if ($institution_id != 0) {
            $where[$Institutions->aliasField('id')] = $institution_id;
        }
		$institutionData = $Institutions->find()
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
                    ])
					->leftJoin(
					['Ownerships' => 'institution_ownerships'],
					[
						'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
					]
					)
					->leftJoin(
					['Sectors' => 'institution_sectors'],
					[
						'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
					]
					)
					->leftJoin(
					['Areas' => 'areas'],
					[
						'Areas.id = '. $Institutions->aliasField('area_id')
					]
					)
					->leftJoin(
					['AreaAdministratives' => 'area_administratives'],
					[
						'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
					]
					)
					->leftJoin(
					['Providers' => 'institution_providers'],
					[
						'Providers.id = '. $Institutions->aliasField('institution_provider_id')
					]
					)
					->leftJoin(
					['Types' => 'institution_types'],
					[
						'Types.id = '. $Institutions->aliasField('institution_type_id')
					]
					)
					->leftJoin(
					['Localities' => 'institution_localities'],
					[
						'Localities.id = '. $Institutions->aliasField('institution_locality_id')
					]
					)
					->where([$where])
					;
		$areaArray = $sectorArray = $sectorData = $ownershipArray = $localityArray = $typeArray = $providerArray = $areaAdministrativeArray = [];	
		$resultArray = array();
		$i = 0;
		foreach($institutionData as $key => $value) { 
			if($i == 0) { 
				$InstitutionTypesTable = TableRegistry::get('institution_types');
				$InstitutionTypes = $InstitutionTypesTable->find('all')->toArray();
				$InstitutionProvidersTable = TableRegistry::get('institution_providers');
				$InstitutionProviders = $InstitutionProvidersTable->find('all')->toArray();
				$resultArray[0][] = 'atoll';
				$keyy = 0;
				$ki = 1;
				foreach($InstitutionProviders as $keyy => $InstitutionProvider){ 
					foreach($InstitutionTypes as $ki => $InstitutionType){ 

						//**************************************************************************** */
						//********************  Section For Country  Start     *****************************
						//**************************************************************************** */
						$institut = $Institutions->find()
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
									])
									->leftJoin(
									['Ownerships' => 'institution_ownerships'],
									[
										'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
									]
									)
									->leftJoin(
									['Sectors' => 'institution_sectors'],
									[
										'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
									]
									)
									->leftJoin(
									['Areas' => 'areas'],
									[
										'Areas.id = '. $Institutions->aliasField('area_id')
									]
									)
									->leftJoin(
									['AreaAdministratives' => 'area_administratives'],
									[
										'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
									]
									)
									->leftJoin(
									['Providers' => 'institution_providers'],
									[
										'Providers.id = '. $Institutions->aliasField('institution_provider_id')
									]
									)
									->leftJoin(
									['Types' => 'institution_types'],
									[
										'Types.id = '. $Institutions->aliasField('institution_type_id')
									]
									)
									->leftJoin(
									['Localities' => 'institution_localities'],
									[
										'Localities.id = '. $Institutions->aliasField('institution_locality_id')
									]
									)
									->where(['institution_provider_id'=>$InstitutionProvider->id,'institution_type_id'=>$InstitutionType->id,$where])
									;

						//**************************************************************************** */
						//********************  Section For Region  Start     *****************************
						//**************************************************************************** */
						
						$finalArrayyyy =[];
						$AreasData = $AreaT->find('all',['conditions'=>['area_level_id'=> $areaLevelId]])->toArray();
						foreach($AreasData as $KEY => $Area_ins){ 
							$finalArrayyyy[$KEY-1] = $Area_ins->name;
							$institutRegion = $Institutions->find()
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
									])
									->leftJoin(
									['Ownerships' => 'institution_ownerships'],
									[
										'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
									]
									)
									->leftJoin(
									['Sectors' => 'institution_sectors'],
									[
										'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
									]
									)
									->leftJoin(
									['Areas' => 'areas'],
									[
										'Areas.id = '. $Institutions->aliasField('area_id')
									]
									)
									->leftJoin(
									['AreaAdministratives' => 'area_administratives'],
									[
										'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
									]
									)
									->leftJoin(
									['Providers' => 'institution_providers'],
									[
										'Providers.id = '. $Institutions->aliasField('institution_provider_id')
									]
									)
									->leftJoin(
									['Types' => 'institution_types'],
									[
										'Types.id = '. $Institutions->aliasField('institution_type_id')
									]
									)
									->leftJoin(
									['Localities' => 'institution_localities'],
									[
										'Localities.id = '. $Institutions->aliasField('institution_locality_id')
									]
									)
									->where(['area_id'=>$Area_ins->id,'institution_provider_id'=>$InstitutionProvider->id,'institution_type_id'=>$InstitutionType->id])
									;

						}	
						

						//**************************************************************************** */
						//********************  Section For District  Start     *****************************
						//**************************************************************************** */
						
						$finalArrayyyy =[];
						//$$institutDist=[];
						$AreasData = $AreaT->find('all',['conditions'=>['area_level_id'=> $areaLevelId]])->toArray();
						foreach($AreasData as $K => $Area_ins){ 
							$finalArrayyyy[$K-1] = $Area_ins->name;

							$institutDist = $Institutions->find()
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
									])
									->leftJoin(
									['Ownerships' => 'institution_ownerships'],
									[
										'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
									]
									)
									->leftJoin(
									['Sectors' => 'institution_sectors'],
									[
										'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
									]
									)
									->leftJoin(
									['Areas' => 'areas'],
									[
										'Areas.id = '. $Institutions->aliasField('area_id')
									]
									)
									->leftJoin(
									['AreaAdministratives' => 'area_administratives'],
									[
										'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
									]
									)
									->leftJoin(
									['Providers' => 'institution_providers'],
									[
										'Providers.id = '. $Institutions->aliasField('institution_provider_id')
									]
									)
									->leftJoin(
									['Types' => 'institution_types'],
									[
										'Types.id = '. $Institutions->aliasField('institution_type_id')
									]
									)
									->leftJoin(
									['Localities' => 'institution_localities'],
									[
										'Localities.id = '. $Institutions->aliasField('institution_locality_id')
									]
									)
									->where(['area_id'=>$Area_ins->id,'institution_provider_id'=>$InstitutionProvider->id,'institution_type_id'=>$InstitutionType->id])
									;

						}
						//**************************************************************************** */
						//********************  Section For District  END     *****************************
						//**************************************************************************** */

						$resultArray[$key][] = $InstitutionType->name;
						$resultArray['countryData'][]= $institut->count();
						$resultArray['DistrictData'][][$KEY]= $institutDist->count();
						$ki++;
					}
					
				}

				$ShiftOptionTable = TableRegistry::get('shift_options');
				$InsStudentTable = TableRegistry::get('institution_students');
				$ShiftOptions = $ShiftOptionTable->find('all')->toArray();
				$shiftArr = [];
				$shiftArrResult = [];
				foreach($ShiftOptions as $keyy => $ShiftOption){ 
					$shiftArr[$keyy] = 'male';
					$shiftArrResult[$keyy] = $InsStudentTable->find()
					->select([
						//'id','institution_id','student_id'
						'student_id' => 'student_id',
						'academic_period_id' => 'academic_period_id',
						'institution_id' => 'institution_id',
						'student_status_id' => 'student_status_id',
						'gender_id' => 'Students.gender_id',
					])
					
					->leftJoin(
						['Students' => 'security_users'],
						[
							'Students.id = '. $InsStudentTable->aliasField('student_id')
						]
						)
					
					->where(['institution_id in'=>$finalIds,'academic_period_id'=> $academic_period_id,'gender_id'=>1])
					->count();
					

					$shiftArr[$keyy.'1'] = 'female';
					$shiftArrResult[$keyy.'1'] = $InsStudentTable->find()
					->select([
						//'id','institution_id','student_id'
						'student_id' => 'student_id',
						'academic_period_id' => 'academic_period_id',
						'institution_id' => 'institution_id',
						'student_status_id' => 'student_status_id',
						'gender_id' => 'Students.gender_id',
					])
					
					->leftJoin(
						['Students' => 'security_users'],
						[
							'Students.id = '. $InsStudentTable->aliasField('student_id')
						]
						)
					
					->where(['institution_id in'=>$finalIds,'academic_period_id'=> $academic_period_id,'gender_id'=>2])
					->count();
				}
				//echo "<pre>";print_r($shiftArrResult);die;

				$arr = ['0'=>'male','1'=>'female'];
				array_merge($resultArray[0],$shiftArr);
				array_merge($resultArray[1],$shiftArrResult);
				$aa =(array_merge($resultArray[0],$shiftArr));//die;
				$resultArray[0] = $aa;
				$aa1 =(array_merge($resultArray['countryData'],$shiftArrResult));//die;
				$resultArray['countryData'] = $aa1;
				//echo "<pre>";print_r($resultArray);die;
				array_unshift($resultArray['countryData'] , 'Country');
			    $AreasData1 = $AreaT->find('all',['conditions'=>['area_level_id'=> $areaLevelId]])->toArray();
				foreach($AreasData1 as $KEYY => $Area_insss){ 

					//provider & types..
					// $InstitutionsTypesTable = TableRegistry::get('institution_types');
					// $InstitutionsTypes = $InstitutionsTypesTable->find('all')->toArray();
					// $InstitutionProviderssTable = TableRegistry::get('institution_providers');
					// $InstitutionsProviders = $InstitutionProviderssTable->find('all')->toArray();
					// $resultArray[0][] = 'atoll';
					// $keyy = 0;
					// $ki = 1;
					// foreach($InstitutionsProviders as $keyy => $InstitutionsProvider){ 
					// 	foreach($InstitutionsTypes as $ki => $InstitutionsType){ 
					// 		$finalArrayyyy =[];
					// 	//$$institutDist=[];
					// 	$AreasData = $AreaT->find('all',['conditions'=>['area_level_id'=> $areaLevelId]])->toArray();
					// 	foreach($AreasData as $K => $Area_ins){ 
					// 		$finalArrayyyy[$K-1] = $Area_ins->name;

					// 		$institutDist = $Institutions->find()
					// 				->select([
					// 					'ownership_name' => 'Ownerships.name',
					// 					'ownership_id' => 'Ownerships.id',
					// 					'sector_name' => 'Sectors.name',
					// 					'sector_id' => 'Sectors.id',
					// 					'provider_name' => 'Providers.name',
					// 					'provider_id' => 'Providers.id',
					// 					'type_name' => 'Types.name',
					// 					'type_id' => 'Types.id',
					// 					'area_id' => 'Areas.id',
					// 					'area_name' => 'Areas.name',
					// 					'area_code' => 'Areas.code',
					// 					'area_administrative_name' => 'AreaAdministratives.name',
					// 					'area_administrative_id' => 'AreaAdministratives.id',
					// 					'area_administrative_code' => 'AreaAdministratives.code',
					// 					'locality_name' => 'Localities.name',
					// 					'locality_id' => 'Localities.id'
					// 				])
					// 				->leftJoin(
					// 				['Ownerships' => 'institution_ownerships'],
					// 				[
					// 					'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
					// 				]
					// 				)
					// 				->leftJoin(
					// 				['Sectors' => 'institution_sectors'],
					// 				[
					// 					'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
					// 				]
					// 				)
					// 				->leftJoin(
					// 				['Areas' => 'areas'],
					// 				[
					// 					'Areas.id = '. $Institutions->aliasField('area_id')
					// 				]
					// 				)
					// 				->leftJoin(
					// 				['AreaAdministratives' => 'area_administratives'],
					// 				[
					// 					'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
					// 				]
					// 				)
					// 				->leftJoin(
					// 				['Providers' => 'institution_providers'],
					// 				[
					// 					'Providers.id = '. $Institutions->aliasField('institution_provider_id')
					// 				]
					// 				)
					// 				->leftJoin(
					// 				['Types' => 'institution_types'],
					// 				[
					// 					'Types.id = '. $Institutions->aliasField('institution_type_id')
					// 				]
					// 				)
					// 				->leftJoin(
					// 				['Localities' => 'institution_localities'],
					// 				[
					// 					'Localities.id = '. $Institutions->aliasField('institution_locality_id')
					// 				]
					// 				)
					// 				->where(['area_id'=>$Area_ins->id,'institution_provider_id'=>$InstitutionProvider->id,'institution_type_id'=>$InstitutionType->id])
					// 				;

					// 	}
					// 	}
					// }

					$resultArray[$KEYY+1] = $resultArray['DistrictData'];
					array_unshift($resultArray[$KEYY+1], $Area_ins->name);
				}

				//echo "<pre>";print_r($resultArray);die;
			} else {  

				if(!empty($value->area_id)) {
					//if (!in_array($value->area_id, $areaArray)) {
						$resultArray[$key]['area_name'] = $value->area_name; 
					// } else { 
					// 	$resultArray[$key]['area_name'] = '';
					// }
					$areaArray[] = $value->area_id;
				} else {
					$resultArray[$key]['area_name'] = '';
				}
				//echo "<pre>";print_r($resultArray);die;
				// $ShiftOptionTable = TableRegistry::get('shift_options');
				// $ShiftOptions = $ShiftOptionTable->find('all')->toArray();
				// $shiftArr = [];
				// foreach($ShiftOptions as $keyy => $ShiftOption){
				// 	$shiftArr[$keyy] = 'male';
				// 	$shiftArr[$keyy.'1'] = 'female';
				// }
				// // echo "<pre>";print_r($resultArray[0]);
				// // echo "<pre>";print_r($shiftArr);
				// array_merge($resultArray[0],$shiftArr);
				// echo "<pre>";print_r($resultArray);
				// $resultArray[0] =(array_merge($resultArray[0],$shiftArr));die;
				// unset ($shiftArr);
				// echo "<pre>";print_r($resultArray);die;
			}
			$i++;	
		}
		//echo "<pre>";print_r($resultArray);die;
		
		
		// $shiftGenderData = $Institutions->find()
        //             ->select([
        //                 'gender' => 'Genders.name',
        //                 'code' => 'Genders.code',
        //                 'shift_option' => 'ShiftOptions.name',
        //                 'shift_option_id' => 'ShiftOptions.id'
        //             ])
		// 			->innerJoin(
		// 			['InstitutionShifts' => 'institution_shifts'],
		// 			[
		// 				'InstitutionShifts.institution_id = '. $Institutions->aliasField('id')
		// 			]
		// 			)
		// 			->innerJoin(
		// 			['InstitutionStudents' => 'institution_students'],
		// 			[
		// 				'InstitutionStudents.institution_id = '. $Institutions->aliasField('id')
		// 			]
		// 			)
		// 			->innerJoin(
		// 			['ShiftOptions' => 'shift_options'],
		// 			[
		// 				'ShiftOptions.id = InstitutionShifts.shift_option_id'
		// 			]
		// 			)
		// 			->innerJoin(
		// 			['Users' => 'security_users'],
		// 			[
		// 				'Users.id = InstitutionStudents.student_id'
		// 			]
		// 			)
		// 			->innerJoin(
		// 			['Genders' => 'genders'],
		// 			[
		// 				'Genders.id = Users.gender_id'
		// 			]
		// 			);
					//echo "<pre>".print_r($shiftGenderData->toArray());die;
		$shift_gender = array();
		// if(!empty($shiftGenderData)) {	
		// 	foreach($shiftGenderData as $gender_key => $gender_value) {
		// 		if($gender_value->code == 'M') {
		// 			$shift_gender[$gender_value->shift_option_id][$gender_value->code][] = $gender_value->gender;
		// 		} 
		// 		if($gender_value->code == 'F') {
		// 			$shift_gender[$gender_value->shift_option_id][$gender_value->code][] = $gender_value->gender;
		// 		}
		// 	}
		// }
		$totalMale = $totalFemale = 0;
		$genderArray = [];
		$ShiftOptions = TableRegistry::get('ShiftOptions');
		$shiftOptionData = $ShiftOptions->find();
		// if(!empty($shiftOptionData)) {
		// 	foreach($shiftOptionData as $key => $value) {
		// 		$genderArray[$value->name]['male_name'] = 'Male';
		// 		$genderArray[$value->name]['male_code'] = 'M';
		// 		$genderArray[$value->name]['female_name'] = 'Female';
		// 		$genderArray[$value->name]['female_code'] = 'F';
		// 		if(!empty($shift_gender[$value->id])) {
		// 			$genderArray[$value->name]['male_count'] = count($shift_gender[$value->id]['M']);
		// 			$genderArray[$value->name]['female_count'] = count($shift_gender[$value->id]['F']);
		// 			$totalMale = $genderArray[$value->name]['male_count'] + $totalMale;
		// 			$totalFemale = $genderArray[$value->name]['female_count'] + $totalFemale;
		// 		} else {
		// 			$genderArray[$value->name]['male_count'] = 0;
		// 			$genderArray[$value->name]['female_count'] = 0;
		// 		}	
		// 	}
		// }
		// $genderArray['total_gender'] = array('male_name'=> 'Male','male_code'=> 'M','male_count'=> $totalMale, 'female_name'=> 'Female','female_code'=> 'F','female_count'=> $totalFemale);
		
		$data = $area = $locality = $areaAdministrative = $sector = $ownership = $provider = $type = array();
		$areaIndex = $areaAdministrativeIndex = $localityIndex = $sectorIndex = $providerIndex = $ownershipIndex = $typeIndex = NULL;
		
		if(!empty($resultArray)) {
			foreach($resultArray as $key => $result) { //echo "<pre>";print_r($result);die;
				if(array_filter($result)) {
					// if($key == 1) { //echo "<pre>";print_r($key);die;
					// 	foreach($result as $key1 => $value1) {
					// 		$data[$key][$key1] = $value1;
					// 		if($key1 === 'area_name'|| $key1 === 'area_code'|| $key1 === 'area_count') {
					// 			if(!empty($value1)) {
					// 				$areaIndex = key($area);
					// 				if(!empty($areaIndex)) {
					// 					$data[$key][$key1] = '';
					// 				}
					// 				$data[$areaIndex][$key1] = $value1;
					// 			} else {
					// 				$area[$key] = $key;
					// 			}
					// 		}
					// 		if($key1 === 'area_administrative_name'|| $key1 === 'area_administrative_code'|| $key1 === 'area_administrative_count') {
					// 			if(!empty($value1)) {
					// 				$areaAdministrativeIndex = key($areaAdministrative);
					// 				if(!empty($areaAdministrativeIndex)) {
					// 					$data[$key][$key1] = '';
					// 				}
					// 				$data[$areaAdministrativeIndex][$key1] = $value1;
					// 			} else {
					// 				$areaAdministrative[$key] = $key;
					// 			}
					// 		}
					// 		if($key1 === 'locality_name'|| $key1 === 'locality_code'|| $key1 === 'locality_count') {
					// 			if(!empty($value1)) {
					// 				$localityIndex = key($locality);
					// 				if(!empty($localityIndex)) {
					// 					$data[$key][$key1] = '';
					// 				}
					// 				$data[$localityIndex][$key1] = $value1;
					// 			} else {
					// 				$locality[$key] = $key;
					// 			}
					// 		}
					// 		if($key1 === 'sector_name'|| $key1 === 'sector_code'|| $key1 === 'sector_count') {
					// 			if(!empty($value1)) {
					// 				$sectorIndex = key($sector);
					// 				if(!empty($sectorIndex)) {
					// 					$data[$key][$key1] = '';
					// 				}
					// 				$data[$sectorIndex][$key1] = $value1;
					// 			} else {
					// 				$sector[$key] = $key;
					// 			}
					// 		}
					// 		if($key1 === 'ownership_name'|| $key1 === 'ownership_code'|| $key1 === 'ownership_count') {
					// 			if(!empty($value1)) {
					// 				$ownershipIndex = key($ownership);
					// 				if(!empty($ownershipIndex)) {
					// 					$data[$key][$key1] = '';
					// 				}
					// 				$data[$ownershipIndex][$key1] = $value1;
					// 			} else {
					// 				$ownership[$key] = $key;
					// 			}
					// 		}
					// 		if($key1 === 'provider_name'|| $key1 === 'provider_code'|| $key1 === 'provider_count') {
					// 			if(!empty($value1)) {
					// 				$providerIndex = key($provider);
					// 				if(!empty($providerIndex)) {
					// 					$data[$key][$key1] = '';
					// 				}
					// 				$data[$providerIndex][$key1] = $value1;
					// 			} else {
					// 				$provider[$key] = $key;
					// 			}
					// 		}
					// 		if($key1 === 'type_name'|| $key1 === 'type_code'|| $key1 === 'type_count') {
					// 			if(!empty($value1)) {
					// 				$typeIndex = key($type);
					// 				if(!empty($typeIndex)) {
					// 					$data[$key][$key1] = '';
					// 				}
					// 				$data[$typeIndex][$key1] = $value1;
					// 			} else {
					// 				$type[$key] = $key;
					// 			}
					// 		}
					// 	} 
					// 	foreach($genderArray as $maleGender) {
					// 		$data[$key][] = $maleGender['male_code'];
					// 		$data[$key][] = $maleGender['male_name'];
					// 		$data[$key][] = $maleGender['male_count'];
					// 	}
					// }
					// if($key == 2) { //echo "Key2";die;
					// 	foreach($result as $key2 => $value2) {
					// 		$data[$key][$key2] = $value2;
					// 		if($key2 === 'sector_name'|| $key2 === 'sector_code'|| $key2 === 'sector_count') {
					// 			if(!empty($value2)) {
					// 				$sectorIndex = key($sector);
					// 				if(!empty($sectorIndex)) {
					// 					$data[$key][$key2] = '';
					// 				}
					// 				$data[$sectorIndex][$key2] = $value2;
					// 			} else {
					// 				$sector[$key] = $key;
					// 			}
					// 		}
					// 		if($key2 === 'area_name'|| $key2 === 'area_code'|| $key2 === 'area_count') {
					// 			if(!empty($value2)) {
					// 				$areaIndex = key($area);
					// 				if(!empty($areaIndex)) {
					// 					$data[$key][$key2] = '';
					// 				}
					// 				$data[$areaIndex][$key2] = $value2;
					// 			} else {
					// 				$area[$key] = $key;
					// 			}
					// 		}
					// 		if($key2 === 'area_administrative_name'|| $key2 === 'area_administrative_code'|| $key2 === 'area_administrative_count') {
					// 			if(!empty($value2)) {
					// 				$areaAdministrativeIndex = key($areaAdministrative);
					// 				if(!empty($areaAdministrativeIndex)) {
					// 					$data[$key][$key2] = '';
					// 				}
					// 				$data[$areaAdministrativeIndex][$key2] = $value2;
					// 			} else {
					// 				$areaAdministrative[$key] = $key;
					// 			}
					// 		}
					// 		if($key2 === 'locality_name'|| $key2 === 'locality_code'|| $key2 === 'locality_count') {
					// 			if(!empty($value2)) {
					// 				$localityIndex = key($locality);
					// 				if(!empty($localityIndex)) {
					// 					$data[$key][$key2] = '';
					// 				}
					// 				$data[$localityIndex][$key2] = $value2;
					// 			} else {
					// 				$locality[$key] = $key;
					// 			}
					// 		}
					// 		if($key2 === 'ownership_name'|| $key2 === 'ownership_code'|| $key2 === 'ownership_count') {
					// 			if(!empty($value2)) {
					// 				$ownershipIndex = key($ownership);
					// 				if(!empty($ownershipIndex)) {
					// 					$data[$key][$key2] = '';
					// 				}
					// 				$data[$ownershipIndex][$key2] = $value2;
					// 			} else {
					// 				$ownership[$key] = $key;
					// 			}
					// 		}
					// 		if($key2 === 'provider_name'|| $key2 === 'provider_code'|| $key2 === 'provider_count') {
					// 			if(!empty($value2)) {
					// 				$providerIndex = key($provider);
					// 				if(!empty($providerIndex)) {
					// 					$data[$key][$key2] = '';
					// 				}
					// 				$data[$providerIndex][$key2] = $value2;
					// 			} else {
					// 				$provider[$key] = $key;
					// 			}
					// 		}
					// 		if($key2 === 'type_name'|| $key2 === 'type_code'|| $key2 === 'type_count') {
					// 			if(!empty($value2)) {
					// 				$typeIndex = key($type);
					// 				if(!empty($typeIndex)) {
					// 					$data[$key][$key2] = '';
					// 				}
					// 				$data[$typeIndex][$key2] = $value2;
					// 			} else {
					// 				$type[$key] = $key;
					// 			}
					// 		}
					// 	} 
					// 	foreach($genderArray as $femaleGender) {
					// 		$data[$key][] = $femaleGender['female_code'];
					// 		$data[$key][] = $femaleGender['female_name'];
					// 		$data[$key][] = $femaleGender['female_count'];
					// 	}
					// }
					foreach($result as $key3 => $value3) { //echo "Key3";
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
				//die;
				
			}
		}		
		$finalArray = array();
		
		$AreaLevelT = TableRegistry::get('area_levels');
		$AreaT = TableRegistry::get('areas');
		$AreaLevel = $AreaLevelT->find('all',['conditions'=>['id'=>$areaLevelId]])->first();
		if(!empty($data)) {

			if($AreaLevel->level == "1"){
				foreach($data as $data_keyy => $data_roww) { //echo "<pre>";print_r($arrayy);die;
					if($data_keyy === 0) { 
						//array_unshift($data['countryData'] , 'Country');//echo "<pre>";print_r($data_roww['countryData']);die;
						$finalArray[$data_keyy] = $data_roww;
						$finalArray[$data_keyy+1] = $data['countryData'];
					}else{
						unset($data_row);
					}
				}
			}elseif($AreaLevel->level == "2"){
				//find level 2 areas

				unset($data['DistrictData']);
				unset($data['countryData']);
				$AreasData = $AreaT->find('all',['conditions'=>['area_level_id'=>2]])->toArray();
				foreach($data as $data_keyy => $data_roww) {
					if($data_keyy === 0) {
						$finalArray[$data_keyy] = $data_roww;
					}
				}
					//provider & types..
					$InstitutionsTypesTable = TableRegistry::get('institution_types');
					$InstitutionsTypes = $InstitutionsTypesTable->find('all')->toArray();
					$InstitutionProviderssTable = TableRegistry::get('institution_providers');
					$InstitutionsProviders = $InstitutionProviderssTable->find('all')->toArray();
					$resultArray[0][] = 'atoll';
					$keyy = 0;
					$ki = 1;
					foreach($InstitutionsProviders as $keyy => $InstitutionsProvider){ 
						foreach($InstitutionsTypes as $ki => $InstitutionsType){ 
							$finalArrayyyy =[];
							//$$institutDist=[];
							$AreasData = $AreaT->find('all',['conditions'=>['area_level_id'=> $areaLevelId]])->toArray();
							foreach($AreasData as $K => $Area_ins){ 
								$finalArrayyyy[$K-1] = $Area_ins->name;

								$institutDist = $Institutions->find()
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
										])
										->leftJoin(
										['Ownerships' => 'institution_ownerships'],
										[
											'Ownerships.id = '. $Institutions->aliasField('institution_ownership_id')
										]
										)
										->leftJoin(
										['Sectors' => 'institution_sectors'],
										[
											'Sectors.id = '. $Institutions->aliasField('institution_sector_id')
										]
										)
										->leftJoin(
										['Areas' => 'areas'],
										[
											'Areas.id = '. $Institutions->aliasField('area_id')
										]
										)
										->leftJoin(
										['AreaAdministratives' => 'area_administratives'],
										[
											'AreaAdministratives.id = '. $Institutions->aliasField('area_administrative_id')
										]
										)
										->leftJoin(
										['Providers' => 'institution_providers'],
										[
											'Providers.id = '. $Institutions->aliasField('institution_provider_id')
										]
										)
										->leftJoin(
										['Types' => 'institution_types'],
										[
											'Types.id = '. $Institutions->aliasField('institution_type_id')
										]
										)
										->leftJoin(
										['Localities' => 'institution_localities'],
										[
											'Localities.id = '. $Institutions->aliasField('institution_locality_id')
										]
										)
										->where(['area_id'=>$Area_ins->id,'institution_provider_id'=>$InstitutionProvider->id,'institution_type_id'=>$InstitutionType->id])
										;

							}
						}
					}

				$arrayy1[0] = "sd";
				$arrayy1[1] = 10;
				$arrayy1[2] = 10;
				$arrayy1[3] = 10;
				$arrayy1[4] = 10;
				$arrayy1[5] = 10;
				$arrayy1[6] = 10;
				$arrayy1[7] = 10;
				$arrayy1[8] = 10;
				$arrayy1[9] = 10;
				$arrayy1[10] = 10;
				$arrayy1[11] = 10;
				$arrayy1[12] = 10;
				$arrayy1[13] = 10;
				$arrayy1[14] = 10;
				$arrayy1[15] = 10;
				$arrayy1[16] = 10;
				$arrayy1[17] = 10;
				$arrayy1[18] = 10;
				$arrayy1[19] = 10;
				$arrayy1[20] = 10;

				foreach($AreasData as $KEy => $AreaINs){
					$finalArray[$KEy+1] = $arrayy1;
				}

			}else{
				unset($data['DistrictData']);
				unset($data['countryData']);

				$AreasData = $AreaT->find('all',['conditions'=>['area_level_id'=>$areaLevelId]])->toArray();
				foreach($data as $data_key => $data_row) { //echo "<pre>";print_r($data_row);die;
					if($data_key === 0) {
						$finalArray[$data_key] = $data_row;
					}
					
				}
				//
				$arrr =[];
				
				foreach($AreasData as $KEy => $AreaINs){ 
					$arrr[0] =$AreaINs->name;
					$arrr[1] =10;
					$arrr[2] =15;
					$arrr[3] =20;
					
					$arrr[4] = 12;
					$arrr[5] = 43;
					$arrr[6] = 23;
					$arrr[7] = 23;
					$arrr[8] = 23;
					$arrr[9] = 32;
					$arrr[10]= 23;
					$arrr[11]= 23;
					$arrr[12]= 45;
					$arrr[13]= 14;
					$arrr[14]= 15;
					$arrr[15]= 2;
					$arrr[16]= 5;
					$arrr[17]= 7;
					$arrr[18]= 8;
					$arrr[19]= 4;
					$arrr[20]= 4;
					$finalArray[$KEy+1] = $arrr;
					
				}

				//echo "<pre>";print_r($finalArray);die;
			}
			
		}
		//echo "<pre>";print_r($AreaLevel);die;
		//echo "<pre>";print_r($finalArray);die;
		return $finalArray;
	}

    public function generate($settings = [])
    {
		$language = I18n::locale();
		$module = $this->_table->alias();
		//echo '<pre>';print_r($module);
		
		$event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, 'area_education', 'fr'], true);
		return $event;
    }

    private function getFields($table, $settings, $label)
    {
        $language = I18n::locale();
		$module = $this->_table->alias();

		$event = $this->dispatchEvent($this->_table, $this->eventKey('onExcelGetLabel'), 'onExcelGetLabel', [$module, $label, $language], true);
		return $event->result;
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
