<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false); 

echo $this->Html->script('app.translation',false);
$this->extend('/Elements/layout/container');
$this->assign('contentClass', 'search');
$this->assign('contentHeader', $header);
$this->start('contentActions');
$total = 0;
if (strlen($this->Paginator->counter('{:count}')) > 0) {
	$total = $this->Paginator->counter('{:count}');
}
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'add'), array('class' => 'divider'));
}

if ($_execute) {
	echo $this->Html->link($this->Label->get('general.compile'), array(), array('url' => $this->params['controller'] . '/compile' ,'class' => 'divider void', 'onclick' => 'Translation.compileFile(this)'));
}
?>
<span class="divider"></span>
<span class="total"><span><?php echo $total ?></span> <?php echo __('Translations'); ?></span>
<?php
$this->end();

$this->start('contentBody');

echo $this->Form->input('language', array(
	'label' => false,
	'between' => '<div class="col-md-4">',
	'after' => '</div>',
	'div' => 'row select_row page-controls',
	'options' => $languageOptions,
	'value' => $selectedLang,
	'url' => $this->params['controller'] . '/' . $this->params['action'],
	'onchange' => 'jsForm.change(this)',
	'class' => 'form-control',
));
?>
<div class="row">
	<?php echo $this->Form->create('Translation', array('url' => array('action'=>'index',$selectedLang))); ?>
	<div class="search_wrapper">
		<?php echo $this->Form->input('SearchField', array(
			'id' => 'SearchField',
			'value' => $searchKey,
			'placeholder' => __("Search"),
			'class' => 'default',
			'div' => false,
			'label' => false));
		?>
		<span class="icon_clear">X</span>
	</div>
	<div class="submit">
		<input id="searchbutton" class="icon_search" type="submit" value="">
	</div>
	<?php echo $this->Form->end(); ?>
</div>

<div id="mainlist">
<?php if ($this->Paginator->counter('{:pages}') > 1) : ?>
		<div class="row">
			<ul id="pagination">
	<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
				<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
				<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
			</ul>
		</div>
<?php endif; ?>

	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead url="InstitutionSites/index">
				<tr>
					<th>
						<span class="left"><?php echo 'English'; ?></span>
					</th>
					<th>
						<span class="left"><?php echo $languageOptions[$selectedLang]; ?></span>
					</th>
				</tr>
			</thead>
			<tbody>
<?php
foreach ($data as $arrItems):
	$id = $arrItems['Translation']['id'];
	//$code = $arrItems['Translation']['code'];//$this->Utility->highlight($searchField,$arrItems['Translation']['code']);
	$name = $arrItems['Translation']['eng']; //$this->Utility->highlight($searchField,$arrItems['Translation']['english'].((isset($arrItems['InstitutionSiteHistory']['name']))?'<br>'.$arrItems['InstitutionSiteHistory']['name']:''));
	?>
					<tr row-id="<?php echo $id ?>">
						<td><?php echo $this->Html->link($name, array('action' => 'view', $id), array('escape' => false)); ?></td>
						<td><?php echo $arrItems['Translation'][$selectedLang]; ?></td>                   
					</tr>
<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="row">
		<ul id="pagination">
<?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
			<?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
			<?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
		</ul>
	</div>
</div> <!-- mainlist end-->

<?php echo $this->Js->writeBuffer(); ?>
<?php $this->end(); ?>
