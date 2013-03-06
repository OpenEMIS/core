<?php
App::uses('AppModel', 'Model');

class InstitutionSiteOwnership extends AppModel {
    public $useTable = 'institution_site_ownership';
    public $hasMany = array('InstitutionSite');
}
