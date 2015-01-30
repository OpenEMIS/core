<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
if ($isEditable) {
    if ($_add) {
        echo $this->Html->link(__('Add'), array('action' => 'financesAdd', $selectedAcademicPeriod), array('class' => 'divider'));
    }
}
$this->end();

$this->start('contentBody');
echo $this->element('census/academic_period_options');
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
							$recordClass = $this->FormUtility->getSourceClass($arrValues['CensusFinance']['source']);
							?>
							<tr>
								<td class="<?php echo $recordClass; ?>"><?php echo $this->Html->link($arrValues['FinanceSource']['name'], array('action' => 'financesEdit', $arrValues['CensusFinance']['id'])); ?></td>
								<td class="<?php echo $recordClass; ?>"><?php echo $arrValues['FinanceCategory']['name']; ?></td>
								<td class="<?php echo $recordClass; ?>"><?php echo $arrValues['CensusFinance']['description']; ?></td>
								<td class="<?php echo $recordClass; ?>"><?php echo $arrValues['CensusFinance']['amount']; ?></td>
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
