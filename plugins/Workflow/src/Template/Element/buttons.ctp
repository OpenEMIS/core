<div class="button-responsive custom-buttons dropdown">
	<?php //echo $this->Html->link('<span class="caret-down"></span>', '#', ['id' => 'action-menu', 'class' => 'btn btn-default action-toggle outline-btn', 'data-toggle' => 'dropdown', 'aria-expanded' => true]); ?>
	<a class="btn btn-default action-toggle outline-btn" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">
		<?= __('More Actions'); ?><span class="caret-down"></span>
	</a>
	<ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
		<div class="dropdown-arrow">
			<i class="fa fa-caret-up"></i>
		</div>
		<li role="presentation">
			<a role="menuitem" tabindex="-1" href="#">Pending Approval</a>
		</li>
		<li role="presentation">
			<a role="menuitem" tabindex="-1" href="#">Assign</a>
		</li>
	</ul>
</div>
