<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\Console\Shell;
use Cake\Filesystem\Folder;
use Cake\Filesystem\File;

use App\Shell\AlertShell;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;

class StudentAdmissionShell extends AlertShell
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadModel('System.StudentAdmission');
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
        $recipient_id = !empty($this->args[4]) ? $this->args[4] : ''; // POCOR-9100

        $this->Alerts->updateAll(['process_id' => getmypid(), 'modified' => FrozenTime::now()], ['process_name' => $processName]); // POCOR-9100

        $rules = $this->getAlertRules($feature);
//        Log::debug(print_r($rules, true));

        foreach ($rules as $rule) {
                $emailList = $this->getStudentAdmissionEmailList($recipient_id); // POCOR-9100
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
