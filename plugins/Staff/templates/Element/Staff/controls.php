<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php        
        $baseUrl = $this->Url->build([
            'plugin' => $this->request->getParam('plugin'),
            'controller' => $this->request->getParam('controller'),
            'action' => $this->request->getParam('action'),
            '0' => 'index',
            '1' => $encodedQueryString,
        ]);
        $template = $this->ControllerAction->getFormTemplate();
        $this->Form->templates($template);       

//start:POCOR-5274
        if($academicPeriodId != 0){
            if (!empty($academicPeriodOptions)) {
                echo $this->Form->input('academic_period_id', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $academicPeriodOptions,
                    'url' => $baseUrl,
                    'default' => $academicPeriodId,
                    'data-named-key' => 'academic_period_id'
                ));
            }
        }else{
            if (!empty($academicPeriodOptions)) {
                echo $this->Form->input('academic_period_id', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $academicPeriodOptions,
                    'url' => $baseUrl,
                    'default' => 0,
                    'data-named-key' => 'academic_period_id'
                ));
            }
        }
//end:POCOR-5274        
        ?>
    </div>
</div>
