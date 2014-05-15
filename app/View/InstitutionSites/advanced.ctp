<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('search', false);

$session = $this->Session;
//$arrKeys = @array_keys($session->read('InstitutionSite.AdvancedSearch'));
//if ($arrKeys) {
//    foreach ($arrKeys as $names) {
//        if (strpos($names, "CustomValue") > 0) {
//            $Model = str_replace("CustomValue", "", $names);
//        }
//    }
//} else {
//    $Model = "InstitutionSite";
//}
//$preload = @array($Model, (is_null($session->read('InstitutionSite.AdvancedSearch.siteType')) ? 0 : $session->read('InstitutionSite.AdvancedSearch.siteType')));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Advanced Search'));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
echo $this->Html->link(__('Clear'), array('action' => 'advanced', 0), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
?>
<div id="institutions" class="customFieldsWrapper search">

    <?php
    echo $this->Form->create('Search', array(
        'url' => array('controller' => 'InstitutionSites', 'action' => 'advanced'),
        'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
    ));
    echo $this->Form->hidden('area_id', array('id' => 'area_id', 'value' => $session->read('InstitutionSite.AdvancedSearch.Search.area_id')));
    ?>
    <h3><?php echo __('General'); ?></h3>
    <div class="row">
        <div class="label"><?php echo __('Area'); ?></div>
        <div class="value"><?php echo $this->Form->input('area', array('id' => 'area', 'class' => 'form-control', 'type' => 'text', 'onfocus' => 'this.select()', 'value' => $session->read('InstitutionSite.AdvancedSearch.Search.area'))); ?></div>
    </div>


    <h3>Custom Fields</h3>
    <?php
    $arrTabs = array('InstitutionSite');
    ?>
    <div class="containerTab">
        <div class="tab_container">
            <div id="tab1" class="tab_content">
                <div id='CustomFieldDiv'>
                    <?php
                    //$session = $this->Session;
                    //$sessModel = (stristr($customfields[0], "Institution") === TRUE) ? 'Institution' : $customfields[0];
                    //$sessVal = $session->read($sessModel . '.AdvancedSearch');
                    $sessVal = $session->read('InstitutionSite.AdvancedSearch');

                    foreach ($customfields as $arrdataFieldsVal) {

                        if ($arrdataFieldsVal == 'InstitutionSite') {
                            echo '<div class="row">
                            <div class="label"> Site Type:</div>
                            <div class="value">
                            

                                            <div class="">
                                            <div class="field_value">
                                                    <select name="data[siteType]" class="form-control" onChange="objCustomFieldSearch.getDataFields($(this).val());">';
                            echo '   <option value="0">All</option>';
                            foreach ($types as $key => $val) {
                                echo '   <option value="' . $key . '" ' . ($key == $typeSelected ? 'selected="selected"' : "") . '>' . __($val) . '</option>';
                            }
                            echo '</select>
                                            </div> 
                                            </div>
                               </div>
                    </div>';
                        }
                        if (count(@$dataFields[$arrdataFieldsVal]) > 0) {
                            foreach ($dataFields[$arrdataFieldsVal] as $arrVals) {
                                if ($arrVals[$arrdataFieldsVal . 'CustomField']['type'] == 1) {//Label
                                    echo '<fieldset class="section_break">
								<legend>' . __($arrVals[$arrdataFieldsVal . 'CustomField']['name']) . '</legend>
						</fieldset>';
                                } else {
                                    ?>
                                    <div class="row">
                                        <div class="label"><?php echo __($arrVals[$arrdataFieldsVal . 'CustomField']['name']); ?></div>
                                        <div class="value">
                                            <?php
                                            if ($arrVals[$arrdataFieldsVal . 'CustomField']['type'] == 2) {//Text
                                                echo '<div class="">
                                                                               <div class="field_value">';


                                                $val = (isset($sessVal[$arrdataFieldsVal . 'CustomValue']['textbox'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'])) ?
                                                        $sessVal[$arrdataFieldsVal . 'CustomValue']['textbox'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'] : "";
                                                echo '<input type="text" class="default form-control" name="data[' . $arrdataFieldsVal . 'CustomValue' . '][textbox][' . $arrVals[$arrdataFieldsVal . 'CustomField']["id"] . '][value]" value="' . $val . '" >';
                                                echo '</div>
                                                               </div>';
                                            } elseif ($arrVals[$arrdataFieldsVal . 'CustomField']['type'] == 3) {//DropDown
                                                echo '<div class="">
                                                                               <div class="field_value">';


                                                if (count($arrVals[$arrdataFieldsVal . 'CustomFieldOption']) > 0) {

                                                    $arrDropDownVal = array_unshift($arrVals[$arrdataFieldsVal . 'CustomFieldOption'], array("id" => "", "value" => ""));
                                                    echo '<select name="data[' . $arrdataFieldsVal . 'CustomValue][dropdown][' . $arrVals[$arrdataFieldsVal . 'CustomField']["id"] . '][value]" class="form-control">';

                                                    foreach ($arrVals[$arrdataFieldsVal . 'CustomFieldOption'] as $arrDropDownVal) {

                                                        if (isset($sessVal[$arrdataFieldsVal . 'CustomValue']['dropdown'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'])) {
                                                            $defaults = $sessVal[$arrdataFieldsVal . 'CustomValue']['dropdown'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'];
                                                        }
                                                        echo '<option value="' . $arrDropDownVal['id'] . '" ' . ($defaults == $arrDropDownVal['id'] ? 'selected="selected"' : "") . '>' . $arrDropDownVal['value'] . '</option>';
                                                    }
                                                    echo '</select>';
                                                }
                                                echo '</div>
                                                               </div>';
                                            } elseif ($arrVals[$arrdataFieldsVal . 'CustomField']['type'] == 4) {
                                                echo '<div class="">
                                                                               <div class="field_value">';
                                                $defaults = array();
                                                if (count($arrVals[$arrdataFieldsVal . 'CustomFieldOption']) > 0) {

                                                    foreach ($arrVals[$arrdataFieldsVal . 'CustomFieldOption'] as $arrDropDownVal) {

                                                        if (isset($sessVal[$arrdataFieldsVal . 'CustomValue']['checkbox'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'])) {

                                                            if (count($sessVal[$arrdataFieldsVal . 'CustomValue']['checkbox'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'] > 0)) {
                                                                foreach ($sessVal[$arrdataFieldsVal . 'CustomValue']['checkbox'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'] as $arrCheckboxVal) {
                                                                    $defaults[] = $arrCheckboxVal;
                                                                }
                                                            }
                                                        }
                                                        echo '<input name="data[' . $arrdataFieldsVal . 'CustomValue][checkbox][' . $arrVals[$arrdataFieldsVal . 'CustomField']["id"] . '][value][]" type="checkbox" ' . (in_array($arrDropDownVal['id'], $defaults) ? 'checked' : "") . ' value="' . $arrDropDownVal['id'] . '"> <label>' . $arrDropDownVal['value'] . '</label> ';
                                                    }
                                                }

                                                echo '</div> 
                                                               </div>';
                                            } elseif ($arrVals[$arrdataFieldsVal . 'CustomField']['type'] == 5) {
                                                echo '<div class=""> 
                                                                               <div class="field_value">';
                                                $val = '';
                                                if (isset($sessVal[$arrdataFieldsVal . 'CustomValue']['textarea'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'])) {
                                                    $val = ($sessVal[$arrdataFieldsVal . 'CustomValue']['textarea'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'] ? $sessVal[$arrdataFieldsVal . 'CustomValue']['textarea'][$arrVals[$arrdataFieldsVal . 'CustomField']["id"]]['value'] : "");
                                                }

                                                echo '<textarea name="data[' . $arrdataFieldsVal . 'CustomValue][textarea][' . $arrVals[$arrdataFieldsVal . 'CustomField']["id"] . '][value]" class="form-control">' . $val . '</textarea>';
                                                echo '</div>
                                                               </div>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                        }
                    }
                    ?>
                </div>
            </div>

        </div>
    </div>
    <div style="clear:both"></div>
    <div class="controls view_controls">
        <input type="submit" value="<?php echo __('Search'); ?>" class="btn_save btn_right" />
    </div>
    <?php echo $this->Form->end(); ?>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        objSearch.attachAutoComplete();

    })
</script>
<?php $this->end(); ?>