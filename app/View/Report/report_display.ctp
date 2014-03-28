<!--h1><?php echo ($reportName == '' ? 'Report Manager' : $reportName);?></h1-->
<div id="reportManagerDisplay">
    <?php 
    $counter = 0;
    $columns = 0;
    $floatFields = array();
    ?>     
    <?php if (!empty($reportData)):?>
    <table class="table" style="margin: 10px; width: auto;>
        <thead>
			<tr>
                <?php foreach ($fieldList as $field): ?>
                <td>
                <?php
                $columns++;
                $modelClass = substr($field, 0,strpos($field, '.'));
                $displayField = strtolower(substr($field, strpos($field, '.')+1));
                $displayField = ( isset($labelFieldList[$modelClass][$displayField]) ? $labelFieldList[$modelClass][$displayField] : ( isset($labelFieldList['*'][$displayField]) ? $labelFieldList['*'][$displayField] : $displayField ));
                $displayField = str_replace('_', ' ', $displayField);
                $displayField = ucfirst($displayField);
                echo $displayField; 
                if ( $fieldsType[$field] == 'float') // init array for float fields sum
                    $floatFields[$field] = 0;
                ?>
                </td>
                <?php endforeach; ?>
			</tr>
        </thead>
        <?php 
        $i = 0;        
        foreach ($reportData as $reportItem): 
            $counter++;
            $class = null;
            if ($i++ % 2 == 0) {
                $class = ' altrow';
            } 
        ?>
            <tr class="body<?php echo $class;?>">
                <?php foreach ($fieldList as $field): ?>
                    <td>
                    <?php                     
                    $params = explode('.',$field);
                    if ( $fieldsType[$field] == 'float') {
                        echo $this->element('format_float',array('f'=>$reportItem[$params[0]][$params[1]]));
                        $floatFields[$field] += $reportItem[$params[0]][$params[1]];
                    }                        
                    else
                        echo $reportItem[$params[0]][$params[1]]; 
                    ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        <?php if ( count($floatFields)>0 ) { ?>
            <tr class="footer">
                <?php foreach ($fieldList as $field): ?>
                <td>
                <?php
                if ( $fieldsType[$field] == 'float') 
                    echo $this->element('format_float',array('f'=>$floatFields[$field]));
                ?>
                </td>
                <?php endforeach; ?>
            </tr>
         <?php } ?>
    </table>
    <?php endif; ?>
</div>