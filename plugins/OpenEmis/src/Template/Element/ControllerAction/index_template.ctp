<?php
$this->start('toolbar');
    foreach ($toolbarButtons as $key => $btn) {
        if (!array_key_exists('type', $btn) || $btn['type'] == 'button') {
            echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
        } else if ($btn['type'] == 'element') {
            echo $this->element($btn['element'], $btn['data'], $btn['options']);
        }
    }
$this->end();

$this->start('panelBody');
    if (isset($toolbarElements)) {
        foreach ($toolbarElements as $element) {
            echo $this->element($element['name'], $element['data'], $element['options']);
        }
    }

    if ($ControllerAction['form']) {
        $formOptions = $this->ControllerAction->getFormOptions();
        if (array_key_exists('class', $formOptions)) {
            unset($formOptions['class']);
        }
        if (isset($ControllerAction['url'])) {
            $formOptions['url'] = $ControllerAction['url'];
        }
        echo $this->Form->create($ControllerAction['table']->newEntity(), $formOptions);
    }

    $phpVersion = substr(phpversion(), 0, 1);

    if ($phpVersion == '7') {
        usort($indexElements, function($a, $b) {
            if (!isset($a['order']) && !isset($b['order'])) {
                return 0;
            } else if (!isset($a['order']) && isset($b['order'])) {
                return 1;
            } else if (isset($a['order']) && !isset($b['order'])) {
                return -1;
            } else {
                return $a["order"] > $b["order"] ? 1 : -1;
            }
        });
    } else {
        usort($indexElements, function($a, $b) {
            if (!isset($a['order']) && !isset($b['order'])) {
                return 1;
            } else if (!isset($a['order']) && isset($b['order'])) {
                return 1;
            } else if (isset($a['order']) && !isset($b['order'])) {
                return -1;
            } else {
                return $a["order"] - $b["order"];
            }
        });
    }

    foreach ($indexElements as $element) {
        echo $this->element($element['name'], $element['data'], $element['options']);
    }

    if ($ControllerAction['formButtons']) {
        echo $this->ControllerAction->getFormButtons();
    }

    if ($ControllerAction['form']) {
        echo $this->Form->end();
    }
$this->end();
