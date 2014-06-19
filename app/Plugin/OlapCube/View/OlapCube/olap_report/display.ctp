<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

?>

<?php echo $this->element('breadcrumb'); ?>

<div id="olap_report" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
            echo $this->Html->link(__('Back'), array('action' => 'olapReport'), array('class' => 'divider'));
          	echo $this->Html->link(__('Export to Excel'), array('action' => 'olapReportExport'), array('class' => 'divider'));
        
		?>
	</h1>
	<?php if(isset($data)) { ?>
    <div class="table" style="overflow:auto;width:670px;display:block;">
		<table style="width:99%;border:none;" cellpadding="0" cellspacing="0" border="0">
	      <thead>
	      	<tr>
			<?php foreach($data[0] as $key=>$value){ ?>
	        	<td class="table_cell"><?php echo $value; ?></td>
	     	<?php } ?>
	       </tr>
           </thead>
           <tbody>
           	<?php foreach($data as $key=>$value){ 
           		if($key==0){
           			continue;
           		}
           	?>
           	<tr>
           	  	<?php foreach ($value as $key2=>$value2){ ?>
           	  		<td><?php echo $value2; ?></td>
           	  	<?php } ?>
           	 </tr>
           	 <?php } ?>
           </tbody>
      </table>
    </div>
    <?php } ?>

</div>