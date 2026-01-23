<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use App\Shell\AlertShell;

class StudentAdmissionShell extends AlertShell
{
    public function initialize(): void
    {
        parent::initialize();

        $this->StudentAdmission = $this->fetchTable('System.StudentAdmission');
    }

    public function main()
    {
        $processName = $this->processName;
        $feature = $this->featureName;
        
        // Assign default values if arguments are missing
        $school_name = !empty($this->args[0]) ? $this->args[0] : '';
        $student_name = !empty($this->args[1]) ? $this->args[1] : '';
        $academic_year = !empty($this->args[2]) ? $this->args[2] : '';
        $grade_name = !empty($this->args[3]) ? $this->args[3] : '';
        $gaurdiand_data = !empty($this->args[4]) ? $this->args[4] : '';
        $gaurdiand_data = json_decode($gaurdiand_data);

        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => Time::now()], ['process_name' => $processName]);

        $rules = $this->getAlertRules($feature);
        foreach ($rules as $rule) {
            if (!empty($rule['security_roles'])) {
                $emailList = $this->getStudentAdmissionEmailList($gaurdiand_data);
                $email = !empty($emailList) ? implode(', ', $emailList) : ' ';

                // Prepare replacements
                $placeholders = ['${school_name}', '${student_name}', '${academic_year}', '${grade_name}'];
                $values = [$school_name, $student_name, $academic_year, $grade_name];

                // Replace all placeholders in subject and message
                $subject = str_replace($placeholders, $values, $rule->subject);
                $message = str_replace($placeholders, $values, $rule->message);

                // Insert into alert log
                $this->AlertLogs->insertStudentAdmissionAlertLog($rule->method, $rule->feature, $email, $subject, $message);
            }
        }
    }
}
