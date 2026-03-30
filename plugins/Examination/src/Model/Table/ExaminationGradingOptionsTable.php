<?php
namespace Examination\Model\Table;

use Cake\Database\ValueBinder;
use Cake\Datasource\EntityInterface;
use Cake\Database\TypeFactory;
use Examination\Model\Table\ExaminationsAppTable;
use Cake\Validation\Validator;
use RuntimeException;

class ExaminationGradingOptionsTable extends ExaminationsAppTable {

    public function initialize(array $config): void {
        parent::initialize($config);

        $this->belongsTo('ExaminationGradingTypes', ['className' => 'Examination.ExaminationGradingTypes']);
        $this->hasMany('ExaminationStudentSubjectResults', ['className' => 'Examination.ExaminationStudentSubjectResults', 'dependent' => true, 'cascadeCallbacks' => true]);

        if ($this->behaviors()->has('Reorder')) {
            // $this->behaviors()->get('Reorder')->config([
            //     'filter' => 'examination_grading_type_id'
            // ]);
            $reorderBehavior = $this->behaviors()->get('Reorder');
            $reorderBehavior->setConfig('filter', 'examination_grading_type_id');

        }

        $this->fields['examination_grading_type_id']['type'] = 'hidden';
        $this->fields['id']['type'] = 'hidden';
        $this->fields['name']['required'] = true;
        $this->fields['max']['attr']['min'] = 0;
        $this->fields['max']['required'] = true;
        $this->fields['max']['length'] = 7;
        $this->fields['min']['attr']['min'] = 0;
        $this->fields['min']['required'] = true;
        $this->fields['min']['length'] = 7;
    }

    public function getFormFields($action = 'edit') {
        if ($action=='edit') {
            return ['code'=>'', 'name'=>'', 'description'=>'', 'min'=>'', 'max'=>'', 'examination_grading_type_id'=>'', 'id'=>''];
        } else {
            return ['code'=>'', 'name'=>'', 'description'=>'', 'min'=>'', 'max'=>''];
        }
    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('code')
            // ->add('code', 'ruleUniqueCode', [
            //     'rule' => ['checkUniqueCode', 'examination_grading_type_id'],
            //     'last' => true
            // ])
            // ->add('code', 'ruleUniqueCodeWithinForm', [
            //     'rule' => ['checkUniqueCodeWithinForm', $this->ExaminationGradingTypes],
            // ])
            ->requirePresence('name')
            // ->add('min', [
            //     'ruleNotMoreThanMax' => [
            //         'rule' => ['checkMinNotMoreThanMax'],
            //     ],
            //     'ruleIsDecimal' => [
            //         'rule' => ['decimal', null],
            //     ],
            //     'ruleRange' => [
            //         'rule' => ['range', 0, 9999.99]
            //     ]
            // ])
            // ->add('max', [
            //     'ruleNotMoreThanGradingTypeMax' => [
            //         'rule' => ['checkNotMoreThanGradingTypeMax', $this->ExaminationGradingTypes],
            //         'provider' => 'table'
            //     ],
            //     'ruleIsDecimal' => [
            //         'rule' => ['decimal', null],
            //     ],
            //     'ruleRange' => [
            //         'rule' => ['range', 0, 9999.99]
            //     ]
            // ])
            ;
        return $validator;
    }

    public static function checkNotMoreThanGradingTypeMax($maxValue, $ExaminationGradingTypes, array $globalData) {
        $formData = $ExaminationGradingTypes->request->data[$ExaminationGradingTypes->getAlias()];
        return intVal($maxValue) <= intVal($formData['max']);
    }

    /**
     * Override to quote the reserved MySQL column name `order` in INSERT statements.
     */
    protected function _insert(EntityInterface $entity, array $data)
    {
        $primary = (array)$this->getPrimaryKey();
        if (empty($primary)) {
            throw new RuntimeException(
                sprintf(
                    'Cannot insert row in "%s" table, it has no primary key.',
                    $this->getTable()
                )
            );
        }
        $keys = array_fill(0, count($primary), null);
        $id = (array)$this->_newId($primary) + $keys;
        $primary = array_combine($primary, $id) ?: [];
        $primary = array_intersect_key($data, $primary) + $primary;
        $filteredKeys = array_filter($primary, function ($v) {
            return $v !== null;
        });
        $data += $filteredKeys;

        if (count($primary) > 1) {
            $schema = $this->getSchema();
            foreach ($primary as $k => $v) {
                if (!isset($data[$k]) && empty($schema->getColumn($k)['autoIncrement'])) {
                    throw new RuntimeException(
                        'Cannot insert row, some of the primary key values are missing. '
                        . sprintf(
                            'Got (%s), expecting (%s)',
                            implode(', ', $filteredKeys + $entity->extract(array_keys($primary))),
                            implode(', ', array_keys($primary))
                        )
                    );
                }
            }
        }

        if (empty($data)) {
            return false;
        }

        $columns = array_keys($data);
        $conn = $this->getConnection();
        $driver = $conn->getDriver();
        $schema = $this->getSchema();
        $quotedTable = $driver->quoteIdentifier($this->getTable());
        $quotedColumns = array_map([$driver, 'quoteIdentifier'], $columns);

        $binder = new ValueBinder();
        $placeholders = [];
        foreach ($columns as $col) {
            $placeholders[] = $binder->placeholder('c');
        }
        foreach ($columns as $i => $col) {
            $type = $schema->getColumnType($col);
            $binder->bind($placeholders[$i], $data[$col], $type ?? 'string');
        }

        $sql = 'INSERT INTO ' . $quotedTable . ' (' . implode(', ', $quotedColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $statement = $conn->prepare($sql);
        $binder->attachTo($statement);
        $statement->execute();

        $success = false;
        if ($statement->rowCount() !== 0) {
            $success = $entity;
            $entity->set($filteredKeys, ['guard' => false]);
            $schema = $this->getSchema();
            $driver = $this->getConnection()->getDriver();
            foreach ($primary as $key => $v) {
                if (!isset($data[$key])) {
                    $id = $statement->lastInsertId($this->getTable(), $key);
                    $type = $schema->getColumnType($key);
                    $entity->set($key, TypeFactory::build($type)->toPHP($id, $driver));
                    break;
                }
            }
        }
        $statement->closeCursor();

        return $success;
    }
}
