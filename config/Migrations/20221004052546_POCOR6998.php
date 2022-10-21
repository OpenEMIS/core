<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Cake\Utility\Hash;
use Cake\Utility\Text;

class POCOR6998 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        //backup the table
        $this->execute('CREATE TABLE IF NOT EXISTS `z_6998_education_programmes_next_programmes` LIKE `education_programmes_next_programmes`');
        $this->execute('INSERT INTO `z_6998_education_programmes_next_programmes` SELECT * FROM `education_programmes_next_programmes`');

        $this->execute('TRUNCATE TABLE `education_programmes_next_programmes`');

        $education_systems = TableRegistry::get('education_systems');
        $education_levels = TableRegistry::get('education_levels');
        $education_cycles = TableRegistry::get('education_cycles');
        $education_programmes = TableRegistry::get('education_programmes');
        $education_programmes_next_programmes = TableRegistry::get('education_programmes_next_programmes'); 
        $education_grades = TableRegistry::get('education_grades');

        $EducationSystemData = $education_systems
                                ->find()
                                ->select(['id', 'name', 'order'])
                                //->where([$education_systems->aliasField('id') => 4])  //using for single education system testing purpose
                                ->order([$education_systems->aliasField('order ASC')])
                                ->hydrate(false)
                                ->toArray();

        if(!empty($EducationSystemData)){
            foreach ($EducationSystemData as $sys_key => $sys_value) {
                $education_system_id = $sys_value['id'];
                //for education levels
                $EducationLevelData = $education_levels
                                        ->find()
                                        ->select([
                                            'level_id' => $education_levels->aliasField('id'), 
                                            'level_name' => $education_levels->aliasField('name'),
                                            'education_system_id'
                                        ])
                                        ->where([$education_levels->aliasField('education_system_id') => $education_system_id])
                                        ->order([$education_levels->aliasField('order ASC')])
                                        ->hydrate(false)
                                        ->toArray();
                
                if(!empty($EducationLevelData)){
                    $EducationLevelArray = [];
                    foreach ($EducationLevelData as $level_key => $level_value) {
                        $EducationLevelArray[] = $level_value['level_id'];
                    }
                    //for education cycles
                    $EducationCycleData = $education_cycles
                                ->find()
                                ->select([
                                    'cycle_id' => $education_cycles->aliasField('id'), 
                                    'cycle_name' => $education_cycles->aliasField('name'),
                                    'order' => $education_levels->aliasField('order'),
                                    'education_level_id'])
                                ->InnerJoin([$education_levels->alias() => $education_levels->table()], [
                                    $education_levels->aliasField('id = ') . $education_cycles->aliasField('education_level_id')
                                ])
                                ->where([$education_cycles->aliasField('education_level_id IN') => $EducationLevelArray])
                                ->order([$education_levels->aliasField('order ASC')])
                                ->hydrate(false)
                                ->toArray();

                    if(!empty($EducationCycleData)){
                        $EducationCycleArray = [];
                        foreach ($EducationCycleData as $cycle_key => $cycle_value) {
                            $EducationCycleArray[] = $cycle_value['cycle_id'];
                        } 

                        //for education program
                        $EducationProgramData = $education_programmes
                                        ->find()
                                        ->select([
                                            'program_id' => $education_programmes->aliasField('id'), 
                                            'program_name' => $education_programmes->aliasField('name'),
                                            'order' => $education_levels->aliasField('order'),
                                            'education_cycle_id'])
                                        ->InnerJoin([$education_cycles->alias() => $education_cycles->table()], [
                                            $education_cycles->aliasField('id = ') . $education_programmes->aliasField('education_cycle_id')
                                        ])
                                        ->InnerJoin([$education_levels->alias() => $education_levels->table()], [
                                            $education_levels->aliasField('id = ') . $education_cycles->aliasField('education_level_id')
                                        ])
                                        ->where([$education_programmes->aliasField('education_cycle_id IN') => $EducationCycleArray])
                                        ->order([$education_levels->aliasField('order ASC'), $education_programmes->aliasField('id ASC')])
                                        ->hydrate(false)
                                        ->toArray();
                                       
                        if(!empty($EducationProgramData)){
                            $EducationProgramArray = [];
                            foreach ($EducationProgramData as $program_key => $program_value) {
                                $EducationProgramArray[] = $program_value['program_id'];
                            }
                            
                            if(!empty($EducationProgramArray)){
                                //for education next program array
                                $EducationNextProgramArray = [];
                                foreach ($EducationProgramArray as $nextprogram_key => $nextprogram_value) {
                                    if($nextprogram_key !=0){
                                        $EducationNextProgramArray[] = $nextprogram_value;
                                    }
                                }

                                if(!empty($EducationNextProgramArray)){
                                    foreach ($EducationProgramArray as $prog_key => $prog_value) {
                                        foreach ($EducationNextProgramArray as $next_key => $next_value) {
                                            if($prog_key == $next_key){
                                                $this->execute("INSERT INTO education_programmes_next_programmes (id, education_programme_id, next_programme_id)
                                                values (uuid(), '$prog_value', '$next_value')");
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }    
        }                       
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `education_programmes_next_programmes`');
        $this->execute('RENAME TABLE `z_6998_education_programmes_next_programmes` TO `education_programmes_next_programmes`');
    }
}

