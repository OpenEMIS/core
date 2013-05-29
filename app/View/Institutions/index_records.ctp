<?php
$pageOptions = array('escape'=>false,'style' => 'display:none');
$pageNumberOptions = array('modulus'=>5,'first' => 2, 'last' => 2,'tag' => 'li', 'separator'=>'','ellipsis'=>'<li><span class="ellipsis">...</span></li>');
?>
<div class="row">
	<ul id="pagination">
		<?php echo $this->Paginator->prev(__('Previous') , null, null, $pageOptions); ?>
		<?php echo $this->Paginator->numbers($pageNumberOptions); ?>
		<?php echo $this->Paginator->next(__('Next') , null, null, $pageOptions); ?>
	</ul>
</div>
<div class="table allow_hover" action="Institutions/listSites/">
	<div class="table_head">
			<div class="table_cell cell_code"><span class="left"><?php echo __('Code'); ?></span><span class="icon_sort_<?php echo ($sortedcol =='Institution.code')?$sorteddir:'up'; ?>"  order="Institution.code"></span></div>
			<div class="table_cell cell_name"><span class="left"><?php echo __('Institution Name'); ?></span><span class="icon_sort_<?php echo ($sortedcol =='Institution.name')?$sorteddir:'up'; ?>" order="Institution.name"></span></div>
			<div class="table_cell"><span class="left"><?php echo __('Sector'); ?></span><span class="icon_sort_<?php echo ($sortedcol =='InstitutionSector.name')?$sorteddir:'up'; ?>" order="InstitutionSector.name"></span></div>
			<div class="table_cell"><span class="left"><?php echo __('Provider'); ?></span><span class="icon_sort_<?php echo ($sortedcol =='InstitutionProvider.name')?$sorteddir:'up'; ?>" order="InstitutionProvider.name"></span></div>
	</div>
	<div class="table_body">
	<?php
			if(isset($institutions) && count($institutions) > 0){
				$ctr = 1;
				foreach ($institutions as $arrItems):
					//$area = (strlen($arrItems['Area']['name'])>14?substr($arrItems['Area']['name'], 0, 14).'...':$arrItems['Area']['name']);
					$id = $arrItems['Institution']['id'];
					$code = $this->Utility->highlight($searchField,$arrItems['Institution']['code']);
					$name = $this->Utility->highlight($searchField,'<b>'.$arrItems['Institution']['name'].'</b>'.((isset($arrItems['InstitutionHistory']['name']))?'<br>'.$arrItems['InstitutionHistory']['name']:''));
			?>
					<div row-id="<?php echo $id ?>" class="table_row table_row_selection <?php echo ((($ctr++%2) != 0)?'odd':'even');?>">
						<div class="table_cell"><?php echo $code; ?></div>
						<div class="table_cell"><?php echo $name; ?></div>
						<div class="table_cell"><?php echo $arrItems['InstitutionSector']['name']; ?></div>
						<div class="table_cell"><?php echo $arrItems['InstitutionProvider']['name']; ?></div>
					</div>
				<?php endforeach;
			}
			?>
	</div>
</div>
<?php if(sizeof($institutions)==0) { ?>
<div class="row center" style="color: red">No Institution found.</div>
<?php } ?>
<div class="row">
	<ul id="pagination">
		<?php echo $this->Paginator->prev(__('Previous') , null, null, $pageOptions); ?>
		<?php echo $this->Paginator->numbers($pageNumberOptions); ?>
		<?php echo $this->Paginator->next(__('Next') , null, null, $pageOptions); ?>
	</ul>
</div>