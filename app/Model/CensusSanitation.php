<?php
App::uses('AppModel', 'Model');

class CensusSanitation extends AppModel {
    public $belongsTo = array('InfrastructureSanitation', 'InfrastructureMaterial');
}
