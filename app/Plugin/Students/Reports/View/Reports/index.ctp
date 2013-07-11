<?php 
echo $this->Html->css('jquery_ui', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Reports/css/reports', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery.ui', false);
echo $this->Html->script('app', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<style type="text/css">
.col_age { width: 90px; text-align: center; }
.col_name { width: 130px; }
.col_desc { width: 300px; }
.col_lastgen { width: 70px; }
</style>

<script>
var maskId ;
var Report = {
	id : 0,
	Part : 0,
	TotalRecords : 0,
	LimitPerRun : 0,
	ProgressTpl : '<div id="prog_wrapper_{id}" >Complete <div id="prog_count_{id}" style="display:inline">0%</div> <img id="prog_img_{id}" style="vertical-align:middle" src="http://dev.openemis.org/demo/img/icons/loader.gif" ></div>',
	init : function(){
		/*$(':button').click(function(o){
			objButton = this;
			Report.id = $(this).attr('reportid');
			$.ajax({
				type: 'GET',
				dataType: "json",
				url: getRootURL()+"Reports/getCSVCount/" +Report.id,
				beforeSend: function (jqXHR) {
					//maskId = $.mask({});
				},
				success: function (data, textStatus) {
					console.log($(objButton).closest('.col_name'));
					Report.TotalRecords = parseInt(data.total);
					Report.LimitPerRun = data.limit;
					  //$(':button').attr('disabled','disabled');
					//$(':button').attr('disabled','disabled')
					$.dialog({
						content:'<div id="progresswrapper" style="background:url(http://jimpunk.net/Loading/wp-content/uploads/loading130.gif); background-repeat:no-repeat; margin:auto;width:66px !important;height:66px;"><div id="progressbar" style=" height: 66px; line-height: 66px; text-align: center; width: 66px;">0%</div></div>',
						title:$(objButton).parent().siblings('.col_name').html(),
						showCloseBtn:false
					})
						
					Report.genReport(0);
					

//$.closeDialog()
					
					
					
				}
			});
			
		});*/
	},
	progressComplete:function(){
		$("#progressbar").html('0%');
		
		window.location = getRootURL()+'Reports/download/'+Report.id;
		$.closeDialog();
		
		
	},
	genReport:function(batch){
		
		$.ajax({
			type: 'GET',
			dataType: "json",
			url: getRootURL()+"Reports/genReport/" +Report.id+'/'+batch,
			beforeSend: function (jqXHR) {
				
			},
			success: function (data, textStatus) {
				if (data.processed_records >=  Report.TotalRecords) {
					percentage = 100;
					Report.progressComplete();
				}else{
					var percentage = Math.floor(100 * parseInt(data.processed_records) / parseInt(Report.TotalRecords));
					console.log(percentage);
					Report.part = data.batch;
					Report.genReport(Report.part);
				}
				//$("#uploadprogressbar").progressBar(percentage);
				$("#progressbar").html(percentage+'%');
				
			}
		});
		
	}
	
}
$(document).ready(function(){	
	Report.init();
	
        setTimeout(function() {
                $('#alertError').fadeOut(2000);
        }, 3000);
   // $("#progressbar").progressbar({ value: 37 });
 
});
</script>
<?php 
$ctr = 0;
if(@$enabled === true){
	if(count($data) > 0){?>
		<div id="report-list" class="content_wrapper">
				<?php foreach($data as $module => $arrVals) {?>
			<h1>
				<span><?php echo __(ucwords($module)); ?></span>
			</h1>
                       
                        <div id="alertError" title="Click to dismiss" class="alert alert-error" style="position:relative; margin-bottom: 10px;display: <?php echo ($msg !='')?'block':'none'; ?>; opacity: 0.891195;"><div class="alert-icon"></div><div class="alert-content"><?php echo __('The selected report is currently being processed.'); ?></div></div>
                       
				<?php foreach($arrVals as $type => $arrTypVals) { ?>
				<fieldset class="section_group">
						<legend><?php echo __($type); ?></legend>
						<div class="table">
							<div class="table_head">
									<div class="table_cell col_name"><?php echo __('Name'); ?></div>
									<div class="table_cell col_desc"><?php echo __('Description'); ?></div>
									<div class="table_cell col_lastgen"><?php echo __('Generated'); ?></div>
                                    <div class="table_cell col_age"><?php echo __('File'); ?></div>
							</div>

							<div class="table_body">
									<?php 
										$ctr = 1;
										foreach ($arrTypVals as $key => $value) { 
									?>
									<div class="table_row <?php echo ($ctr%2==0)?'even':''; ?>">
											<div class="table_cell col_name"><?php echo __($value['name']);?></div>
											<div class="table_cell col_desc"><?php echo __($value['description']);?></div>
                                                                                        <div class="table_cell col_lastgen"><?php echo $value['lastgen'];?></div>
											<div class="table_cell col_age"><!--input type="button" onclick="window.location=getRootURL()+'/Reports/download/<?php echo $value['id']; ?>'" reportid="<?php echo $value['id']; ?>" value="Download"-->
											
											<?php 
												foreach($value['file_kinds'] as $ktype => $vtype){
														if($checkFileExist[$ktype]['isExists']){
															echo ' <a href="javascript:void(0);" onclick="window.location=getRootURL()+\'/Reports/download/'.$ktype.'\'" reportid="'.$ktype.'">'.strtoupper($vtype).'</a>';
														}else{
															echo ' <span reportid="'.$ktype.'" style="font-color:gray">'.strtoupper($vtype).'</span>';

														}

												} 
											?>
											</div>
									</div>
									<?php  $ctr++;  } ?>
							</div>
						</div>
				</fieldset>
				<?php } ?>
			<?php } ?>
		</div>
	<?php $ctr++; } else {?> 
	<div class="content_wrapper" id="report-list">
							<h1>
				<span><?php echo __('Custom Reports'); ?></span>
			</h1>
								<fieldset class="section_group">
						<legend><?php echo __('Custom'); ?></legend>
						<div class="table">
							<div class="table_head">
									<div class="table_cell col_name"><?php echo __('Name'); ?></div>
									<div class="table_cell col_desc"><?php echo __('Description'); ?></div>
									<div class="table_cell col_lastgen"><?php echo __('Generated'); ?></div>
                                    <div class="table_cell col_age"><?php echo __('File'); ?></div>
							</div>

							
						</div>
						<div style="width:100%;margin:15px 5px;"><?php echo __('Please contact'); ?> <a href="<?php echo $this->Html->url(array('plugin' => null,'controller'=>'Home','action'=>'support'))?>"> <?php echo __('support'); ?> </a> <?php echo __('for more information on Custom Reports.'); ?></div>
				</fieldset>
									</div>
<?php	  }

}else{ ?>
	Report Feature disabled
	
<?php } ?>