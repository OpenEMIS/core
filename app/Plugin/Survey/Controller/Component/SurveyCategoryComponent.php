<?php
App::uses('Component', 'Controller');
class SurveyCategoryComponent extends Component {

    private $controller;
    public $uses = array(
        'Area',
        'AreaEducation',
        'BatchProcess',
        'Reports.Report',
        'Reports.BatchReport',
        'Institution',
        'InstitutionStatus',
        'InstitutionProvider',
        'InstitutionSector',
        'InstitutionCustomField',
        'InstitutionCustomFieldOption',
        'InstitutionCustomValue',
        'InstitutionSite',
        'InstitutionSiteProgramme',
        'InstitutionSiteType',
        'InstitutionSiteStatus',
        'InstitutionSiteLocality',
        'InstitutionSiteType',
        'InstitutionSiteOwnership',
        'InstitutionSiteCustomField',
        'InstitutionSiteCustomFieldOption',
        'InstitutionSiteCustomValue',
        'InstitutionSiteProgramme',
        'EducationProgramme',
        'EducationCertification',
        'EducationGrade',
        'EducationCycle',
        'EducationLevel',
        'CensusStudent',
        'CensusGraduate',
        'CensusAttendance',
        'CensusAssessment',
        'CensusClass',
        'CensusTextbook',
        'CensusTeacher',
        'CensusStaff',
        'CensusBehaviour',
        'CensusBuilding',
        'CensusResource',
        'CensusFurniture',
        'CensusEnergy',
        'CensusRoom',
        'CensusSanitation',
        'CensusWater',
        'CensusFinance',
        'FinanceType',
        'FinanceNature',
        'FinanceSource',
        'FinanceCategory',
        'InfrastructureBuilding',
        'InfrastructureResource',
        'InfrastructureFurniture',
        'InfrastructureEnergy',
        'InfrastructureRoom',
        'InfrastructureSanitation',
        'InfrastructureWater',
        'InfrastructureCategory',
        'InfrastructureMaterial',
        'InfrastructureStatus',
        'SchoolYear',
        'StudentCategory',
        'StaffCategory',
        'EducationGradeSubject',
        'Students.Student',
        'Students.StudentCustomField',
        'Students.StudentCustomFieldOption',
        'Students.StudentCustomValue',
        'StudentBehaviourCategory',
        'Teachers.Teacher',
        'Teachers.TeacherCustomField',
        'Teachers.TeacherCustomFieldOption',
        'Teachers.TeacherCustomValue',
        'Staff.Staff',
        'Staff.StaffCustomField',
        'Staff.StaffCustomFieldOption',
        'Staff.StaffCustomValue'
    );

    //called before Controller::beforeFilter()
    public function initialize(Controller $controller) {
        $this->controller =& $controller;
        $this->init();
    }

    //called after Controller::beforeFilter()
    public function startup(Controller $controller) { }

    //called after Controller::beforeRender()
    public function beforeRender(Controller $controller) { }

    //called after Controller::render()
    public function shutdown(Controller $controller) { }

    //called before Controller::redirect()
    public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) { }

    public function init() {
        foreach($this->uses as $model){
            $this->$model = ClassRegistry::init($model);
        }
    }

    public function clearEmptyQuestions(&$arrayQuestions){
        foreach($arrayQuestions as $topicID => $topicVal) {
            if(count($topicVal)>1){
                foreach($topicVal as $sectID => $sectVal) {
                    if($sectID!='order'){
                        foreach($sectVal as $qID => $qVal) {
                            if($qID=='questions'){
                                if(count($qVal)<1){
                                    unset($arrayQuestions[$topicID][$sectID]);
                                }
                            }
                        }
                    }
                }
            }else{
                unset($arrayQuestions[$topicID]);
            }
        }
    }

    public function getCategoryQuestion($arr){
        $catID = $arr['catID'];
        $siteID = $arr['siteID'];
        $arrayQuestions = array();

        switch($catID){
            case '0':
                $arrayQuestions = $this->getInstitutionQuestions($siteID);
                $this->clearEmptyQuestions($arrayQuestions);
                break;
            case '1':
                $arrayQuestions = $this->getInstitutionSiteQuestions($siteID);
                $this->clearEmptyQuestions($arrayQuestions);
                break;
            case '2':
                $arrayQuestions = $this->getStudentQuestions($siteID);
                $this->clearEmptyQuestions($arrayQuestions);
                break;
            case '3':
                $arrayQuestions = $this->getTeacherQuestions($siteID);
                $this->clearEmptyQuestions($arrayQuestions);
                break;
            case '4':
                $arrayQuestions = $this->getStaffQuestions($siteID);
                $this->clearEmptyQuestions($arrayQuestions);
                break;
        }

        return $arrayQuestions;
    }

    private function getStaffQuestions($siteID=2){
        // Create the Custom Fields
        $customStaffArr = array('tableName'=>'Staff.Staff','id'=>$siteID,'customName'=>'Staff.StaffCustomField','labelName'=>'More','cnt'=>'');

        // Declare the Database Tables to use for Survey
        // ---- Institution Table
        $tableStaff =  array('Staff - INFORMATION'=>array('Staff.Staff'=>array()));
        $tableStaffNames = array('Staff.Staff'=>'General');
        $tableStaffLinkField = array(); // No linkup needed

        $arrayQuestions = array();

        // Some of the methods can be refactored
        // Replace this with for loop and counter if need dynamic populate ---------------------------------------------------------------------------------------------------
        $arrayQuestions = array_merge($arrayQuestions, $this->createFields($tableStaff,$tableStaffLinkField,$tableStaffNames,$customStaffArr,'Single',1,$siteID));
        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------
        $arrayQuestions = array_filter($arrayQuestions);

        // Clear empty arrays
        foreach($arrayQuestions as $topicID => $topicVal) {
            if(count($topicVal)>1){
                foreach($topicVal as $sectID => $sectVal) {
                    if($sectID!='order'){
                        foreach($sectVal as $qID => $qVal) {
                            if($qID=='questions'){
                                if(count($qVal)<1){
                                    unset($arrayQuestions[$topicID][$sectID]);
                                }
                            }
                        }
                    }
                }
            }else{
                unset($arrayQuestions[$topicID]);
            }
        }
        return $arrayQuestions;
    }

    private function getTeacherQuestions($siteID=2){
        // Create the Custom Fields
        $customTeachArr = array('tableName'=>'Teachers.Teacher','id'=>$siteID,'customName'=>'Teachers.TeachersCustomField','labelName'=>'More','cnt'=>'');

        // Declare the Database Tables to use for Survey
        // ---- Institution Table
        $tableTeacher =  array('Teacher - INFORMATION'=>array('Teachers.Teacher'=>array()));
        $tableTeacherNames = array('Teachers.Teacher'=>'General');
        $tableTeacherLinkField = array(); // No linkup needed

        $arrayQuestions = array();

        // Some of the methods can be refactored
        // Replace this with for loop and counter if need dynamic populate ---------------------------------------------------------------------------------------------------
        $arrayQuestions = array_merge($arrayQuestions, $this->createFields($tableTeacher,$tableTeacherLinkField,$tableTeacherNames,$customTeachArr,'Single',1,$siteID));
        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------
        $arrayQuestions = array_filter($arrayQuestions);

        // Clear empty arrays
        foreach($arrayQuestions as $topicID => $topicVal) {
            if(count($topicVal)>1){
                foreach($topicVal as $sectID => $sectVal) {
                    if($sectID!='order'){
                        foreach($sectVal as $qID => $qVal) {
                            if($qID=='questions'){
                                if(count($qVal)<1){
                                    unset($arrayQuestions[$topicID][$sectID]);
                                }
                            }
                        }
                    }
                }
            }else{
                unset($arrayQuestions[$topicID]);
            }
        }
        return $arrayQuestions;
    }

    private function getStudentQuestions($siteID=2){
        // Create the Custom Fields
        $customStudArr = array('tableName'=>'Students.Student','id'=>$siteID,'customName'=>'Students.StudentCustomField','labelName'=>'More','cnt'=>'');

        // Declare the Database Tables to use for Survey
        // ---- Institution Table
        $tableStudent =  array('Student - INFORMATION'=>array('Students.Student'=>array()));
        $tableStudentNames = array('Students.Student'=>'General');
        $tableStudentLinkField = array(); // No linkup needed

        $arrayQuestions = array();

        // Some of the methods can be refactored
        // Replace this with for loop and counter if need dynamic populate ---------------------------------------------------------------------------------------------------
        $arrayQuestions = array_merge($arrayQuestions, $this->createFields($tableStudent,$tableStudentLinkField,$tableStudentNames,$customStudArr,'Single',1,$siteID));
        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------
        $arrayQuestions = array_filter($arrayQuestions);

        // Clear empty arrays
        foreach($arrayQuestions as $topicID => $topicVal) {
            if(count($topicVal)>1){
                foreach($topicVal as $sectID => $sectVal) {
                    if($sectID!='order'){
                        foreach($sectVal as $qID => $qVal) {
                            if($qID=='questions'){
                                if(count($qVal)<1){
                                    unset($arrayQuestions[$topicID][$sectID]);
                                }
                            }
                        }
                    }
                }
            }else{
                unset($arrayQuestions[$topicID]);
            }
        }
        return $arrayQuestions;
    }

    private function getInstitutionQuestions($siteID=2){
        // Create the Custom Fields
        $customInfoArr = array('tableName'=>'Institution','id'=>$siteID,'customName'=>'InstitutionCustomField','labelName'=>'More','cnt'=>'');

        // Declare the Database Tables to use for Survey
        // ---- Institution Table
        $tableInstitution =  array('Institution - INFORMATION'=>array('Institution'  =>array('InstitutionStatus','InstitutionProvider','InstitutionSector')));
        $tableInstitutionNames = array('Institution'=>'General');
        $tableInstitutionLinkField = array(); // No linkup needed

        $arrayQuestions = array();

        // Some of the methods can be refactored
        // Replace this with for loop and counter if need dynamic populate ---------------------------------------------------------------------------------------------------
        $arrayQuestions = array_merge($arrayQuestions, $this->createFields($tableInstitution,$tableInstitutionLinkField,$tableInstitutionNames,$customInfoArr,'Single',1,$siteID));
        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------
        $arrayQuestions = array_filter($arrayQuestions);

        // Clear empty arrays
        foreach($arrayQuestions as $topicID => $topicVal) {
            if(count($topicVal)>1){
                foreach($topicVal as $sectID => $sectVal) {
                    if($sectID!='order'){
                        foreach($sectVal as $qID => $qVal) {
                            if($qID=='questions'){
                                if(count($qVal)<1){
                                    unset($arrayQuestions[$topicID][$sectID]);
                                }
                            }
                        }
                    }
                }
            }else{
                unset($arrayQuestions[$topicID]);
            }
        }
        return $arrayQuestions;
    }

    public function getInstitutionSiteQuestions($siteID=2){
        // Create the Custom Fields
        $customInfoSiteArr = array('tableName'=>'InstitutionSite','id'=>$siteID,'customName'=>'InstitutionSiteCustomField','labelName'=>'More', 'cnt'=>'');

        // Declare the Database Tables to use for Survey
        // ---- Institution Site Tables
        $tableInstitutionSite =  array('Institution Site - INFORMATION'=>array('InstitutionSite'=>array('InstitutionSiteStatus','InstitutionSiteLocality','InstitutionSiteType',
            'InstitutionSiteOwnership', 'Area', 'AreaEducation')));
        $tableInstitutionSiteNames = array('InstitutionSite'=>'General');
        $tableInstitutionSiteLinkField = array(); // No linkup needed

        $arrayQuestions = array();


        // Some of the methods can be refactored
        // Replace this with for loop and counter if need dynamic populate ---------------------------------------------------------------------------------------------------
        $arrayQuestions = array_merge($arrayQuestions, $this->createFields($tableInstitutionSite,$tableInstitutionSiteLinkField,
            $tableInstitutionSiteNames,$customInfoSiteArr,'Single',1,$siteID));
        $arrayQuestions = array_merge($arrayQuestions, $this->getInstitutionCensusQuestions(2, $siteID));
        //----------------------------------------------------------------------------------------------------------------------------------------------------------------------
        $arrayQuestions = array_filter($arrayQuestions);

        // Clear empty arrays
        foreach($arrayQuestions as $topicID => $topicVal) {
            if(count($topicVal)>1){
                foreach($topicVal as $sectID => $sectVal) {
                    if($sectID!='order'){
                        foreach($sectVal as $qID => $qVal) {
                            if($qID=='questions'){
                                if(count($qVal)<1){
                                    unset($arrayQuestions[$topicID][$sectID]);
                                }
                            }
                        }
                    }
                }
            }else{
                unset($arrayQuestions[$topicID]);
            }
        }
        return $arrayQuestions;
    }

    public function getInstitutionCensusQuestions($index, $siteID){
        // Get All Infrastructure Categories
        $arrInfrastructure = $this->InfrastructureCategory->find('all', array('fields'=>array('id','name'),'conditions'=>array('visible'=>1)));

        // ---- Census Tables
        $tableCensus =  array('Institution Site - TOTALS'=>array('CensusStudent'  =>array('EducationGrade','StudentCategory','EducationProgramme'),
                'CensusTeacher' =>array('EducationLevel','EducationProgramme'),
                'CensusStaff' =>array('StaffCategory'),
                'CensusClass' =>array('EducationGrade','EducationProgramme'),
                'CensusGraduate' =>array('EducationProgramme','EducationCertification','EducationLevel'),
                'CensusAttendance' =>array('EducationProgramme','EducationGrade'),
                'CensusAssessment' =>array('EducationProgramme','EducationGrade','EducationGradeSubject'),
                'CensusBehaviour' =>array('StudentBehaviourCategory'),
                'CensusTextbook' =>array('EducationGrade','EducationProgramme','EducationGradeSubject'),
                'CensusFinance' =>array('FinanceType', 'FinanceNature', 'FinanceSource', 'FinanceCategory')
            ));

        // ---- Add InfraStructure to Census Tables
        foreach($arrInfrastructure as $infrakey => $infraname) {
            foreach($infraname as $id => $infraVal) {
                $name = rtrim($infraVal['name'],'s');
                switch($name){
                    case 'Building':
                        $tableCensus['Institution Site - TOTALS'] += array('Census'.$name =>array($infraVal['id'],
                            'Infrastructure'.$name,
                            'InfrastructureStatus',
                            'InfrastructureMaterial'));
                        break;
                    case 'Resource':
                        $tableCensus['Institution Site - TOTALS'] += array('Census'.$name =>array($infraVal['id'],
                            'Infrastructure'.$name,
                            'InfrastructureStatus'));
                        break;
                    case 'Furniture':
                        $tableCensus['Institution Site - TOTALS'] += array('Census'.$name =>array($infraVal['id'],
                            'Infrastructure'.$name,
                            'InfrastructureStatus'));
                        break;
                    case 'Energy':
                        $tableCensus['Institution Site - TOTALS'] += array('Census'.$name =>array($infraVal['id'],
                            'Infrastructure'.$name,
                            'InfrastructureStatus'));
                        break;
                    case 'Room':
                        $tableCensus['Institution Site - TOTALS'] += array('Census'.$name =>array($infraVal['id'],
                            'Infrastructure'.$name,
                            'InfrastructureStatus'));
                        break;
                    case 'Sanitation':
                        $tableCensus['Institution Site - TOTALS'] += array('Census'.$name =>array($infraVal['id'],
                            'Infrastructure'.$name,
                            'InfrastructureStatus',
                            'InfrastructureMaterial'));
                        break;
                    case 'Water':
                        $tableCensus['Institution Site - TOTALS'] += array('Census'.$name =>array($infraVal['id'],
                            'Infrastructure'.$name,
                            'InfrastructureStatus'));
                        break;
                }

            }

        }

        // This are fields that are not in the table schema but are required to assist user in filling up survey
        $tableCensusLinkField = array(
            'CensusStudent' => array('education_programme_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                )),
            'CensusGraduate' => array('education_certification_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                ),
                'education_level_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                )),
            'CensusAttendance' => array('education_grade_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                ),
                'education_programme_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                )),
            'CensusAssessment' => array('education_grade_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                ),
                'education_programme_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                )),
            'CensusClass' => array('education_grade_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                ),
                'education_programme_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                )),
            'CensusTextbook' => array('education_grade_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                ),
                'education_programme_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                )),
            'CensusTeacher' => array('education_level_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                ),
                'education_programme_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                )),
            'CensusFinance' => array('finance_type_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                ),
                'finance_nature_id' => array
                (
                    'type' => 'integer',
                    'null' => '',
                    'length' => 11
                )));
        $tableCensusNames = array('CensusStudent'=>'Students',
                                    'CensusTeacher'=>'Teachers',
                                    'CensusStaff'=>'Staff',
                                    'CensusClass'=>'Classes',
                                    'CensusGraduate'=>'Graduates',
                                    'CensusAttendance'=>'Attendance',
                                    'CensusAssessment'=>'Assessment',
                                    'CensusBehaviour'=>'Behaviour',
                                    'CensusTextbook'=>'Textbooks',
                                    'CensusBuilding'=>'Infrastructure - Building',
                                    'CensusFinance'=>'Finance');

        // Add InfraStructure Names to Census Tables
        foreach($arrInfrastructure as $infrakey => $infraname) {
            foreach($infraname as $id => $infraVal) {
                $name = rtrim($infraVal['name'],'s');
                $tableCensusNames += array('Census'.$name =>'Infrastructure - '.$name);
            }
        }

        $arrayQuestions = array();
        $arrayQuestions = $this->createFields($tableCensus,$tableCensusLinkField,$tableCensusNames,'','Grid',$index,$siteID);

        $this->customisedArray($arrayQuestions); // Applying Custom Names/Heading Format Style for Survey

        return $arrayQuestions;
    }

    private function customisedArray(&$arrayQuestions)
    {
        $newArray = array();
        foreach($arrayQuestions['Institution Site - TOTALS'] as $name=>$val)
        {
            switch($name){
                case 'CensusClass':
                    // Customise Census Class so that Survey can show two types -------------------------------------------------------------------------
                    $newArray[$name] = array(	'null'=>'1',
                        'order'=> $arrayQuestions['Institution Site - TOTALS']['CensusClass']['order'],
                        'type'=> 'Grid_Multi',
                        'label'=> 'Classes',
                        'value' => 'Category',
                        'questions'=> array('SingleGradeClass'	=>$arrayQuestions['Institution Site - TOTALS']['CensusClass'],
                                            'MultiGradeClass'	=>  array()
                        ));

                    // Single Grade Options
                    $newArray[$name]['questions']['SingleGradeClass']['order'] = '1';
                    $newArray[$name]['questions']['SingleGradeClass']['label'] = 'Single Grade Classes Only';

                    // Multi Grade Options
                    $newArray[$name]['questions']['MultiGradeClass']['order']  = '2';
                    $newArray[$name]['questions']['MultiGradeClass']['label'] = 'Multi Grade Classes';
                    $newArray[$name]['questions']['MultiGradeClass']['type']  = 'Grid_Unlimited';
                    $newArray[$name]['questions']['MultiGradeClass']['value']  = array();
                    $newArray[$name]['questions']['MultiGradeClass']['questions'] = $arrayQuestions['Institution Site - TOTALS']['CensusClass']['questions'];

                    // Multi Grade - Grades Options
                    $newArray[$name]['questions']['MultiGradeClass']['questions']['Grades']['order']  = '1';
                    $newArray[$name]['questions']['MultiGradeClass']['questions']['Grades']['label'] = 'Grades';
                    $newArray[$name]['questions']['MultiGradeClass']['questions']['Grades']['type']  = 'Grid_Unlimited';
                    $newArray[$name]['questions']['MultiGradeClass']['questions']['Grades']['box']  = '1';
                    $newArray[$name]['questions']['MultiGradeClass']['questions']['Grades']['value']  = array();
                    $newArray[$name]['questions']['MultiGradeClass']['questions']['Grades']['questions']  = $arrayQuestions['Institution Site - TOTALS']['CensusClass']['questions'];

                    // Remove duplicated columns in Grades
                    unset($newArray[$name]['questions']['MultiGradeClass']['questions']['Grades']['questions']['classes']);
                    unset($newArray[$name]['questions']['MultiGradeClass']['questions']['Grades']['questions']['seats']);
                    unset($newArray[$name]['questions']['MultiGradeClass']['questions']['education_programme_id']);
                    unset($newArray[$name]['questions']['MultiGradeClass']['questions']['education_grade_id']);

                    // End Customise Census Class so that Survey can show two types ---------------------------------------------------------------------
                    break;

                case 'CensusBuilding':
                    // Customise Infrastructure Class so that Survey can group them all -----------------------------------------------------------------
                    $newArray['Infrastructure'] = array('null'=>'1',
                        'order'=> $arrayQuestions['Institution Site - TOTALS']['CensusBuilding']['order'],
                        'type'=> 'Grid_Multi',
                        'label'=> 'Infrastructure',
                        'value' => 'Category',
                        'questions'=> array('CensusBuilding'	=>$arrayQuestions['Institution Site - TOTALS']['CensusBuilding'],
                            'CensusResource'	=>$arrayQuestions['Institution Site - TOTALS']['CensusResource'],
                            'CensusFurniture'	=>$arrayQuestions['Institution Site - TOTALS']['CensusFurniture'],
                            'CensusEnergy'		=>$arrayQuestions['Institution Site - TOTALS']['CensusEnergy'],
                            'CensusRoom'		=>$arrayQuestions['Institution Site - TOTALS']['CensusRoom'],
                            'CensusSanitation'	=>$arrayQuestions['Institution Site - TOTALS']['CensusSanitation'],
                            'CensusWater'		=>$arrayQuestions['Institution Site - TOTALS']['CensusWater'],
                        ));
                    // End Customise Infrastructure Class so that Survey can group them all -------------------------------------------------------------
                    $cnt = 1;
                    foreach($newArray['Infrastructure']['questions'] as $name=>$val){
                        $newArray['Infrastructure']['questions'][$name]['order'] = $cnt;
                        $label = explode('-', $newArray['Infrastructure']['questions'][$name]['label']);
                        $newArray['Infrastructure']['questions'][$name]['label'] = $label[count($label)-1];
                        $cnt++;
                    }
                    break;

                case 'CensusResource':
                    break;

                case 'CensusFurniture':
                    break;

                case 'CensusEnergy':
                    break;

                case 'CensusRoom':
                    break;

                case 'CensusSanitation':
                    break;

                case 'CensusWater':
                    break;

                default:
                    $newArray[$name] = $val;
                    break;
            }
        }
        $arrayQuestions['Institution Site - TOTALS'] = $newArray;
    }

    private function getLabel($table,$field){
        $arrLabel = array(
            'Institution' => array(
                'name' => 'Institution Name',
                'institution_Status_id' => 'Status',
                'institution_provider_id' => 'Provider',
                'institution_sector_id' => 'Sector',
                'date_open' => 'Date Open'
            )
        );
        if(isset($arrLabel[$table][$field]))
            return $arrLabel[$table][$field];
        else
            return false;
    }

    private function createMapping($myTable,$tbName,$allowFields){
        $myMapTable = array();
        $myData = array();
        $myMapTable = $this->{$myTable}->belongsTo;
        if(is_array($myMapTable) && !empty($myMapTable)){
            $field1 = Inflector::underscore($myTable)."_id";
            foreach($myMapTable as $name=>$fTablename){
                foreach($fTablename as $name=>$field2){
                    if($name=='foreignKey'){
                        $data = $this->{$myTable}->find('list',array('fields'=>array($field2,'id'),
                            'conditions'=>array('visible'=>1),
                            'group' => array("`".$field2."`,`id`")));

                        $data2 = $this->{$myTable}->find('list',array('fields'=>array('id',$field2),
                            'conditions'=>array('visible'=>1),
                            'group' => array("`".$field2."`,`id`")));
                        if (in_array($field1, $allowFields) && in_array($field2, $allowFields)) {
                            $myData = array('fields'=>array(),'ids'=>array());
                            $myData['fields'] = $myData['fields'] + array($field2=>$field1);
                            foreach($data as $valname=>$valfield){
                                $temp = array();
                                foreach($data2 as $valname2=>$valfield2){
                                    if($valname==$valfield2){
                                        $temp[] = $valname2;
                                    }
                                }
                                $myData['ids'] = $myData['ids'] + array(str_replace(' ', '', $valname)=>$temp);
                            }
                        }
                    }
                }

                // Added for education level type
                if($field1=='education_level_id'){

                    $field2='education_programme_id';

                    $data = $this->{'EducationProgramme'}->find('all', array(
                        'recursive' => -1,
                        'joins' => array(
                            array(
                                'table' => 'education_cycles',
                                'alias' => 'EducationCycle',
                                'type' => 'INNER',
                                'conditions' => array(
                                    'EducationCycle.id = EducationProgramme.education_cycle_id',
                                    'EducationCycle.visible = 1'
                                )
                            ),
                            array(
                                'table' => 'education_levels',
                                'alias' => 'EducationLevel',
                                'type' => 'INNER',
                                'conditions' => array(
                                    'EducationLevel.id = EducationCycle.education_level_id',
                                    'EducationLevel.visible = 1'
                                )
                            )
                        ),
                        'fields' => array('EducationProgramme.id','EducationLevel.id'),
                        'conditions' => array("EducationProgramme.visible" => "1")
                    ));
                    $data = Set::combine($data, '{n}.EducationProgramme.id', array('{0} {1}', '{n}.EducationLevel.id'));

                    if(count($myData)<1){
                        $myData = array('fields'=>array(),'ids'=>array());
                    }
                    $myData['fields'] = $myData['fields'] + array($field1=>$field2);
                    foreach($data as $valname=>$valfield){
                        $temp = array();
                        foreach($data as $valname2=>$valfield2){
                            if($valfield==$valfield2){
                                $temp[] = $valname2;
                            }
                        }
                        $myData['ids'] = $myData['ids'] + array(str_replace(' ', '', $valfield)=>$temp);
                    }
                }
            }
        }

        return $myData;
    }

    private function removeFields($arrTable){
        $excludedColumns = array('id','modified','modified_user_id','school_year_id','created_user_id', 'institution_id', 'institution_site_id',
            'institution_site_programme_id','photo_name','photo_content','source','created','visible','order');
        $excludeProp  = array('default','collate','charset','key');
        foreach ($excludedColumns as  $value) {
            if(isset($arrTable[$value])){ unset($arrTable[$value]); continue; }

        }
        foreach ($arrTable as $k => &$arrvalue){
            foreach($excludeProp as $valuep){
                unset($arrvalue[$valuep]);

            }
        }

        return $arrTable;
    }

    private function applyTableFilter($arr,$siteID){
        $fTablename = $arr['tbl'];
        $infrastructure_category_id = $arr['catId'];
        $result = array();
        switch($fTablename){
            case 'InfrastructureStatus':
                $result = $this->{$fTablename}->find('list', array('conditions' => array("infrastructure_category_id" => $infrastructure_category_id,
                    "visible" => "1")));
                break;
            case 'InfrastructureMaterial':
                $result = $this->{$fTablename}->find('list', array('conditions' => array("infrastructure_category_id" => $infrastructure_category_id,
                    "visible" => "1")));
                break;
            case 'Area':
                $lowest_level = Set::flatten($this->{$fTablename}->query('SELECT MAX(`level`) FROM `area_levels`'));
                $lowest_level = $lowest_level[key($lowest_level)];
                $result = $this->{$fTablename}->find('list', array('conditions' => array("area_level_id" => $lowest_level,"visible" => "1")));
                break;
            case 'AreaEducation':
                $lowest_level = Set::flatten($this->{$fTablename}->query('SELECT MAX(`level`) FROM `area_education_levels`'));
                $lowest_level = $lowest_level[key($lowest_level)];
                $result = $this->{$fTablename}->find('list', array('conditions' => array("area_education_level_id" => $lowest_level,"visible" => "1")));
                break;
            case 'EducationProgramme':
                $result = $this->{$fTablename}->find('all', array(
                    'recursive' => -1,
                    'joins' => array(
                        array(
                            'table' => 'education_cycles',
                            'alias' => 'EducationCycle',
                            'type' => 'INNER',
                            'conditions' => array(
                                'EducationCycle.id = EducationProgramme.education_cycle_id',
                                'EducationCycle.visible = 1'
                            )
                        ),
                        array(
                            'table' => 'education_levels',
                            'alias' => 'EducationLevel',
                            'type' => 'INNER',
                            'conditions' => array(
                                'EducationLevel.id = EducationCycle.education_level_id',
                                'EducationLevel.visible = 1'
                            )
                        ),
                        array(
                            'table' => 'institution_site_types',
                            'alias' => 'InstitutionSiteType',
                            'type' => 'INNER',
                            'conditions' => array(
                                'InstitutionSiteType.name = EducationLevel.name',
                                'InstitutionSiteType.visible = 1'
                            )
                        )
                    ),
                    'fields' => array('EducationProgramme.id', 'EducationProgramme.name', 'EducationCycle.name'),
                    'conditions' => array("EducationProgramme.visible" => "1")
                ));
                $result = Set::combine($result, '{n}.EducationProgramme.id', array('{0} {1}', '{n}.EducationCycle.name', '{n}.EducationProgramme.name'));
                break;
            case 'EducationGradeSubject':
                $result = $this->{$fTablename}->find('all', array(
                    'recursive' => -1,
                    'joins' => array(
                        array(
                            'table' => 'education_subjects',
                            'alias' => 'EducationSubject',
                            'type' => 'INNER',
                            'conditions' => array(
                                'EducationSubject.id = EducationGradeSubject.education_subject_id',
                                'EducationSubject.visible = 1'
                            )
                        )
                    ),
                    'fields' => array('EducationGradeSubject.id', 'EducationSubject.name'),
                    'conditions' => array("EducationGradeSubject.visible" => "1")
                ));
                $result = Set::combine($result, '{n}.EducationGradeSubject.id', array('{0} {1}', '{n}.EducationSubject.name'));
                break;
            case 'EducationLevel':
                $result = $this->{$fTablename}->find('all', array(
                    'recursive' => -1,
                    'joins' => array(
                        array(
                            'table' => 'education_cycles',
                            'alias' => 'EducationCycle',
                            'type' => 'INNER',
                            'conditions' => array(
                                'EducationCycle.education_level_id = EducationLevel.id',
                                'EducationCycle.visible = 1'
                            )
                        ),
                        array(
                            'table' => 'institution_site_types',
                            'alias' => 'InstitutionSiteType',
                            'type' => 'INNER',
                            'conditions' => array(
                                'InstitutionSiteType.name = EducationLevel.name',
                                'InstitutionSiteType.visible = 1'
                            )
                        )
                    ),
                    'fields' => array('EducationLevel.id', 'EducationLevel.name'),
                    'conditions' => array("EducationLevel.visible" => "1")
                ));
                $result = Set::combine($result, '{n}.EducationLevel.id', array('{0} {1}', '{n}.EducationLevel.name'));
                break;
            case 'EducationCertification':
                $result = $this->{$fTablename}->find('all', array(
                    'recursive' => -1,
                    'joins' => array(
                        array(
                            'table' => 'education_programmes',
                            'alias' => 'EducationProgramme',
                            'type' => 'INNER',
                            'conditions' => array(
                                'EducationProgramme.education_certification_id = EducationCertification.id',
                                'EducationProgramme.visible = 1'
                            )
                        ),
                        array(
                            'table' => 'education_cycles',
                            'alias' => 'EducationCycle',
                            'type' => 'INNER',
                            'conditions' => array(
                                'EducationCycle.id = EducationProgramme.education_cycle_id',
                                'EducationCycle.visible = 1'
                            )
                        ),
                        array(
                            'table' => 'education_levels',
                            'alias' => 'EducationLevel',
                            'type' => 'INNER',
                            'conditions' => array(
                                'EducationLevel.id = EducationCycle.education_level_id',
                                'EducationLevel.visible = 1'
                            )
                        ),
                        array(
                            'table' => 'institution_site_types',
                            'alias' => 'InstitutionSiteType',
                            'type' => 'INNER',
                            'conditions' => array(
                                'InstitutionSiteType.name = EducationLevel.name',
                                'InstitutionSiteType.visible = 1'
                            )
                        )
                    ),
                    'fields' => array('EducationCertification.id', 'EducationCertification.name'),
                    'conditions' => array("EducationCertification.visible" => "1")
                ));
                $result = Set::combine($result, '{n}.EducationCertification.id', array('{0} {1}', '{n}.EducationCertification.name'));
                break;
            default:
                try{
                    $result = $this->{$fTablename}->find('list', array('conditions' => array("visible" => "1")));
                }catch(Exception $e){
                    $result = $this->{$fTablename}->find('list');
                }
                break;
        }
        return $result;
    }

    private function createFields($arr,$arrlink,$arrNames,$customArr,$type,$i,$siteID){
        $arrayQuestions = array();
        foreach($arr as $censusPackage => $censusTables){
            $cntr = 1;
            $infrastructure_category_id = 0;
            foreach($censusTables as $tablename => $foreignTables){
                $customName = explode(".", $tablename);
                $customName = $customName[count($customName)-1];
                isset($arrlink[$tablename])? $linkFields = $arrlink[$tablename]: $linkFields = array();
                if($type=='Grid'){
                    $arrayQuestions[$censusPackage][$customName] = array('null'=>'1','order'=>$cntr,'type'=>'Grid','label'=>__($arrNames[$tablename]),'value'=>array());
                }else{
                    $arrayQuestions[$censusPackage][$customName] = array('null'=>'1','order'=>$cntr,'type'=>'Single','label'=>__($arrNames[$tablename]));
                }
                $arrayQuestions[key($arr)][$customName]["questions"] = array_merge($this->removeFields($this->{$tablename}->schema()),$linkFields);

                // Order the sequence of questions
                $this->sortOrder($arrayQuestions[key($arr)][$customName],$customName);

                if(is_array($foreignTables) && !empty($foreignTables)){
                    $arrayMap = array();
                    $arrayQuestionsMap = array();
                    foreach($foreignTables as $fTablename){
                        if(is_numeric($fTablename)){ // For Infrastructure only
                            $infrastructure_category_id =  $fTablename;
                        }else{
                            $filterArr = array('tbl'=>$fTablename,
                                'catId'=>$infrastructure_category_id);

                            // Apply Database Constraint to get the items for foreign key fields
                            $items['items'] = $this->applyTableFilter($filterArr,$siteID);

                            // Do the mapping rule for the foreign keys -----------------------------------------------------------------------------------
                            if($type=='Grid'){
                                $arrayQuestionsMap = $this->createMapping($fTablename,$tablename,array_keys($arrayQuestions[key($arr)][$tablename]["questions"]));

                                if($arrayQuestionsMap){
                                    $arrayMap['Mapping'][] =$arrayQuestionsMap;
                                }
                            }
                            // End Mapping rule -----------------------------------------------------------------------------------------------------------
                            $arrayQuestions[key($arr)][$customName]["questions"][Inflector::underscore($fTablename)."_id"] = array_merge($arrayQuestions[key($arr)][$customName]["questions"][Inflector::underscore($fTablename)."_id"],$items);
                        }
                    }
                    if($type=='Grid'){
                        $arrayQuestions[key($arr)][$customName]["Rule"] = $arrayMap; // Assign mapping to question array
                    }
                }
                $ctr = 1;
                foreach($arrayQuestions[key($arr)][$customName]["questions"] as $colname => &$arrproperties){
                    $label = $this->getLabel($customName, $colname);
                    //$arrproperties['label'] =  ($label)?$label:Inflector::humanize($colname);
                    $arrproperties['order'] =  $ctr++;
                    if($type=='Single'){
                        $arrproperties['value'] =  '';
                    }
                }
                $cntr++;
            }

            // If there are custom Fields appended to this Section
            if(is_array($customArr)){
                $customArr['cnt'] = $cntr;
                $arrayQuestions[$censusPackage]= array_merge($arrayQuestions[$censusPackage], $this->createCustomFields($customArr));
            }

            // Set the order no
            $arrayQuestions[$censusPackage] = array_merge(array('order'=>$i),$arrayQuestions[$censusPackage]);
        }

        return $arrayQuestions;
    }

    private function createCustomFields($arr){
        // Declare variables
        $tableName = $arr['tableName'];
        $actualtableName = explode(".", $tableName);
        $actualtableName = $actualtableName[count($actualtableName)-1];
        $customName = explode(".", $arr['customName']);
        $customName = $customName[count($customName)-1];
        $labelName = $arr['labelName'];
        $id = $arr['id'];
        $i = $arr['cnt'];

        // Database Values Lookup
        switch($tableName){
            case 'InstitutionSite':
                $datafields = $this->{$tableName.'CustomField'}->find('all',array('conditions'=>array('visible'=>1,
                    'institution_site_type_id'=>$id)));
                $this->{$tableName.'CustomValue'}->unbindModel(array('belongsTo' => array($actualtableName)));
                $datavalues = $this->{$tableName.'CustomValue'}->find('all',array('conditions'=>array('institution_site_id'=>$id)));
                break;

            default:
                $datafields = $this->{$tableName.'CustomField'}->find('all',array('conditions'=>array('visible'=>1),
                    'order'=>'order'));
                $this->{$tableName.'CustomValue'}->unbindModel(array('belongsTo' => array($actualtableName)));
                $datavalues = $this->{$tableName.'CustomValue'}->find('all');
                break;
        }

        // Restructure the data to match schema pattern
        $tmp=array();
        foreach($datavalues as $arrV){
            $tmp[$arrV[$actualtableName.'CustomField']['id']][] = $arrV[$actualtableName.'CustomValue'];
        }
        $datavalues = $tmp;
        $arrayX = array();
        $arrayQuestions = array();

        foreach($datafields as $arrVals){
            if($arrVals[$actualtableName.'CustomField']['type']!=1){
                switch($arrVals[$actualtableName.'CustomField']['type']){
                    case 2:
                        $arrayX =array_merge($arrayX, array($arrVals[$actualtableName.'CustomField']['name']=>array("type"=>"string","null"=>"1","length"=>150)));
                        break;
                    case 3:
                        $arrOptions = array();
                        foreach($arrVals[$actualtableName.'CustomFieldOption'] as $arrDropDownVal){
                            $arrOptions = $arrOptions + array($arrDropDownVal['id']=>$arrDropDownVal['value']);
                        }
                        $arrayX =array_merge($arrayX, array($arrVals[$actualtableName.'CustomField']['name']=>array("type"=>"integer","null"=>"1","length"=>11,
                            "items"=>$arrOptions)));
                        break;
                    case 4:
                        $arrOptions = array();
                        foreach($arrVals[$actualtableName.'CustomFieldOption'] as $arrDropDownVal){
                            $arrOptions = $arrOptions + array($arrDropDownVal['id']=>$arrDropDownVal['value']);
                        }
                        $arrayX =array_merge($arrayX, array($arrVals[$actualtableName.'CustomField']['name']=>array("type"=>"checkbox","null"=>"1","length"=>11,
                            "items"=>$arrOptions)));
                        break;
                    case 5:
                        $arrayX =array_merge($arrayX, array($arrVals[$actualtableName.'CustomField']['name']=>array("type"=>"text","null"=>"1","length"=>150)));
                        break;

                }

            }
        }

        // The Custom Fields Questions for Survey
        $arrayQuestions[$customName]['questions'] = array_merge($arrayQuestions, $arrayX);

        $ctr = 1;
        foreach($arrayQuestions[$customName]['questions'] as $colname => &$arrproperties){
            $label = $this->getLabel($customName, $colname);
            $arrproperties['label'] =  ($label)?$label:Inflector::humanize($colname);
            $arrproperties['value'] =  '';
            $arrproperties['order'] =  $ctr++;
        }

        // The order no and custom label names
        $arrayQuestions[$customName] = array_merge(array('null'=>'1','order'=>$i,'type'=>'Single','label'=>__($labelName)),$arrayQuestions[$customName]);

        return $arrayQuestions;
    }


    // Re-Order Elements function
    private function sortOrder(&$arr, $tblName){
        switch($tblName){
            case 'Staff':
                $arr['questions'] = array("identification_no" => array_merge($arr['questions']["identification_no"],array("label"=>'OpenEMIS ID')),
                    "first_name" => array_merge($arr['questions']["first_name"],array("label"=>'First Name')),
                    "last_name" => array_merge($arr['questions']["last_name"],array("label"=>'Last Name')),
                    "gender" => array_merge($arr['questions']["gender"],array("label"=>'Gender', "items"=>array('Male','Female'))),
                    "date_of_birth" => array_merge($arr['questions']["date_of_birth"],array("label"=>'Date of Birth')),
                    "address" => array_merge($arr['questions']["address"],array("label"=>'Address')),
                    "postal_code" => array_merge($arr['questions']["postal_code"],array("label"=>'Postal Code')),
                    "address_area_id" => array_merge($arr['questions']["address_area_id"],array("label"=>'Address Area')),
                    "birthplace_area_id" => array_merge($arr['questions']["birthplace_area_id"],array("label"=>'Birth Place Area')),
                    "telephone" => array_merge($arr['questions']["telephone"],array("label"=>'Telephone')),
                    "email" => array_merge($arr['questions']["email"],array("label"=>'Email')));
                break;

            case 'Teacher':
                $arr['questions'] = array("identification_no" => array_merge($arr['questions']["identification_no"],array("label"=>'OpenEMIS ID')),
                    "first_name" => array_merge($arr['questions']["first_name"],array("label"=>'First Name')),
                    "last_name" => array_merge($arr['questions']["last_name"],array("label"=>'Last Name')),
                    "gender" => array_merge($arr['questions']["gender"],array("label"=>'Gender', "items"=>array('Male','Female'))),
                    "date_of_birth" => array_merge($arr['questions']["date_of_birth"],array("label"=>'Date of Birth')),
                    "address" => array_merge($arr['questions']["address"],array("label"=>'Address')),
                    "postal_code" => array_merge($arr['questions']["postal_code"],array("label"=>'Postal Code')),
                    "address_area_id" => array_merge($arr['questions']["address_area_id"],array("label"=>'Address Area')),
                    "birthplace_area_id" => array_merge($arr['questions']["birthplace_area_id"],array("label"=>'Birth Place Area')),
                    "telephone" => array_merge($arr['questions']["telephone"],array("label"=>'Telephone')),
                    "email" => array_merge($arr['questions']["email"],array("label"=>'Email')));
                break;

            case 'Student':
                $arr['questions'] = array("identification_no" => array_merge($arr['questions']["identification_no"],array("label"=>'OpenEMIS ID')),
                    "first_name" => array_merge($arr['questions']["first_name"],array("label"=>'First Name')),
                    "last_name" => array_merge($arr['questions']["last_name"],array("label"=>'Last Name')),
                    "gender" => array_merge($arr['questions']["gender"],array("label"=>'Gender', "items"=>array('Male','Female'))),
                    "date_of_birth" => array_merge($arr['questions']["date_of_birth"],array("label"=>'Date of Birth')),
                    "address" => array_merge($arr['questions']["address"],array("label"=>'Address')),
                    "postal_code" => array_merge($arr['questions']["postal_code"],array("label"=>'Postal Code')),
                    "address_area_id" => array_merge($arr['questions']["address_area_id"],array("label"=>'Address Area')),
                    "birthplace_area_id" => array_merge($arr['questions']["birthplace_area_id"],array("label"=>'Birth Place Area')),
                    "telephone" => array_merge($arr['questions']["telephone"],array("label"=>'Telephone')),
                    "email" => array_merge($arr['questions']["email"],array("label"=>'Email')));
                break;

            case 'Institution':
                unset($arr['questions']["area_id"]);
                $arr['questions'] = array("name" => array_merge($arr['questions']["name"],array("label"=>'Institution Name')),
                    "code" => array_merge($arr['questions']["code"],array("label"=>'Institution Code')),
                    "institution_sector_id" => array_merge($arr['questions']["institution_sector_id"],array("label"=>'Sector')),
                    "institution_provider_id" => array_merge($arr['questions']["institution_provider_id"],array("label"=>'Provider')),
                    "institution_status_id" => array_merge($arr['questions']["institution_status_id"],array("label"=>'Status')),
                    "date_opened" => array_merge($arr['questions']["date_opened"],array("label"=>'Date Opened')),
                    "date_closed" => array_merge($arr['questions']["date_closed"],array("label"=>'Date Closed')),
                    "address" => array_merge($arr['questions']["address"],array("label"=>'Address')),
                    "postal_code" => array_merge($arr['questions']["postal_code"],array("label"=>'Postal Code')),
                    "contact_person" => array_merge($arr['questions']["contact_person"],array("label"=>'Contact Person')),
                    "telephone" => array_merge($arr['questions']["telephone"],array("label"=>'Telephone')),
                    "fax" => array_merge($arr['questions']["fax"],array("label"=>'Fax')),
                    "email" => array_merge($arr['questions']["email"],array("label"=>'Email')),
                    "website" => array_merge($arr['questions']["website"],array("label"=>'Website')));
                break;
            case 'InstitutionSite':
                $arr['questions'] = array("name" => array_merge($arr['questions']["name"],array("label"=>'Site Name')),
                    "code" => array_merge($arr['questions']["code"],array("label"=>'Site Code')),
                    "institution_site_type_id" => array_merge($arr['questions']["institution_site_type_id"],array("label"=>'Type')),
                    "institution_site_ownership_id" => array_merge($arr['questions']["institution_site_ownership_id"],array("label"=>'Ownership')),
                    "institution_site_status_id" => array_merge($arr['questions']["institution_site_status_id"],array("label"=>'Status')),
                    "date_opened" => array_merge($arr['questions']["date_opened"],array("label"=>'Date Opened')),
                    "date_closed" => array_merge($arr['questions']["date_closed"],array("label"=>'Date Closed')),
                    "area_id" => array_merge($arr['questions']["area_id"],array("label"=>'Area')),
                    "area_education_id" => array_merge($arr['questions']["area_education_id"],array("label"=>'Area (Education)')),
                    "address" => array_merge($arr['questions']["address"],array("label"=>'Address')),
                    "postal_code" => array_merge($arr['questions']["postal_code"],array("label"=>'Postal Code')),
                    "institution_site_locality_id" => array_merge($arr['questions']["institution_site_locality_id"],array("label"=>'Locality')),
                    "latitude" => array_merge($arr['questions']["latitude"],array("label"=>'Latitude')),
                    "longitude" => array_merge($arr['questions']["longitude"],array("label"=>'Longitude')),
                    "contact_person" => array_merge($arr['questions']["contact_person"],array("label"=>'Contact Person')),
                    "telephone" => array_merge($arr['questions']["telephone"],array("label"=>'Telephone')),
                    "fax" => array_merge($arr['questions']["fax"],array("label"=>'Fax')),
                    "email" => array_merge($arr['questions']["email"],array("label"=>'Email')),
                    "website" => array_merge($arr['questions']["website"],array("label"=>'Website')));
                break;
            case 'CensusStudent':
                $arr['type'] = 'Grid_Unlimited';
                $arr['questions'] = array("education_programme_id" => array_merge($arr['questions']["education_programme_id"],array("label"=>'Programme',"box"=>0)),
                    "education_grade_id" => array_merge($arr['questions']["education_grade_id"],array("label"=>'Grade',"box"=>0)),
                    "student_category_id" => array_merge($arr['questions']["student_category_id"],array("label"=>'Category',"box"=>0)),
                    "age" => array_merge($arr['questions']["age"],array("label"=>'Age',"box"=>1)),
                    "male" => array_merge($arr['questions']["male"],array("label"=>'Male',"box"=>1)),
                    "female" => array_merge($arr['questions']["female"],array("label"=>'Female',"box"=>1)));
                break;
            case 'CensusGraduate':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("education_level_id" => array_merge($arr['questions']["education_level_id"],array("label"=>'Level',"box"=>0)),
                    "education_programme_id" => array_merge($arr['questions']["education_programme_id"],array("label"=>'Programme',"box"=>1)),
                    "education_certification_id" => array_merge($arr['questions']["education_certification_id"],array("label"=>'Certification',"box"=>1)),
                    "male" => array_merge($arr['questions']["male"],array("label"=>'Male',"box"=>1)),
                    "female" => array_merge($arr['questions']["female"],array("label"=>'Female',"box"=>1)));
                break;
            case 'CensusAttendance':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("education_programme_id" => array_merge($arr['questions']["education_programme_id"],array("label"=>'Programme',"box"=>0)),
                    "education_grade_id" => array_merge($arr['questions']["education_grade_id"],array("label"=>'Grade',"box"=>1)),
                    "attended_male" => array_merge($arr['questions']["attended_male"],array("label"=>'Days Attended (Male)',"box"=>1)),
                    "attended_female" => array_merge($arr['questions']["attended_female"],array("label"=>'Days Attended (Female)',"box"=>1)),
                    "absent_male" => array_merge($arr['questions']["absent_male"],array("label"=>'Days Absent (Male)',"box"=>1)),
                    "absent_female" => array_merge($arr['questions']["absent_female"],array("label"=>'Days Absent (Female)',"box"=>1)));
                break;
            case 'CensusAssessment':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("education_programme_id" => array_merge($arr['questions']["education_programme_id"],array("label"=>'Programme',"box"=>0)),
                    "education_grade_id" => array_merge($arr['questions']["education_grade_id"],array("label"=>'Grade',"box"=>1)),
                    "education_grade_subject_id" => array_merge($arr['questions']["education_grade_subject_id"],array("label"=>'Subject',"box"=>1)),
                    "value" => array_merge($arr['questions']["value"],array("label"=>'Score',"box"=>1)));
                break;
            case 'CensusClass':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("education_programme_id" => array_merge($arr['questions']["education_programme_id"],array("label"=>'Programme',"box"=>1)),
                    "education_grade_id" => array_merge($arr['questions']["education_grade_id"],array("label"=>'Grade',"box"=>1)),
                    "classes" => array_merge($arr['questions']["classes"],array("label"=>'Classes',"box"=>1)),
                    "seats" => array_merge($arr['questions']["seats"],array("label"=>'Seats',"box"=>1)));
                break;
            case 'CensusTextbook':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("education_programme_id" => array_merge($arr['questions']["education_programme_id"],array("label"=>'Programme',"box"=>0)),
                    "education_grade_id" => array_merge($arr['questions']["education_grade_id"],array("label"=>'Grade',"box"=>1)),
                    "education_grade_subject_id" => array_merge($arr['questions']["education_grade_subject_id"],array("label"=>'Subject',"box"=>1)),
                    "value" => array_merge($arr['questions']["value"],array("label"=>'Questions',"box"=>1)));
                break;
            case 'CensusTeacher':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("education_programme_id" => array_merge($arr['questions']["education_programme_id"],array("label"=>'Programme',"box"=>0)),
                    "education_level_id" => array_merge($arr['questions']["education_level_id"],array("label"=>'Level',"box"=>1)),
                    "male" => array_merge($arr['questions']["male"],array("label"=>'Male',"box"=>1)),
                    "female" => array_merge($arr['questions']["female"],array("label"=>'Female',"box"=>1)));
                break;
            case 'CensusStaff':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("staff_category_id" => array_merge($arr['questions']["staff_category_id"],array("label"=>'Category',"box"=>1)),
                    "male" => array_merge($arr['questions']["male"],array("label"=>'Male',"box"=>1)),
                    "female" => array_merge($arr['questions']["female"],array("label"=>'Female',"box"=>1)));
                break;
            case 'CensusBehaviour':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("student_behaviour_category_id" => array_merge($arr['questions']["student_behaviour_category_id"],array("label"=>'Category',"box"=>1)),
                    "male" => array_merge($arr['questions']["male"],array("label"=>'Male',"box"=>1)),
                    "female" => array_merge($arr['questions']["female"],array("label"=>'Female',"box"=>1)));
                break;
            case 'CensusFinance':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("finance_nature_id" => array_merge($arr['questions']["finance_nature_id"],array("label"=>'Nature',"box"=>1)),
                    "finance_type_id" => array_merge($arr['questions']["finance_type_id"],array("label"=>'Type',"box"=>1)),
                    "finance_source_id" => array_merge($arr['questions']["finance_source_id"],array("label"=>'Source',"box"=>1)),
                    "finance_category_id" => array_merge($arr['questions']["finance_category_id"],array("label"=>'Category',"box"=>1)),
                    "description" => array_merge($arr['questions']["description"],array("label"=>'Description',"box"=>1)),
                    "amount" => array_merge($arr['questions']["amount"],array("label"=>'Amount',"box"=>1)));
                break;
            case 'CensusBuilding':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("infrastructure_material_id" => array_merge($arr['questions']["infrastructure_material_id"],array("label"=>'Material',"box"=>0)),
                    "infrastructure_building_id" => array_merge($arr['questions']["infrastructure_building_id"],array("label"=>'Building',"box"=>1)),
                    "infrastructure_status_id" => array_merge($arr['questions']["infrastructure_status_id"],array("label"=>'Status',"box"=>1)),
                    "value" => array_merge($arr['questions']["value"],array("label"=>'Total',"box"=>1)));
                break;
            case 'CensusResource':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("infrastructure_resource_id" => array_merge($arr['questions']["infrastructure_resource_id"],array("label"=>'Resource',"box"=>0)),
                    "infrastructure_status_id" => array_merge($arr['questions']["infrastructure_status_id"],array("label"=>'Status',"box"=>1)),
                    "value" => array_merge($arr['questions']["value"],array("label"=>'Total',"box"=>1)));
                break;
            case 'CensusFurniture':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("infrastructure_furniture_id" => array_merge($arr['questions']["infrastructure_furniture_id"],array("label"=>'Furniture',"box"=>1)),
                    "infrastructure_status_id" => array_merge($arr['questions']["infrastructure_status_id"],array("label"=>'Status',"box"=>1)),
                    "value" => array_merge($arr['questions']["value"],array("label"=>'Total',"box"=>1)));
                break;
            case 'CensusEnergy':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("infrastructure_energy_id" => array_merge($arr['questions']["infrastructure_energy_id"],array("label"=>'Energy',"box"=>1)),
                    "infrastructure_status_id" => array_merge($arr['questions']["infrastructure_status_id"],array("label"=>'Status',"box"=>1)),
                    "value" => array_merge($arr['questions']["value"],array("label"=>'Total',"box"=>1)));
                break;
            case 'CensusRoom':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("infrastructure_room_id" => array_merge($arr['questions']["infrastructure_room_id"],array("label"=>'Room',"box"=>1)),
                    "infrastructure_status_id" => array_merge($arr['questions']["infrastructure_status_id"],array("label"=>'Status',"box"=>1)),
                    "value" => array_merge($arr['questions']["value"],array("label"=>'Total',"box"=>1)));
                break;
            case 'CensusSanitation':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("infrastructure_material_id" => array_merge($arr['questions']["infrastructure_material_id"],array("label"=>'Material',"box"=>0)),
                    "infrastructure_sanitation_id" => array_merge($arr['questions']["infrastructure_sanitation_id"],array("label"=>'Sanitation',"box"=>0)),
                    "infrastructure_status_id" => array_merge($arr['questions']["infrastructure_status_id"],array("label"=>'Status',"box"=>1)),
                    "male" => array_merge($arr['questions']["male"],array("label"=>'Male',"box"=>1)),
                    "female" => array_merge($arr['questions']["female"],array("label"=>'Female',"box"=>1)),
                    "unisex" => array_merge($arr['questions']["unisex"],array("label"=>'Unisex',"box"=>1)));
                break;
            case 'CensusWater':
                $arr['type'] = 'Grid_Fix';
                $arr['questions'] = array("infrastructure_water_id" => array_merge($arr['questions']["infrastructure_water_id"],array("label"=>'Water',"box"=>1)),
                    "infrastructure_status_id" => array_merge($arr['questions']["infrastructure_status_id"],array("label"=>'Status',"box"=>1)),
                    "value" => array_merge($arr['questions']["value"],array("label"=>'Total',"box"=>1)));
                break;
            default: // Do Nothing for tables not needed to sort
                break;
        }
    }

}
?>