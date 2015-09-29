<?php
namespace ControllerAction\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\Table;

class DeleteBehavior extends Behavior {

	/**
	 *
	 */
	public function associationCount(Table $table, $id) {
		$totalCount = 0;
		$associations = [];
		foreach ($table->associations() as $assoc) {
			if ($assoc->dependent()) {
				if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
					if (!in_array($assoc->table(), $associations)) {
						$count = 0;
						if($assoc->type() == 'oneToMany') {
							$count = $assoc->find()
							->where([$assoc->aliasField($assoc->foreignKey()) => $id])
							->count();
							$totalCount = $totalCount + $count;
						} else {
							$modelAssociationTable = $assoc->junction();
							$count += $modelAssociationTable->find()
								->where([$modelAssociationTable->aliasField($assoc->foreignKey()) => $id])
								->count();
							$totalCount = $totalCount + $count;
						}
						$associations[] = $assoc->table();
					}
				}
			}
		}
		return $totalCount;
	}
}
