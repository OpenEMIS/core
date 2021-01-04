<style>
   .table-wrapper.shadow-right .table>tbody>tr>td:first-child, 
   .table-wrapper.shadow-left .table>tbody>tr>td:first-child{
      
       max-width: 100% !important;
   }
</style>
<?php $comment = $this->request->query['comment']; ?>
<?php if (!empty($academicPeriodOptions) || !empty($templateOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                if(!empty($comment) && $comment == 1){
                    $baseUrl = $this->Url->build([
                        'plugin' => $this->request->params['plugin'],
                        'controller' => $this->request->params['controller'],
                        'action' => $this->request->params['action'],
                        'comment' => 1
                    ]);
                }else{
                    $baseUrl = $this->Url->build([
                        'plugin' => $this->request->params['plugin'],
                        'controller' => $this->request->params['controller'],
                        'action' => $this->request->params['action']
                    ]);
                }
                
                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                if (!empty($academicPeriodOptions)) {
                    echo $this->Form->input('academic_period', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $academicPeriodOptions,
                        'default' => $selectedAcademicPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'academic_period'
                    ));
                }

                if (!empty($templateOptions)) {
                    echo $this->Form->input('template', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $templateOptions,
                        'default' => $selectedTemplate,
                        'url' => $baseUrl,
                        'data-named-key' => 'template',
                        'data-named-group' => 'academic_period'
                    ));
                }

                if (!empty($periodOptions)) {
                    echo $this->Form->input('period', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $periodOptions,
                        'default' => $selectedPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'period',
                        'data-named-group' => 'academic_period,template,subject'
                    ));
                }

                if (!empty($subjectOptions)) {
                    echo $this->Form->input('subject', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $subjectOptions,
                        'default' => $selectedSubject,
                        'url' => $baseUrl,
                        'data-named-key' => 'subject',
                        'data-named-group' => 'academic_period,template,period'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>

<div id="tabs" class="nav nav-tabs horizontal-tabs">
<span role="presentation" class="<?php echo (empty($comment))?'tab-active':''; ?>"><a href="<?php echo $this->Url->build([
                        'plugin' => $this->request->params['plugin'],
                        'controller' => $this->request->params['controller'],
                        'action' => $this->request->params['action']
                    ]); ?>"><?php echo __('Results') ?></a></span>
<span role="presentation" class="<?php echo (!empty($comment) && $comment == 1)?'tab-active':''; ?>"><a href="<?php echo $this->Url->build([
                        'plugin' => $this->request->params['plugin'],
                        'controller' => $this->request->params['controller'],
                        'action' => $this->request->params['action'],
                        'comment' => 1
                    ]); ?>"><?php echo __('Comments'); ?></a></span>
</div>