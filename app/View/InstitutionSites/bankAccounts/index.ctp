<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'bankAccountsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Active'); ?></th>
				<th><?php echo __('Account Name'); ?></th>
				<th><?php echo __('Account Number'); ?></th>
				<th><?php echo __('Bank'); ?></th>
				<th><?php echo __('Branch'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php
			if(count($data) > 0){
				foreach($data as $obj) {
					$id = $obj[$model]['id'];
					echo '<tr>
							<td class="center">'.($obj[$model]['active'] == 1? '&#10003;' : '').'</td>
							<td>'.$this->Html->link($obj[$model]['account_name'], array('action' => 'bankAccountsView', $id), array('escape' => false)).'</td>
							<td>'.$obj[$model]['account_number'].'</td>
							<td>'.$bankList[$obj['BankBranch']['bank_id']].'</td>
							<td>'.$obj['BankBranch']['name'].'</td>
						</tr>';
				}
			}
			?>
		</tbody>
	</table>
</div>
<?php $this->end(); ?>
