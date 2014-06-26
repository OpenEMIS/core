<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subTitle));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-bordered">
		<tbody>
			<tr>
				<td><?php echo __('Telephone'); ?></td>
				<td>
				<?php
					if(isset($supportInformation['phone']) && !is_null($supportInformation['phone']) && !empty($supportInformation['phone'])) {
						echo $supportInformation['phone']; 
					} else {
						echo __('Data not available.');
					}
				?>
				</td>
			</tr>
			<tr>
				<td><?php echo __('Email'); ?></td>
				<td>
				<?php 
					if(isset($supportInformation['email']) && !is_null($supportInformation['email']) && !empty($supportInformation['email'])) {
						echo '<a href="mailto:'. $supportInformation['email'] . '">'.$supportInformation['email'] .'</a>';
					} else {
						echo __('Data not available.');
					}
				?>
				</td>
			</tr>
			<tr>
				<td><?php echo __('Address'); ?></td>
				<td>
				<?php 
					if(isset($supportInformation['address']) && !is_null($supportInformation['address']) && !empty($supportInformation['address'])) {
						echo $supportInformation['address'];
					} else {
						echo __('Data not available.');
					}
				?>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
