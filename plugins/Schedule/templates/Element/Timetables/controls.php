<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
    <?php
        $baseUrl = $this->Url->build([
            'plugin' => $this->request->getParam('plugin'),
            'controller' => $this->request->getParam('controller'),
            'action' => $this->request->getParam('action')
        ]);
        $template = $this->ControllerAction->getFormTemplate();
        $this->Form->templates($template); ?>
       
        <div class="input select required">
            <div class="input-select-wrapper">
            <?php   echo $this->Form->input('period', array(
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $periodOptions,
                    'url' => $baseUrl,
                    'data-named-key' => 'period',
                    'default' => $selectedPeriodOption,
                )); ?>
            </div>
        </div>
        <div class="input select required">
            <div class="input-select-wrapper">
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
            </div>
        </div>
        <div class="input select required">
            <div class="input-select-wrapper">
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
            </div>
        </div>
        <div class="input select required">
            <div class="input-select-wrapper">
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
    </div>
</div>  
