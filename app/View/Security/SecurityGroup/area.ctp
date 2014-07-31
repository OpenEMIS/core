<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('Area.title') ?></label>
	<div class="col-md-6">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('Area.name') ?></th>
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
							echo $this->Form->hidden("SecurityGroupArea.$i.area_id", array('class' => 'value-id', 'value' => $area['SecurityGroupArea']['area_id']));
							echo $this->Form->hidden('index', array('name' => 'index', 'class' => 'index', 'value' => $i));
							echo $area['Area']['name'];
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
			<a class="void icon_plus" url="Security/SecurityGroup/ajaxGetAccessOptionsRow/0" onclick="SecurityGroup.getAccessOptionsRow(this)">
				<?php echo $this->Label->get('general.add') . ' ' . $this->Label->get('Area.name') ?>
			</a>
		</div>
	</div>
</div>
