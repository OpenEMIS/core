<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($isEditable) {
    if ($_add) {
        echo $this->Html->link(__('Add'), array('action' => 'financesAdd', $selectedYear), array('class' => 'divider'));
    }
    if ($_edit) {
        echo $this->Html->link(__('Edit'), array('action' => 'financesEdit', $selectedYear), array('class' => 'divider'));
    }
}
$this->end();

$this->start('contentBody');
echo $this->element('census/year_options');
?>

<?php
foreach ($data as $nature => $arrFinanceType) {
	?>
	<fieldset class="section_group">
		<legend><?php echo $nature; ?></legend>
		<?php foreach ($arrFinanceType as $finance => $arrCategories) { ?>
			<fieldset class="section_break">
				<legend><?php echo $finance; ?></legend>
				<table class="table table-striped table-hover table-bordered">
					<thead>
						<tr>
							<th><?php echo __('Source'); ?></th>
							<th><?php echo __('Category'); ?></th>
							<th><?php echo __('Description'); ?></th>
							<th><?php echo __('Amount'); ?> (<?php echo $this->Session->read('configItem.currency'); ?>)</th>
						</tr></thead>
					<tbody>
						<?php
						foreach ($arrCategories as $arrValues) {
							$record_tag = "";
							switch ($arrValues['CensusFinance']['source']) {
								case 1:
									$record_tag.="row_external";
									break;
								case 2:
									$record_tag.="row_estimate";
									break;
							}
							?>
							<tr>
								<td class="<?php echo $record_tag; ?>"><?php echo $arrValues['FinanceSource']['name']; ?></td>
								<td class="<?php echo $record_tag; ?>"><?php echo $arrValues['FinanceCategory']['name']; ?></td>
								<td class="<?php echo $record_tag; ?>"><?php echo $arrValues['CensusFinance']['description']; ?></td>
								<td class="<?php echo $record_tag; ?>"><?php echo $arrValues['CensusFinance']['amount']; ?></td>
							</tr>
						<?php } ?>   
					</tbody>
				</table>
			</fieldset>
		<?php } ?>
	</fieldset>
	<?php
}
?>

<?php $this->end(); ?>
