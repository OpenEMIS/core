<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

$wantedOptions = array_flip(['length', 'limit', 'default', 'unsigned', 'null']);
$tableMethod = $this->Migration->tableMethod($action);
$columnMethod = $this->Migration->columnMethod($action);
$indexMethod = $this->Migration->indexMethod($action);
?>
<CakePHPBakeOpenTagphp
use Phinx\Migration\AbstractMigration;

class <?= $name ?> extends AbstractMigration
{
    public function up()
    {
<?php foreach ($tables as $table): ?>
<?php $primaryKeys = $this->Migration->primaryKeys($table);
        $specialPk = count($primaryKeys) > 1 || $primaryKeys[0]['name'] !== 'id' || $primaryKeys[0]['info']['columnType'] !== 'integer';
        if ($specialPk) {
        ?>
        $table = $this->table('<?= $table?>', ['id' => false, 'primary_key' => ['<?= implode("', '", \Cake\Utility\Hash::extract($primaryKeys, '{n}.name')) ?>']]);
<?php } else { ?>
        $table = $this->table('<?= $table?>');
<?php } ?>
        $table
<?php if ($specialPk) { ?>
<?php foreach ($primaryKeys as $primaryKey) :?>
            -><?= $columnMethod ?>('<?= $primaryKey['name'] ?>', '<?= $primaryKey['info']['columnType'] ?>', [<?php
                $options = [];
                $columnOptions = array_intersect_key($primaryKey['info']['options'], $wantedOptions);
                echo $this->Migration->stringifyList($columnOptions, ['indent' => 4]);
            ?>])
<?php endforeach; ?>
<?php } ?>
<?php foreach ($this->Migration->columns($table) as $column => $config): ?>
            -><?= $columnMethod ?>('<?= $column ?>', '<?= $config['columnType'] ?>', [<?php
                $options = [];
                $columnOptions = array_intersect_key($config['options'], $wantedOptions);
                echo $this->Migration->stringifyList($columnOptions, ['indent' => 4]);
            ?>])
<?php endforeach; ?>
            -><?= $tableMethod ?>();
<?php endforeach; ?>
    }

    public function down()
    {
<?php foreach ($tables as $table): ?>
        $this->dropTable('<?= $table?>');
<?php endforeach; ?>
    }
}
