<?php if ($action == 'view') : ?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo $this->Label->get('general.name') ?></th>
				<th><?php echo $this->Label->get('SecurityRole.name') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data[$model]['SecurityGroupUser'] as $obj) : ?>
			<tr>
				<td><?php echo trim($obj['SecurityUser']['first_name'] . ' ' . $obj['SecurityUser']['last_name']) ?></td>
				<td><?php echo $obj['SecurityRole']['name'] ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php else : ?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get("$model.SecurityGroupUser") ?></label>
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
					foreach ($this->data[$model]['SecurityGroupUser'] as $i => $obj) :
				?>
					<tr>
						<td>
							<?php
							echo $this->Form->hidden("SecurityGroupUser.$i.security_user_id", array('class' => 'value-id', 'value' => $obj['SecurityGroupUser']['security_user_id']));
							echo $this->Form->hidden("SecurityGroupUser.$i.security_role_id", array('value' => $obj['SecurityGroupUser']['security_role_id']));
							echo $this->Form->hidden('index', array('name' => 'index', 'class' => 'index', 'value' => $i));
							echo trim($obj['SecurityUser']['first_name'] . ' ' . $obj['SecurityUser']['last_name']);
							?>
						</td>
						<td><?php echo $obj['SecurityRole']['name'] ?></td>
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
<?php endif ?>
