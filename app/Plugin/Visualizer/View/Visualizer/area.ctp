<?php
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Visualizer.visualizer', 'stylesheet', array('inline' => false));
echo $this->Html->script('Visualizer.visualizer', false);

$this->extend('Elements/layout/container_visualizer_wizard');
$this->assign('contentHeader', $header);
$this->assign('contentId', 'visualizer');
$this->start('contentActions');

echo $this->Html->link($this->Label->get('general.reset'), array('action' => 'reset'), array('class' => 'divider'));
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin' => 'Visualizer'));
$formOptions['inputDefaults']['label']['class'] = 'col-md-1 control-label';

$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($this->action, $formOptions);
echo $this->Form->input('search', array('id' => 'searchDB', 'url' => 'Visualizer/ajaxAreaSearch'));
echo $this->Form->input('area_level', array('id'=> 'areaLevel' ,'options' => $areaLevelOptions, 'selected'=> $selectedAreaLevel,  'empty' => 'All', 'onchange' => 'Visualizer.areaLevelChange(this)', 'url' => 'Visualizer/'.$this->action));
?>
<div class='visualizer-list-table disable-overflow'>
	<?php
	$tableClass = 'table-checkable table-input';
	$headerfirstCol = array($this->Form->input(null, array('class' => 'icheck-input', 'label' => false, 'div' => false, 'type' => 'checkbox', 'checked' => false)) => array(
		'class' => 'checkbox-column',
		'onchange' => 'Visualizer.checkboxChangeAll(this)',
		'url' => 'Visualizer/ajaxUpdateUserCBSelection'
		));

	array_unshift($tableHeaders, $headerfirstCol); //array($headerfirstCol,$this->Label->get('datawarehouse.indicator'),$this->Label->get('datawarehouse.unit'),$this->Label->get('datawarehouse.dimension'));

	$tableData = array();
	if (!empty($tableRowData)) {
		$i = 0;
		foreach ($tableRowData as $obj) {
			//	pr($obj);
			if (empty($selectedAreaIds)) {
				$checked = false;
			} else {
				$checked = (in_array($obj['Area_NId'], $selectedAreaIds)) ? 'checked' : false;
			}
			
			$bodyFirstColOptions = array(
				'type' => 'checkbox', 
				'class' => 'icheck-input', 
				'label' => false, 
				'div' => false, 
				'checked' => $checked, 
				'value' => $obj['Area_NId'], 
				'sectionType' => 'area', 
				'onchange' => 'Visualizer.checkboxChange(this)',
				'url' => 'Visualizer/ajaxUpdateUserCBSelection'
				);
			$additionalClass = 'checkbox-column center';
			$input = $this->Form->input($this->action . '.Area_NId.' . $i, $bodyFirstColOptions);

			$row = array();
			$row[] = array($input, array('class' => $additionalClass));
			for ($i = 1; $i < count($tableHeaders); $i++) {
				$row[] = array($obj['level_' . $i . '_name'], array('class' => 'data-list'));
			}
			$tableData[] = $row;
			$i++;
		}
	}
	echo $this->element('layout/table', compact('tableHeaders', 'tableData', 'tableClass'));
	?>
</div>
<?php if ($this->Paginator->counter('{:pages}') > 1) : ?>
	<div class="row">
		<ul id="pagination">
			<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
	</div>
	<?php
endif;
echo $this->Form->end();
$this->end();
?>
