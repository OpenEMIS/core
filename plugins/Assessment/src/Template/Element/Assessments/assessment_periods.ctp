<?php use Cake\Utility\Inflector;?>

<?php if ($action == 'add' || $action == 'edit') : ?>
	<style>
		table.table-body-scrollable  {
		    width: 100%;
		    border-spacing: 0;

		    border: 1px solid #CCC !important;
		    -webkit-box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.08);
		    -moz-box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.08);
		    box-shadow: inset 0 -5px 10px rgba(0, 0, 0, 0.08);
		    -webkit-border-radius: 5px;
		    -moz-border-radius: 5px;
		    border-radius: 5px;
		}

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
		<?php
			echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add New Assessment Period').'</span>', [
				'label' => $this->Label->get('Assessments.assessmentPeriods'),
				'type' => 'button',
				'class' => 'btn btn-default',
				'aria-expanded' => 'true',
				'onclick' => "$('#reload').val('newAssessmentPeriod').click();"
			]);
		?>
		</div>
		<div class="table-wrapper full-width">
			<div class="table-responsive">
			    <!-- <table class="table"> -->
			    <table class="table table-body-scrollable">
					<thead>
						<tr>
							<?php foreach ($attr['formFields'] as $formField) : ?>
								<?php if ($attr['fields'][$formField]['type']!='hidden') :
									$thClass = (isset($attr['fields'][$formField]['required']) && $attr['fields'][$formField]['required']) ? 'required' : '';
								?>
									<th class="<?= $thClass ?>"><label class="table-header-label"><?= Inflector::humanize(str_replace('_id', '', $formField)) ?></label></th>
									<th></th>
								<?php endif; ?>
							<?php endforeach;?>

							<th class="cell-delete"></th>
						</tr>
					</thead>

					<tbody id='table_assessment_periods'>
						
						<?php 
						if (count($data->assessment_periods)>0) :
							// iterate each row
							foreach ($data->assessment_periods as $key => $record) :
								$rowErrors = $record->errors();
								if ($rowErrors) {
									$trClass = 'error';
								} else {
									$trClass = '';
								}
						?>
						<tr class="<?= $trClass ?>">

							<?php 
								// iterate each field in a row
								foreach ($attr['formFields'] as $formField):
									$field = $attr['fields'][$formField];
									$fieldErrors = $record->errors($field['field']);
									if ($fieldErrors) {
										$tdClass = 'error';
										$fieldClass = 'form-error';
									} else {
										$tdClass = '';
										$fieldClass = '';
									}
									$options = [
										'label'=>false,
										'name'=>'Assessments[assessment_periods]['.$key.']['.$field['field'].']',
										'class'=>$fieldClass,
										'value'=>$record->$field['field']
									];
									if ($field['type']=='date') {
										$field['fieldName'] = 'Assessments[assessment_periods]['.$key.']['.$field['field'].']';
										$field['inputWrapperStyle'] = 'margin-top:2px;margin-bottom:-1px;';
									}
							?>
								<?php if ($field['type']!='hidden') : ?>

									<td class="<?= $tdClass ?>">
										<?= $this->HtmlField->{$field['type']}('edit', $record, $field, $options); ?>
									</td>

									<td class="<?= $tdClass ?>">
										<ul class="error-message error-message-in-table">
										<?php if ($fieldErrors) : ?>
											<?php foreach ($fieldErrors as $error) : ?>
												<li><?= $error ?></li>
											<?php endforeach ?>
										<?php endif; ?>
										</ul>
									</td>

								<?php else : ?>
									<?= $this->HtmlField->{$field['type']}('edit', $record, $field, $options);?>
								<?php endif; ?>
							
							<?php endforeach;?>
							
							<td>
								<?php
								echo $this->Form->input('<i class="fa fa-trash"></i> <span>'.__('Delete').'</span>', [
									'label' => false,
									'type' => 'button',
									'class' => 'btn btn-dropdown action-toggle btn-single-action',
									'title' => "Delete",
									'aria-expanded' => 'true',
									'onclick' => "jsTable.doRemove(this); "
								]);
								?>
							</td>
						</tr>
						<?php 
							endforeach;
						endif;
						?>

					</tbody>
					
				</table>
			</div>
		</div>
	</div>

	<script>
		function updateDates() {
			// var startDates = $('#table_assessment_periods').find('td .updateOnPeriodChanged');
			// console.log(startDates);
		}
	</script>

<?php else : ?>

		<div class="table-in-view">
			<table class="table">
				<thead>
					<tr>
						<?php foreach ($attr['formFields'] as $formField) : ?>
							<?php
								$associated = explode('.', $formField);
								if (count($associated)>1) {
									$header = Inflector::humanize(str_replace('_id', '', $associated[1]));
								} else {
									$header = Inflector::humanize(str_replace('_id', '', $formField));
								}
							?>
							<th><?= $header ?></th>
						<?php endforeach;?>
					</tr>
				</thead>
				<tbody>
				<?php 
				if (count($data->assessment_periods)>0) :
					// iterate each row
					foreach ($data->assessment_periods as $key => $period) :
				?>
					<tr>

					<?php 
						// iterate each field in a row
						foreach ($attr['formFields'] as $formField):
							$field = $attr['fields'][$formField];
							$associated = explode('.', $formField);
							if (count($associated)>1) {
								$record = $period->$associated[0];
							} else {
								$record = $period;
							}
					?>
	
						<td><?= $this->HtmlField->{$field['type']}('view', $record, $field, ['label'=>false, 'name'=>'']); ?></td>

					<?php endforeach;?>
					
					</tr>
				<?php
					endforeach;
				endif;
				?>
				</tbody>
			</table>
		</div>

<?php endif ?>
