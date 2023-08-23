	<div class="form-buttons no-margin-top">
	    <label for="">Action</label>
	    <button onclick="" type="button" class="btn btn-default btn-sm">
	        <span><i class="fa fa-plus"></i><?= __('Add Comment') ?></span>
	    </button>
	</div>
	<?php
    $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
    $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
    ?>
	<div class="table-wrapper">
	    <div class="table-responsive">
	        <table class="table table-curved table-input">
	            <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
	            <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
	        </table>
	    </div>
	</div>