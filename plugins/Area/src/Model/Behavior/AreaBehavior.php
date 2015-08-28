<?php 
namespace Area\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class AreaBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function findAreas(Query $query, array $options) {
		if (array_key_exists('area_id', $options) && array_key_exists('columnName', $options)) {
			$Table = TableRegistry::get('Area.Areas');
			$lft = $Table->get($options['area_id'])->lft;
			$rgt = $Table->get($options['area_id'])->rght;
			$tableAlias = $options['columnName'].'Areas';
			return $query->innerJoin([ $tableAlias => 'areas'], [
							$tableAlias.'.id = '. $options['columnName'],
							$tableAlias.'.lft >=' => $lft,
							$tableAlias.'.rght <=' => $rgt,
						]);
		} else {
			return $query;
		}
	}

	public function findAreaAdministratives(Query $query, array $options) {
		if (array_key_exists('area_administrative_id', $options) && array_key_exists('columnName', $options)) {
			$Table = TableRegistry::get('Area.AreaAdministratives');
			$lft = $Table->get($options['area_administrative_id'])->lft;
			$rgt = $Table->get($options['area_administrative_id'])->rght;
			$tableAlias = $options['columnName'].'AreaAdministratives';
			
			return $query->innerJoin([ $tableAlias => 'area_administratives'], [
							$tableAlias.'.id = '. $options['columnName'],
							$tableAlias.'.lft >=' => $lft,
							$tableAlias.'.rght <=' => $rgt,
						]);
		} else {
			return $query;
		}
	}
}
