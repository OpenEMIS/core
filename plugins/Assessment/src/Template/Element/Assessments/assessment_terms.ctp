<?php

use Cake\Utility\Inflector;

$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

	<style>
		table .error-message-in-table {
			min-width: 100px;
			width: 100%;
		}
		table th label.table-header-label {
		  background-color: transparent;
		  border: medium none;
		  margin: 0;
		  padding: 0;
		}
	</style>

	<div class="input clearfix">
		<div class="clearfix">
			<div class="input">
				<label for="i-class-fa-fa-plus-i-span-add-new-option-span"><?= __('Assessment Periods') ?></label>
				<div class="input-form-wrapper">
					<div class="table-wrapper full-width">
						<div class="table-in-view">
						    <table class="table">
								<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
								<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

