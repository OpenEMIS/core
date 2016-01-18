<?php 
namespace Area\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class AreaBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function findAreas(Query $query, array $options) {
		if (array_key_exists('id', $options) && array_key_exists('columnName', $options) && array_key_exists('table', $options)) {
			$Table = '';
			if ($options['table'] == 'areas') {
				$Table = TableRegistry::get('Area.Areas');
			}else if ($options['table'] == 'area_administratives') {
				$Table = TableRegistry::get('Area.AreaAdministratives');;
			}
			
			if (!empty($options['table'])) {
				$lft = $Table->get($options['id'])->lft;
				$rgt = $Table->get($options['id'])->rght;
				$tableAlias = $options['columnName'].'Areas';
				return $query->innerJoin([ $tableAlias => $options['table']], [
								$tableAlias.'.id = '. $options['columnName'],
								$tableAlias.'.lft >=' => $lft,
								$tableAlias.'.rght <=' => $rgt,
							]);
			}
		} else {
			return $query;
		}
	}
}
