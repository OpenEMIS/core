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

	/** Get the feature of advance search of the InstitutionShifts
		* @author Rahul Singh <rahul.singh@mail.valuecoder.com>
		*return array
		*POCOR-6764
	*/

	public function findShiftOptions(Query $query, array $options) {
		if (array_key_exists('shift_option_id', $options) && array_key_exists('columnName', $options) && array_key_exists('table', $options)) {
			$Table = '';
			if ($options['table'] == 'institution_shifts') {
				$Table = TableRegistry::get('Institution.InstitutionShifts');
			}
			if (!empty($options['table'])) {
				if (!empty($options['conditionCheck']['alternative_name'] == 2) && !empty($options['shift_option_id'])) {
					$tableAlias = $options['columnName'].'institution_shifts';
					$query->LeftJoin([ $tableAlias => $options['table']], [
						$tableAlias.'.location_institution_id = '. $this->_table->alias().'.id'
					])
					->LeftJoin(['ShiftOptions' => 'shift_options'], [
						'ShiftOptions.id = '. $tableAlias.'.shift_option_id',
						$tableAlias.'.shift_option_id =' => $options['shift_option_id'],
					])
					->where([$tableAlias.'.shift_option_id =' => $options['shift_option_id']]);
				}
				else{
					$tableAlias = $options['columnName'].'institution_shifts';
					$query->LeftJoin([ $tableAlias => $options['table']], [
						$tableAlias.'.institution_id = '. $this->_table->alias().'.id'
					])
					->LeftJoin(['ShiftOptions' => 'shift_options'], [
						'ShiftOptions.id = '. $tableAlias.'.shift_option_id',
						$tableAlias.'.shift_option_id =' => $options['shift_option_id'],
					])
					->where([$tableAlias.'.shift_option_id =' => $options['shift_option_id']])
					->group($tableAlias.'.institution_id');					
				}
			}
		} else {
			return $query;
		}
	}

	/** Get the feature of advance search of the  (Owner/Occupier)
		* @author Rahul Singh <rahul.singh@mail.valuecoder.com>
		*return array
		*POCOR-6764
	*/

	public function findShiftOwnership(Query $query, array $options) {
		
		if (array_key_exists('shift_ownership', $options) && array_key_exists('columnName', $options) && array_key_exists('table', $options)) {
			$Table = '';
			if ($options['table'] == 'institution_shifts') {
				$Table = TableRegistry::get('Institution.InstitutionShifts');
			}
			if (!empty($options['table'])) {
				$tableAlias = $options['columnName'].'institution_shifts';

				if ($options['shift_ownership'] == 1) {
					if (!empty($options['conditionCheck']['shift_type'])) {
						$conditions[$tableAlias.'.shift_option_id'] = $options['conditionCheck']['shift_type'];
						$query->LeftJoin([ $tableAlias => $options['table']], [
							$tableAlias.'.institution_id = '. $this->_table->alias().'.id'
						])->where([$conditions])->group($tableAlias.'.institution_id');
					}
				}
				else{
					$InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
					$academicPeriod = $this->getCurrent();
					$conditions = [];
					if (!empty($academicPeriod)) {
						$conditions[$InstitutionShifts->aliasField('academic_period_id')] = $academicPeriod;
					}
					if (!empty($options['conditionCheck']['shift_type'])) {
						$conditions[$InstitutionShifts->aliasField('shift_option_id')] = $options['conditionCheck']['shift_type'];
					}

					$data = $InstitutionShifts->find('all')
							->select(['institution_id','location_institution_id',
								'shift_ownershipss' => '(
								CASE
								WHEN '.$InstitutionShifts->aliasField('institution_id = location_institution_id').' THEN '."false".'
								ELSE '."true".'
								END
							  )',
							])
							->where([$conditions])
							->group('location_institution_id')
							->toArray();

					$institutionId = [];
					foreach ($data as $key => $value) {
						if ($value->shift_ownershipss == 1){
							$institutionId [] =$value->location_institution_id;
						}
					}

					if (!empty($institutionId)) {
						$query->LeftJoin([ $tableAlias => $options['table']], [
							$tableAlias.'.location_institution_id = '. $this->_table->alias().'.id'
						])
						->where([$tableAlias.'.location_institution_id IN' => $institutionId])
						->group($tableAlias.'.location_institution_id');
					}
					else{
						return $query;
					}
				}
			}
		} else {
			return $query;
		}
	}

	/** Get the feature of get current
		* @author Rahul Singh <rahul.singh@mail.valuecoder.com>
		*return array
		*POCOR-6764
	*/

	public function getCurrent()
    {
    	$AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $query = $AcademicPeriod->find('all')
                    ->select([$AcademicPeriod->aliasField('id')])
                    ->where([
                        $AcademicPeriod->aliasField('editable') => 1,
                        $AcademicPeriod->aliasField('visible').' > 0',
                        $AcademicPeriod->aliasField('current') => 1,
                        $AcademicPeriod->aliasField('parent_id').' > 0',
                    ])
                    ->order(['start_date DESC']);
        $countQuery = $query->count();
        if ($countQuery > 0) {
            $result = $query->first();
            return $result->id;
        } else {
            $query = $AcademicPeriod->find('all')
                    ->select([$AcademicPeriod->aliasField('id')])
                    ->where([
                        $AcademicPeriod->aliasField('editable') => 1,
                        $AcademicPeriod->aliasField('visible').' > 0',
                        $AcademicPeriod->aliasField('parent_id').' > 0',
                    ])
                    ->order(['start_date DESC']);
            $countQuery = $query->count();
            if ($countQuery > 0) {
                $result = $query->first();
                return $result->id;
            } else {
                return 0;
            }
        }
    }
}
