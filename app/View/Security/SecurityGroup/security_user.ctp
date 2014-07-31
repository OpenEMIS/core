<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('SecurityUser.title') ?></label>
	<div class="col-md-6">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('general.name') ?></th>
						<th><?php echo $this->Label->get('SecurityRole.name') ?></th>
						<th class="cell-delete"></th>
					</tr>
				</thead>
				<tbody>
				<?php 
				if ($action == 'edit') :
					foreach ($areas as $i => $area) :
				?>
					<tr>
						<td>
							<?php
							echo $this->Form->hidden("SecurityGroupUser.$i.institution_site_id", array('class' => 'value-id', 'value' => $institution['SecurityGroupInstitutionSite']['institution_site_id']));
							echo $this->Form->hidden('index', array('name' => 'index', 'class' => 'index', 'value' => $i));
							echo $institution['InstitutionSite']['name'];
							?>
						</td>
						<td><span class="icon_delete" title="<?php echo $this->Label->get('general.delete') ?>" onclick="jsTable.doRemove(this)"></span></td>
					</tr>
				<?php
					endforeach;
				endif;
				?>
				</tbody>
			</table>
			<a class="void icon_plus" url="Security/SecurityGroup/ajaxGetAccessOptionsRow/2" onclick="SecurityGroup.getAccessOptionsRow(this)">
				<?php echo $this->Label->get('general.add') . ' ' . $this->Label->get('SecurityUser.name') ?>
			</a>
		</div>
	</div>
</div>
