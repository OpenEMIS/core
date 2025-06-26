<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php
        if (!empty($encodedQueryString)) {
            $baseUrl = $this->Url->build([
                'plugin' => $this->request->getParam('plugin'),
                'controller' => $this->request->getParam('controller'),
                'action' => $this->request->getParam('action'),
                '0' => 'index',
                '1' => $encodedQueryString,
            ]);
        }


        echo $this->Form->control('academic_period_id', [
            'class' => 'form-control',
            'label' => false,
            'options' => $academicPeriodList ?? [],
            'url' => $baseUrl,
            'default' => $selectedPeriod ?? '',
            'data-named-key' => 'academic_period_id',
        ]);

        echo $this->Form->control('month', [
            'class' => 'form-control',
            'label' => false,
            'options' => $monthOptions ?? [],
            'url' => $baseUrl,
            'default' => $selectedMonth ?? '',
            'data-named-key' => 'month',
        ]);
        ?>
    </div>
</div>
