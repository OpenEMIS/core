<?php 
$formOptions = !isset($formOptions) ? array() : $formOptions;
$form = !isset($form) ? true : $form;
$pagination = !isset($pagination) ? true : $pagination;
$placeholder = !isset($placeholder) ? 'Search' : $placeholder;
if ($form) {
	echo $this->Form->create($model, $formOptions);
}
?>
<div class="row search">
	<div class="col-md-5">
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-search"></i></span>
			<?php 
			echo $this->Form->input('search', array(
				'label' => false,
				'div' => false,
				'class' => 'form-control search-input', 
				'placeholder' => __($placeholder)
			))
			?>
			<span class="input-group-btn">
				<button class="btn btn-default" type="button" onclick="$('.search-input').val('');$(this).closest('form').submit()"><i class="fa fa-close"></i></button>
			</span>
		</div>
	</div>
</div>

<?php 
if ($pagination) {
	echo $this->element('layout/pagination');
}
if ($form) {
	echo $this->Form->end();
}
?>
