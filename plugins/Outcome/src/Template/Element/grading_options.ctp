<?php if ($action == 'add' || $action == 'edit') : ?>
	<style>
		table th label.table-header-label {
		  background-color: transparent;
		  border: medium none;
		  margin: 0;
		  padding: 0;
		}
	</style>

	<div class="input clearfix">
		<div class="clearfix">
		<?php
			echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add New Option').'</span>', [
				'label' => __('Grading Options'),
				'type' => 'button',
				'class' => 'btn btn-default',
				'aria-expanded' => 'true',
				'onclick' => "$('#reload').val('addOption').click();",
				'required' =>'required'
			]);
			$this->Form->unlockField('OutcomeGradingTypes.grading_options');
		?>
		</div>
		<div class="table-wrapper full-width">
			<div class="table-responsive">
			    <table class="table table-curved table-input row-align-top">
					<thead>
						<tr>
							<th><label class="table-header-label"><?= __('Code') ?></label></th>
							<th class="required"><label class="table-header-label"><?= __('Name') ?></label></th>
							<th><label class="table-header-label"><?= __('Description') ?></label></th>
							<th class="cell-delete"></th>
						</tr>
					</thead>

					<?php if (isset($data['grading_options'])) : ?>
						<tbody>
							<?php foreach ($data['grading_options'] as $i => $option) : ?>
								<?php
		                            $fieldPrefix = $ControllerAction['table']->alias() . ".grading_options.$i";
		                        ?>
								<tr>
									<td>
										<?php
											if ($option->has('id')) {
												echo $this->Form->hidden("$fieldPrefix.id", ['value' => $option->id]);
											}
											echo $this->Form->hidden("$fieldPrefix.outcome_grading_type_id", ['value' => $option->outcome_grading_type_id]);

											echo $this->Form->input("$fieldPrefix.code", [
                                                'type' => 'string',
                                                'label' => false
                                            ]);
										?>
									</td>
									<td>
										<?php
											echo $this->Form->input("$fieldPrefix.name", [
                                                'type' => 'string',
                                                'label' => false
                                            ]);
										?>
									</td>
									<td>
										<?php
											echo $this->Form->input("$fieldPrefix.description", [
                                                'type' => 'textarea',
                                                'label' => false
                                            ]);
										?>
									</td>
									<td>
										<?php
											if (!empty($option->institution_outcome_results)) {
												echo __('In use');
											} else {
												echo $this->Form->input('<i class="fa fa-trash"></i> <span>'.__('Delete').'</span>', [
													'label' => false,
													'type' => 'button',
													'class' => 'btn btn-dropdown action-toggle btn-single-action',
													'title' => __('Delete'),
													'aria-expanded' => 'true',
													'onclick' => "jsTable.doRemove(this); "
												]);
											}
										?>
									</td>
								</tr>
							<?php endforeach ?>
						</tbody>
					<?php endif ?>
				</table>
			</div>
		</div>
	</div>

<?php else : ?>

	<div class="table-in-view">
		<table class="table">
			<thead>
				<tr>
					<th><?= __('Code') ?></th>
					<th><?= __('Name') ?></th>
					<th><?= __('Description') ?></th>
				</tr>
			</thead>
			<?php if (isset($data['grading_options'])) : ?>
				<tbody>
					<?php foreach ($data['grading_options'] as $i => $option) : ?>
						<tr>
							<td><?= $option->code ?></td>
							<td><?= $option->name ?></td>
							<td><?= $option->description ?></td>
						</tr>
					<?php endforeach ?>
				</tbody>
			<?php endif ?>
		</table>
	</div>

<?php endif ?>
