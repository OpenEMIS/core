<?php
App::uses('AppModel', 'Model');

class CensusFinance extends AppModel {
        public $belongsTo = array('FinanceSource'=> array('foreignKey' => 'finance_source_id'),
                               'FinanceCategory' => array('foreignKey' => 'finance_category_id'),
                               'SchoolYear' => array('foreignKey' => 'school_year_id')
        );
	
}
