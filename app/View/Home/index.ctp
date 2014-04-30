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
             // echo $this->Html->image($image['imagePath'], array(
            $leftPos = "left:-{$image['x']}px;";
            if($lang_dir=='rtl'){
                $leftPos ="margin-left:-{$image['x']}px;";
            }
             echo $this->Html->image(array("controller" => "Config", "action" => "fetchImage", $image["id"]), array(
                'style' => "width:initial;height:initial;position:relative;top:-{$image['y']}px;$leftPos"
            ));
        ?>
        </div>
        <?php } ?>
    </div><!-- 
    <div id="banner_image">
        <?php echo $this->Html->image('demoBanner.jpg') ?>
    </div> -->
</div><!-- end banner -->

<div id="highlight">
	<div id="news">
    	<div class="landing_header">
        	<?php echo __('Statistics'); ?>
        </div>
            <span id="StatisticsContent"><?php echo $this->Html->image('loading.gif'); ?></span>
        <!-- <div class="landing_li" style="height:162px; overflow-y: auto; padding: 8px 5px 0 3px;">
            <?php echo $message; ?>
        </div> -->
        <!-- 
        <div class="landing_li">
        	OpenEMIS has been updated to version 1.2.5.
            <br />
            <span class="landing_li_date">
            	26 September 2012
            </span>
        </div> -->
    </div><!-- end news -->
    
    <div id="activity">
    	<div class="landing_header">
        	<?php echo __('Activities'); ?>
        </div>
        <span id="ActivitiesContent"><?php echo $this->Html->image('loading.gif'); ?></span>
    </div><!-- end activity -->
	<div class="clear_both"></div>
</div><!-- end highlight -->

<?php //echo $this->element('sql_dump'); ?>

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