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
             echo $this->Html->image(array("controller" => "Config", "action" => "fetchImage", $image["id"]), array(
                'style' => "width:initial;height:initial;position:relative;top:-{$image['y']}px;left:-{$image['x']}px;"
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
        
        <div class="landing_li banner_box_li">
            <?php echo $this->Number->format($institutions, $SeparateThousandsFormat); ?> 
            <br/><span><?php echo __('Institutions'); ?></span>
        </div>
        <div class="landing_li banner_box_li">
            <?php echo $this->Number->format($institutionSites, $SeparateThousandsFormat); ?> 
            <br/><span><?php echo __('Institutions Sites'); ?></span>
        </div>
        <div class="landing_li banner_box_li">
            <?php echo $this->Number->format($students, $SeparateThousandsFormat); ?> 
            <br/><span><?php echo __('Students'); ?></span>
        </div>
        <div class="landing_li banner_box_li">
            <?php echo $this->Number->format($teachers, $SeparateThousandsFormat); ?> 
            <br/><span><?php echo __('Teachers'); ?></span>
        </div>
        <div class="landing_li banner_box_li">
            <?php echo $this->Number->format($staffs, $SeparateThousandsFormat); ?> 
            <br/><span><?php echo __('Staff'); ?></span>
        </div>
        
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
        <?php foreach ($latestActivities as $element) { ?>
        
        
        <div class="landing_li">
            <div class="landing_li_date activity_date" style="margin-left:5px;">
                <?php echo $this->Utility->formatDate($element['created']);//date('d M Y',strtotime($element['created'])); ?>
            </div>
            <?php 
                $name = $element['name'].(is_null($element['last_name'])? '': $element['last_name']);

                $truncate_size = 35;

                $truncate_option = array(
                    'ellipsis' => '...',
                    'extact' => false,
                    'html' => true
                );

                // $truncated_text = String::truncate($name, $truncate_size, $truncate_option);
                $truncated_text = $name;
            ?>
            <?php 
                /*$text = '';
                if(strtolower($element['action']) == 'edited'){ 
                    
                    $text = '<div class="activites_em"><span class="activites_em">' . $truncated_text . '</span> (' . $element['module'] . ') '. __('had been ' . strtolower($element['action']) . ' by'). ' <span class="activites_em">'. $element['user_first_name'] . ' ' . $element['user_last_name'] . '</span></div>';
                
                } 
                if(strtolower($element['action']) == 'deleted'){ 
                    
                    $text = '<div class="activites_em"><span class="activites_em">' . $truncated_text . '</span> (' . $element['module'] . ') '.__('had been ' . strtolower($element['action']) . ' by').' <span class="activites_em">'. $element['user_first_name'] . ' ' . $element['user_last_name'] . '</span></div>';
                
                } 
                
                if(strtolower($element['action']) == 'added'){ 
                    $text = '<div class="activites_em"><span class="activites_em">' . $truncated_text. '</span> had been ' . strtolower($element['action']) . ' to the List of '. $element['module']. ' ';

                    if(array_key_exists('institution', $element) && !empty($element['institution'])){
                        $text .= 'in <span class="activites_em">' . $element['institution']. '</span> ';
                    }

                    $text .= "by <span class=\"activites_em\">{$element['user_first_name']} {$element['user_last_name']}</span></div>";
                }
				echo $text; */
				if(strtolower($element['action']) == 'edited' || strtolower($element['action']) == 'deleted'){
					echo sprintf('<div class="activites_em"><span class="activites_em">%s</span> (%s) '.__('has been '.strtolower($element['action'])).' '.__('by').' <span class="activites_em">%s</span></div>', $truncated_text,  __($element['module']),$element['user_first_name'] . ' ' . $element['user_last_name']);
				}elseif(strtolower($element['action']) == 'added'){
					echo sprintf('<div class="activites_em"><span class="activites_em">%s</span> '.__('has been added to the List of').' %s '.__('by').' <span class="activites_em">%s</span></div>',$truncated_text, __($element['module']),$element['user_first_name'] . ' ' . $element['user_last_name']);
				}
            ?>
        </div>
            
        <?php } ?>
    </div><!-- end activity -->
	<div class="clear_both"></div>
</div><!-- end highlight -->

<?php //echo $this->element('sql_dump'); ?>

<div class="clear_both"></div>
