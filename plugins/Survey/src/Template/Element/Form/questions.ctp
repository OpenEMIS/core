	<div class="clearfix"></div>

	<hr>

	<h3><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></h3>

	<?php if ($action=='edit') :?>
	<div class="clearfix">
	<?php
		echo $this->Form->input('student_id', array(
			//'options' => $attr['data']['studentOptions'],
			'label' => $this->Label->get('Users.add_student'),
			'onchange' => "$('#reload').val('add').click();"
		));
		?>
	</div>
	<?php endif;?>

	<div class="table-wrapper">
		<div class="table-responsive">
			<table class="table table-curved">
				<thead>
					<tr>
						<th><?= $this->Label->get('Users.openemis_no'); ?></th>
						<th><?= $this->Label->get('Users.name'); ?></th>
						<th><?= $this->Label->get('Users.gender_id'); ?></th>
						<th><?= $this->Label->get('Users.date_of_birth'); ?></th>
						<th><?= $this->Label->get($attr['model'] . '.education_grade'); ?></th>
						<th><?= $this->Label->get('Users.student_category'); ?></th>
						<th class="cell-delete"></th>
					</tr>
				</thead>

				<tbody>

					
				</tbody>
			</table>
		</div>	
	</div>	
