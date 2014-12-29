<?php

class AppTask extends Shell {

    public $uses = array(
        'Area',
        'AreaLevel',
        'InstitutionSite',
        'InstitutionSiteBankAccount',
        'InstitutionSiteCustomField',
        'InstitutionSiteCustomFieldOption',
        'InstitutionSiteCustomValue',
        'InstitutionSiteProgramme',
        'BatchProcess',
        'BatchIndicator',
        'Reports.Report',
        'Reports.BatchReport',
        'CensusCustomValue',
        'CensusCustomField',
        'CensusGridValue',
        'CensusStudent',
        'CensusClass',
        'CensusTextbook',
        'CensusTeacher',
        'CensusTeacherTraining',
        'CensusStaff',
        'CensusFinance',
        'CensusBuilding',
        'CensusRoom',
        'CensusSanitation',
        'CensusFurniture',
        'CensusResource',
        'CensusEnergy',
        'CensusWater',
        'Students.Student',
        'StudentCustomField',
        'StudentCustomFieldOption',
        'StudentCustomValue',
        'Staff.Staff',
        'StaffCustomField',
        'StaffCustomFieldOption',
        'StaffCustomValue',
        'Quality.QualityInstitutionVisit',
        'Quality.RubricsTemplate'
    );
}

?>
