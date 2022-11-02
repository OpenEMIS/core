<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
    <?php
        $baseUrl = $this->Url->build([
            'plugin' => $this->request->params['plugin'],
            'controller' => $this->request->params['controller'],
            'action' => $this->request->params['action']
        ]);
        $template = $this->ControllerAction->getFormTemplate();
        $this->Form->templates($template);

        echo $this->Form->input('period', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $periodOptions,
            'url' => $baseUrl,
            'data-named-key' => 'period',
            'default' => $selectedPeriodOption,
        ));

        echo $this->Form->input('term', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $termOptions,
            'url' => $baseUrl,
            'data-named-group' => 'period,grade,status',
            'data-named-key' => 'term',
            'default' => $selectedTermOptions,
        ));

        echo $this->Form->input('grade', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $educationGradeOptions,
            'url' => $baseUrl,
            'data-named-group' => 'period,term,status',
            'data-named-key' => 'grade',
            'default' => $selectedGradeOptions,
        ));

        echo $this->Form->input('status', array(
            'class' => 'form-control',
            'label' => false,
            'options' => $statusOptions,
            'url' => $baseUrl,
            'data-named-group' => 'period,term,grade',
            'data-named-key' => 'status',
            'default' => $selectedStatusOption,
        ));


    ?>
    </div>
</div>  
