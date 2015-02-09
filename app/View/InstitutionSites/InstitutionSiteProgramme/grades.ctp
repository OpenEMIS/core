<?php
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);
?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('general.grades'); ?></label>
	<div class="col-md-8">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<th><?php echo $this->Label->get('general.name'); ?></th>
				</tr>
			</thead>
			
			<tbody>
				<?php
					$i = 0;
					foreach($educationGrades as $educationGradeId => $educationGrade) :
				?>
					<tr>
						<td class="checkbox-column">
							<?php
								if(isset($this->request->data['InstitutionSiteProgramme']['id'])) {	//edit
									echo $this->Form->hidden('InstitutionSiteGrade.' . $i . '.id');
								}
								echo $this->Form->hidden('InstitutionSiteGrade.' . $i . '.institution_site_id', array('value' => $institutionSiteId));
								echo $this->Form->hidden('InstitutionSiteGrade.' . $i . '.education_grade_id', array('value' => $educationGradeId));
								echo $this->Form->checkbox('InstitutionSiteGrade.' . $i . '.status', array('class' => 'icheck-input'));
							?>
						</td>
						<td>
							<?php
								echo $educationGrade;
							?>
						</td>
					</tr>
				<?php 
					$i++;
					endforeach; 
				?>
			</tbody>
		</table>
	</div>
</div>
