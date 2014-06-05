
        <?php foreach ($latestActivities as $element) { ?>
        
        
        <div class="landing_li">
            <div class="landing_li_date activity_date" style="margin-left:5px;">
                <?php echo $this->Utility->formatDate($element['created']);//date('d M Y',strtotime($element['created'])); ?>
            </div>
            <?php 
				//pr($element['name']);
				
				
                $name = @$element['name'];//. (@is_null($element['last_name'])? '' : @$element['last_name']);

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

