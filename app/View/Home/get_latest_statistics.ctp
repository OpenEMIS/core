
        <?php
        //pr($tableCounts);
        foreach ($tableCounts as $key => $value) {
            $plural = Inflector::pluralize($key);
            $plural = ucfirst(str_replace("_", ' ', Inflector::underscore($plural)));
            $plural = ($plural == 'Staffs')?'Staff':$plural;
            $plural = ($plural == 'Institution sites')?'Institutions Sites':$plural;
        ?>
        <div class="landing_li banner_box_li"> <?php //echo $key.'<br>'; ?>
            <?php echo $this->Number->format($tableCounts[$key], $SeparateThousandsFormat); ?> 
            <br/><span><?php echo __($plural); ?></span>
        </div>
<?php }
        ?>
        