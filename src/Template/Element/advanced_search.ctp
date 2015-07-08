<?php
	// pr($filters);
?>
<div id="advanced-search" class="advanced-search-wrapper alert search-box">

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


		<!-- <div class="input select">
		  <label class="form-label">Education Programme:</label>
		  <div class="input-select-wrapper">	 
			  <select>
			    <option></option>
			    <option>Primary</option>
			    <option>Secondary 1 - 2 Expres or Normal (Academic)</option>
			    <option>Secondary 1 - 2 Normal (Technical) or equivalent</option>
			    <option>Secondary 3 - 4/5 Express or Normal (Academic)</option>
			    <option>Secondary 3 - 4 Normal (Technical) or equivalent</option>		    
			  </select>
		   </div>	  
		</div>

		<div class="input select">
		  <label class="form-label">Country:</label>
		  <div class="input-select-wrapper">	 
			  <select>
			    <option></option>
			    <option>Singapore</option>
			    <option>Malaysia</option>
			    <option>Indonesia</option>
			    <option>Australia</option>
			    <option>Vietnam</option>		    
			  </select>
		   </div>	  
		</div>

		<div class="input text">
			<label class="form-label">Custom Field:</label>
			<input type="text" id="" maxlength="150" name="">
		</div>	 -->

		<hr>

		<div class="input">
			<label class="form-label">Filter by:</label>
			<div class="input-checkbox-inline">
				<div class="input">
					<input class="icheck-input" type="checkbox"><label class="checkbox-label">Area</label>
				</div>
				<div class="input">
					<input class="icheck-input" type="checkbox"><label class="checkbox-label">Topic</label>
				</div>
				<div class="input">
					<input class="icheck-input" type="checkbox"><label class="checkbox-label">Region</label>
				</div>
				<div class="input">
					<input class="icheck-input" type="checkbox"><label class="checkbox-label">Location</label>
				</div>
				<div class="input">
					<input class="icheck-input" type="checkbox"><label class="checkbox-label">Country</label>
				</div>
			</div>
		</div>		

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