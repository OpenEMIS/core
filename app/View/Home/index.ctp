<?php echo $this->Html->css('home', null, array('inline' => false)); ?>

<?php echo $this->element('alert'); ?>

<?php if(!empty($adaptation)){ ?>
<div id="software_title">
	<?php echo $adaptation; ?>
</div>
<?php } ?>
<div id="banner">
	<div id="banner_box">
	   <h2><?php echo __('Notice'); ?></h2>
		<?php echo $message; ?>
	</div><!-- end banner_box -->
	
	<div id="banner_image">
		<?php if(isset($image)){ ?>
		<div style="overflow:hidden;width:<?php echo $image['width']; ?>px;height:<?php echo $image['height']; ?>px;" >
		<?php 
		 	$leftPos = '-'.$image['x'];
            if($lang_dir=='rtl'){
           		$leftPos = $image['original_width']-($image['width']+$image['x']);
            }

			 echo $this->Html->image(array("controller" => "Config", "action" => "fetchImage", $image["id"]), array(
				'style' => "width:initial;height:initial;position:relative;top:-{$image['y']}px;left:{$leftPos}px;"
			));
		?>
		</div>
		<?php } ?>
	</div>
</div><!-- end banner -->

<div id="highlight">
	<div id="news">
		<div class="landing_header">
			<?php echo __('Statistics'); ?>
		</div>
		<span id="StatisticsContent"><?php echo $this->Html->image('loading.gif'); ?></span>
	</div><!-- end news -->
	
	<div id="activity">
		<div class="landing_header">
			<?php echo __('Activities'); ?>
		</div>
		<span id="ActivitiesContent"><?php echo $this->Html->image('loading.gif'); ?></span>
	</div><!-- end activity -->
	<div class="clear_both"></div>
</div><!-- end highlight -->

<div class="clear_both"></div>

<script>
	$(document).ready(function(){
		var Stats = ['Statistics','Activities'];
		$.each(Stats,function(i,v){
			$.get(getRootURL()+'Home/getLatest'+v, function(data) {
				$('#'+v+'Content').fadeOut(500,function(){
					$('#'+v+'Content').html(data).promise().done(function(){
						$('#'+v+'Content').fadeIn(500);	
					});
				})
			});
		});
	});
</script>
