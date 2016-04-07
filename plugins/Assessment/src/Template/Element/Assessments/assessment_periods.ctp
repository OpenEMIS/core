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
				'ca-on-click-element' => 'addRow',
				'ca-on-click-source-url' => '/phpoe/Assessments/addNewAssessmentPeriod',
				'ca-on-click-target' => 'assessment_periods',
			]);
		?>
			<!-- <span class="loading_img margin-left-10"><img src="<?= $this->Url->build('/')?>open_emis/img/../plugins/autocomplete/img/loader.gif" plugin="false" alt=""></span> -->
		</div>
		<div class="table-wrapper full-width">
			<div class="table-responsive">
			    <!-- <table class="table"> -->
			    <table class="table table-body-scrollable">
					<thead>
						<tr>
							<th></th>
							<?php foreach ($attr['formFields'] as $formField) : ?>
								<?php if ($attr['fields'][$formField]['type']!='hidden') :
									$thClass = (isset($attr['fields'][$formField]['required']) && $attr['fields'][$formField]['required']) ? 'required' : '';
								?>
									<th class="<?= $thClass ?>"><label class="table-header-label"><?= Inflector::humanize(str_replace('_id', '', $formField)) ?></label></th>
								<?php endif; ?>
							<?php endforeach;?>

							<th></th>
							<th class="cell-delete">&nbsp;</th>
						</tr>
					</thead>

					<tbody id="table_assessment_periods" ca-id="assessment_periods" ca-on-click-target-element ca-on-click-target-handler="addRow" on-ready="onReadyFunction">
						
						<tr ng:repeat="(key, period) in clickTarget.handlers.addRow.assessment_periods">

							<td ng:repeat="(fieldname, attr) in period">
								
								<input type="{{attr.type}}" id="assessmentperiods-{{fieldname}}" class="form-error" name="Assessments[assessment_periods][{{key}}][{{fieldname}}]" value="{{attr.value}}"/>
								<span ng:show="attr.type!='hidden'" class="error">
									<ul ng:show="attr.errors.length>0" class="error-message error-message-in-table">
										<li ng:repeat="error in attr.errors">{{error}}</li>
									</ul>
								</span>

							</td>

							<td>
								<button class="btn btn-dropdown action-toggle btn-single-action" title="Delete" aria-expanded="true" type="reset" ng-click="clickTarget.removeRow('assessment_periods', key)"><i class="fa fa-trash"></i> <span><?= __('Delete')?></span></button>
							</td>

						</tr>

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
