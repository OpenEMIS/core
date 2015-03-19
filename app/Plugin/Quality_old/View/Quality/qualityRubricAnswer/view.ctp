<?php
echo $this->Html->css('/Quality/css/rubrics', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Quality/js/quality.rubric', false);
//echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Students/js/students', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Utility->ellipsis(__($subheader), 50));
$this->assign('contentId', 'student');
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'qualityRubricHeader', $selectedQualityRubricId, $rubricTemplateId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>

    <table class='rubric-table'>
        <?php
        $this->RubricsView->defaultNoOfColumns = $totalColumns;
        //$this->RubricsView->defaultNoOfRows = $totalRows;
        //$options = array('columnHeader'=> $columnHeaderData);
        //pr($options);
        foreach ($this->data['RubricsTemplateDetail'] as $key => $item) {
            $item['editable'] = $editable;
            
            if (array_key_exists('RubricsTemplateSubheader', $item)) {
                echo $this->RubricsView->insertRubricTableHeader($item, $key, NULL);
            } else {
                $item['columnHeader'] = $columnHeaderData;
                echo $this->RubricsView->insertQualityRubricTableQuestionRow($item, $key, NULL);
            }
        }
        ?>
    </table>
   
<?php $this->end(); ?>  
