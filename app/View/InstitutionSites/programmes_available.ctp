<?php
    $ctr = 1;
    if(count($data) > 0){
        foreach($data as $System => $arrEduc){ 
?>
            <div class="table_row">
                    <div class="table_cell">
                        <input type="checkbox" name="data[InstitutionSiteProgramme][<?php echo $ctr;?>][education_programme_id]" value="<?php echo $arrEduc['EducationProgramme']['id'];?>">
                    </div>
                    <div class="table_cell"><?php echo $arrEduc['EducationProgramme']['name'];?></div>
                    <div class="table_cell"><?php echo $arrEduc['EducationLevel']['name'];?></div>
                    <div class="table_cell"><?php echo $arrEduc['EducationCycle']['name'];?></div>
                    <div class="table_cell"><?php echo $arrEduc['EducationFieldOfStudy']['name'];?></div>
            </div>
<?php 
            $ctr++;
        }
    }
?>
