<?php
App::uses('AppModel', 'Model');

class CensusBuilding extends AppModel {
    public $belongsTo = array('InfrastructureBuilding', 'InfrastructureMaterial');
}
