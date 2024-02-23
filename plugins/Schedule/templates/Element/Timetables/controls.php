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
        $this->Form->templates($template); ?>
        <?php   echo $this->Form->input('period', array(
                'type' => 'select',
                'class' => 'form-control',
                'label' => false,
                'options' => $periodOptions,
                'url' => $baseUrl,
                'data-named-key' => 'period',
                'default' => $selectedPeriodOption,
        )); ?>
        <?php   echo $this->Form->input('term', array(
                'type' => 'select',
                'class' => 'form-control',
                'label' => false,
                'options' => $termOptions,
                'url' => $baseUrl,
                'data-named-group' => 'period,grade,status',
                'data-named-key' => 'term',
                'default' => $selectedTermOptions,
        )); ?>
        <?php echo $this->Form->input('grade', array(
            'type' => 'select',
            'class' => 'form-control',
            'label' => false,
            'options' => $educationGradeOptions,
            'url' => $baseUrl,
            'data-named-group' => 'period,term,status',
            'data-named-key' => 'grade',
            'default' => $selectedGradeOptions,
        )); ?>
        <?php echo $this->Form->input('status', array(
            'type' => 'select',
            'class' => 'form-control',
            'label' => false,
            'options' => $statusOptions,
            'url' => $baseUrl,
            'data-named-group' => 'period,term,grade',
            'data-named-key' => 'status',
            'default' => $selectedStatusOption,
        )); ?>
    </div>
</div>  
