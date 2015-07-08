<?php
	// pr($filters);
?>
<div id="advanced-search" class="advanced-search-wrapper alert search-box hidden">

	<button id="search-toggle" class="btn btn-xs close" type="button" alt="Collapse">×</button>
	<h4>Advanced Search</h4>

	<?= $this->Form->create(NULL, ['url' => '', 'id'=>'advance-search-form', 'novalidate'=>"novalidate", 'class'=>"form-horizontal", 'accept-charset'=>"utf-8" ]) ?>
	<!-- <form id="search-form" action="/styleguide/users/add" novalidate="novalidate" class="form-horizontal" accept-charset="utf-8" method="post"> -->


	<?php
		// pr($model);die;
		foreach ($filters as $key=>$filter):
	?>

		<div class="input select">
		  <label class="form-label"><?= $filter['label'] ?>:</label>
		  <div class="input-select-wrapper">	 
			  <select name="AdvanceSearch[<?= $model ?>][<?= $key ?>]">
			    <option>&nbsp;</option>
				<?php foreach ($filter['options'] as $optKey=>$optVal): ?>
					<?php $selected = ($optKey==$filter['selected']) ? 'selected' : ''; ?>
				    <option value="<?= $optKey ?>" <?= $selected ?>><?= $optVal ?></option>
				<?php endforeach; ?>
			  </select>
		   </div>	  
		</div>

	<?php
		endforeach;
	?>

	<hr>

	<button class="btn btn-default btn-xs" href="">Search</button>
	<button id="reset" class="btn btn-default btn-xs" type="reset" value="Reset" href="">Reset</button>		

	<?= $this->Form->end() ?>

</div>

<script type="text/javascript">   
	var box = $('#advanced-search');
	$('button#search-toggle').on('click', function () {
	  box.toggleClass('hidden');
	});


	//reset form 
	$("#reset").click(function(){
	    $("#advance-search-form").find('input:text, select').val('');
	    $(".icheckbox_minimal-grey").removeClass("checked");
	});

</script>