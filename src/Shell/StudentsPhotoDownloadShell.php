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

class StudentsPhotoDownloadShell extends Shell
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
                        ->where(['is_student' => 1, 'photo_content !=' =>''])
                        ->toList();
          
            $target_dir = "webroot/downloads/student-photo/";
           
            foreach ($studentData as $studentDatas){

                $target_file = $target_dir . basename($studentDatas->photo_name);
                $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
                $extensions_arr = array("jpg","jpeg","png","gif");

                if( in_array($imageFileType,$extensions_arr) )
                {
                    file_put_contents("webroot/downloads/student-photo/".$studentDatas->openemis_no.'.'.$imageFileType, $studentDatas->photo_content);
                }   
             }
            
         } catch (Exception $e) {
                 pr($e->getMessage());
        }
    }
}
