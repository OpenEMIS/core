<?php if (!empty($periodOptions) || !empty($outcomeOptions)) : ?>
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
            <?php   if (!empty($periodOptions)) { ?>
                    <?php   echo $this->Form->input('period', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $periodOptions,
                        'default' => $selectedPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'period'
                    )); ?>
            <?php   } ?>  

            <?php   if (!empty($outcomeOptions)) { ?>
                    <?php   echo $this->Form->input('outcome', array(
                        'type' => 'select',
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $outcomeOptions,
                        'default' => $selectedOutcome,
                        'url' => $baseUrl,
                        'data-named-key' => 'outcome',
                        'data-named-group' => 'period'
                    )); ?>
            <?php   } ?>   
        </div>
    </div>
<?php endif ?>
