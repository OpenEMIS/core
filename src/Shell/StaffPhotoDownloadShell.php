<?php
namespace App\Shell;

use ArrayObject;
use Exception;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Datasource\ConnectionManager;

class StaffPhotoDownloadShell extends Shell
{
    CONST SLEEP_TIME = 10;
    CONST ACADEMIC_PERIOD_ID = 18;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Security.Users');
       
    }

    public function main()
    {   
        
        try {
            $connection = ConnectionManager::get('default');
            $userTable = TableRegistry::get('Security.Users');
            
            $studentData = $userTable->find()
                        ->select(['id','openemis_no','photo_name','photo_content'])
                        ->where(['is_staff' => 1, 'photo_content !=' =>''])
                        ->toList();


            $target_dir = ROOT . DS ."webroot/downloads/Staff-photo/";   // POCOR-6309
            // Start POCOR-6309
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $fullpath ='';
            if (!empty($this->args[0])) {
                $report_progress_res = $connection->execute('SELECT file_path FROM report_progress WHERE id="'.$this->args[0].'"');
                $report_progress_data = $report_progress_res->fetch('assoc');
                $fullpath .= $report_progress_data['file_path'];
            }
            // End POCOR-6309
            
            foreach ($studentData as $studentDatas){

                $target_file = $target_dir . basename($studentDatas->photo_name);
                $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
                $extensions_arr = array("jpg","jpeg","png","gif");

                if( in_array($imageFileType,$extensions_arr) )
                {
                    // Start POCOR-6309
                    $fullpath .= $studentDatas->openemis_no.'.'.$imageFileType;
                    $connection->execute('UPDATE report_progress SET file_path= "'.$fullpath.'" WHERE id="'.$this->args[0].'"');
                    // End POCOR-6309
                    
                    file_put_contents(ROOT . DS ."webroot/downloads/Staff-photo/".$studentDatas->openemis_no.'.'.$imageFileType, $studentDatas->photo_content); // POCOR-6309
                }   
             }
            
         } catch (Exception $e) {
                 pr($e->getMessage());
        }
    }
}
