<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Survey.survey', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Survey/js/survey', false);
echo $this->Html->script('/Survey/js/jquery.quicksand', false);
echo $this->Html->script('/Survey/js/jquery.sort', false);
echo $this->Html->script('/Survey/js/jquery-ui', false);
?>
<style>
    .table .cell_section_name { min-width: 0 !important; width: 600px; }
    .table .cell_section_option { text-align:center; }
    .table_row { background-color:#FFF; }
</style>
<?php echo $this->element('breadcrumb'); ?>

<div id="survey" class="content_wrapper">
    <?php
    echo $this->Form->create('add', array(
        'id' => 'submitForm',
        'inputDefaults' => array('label' => false, 'div' => false), 
        'url' => array('controller' => 'Survey', 'action' => 'add')
    ));
    ?>
    <h1>
        <span><?php echo __('New Surveys'); ?></span>
        <?php
        if($_add) {
            echo $this->Html->link(__('View'), array('action' => 'index'), array('class' => 'divider'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <!-- No Database value for the moment -->
        <div style="padding-left:10px;">
            <div class="row">
                <div class="label" ><?php echo __('Year'); ?></div>
                <div class="value"><?php echo $this->Form->input('year', array('options' => $year, 'id' => 'year'));  ?></div>
            </div>
            <div class="row">
                <div class="label"><?php echo __('Category'); ?></div>
                <div class="value"><?php echo $this->Form->input('category', array('options' => $category,
                                                                                'id' => 'category',
                                                                                'onChange' => 'Survey.siteTypeChange();'));  ?></div>
            </div>

            <div class="row" id="SiteTypeDDL" style="display:none">
                <div class="label"><?php echo __('Site Type'); ?></div>
                <div class="value"><?php echo $this->Form->input('siteTypes', array('options' => $siteTypes,
                                                                                    'id'=> 'siteTypes',
                                                                                    'onChange' => 'Survey.siteTypeChange();'));  ?></div>                                                              
            </div>
            <div class="row">
                <div class="label"><?php echo __('Survey Name'); ?></div>
                <div class="value"><?php echo $this->Form->input('filename', array('type' => 'text',
                                                                                'id' => 'filename',
                                                                                'class' => 'default'));  ?></div>
            </div>
        </div>
        <div id="questionOps">
        <!-- Topic Heading -->
        <!--<ul id="sort-topic">-->
            <?php $topicCnt = 1; ?>
            <?php foreach($questions as $topic => $arrTopVal) { ?>
            <fieldset class="section_break">
                <legend>
                    <?php echo $topic;?>
                    <?php echo $this->Form->input($topic.'.order', array( 'value' => $arrTopVal['order'], 'type' => 'hidden')); ?>
                </legend>
                    <!-- Section Heading -->
                    <!-- Sorting Javascript -->
                    <script language="javascript">
                        $(function() {
                                    $( '#sort-section<?php echo $topicCnt;?>' ).sortable();
                                    $( "#sort-section<?php echo $topicCnt;?>" ).disableSelection();
                                    });
                    </script>
                    <!-- End Sorting Javascript -->
                    <ul id="sort-section<?php echo $topicCnt;?>" class="table_body">
                    <?php $sectionCnt = 1; ?>
                    <?php foreach($arrTopVal as $section => $arrSecVal) { ?>
                        <?php if($section!='order'){ ?>
                                    <fieldset class="section_group">
                                    <legend><?php echo $arrSecVal['label']; ?></legend>
                                        <!-- This portion can be simplified -->
                                        <?php if($arrSecVal['type']=='Grid_Multi'){ ?> 
                                            <!-- Question Heading -->
                                            <div class="table" style=" <?php if($arrSecVal['type']=='Grid_Fix' || $arrSecVal['type']=='Grid_Unlimited'){ echo 'display:none'; } ?>; margin-top:10px;">
                                                <div class="table_head">
                                                    <div class="table_cell cell_checkbox">
                                                    <?php
                                                        echo $this->Form->input($topic.'.'.$section.'.checked', array('label'=>false, 'type' => 'checkbox',
                                                                                                                      'onchange'=>'Survey.activate(this, \'.table_row\')'));
                                                        echo $this->Form->input($topic.'.'.$section.'.order', array( 'value' => $arrSecVal['order'], 'type' => 'hidden'));
                                                        echo $this->Form->input($topic.'.'.$section.'.type', array( 'value' => $arrSecVal['type'], 'type' => 'hidden'));
                                                        echo $this->Form->input($topic.'.'.$section.'.label', array( 'value' => __($arrSecVal['label']), 'type' => 'hidden'));
                                                        if($arrSecVal['type']!='Single'){ 
                                                        echo $this->Form->input($topic.'.'.$section.'.value', array( 'value' => __($arrSecVal['value']), 'type' => 'hidden'));
                                                        }
                                                    ?>
                                                    </div>
                                                    <div class="table_cell" style="text-align:left;"><?php echo __('Options'); ?></div>
                                                </div>
                                                <!-- Sorting Javascript -->
                                                <script language="javascript">
                                                    $(function() {
                                                                $( '#sort-question<?php echo $topicCnt.$sectionCnt;?>' ).sortable();
                                                                $( "#sort-question<?php echo $topicCnt.$sectionCnt;?>" ).disableSelection();
                                                                });
                                                </script>
                                                <!-- End Sorting Javascript -->
                                                <ul id="sort-question<?php echo $topicCnt.$sectionCnt;?>" class="table_body">
                                                <?php $qCnt = 1; ?>
                                                <?php foreach($arrSecVal['questions'] as $question => $arrQuestionVal) { ?>
                                                    <div class="table_row">
                                                        <div class="table_cell cell_checkbox">
                                                        <?php
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.checked', 
                                                                                array('label'=>false, 'type' => 'checkbox','onchange'=>'Survey.activateQuestion(this, \'.table_row\')'));
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.order', 
                                                                                array( 'value' => $arrQuestionVal['order'], 'type' => 'hidden'));
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.type', 
                                                                                array( 'value' => $arrQuestionVal['type'], 'type' => 'hidden'));
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.label', 
                                                                                array( 'value' => __($arrQuestionVal['label']), 'type' => 'hidden'));
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.value', 
                                                                                array( 'value' => '', 'type' => 'hidden'));
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.null', 
                                                                                array( 'value' => $arrQuestionVal['null'], 'type' => 'hidden'));
                                                            foreach($arrQuestionVal['questions'] as $subQuestion => $arrSubQuestionVal) {
                                                                echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.questions.'.$subQuestion.'.order', 
                                                                                    array( 'value' => $arrSubQuestionVal['order'], 'type' => 'hidden'));
                                                                echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.questions.'.$subQuestion.'.type', 
                                                                                    array( 'value' => $arrSubQuestionVal['type'], 'type' => 'hidden'));
                                                                echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.questions.'.$subQuestion.'.label', 
                                                                                    array( 'value' => __($arrSubQuestionVal['label']), 'type' => 'hidden'));
                                                                echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.questions.'.$subQuestion.'.box', 
                                                                                    array( 'value' => __($arrSubQuestionVal['box']), 'type' => 'hidden'));
                                                                echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.questions.'.$subQuestion.'.value', 
                                                                                    array( 'value' =>'', 'type' => 'hidden'));
                                                                                    
                                                                // Items Handle
                                                                if(isset($arrSubQuestionVal['items'])){
                                                                    if(count($arrSubQuestionVal['items'])>0){
                                                                        foreach($arrSubQuestionVal['items'] as $key=>$itemval){
                                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.questions.'.$subQuestion.'.items.'.$key, array( 'value' => __($itemval), 'type' => 'hidden'));
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        ?>
                                                        <!-- Add Mapping here -->
                                                        <?php
                                                        // Mapping Handle
                                                        if(isset($arrQuestionVal['Rule'])){
                                                            foreach($arrQuestionVal['Rule'] as $colrulename => $arrcolruleprop) { // rule
                                                                $myCtr=0;
                                                                foreach($arrcolruleprop as $colmapname => $arrcolmapprop) { // mapping
                                                                    foreach($arrcolmapprop as $itemname => $itemvalue) {
                                                                        if($itemname=="fields"){
                                                                            foreach($itemvalue as $fieldkey=>$fieldval){
                                                                                echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.rule.mapping.'.$myCtr.'.fields.'.$fieldkey, 
                                                                                                    array('value' => $fieldval, 'type' => 'hidden'));
                                                                            }
                                                                        }
                                                                        if($itemname=="ids"){
                                                                            foreach($itemvalue as $idkey=>$idval){
                                                                                foreach($idval as $id=>$val){
                                                                                    echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.rule.mapping.'.$myCtr.'.ids.'.$idkey.'.'.$id, 
                                                                                                    array('value' => $val, 'type' => 'hidden'));
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                    $myCtr++;
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        <!-- End Mapping here -->
                                                        </div>
                                                        <div class="table_cell cell_section_name"><?php echo $arrQuestionVal['label']; ?></div>
                                                    </div>
                                                <?php $qCnt++; ?>
                                                <?php } ?>
                                                </ul>
                                            </div>
                                            <!-- End Question Heading -->
                                        <?php }else{ ?>
                                            <!-- Question Heading -->
                                            <div class="table" style=" <?php if($arrSecVal['type']=='Grid_Fix' || $arrSecVal['type']=='Grid_Unlimited'){ echo ''; } ?>; margin-top:10px;">
                                                <div class="table_head">
                                                    <div class="table_cell cell_checkbox">
                                                    <?php
                                                        echo $this->Form->input($topic.'.'.$section.'.checked', array('label'=>false, 'type' => 'checkbox',
                                                                                                                      'onchange'=>'Survey.activate(this, \'.table_row\')'));
                                                        echo $this->Form->input($topic.'.'.$section.'.order', array( 'value' => $arrSecVal['order'], 'type' => 'hidden'));
                                                        echo $this->Form->input($topic.'.'.$section.'.type', array( 'value' => $arrSecVal['type'], 'type' => 'hidden'));
                                                        echo $this->Form->input($topic.'.'.$section.'.label', array( 'value' => __($arrSecVal['label']), 'type' => 'hidden'));
                                                        if($arrSecVal['type']!='Single'){ 
                                                        echo $this->Form->input($topic.'.'.$section.'.value', array( 'value' => __($arrSecVal['value']), 'type' => 'hidden'));
                                                        }
                                                    ?>
                                                    </div>
                                                    <div class="table_cell" style="text-align:left;"><?php echo __('Options'); ?></div>
                                                </div>
                                                <!-- Sorting Javascript -->
                                                <script language="javascript">
                                                    $(function() {
                                                                $( '#sort-question<?php echo $topicCnt.$sectionCnt;?>' ).sortable();
                                                                $( "#sort-question<?php echo $topicCnt.$sectionCnt;?>" ).disableSelection();
                                                                });
                                                </script>
                                                <!-- End Sorting Javascript -->
                                                <ul id="sort-question<?php echo $topicCnt.$sectionCnt;?>" class="table_body">
                                                <?php $qCnt = 1; ?>
                                                <?php foreach($arrSecVal['questions'] as $question => $arrQuestionVal) { ?>
                                                    <div class="table_row">
                                                        <div class="table_cell cell_checkbox">
                                                        <?php
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.checked', 
                                                                                array('label'=>false, 'type' => 'checkbox','onchange'=>'Survey.activateQuestion(this, \'.table_row\')'));
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.order', 
                                                                                array( 'value' => $arrQuestionVal['order'], 'type' => 'hidden'));
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.type', 
                                                                                array( 'value' => $arrQuestionVal['type'], 'type' => 'hidden'));
                                                            echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.label', 
                                                                                array( 'value' => __($arrQuestionVal['label']), 'type' => 'hidden'));
                                                            
                                                            if($arrSecVal['type']=='Single'){
                                                                echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.value', 
                                                                                    array( 'value' =>'', 'type' => 'hidden'));
                                                            }else{
                                                                echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.box', 
                                                                                    array( 'value' => __($arrQuestionVal['box']), 'type' => 'hidden'));
                                                            }
                                                                                
                                                            // Items Handle
                                                            if(isset($arrQuestionVal['items'])){
                                                                if(count($arrQuestionVal['items'])>0){
                                                                    foreach($arrQuestionVal['items'] as $key=>$itemval){
                                                                        echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.items.'.$key, array( 'value' => __($itemval), 'type' => 'hidden'));
                                                                    }
                                                                }
                                                            }
                                                        ?>
                                                        </div>
                                                        <div class="table_cell cell_section_name"><?php echo $arrQuestionVal['label']; ?></div>
                                                    </div>
                                                <?php $qCnt++; ?>
                                                <?php } ?>
                                                <!-- Add Mapping here -->
                                                <?php
                                                // Mapping Handle
                                                if(isset($arrSecVal['Rule'])){
                                                    foreach($arrSecVal['Rule'] as $colrulename => $arrcolruleprop) { // rule
                                                        $myCtr=0;
                                                        foreach($arrcolruleprop as $colmapname => $arrcolmapprop) { // mapping
                                                            foreach($arrcolmapprop as $itemname => $itemvalue) {
                                                                if($itemname=="fields"){
                                                                    foreach($itemvalue as $fieldkey=>$fieldval){
                                                                        echo $this->Form->input($topic.'.'.$section.'.rule.mapping.'.$myCtr.'.fields.'.$fieldkey, 
                                                                                            array('value' => $fieldval, 'type' => 'hidden'));
                                                                    }
                                                                }
                                                                if($itemname=="ids"){
                                                                    foreach($itemvalue as $idkey=>$idval){
                                                                        foreach($idval as $id=>$val){
                                                                            echo $this->Form->input($topic.'.'.$section.'.rule.mapping.'.$myCtr.'.ids.'.$idkey.'.'.$id, 
                                                                                            array('value' => $val, 'type' => 'hidden'));
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            $myCtr++;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <!-- End Mapping here -->
                                                </ul>
                                            </div>
                                            <!-- End Question Heading -->
                                        <?php } ?>
                             <?php $sectionCnt++; ?>
                             </fieldset>
                        <?php } ?>
                    <?php } ?>
                    </ul>
                    <!-- End Section Heading -->
            </fieldset>
            <?php $topicCnt++; ?>
            <?php } ?>
        <!--</ul>-->
        <!-- End Topic Heading -->
        </div>
    <div class="controls">
        <input type="submit" name="SaveButton" value="<?php echo __('Save'); ?>" onclick="return Survey.checkDuplicate();" class="btn_save btn_right"/>
        <input type="submit" name="CancelButton" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
    </div>
    <?php echo $this->Form->end(); ?>
</div>