<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('Survey.survey', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Survey/js/survey', false);
echo $this->Html->script('/Survey/js/jquery.quicksand', false);
echo $this->Html->script('/Survey/js/jquery.sort', false);
echo $this->Html->script('/Survey/js/jquery-ui', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Edit Survey'));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), '/Survey/index', array('class' => 'divider', 'id'=>'back'));
$this->end();
$this->assign('contentId', 'divSurvey');
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action']));
echo $this->Form->create('Survey', array_merge(array('id'=>'submitForm'), $formOptions));
?>
	
<?php echo $this->element('alert');?>
<!-- No Database value for the moment -->

 <div>
    <?php echo $this->Form->input('year', array('value' => $myYear, 'id' => 'year', 'readonly'=>true));  ?>
    <?php echo $this->Form->input('category', array('value' => $myCategory, 'id' => 'category', 'readonly'=>true));  ?>
    <?php echo $this->Form->hidden('siteTypes', array('value'=> $mySiteID, 'id'=> 'siteTypes', 'readonly'=>true));  ?>
    <?php if($myCatID=='0'){ ?>
    <?php echo $this->Form->input('siteTypesName', array('value'=> $mySiteType, 'id'=> 'siteTypes', 'readonly'=>true, 'label'=>array('text'=>'Site Types', 'class'=>'control-label col-md-3')));  ?>
    <?php } ?>
    <?php echo $this->Form->input('filename', array('type' => 'text', 'value'=>$name, 'id' => 'filename', 'readonly'=>true));  ?>
</div>
<?php
	$tmp =array();	
?>
<div id="questionOps" class="table-responsive">
<!-- Topic Heading -->
 <!--<ul id="sort-topic" style="margin-left:-40px;">-->
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
                                    <table style=" <?php if($arrSecVal['type']=='Grid_Fix' || $arrSecVal['type']=='Grid_Unlimited'){ echo 'display:none'; } ?>;"
                                     class="table table-striped table-hover table-bordered">
                                        <thead class="table_head">
                                            <tr>
                                            <th style="width:80px;">
											<?php
                                                echo $this->Form->input($topic.'.'.$section.'.checked', array('label'=>false, 'type' => 'checkbox',
																											'checked'=>((isset($data[$topic][$section]))?true:false),
                                                                                                              'onchange'=>'Survey.activate(this, \'.table_row\')'));
                                                echo $this->Form->input($topic.'.'.$section.'.order', array( 'value' => $arrSecVal['order'], 'type' => 'hidden'));
                                                echo $this->Form->input($topic.'.'.$section.'.type', array( 'value' => $arrSecVal['type'], 'type' => 'hidden'));
                                                echo $this->Form->input($topic.'.'.$section.'.label', array( 'value' => __($arrSecVal['label']), 'type' => 'hidden'));
                                                if($arrSecVal['type']!='Single'){ 
                                                echo $this->Form->input($topic.'.'.$section.'.value', array( 'value' => __($arrSecVal['value']), 'type' => 'hidden'));
                                                }
                                            ?>
                                            </th>
                                            <th style="vertical-align:middle;"><?php echo __('Options'); ?></th>
                                            </tr>
                                        </thead>
                                        <!-- Sorting Javascript -->
                                        <script language="javascript">
                                            $(function() {
                                                        $( '#sort-question<?php echo $topicCnt.$sectionCnt;?>' ).sortable();
                                                        $( "#sort-question<?php echo $topicCnt.$sectionCnt;?>" ).disableSelection();
                                                        });
                                        </script>
                                        <!-- End Sorting Javascript -->
                                        <tbody id="sort-question<?php echo $topicCnt.$sectionCnt;?>" class="table_body">
                                        <?php $qCnt = 1; ?>
                                        <?php foreach($arrSecVal['questions'] as $question => $arrQuestionVal) { ?>
                                            <tr>
                                                <td>
                                                <?php
                                                    echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.checked', 
                                                                        array('label'=>false, 'type' => 'checkbox',
																		'checked'=>((isset($data[$topic][$section]['questions'][$question]))?true:false),
																		'onchange'=>'Survey.activateQuestion(this, \'.table_row\')'));
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
                                                </td>
                                                <td><?php echo $arrQuestionVal['label']; ?></td>
                                            </tr>
                                        <?php $qCnt++; ?>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                    <!-- End Question Heading -->
                                <?php }else{ ?>
                                    <!-- Question Heading -->
                                    <table style=" <?php if($arrSecVal['type']=='Grid_Fix' || $arrSecVal['type']=='Grid_Unlimited'){ echo ''; } ?>;" class="table table-striped table-hover table-bordered">
                                        <thead>
                                            <tr>
                                            <th style="width:80px;">
											<?php
                                                echo $this->Form->input($topic.'.'.$section.'.checked', array('label'=>false, 'type' => 'checkbox',
																										'checked'=>((isset($data[$topic][$section]))?true:false),
                                                                                                              'onchange'=>'Survey.activate(this, \'.table_row\')'));
                                                echo $this->Form->input($topic.'.'.$section.'.order', array( 'value' => $arrSecVal['order'], 'type' => 'hidden'));
                                                echo $this->Form->input($topic.'.'.$section.'.type', array( 'value' => $arrSecVal['type'], 'type' => 'hidden'));
                                                echo $this->Form->input($topic.'.'.$section.'.label', array( 'value' => __($arrSecVal['label']), 'type' => 'hidden'));
                                                if($arrSecVal['type']!='Single'){ 
                                                echo $this->Form->input($topic.'.'.$section.'.value', array( 'value' => __($arrSecVal['value']), 'type' => 'hidden'));
                                                }
                                            ?>
                                            </th>
                                            <th style="vertical-align:middle;"><?php echo __('Options'); ?></th>
                                            </tr>
                                        </thead>
                                        <!-- Sorting Javascript -->
                                        <script language="javascript">
                                            $(function() {
                                                        $( '#sort-question<?php echo $topicCnt.$sectionCnt;?>' ).sortable();
                                                        $( "#sort-question<?php echo $topicCnt.$sectionCnt;?>" ).disableSelection();
                                                        });
                                        </script>
                                        <!-- End Sorting Javascript -->
                                        <tbody id="sort-question<?php echo $topicCnt.$sectionCnt;?>" class="table_body">
                                        <?php $qCnt = 1; ?>
                                        <?php foreach($arrSecVal['questions'] as $question => $arrQuestionVal) { ?>
                                            <tr>
                                                <td>
                                                <?php
                                                    echo $this->Form->input($topic.'.'.$section.'.questions.'.$question.'.checked', 
                                                                        array('label'=>false, 'type' => 'checkbox',
																		'checked'=>((isset($data[$topic][$section]['questions'][$question]))?true:false),
																		'onchange'=>'Survey.activateQuestion(this, \'.table_row\')'));
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
                                                </td>
                                                <td><?php echo $arrQuestionVal['label']; ?></td>
                                            </tr>
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
                                        </tbody>
                                    </table>
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
<?php 
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index')));
echo $this->Form->end();
$this->end();
?>