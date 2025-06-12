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

        // 🧼 Safer argument parsing with fallback defaults
        $school_name   = $this->args[0] ?? '';
        $student_name  = $this->args[1] ?? '';
        $academic_year = $this->args[2] ?? '';
        $grade_name    = $this->args[3] ?? '';
        $recipient_id  = $this->args[4] ?? '';

        // 🧠 Decode recipient IDs — fallback to empty array if decoding fails
        $recipient_ids = json_decode($recipient_id, true) ?? [];

        // 🕒 Mark alerts in progress
        $this->Alerts->updateAll(
            ['process_id' => getmypid(), 'modified' => FrozenTime::now()],
            ['process_name' => $processName]
        );

        $rules = $this->getAlertRules($feature);

        foreach ($rules as $rule) {
            $contactList = $this->getStudentAdmissionContactList($recipient_ids);
            $methods = array_map('trim', explode(',', $rule->method));

            // ✏️ Placeholder replacement
            $placeholders = ['${school_name}', '${student_name}', '${academic_year}', '${grade_name}'];
            $values = [$school_name, $student_name, $academic_year, $grade_name];

            $subject = str_replace($placeholders, $values, $rule->subject);
            $message = str_replace($placeholders, $values, $rule->message);

            // 📨 Dispatch alerts
            foreach ($methods as $method) {
                if ($method === 'Email' && !empty($contactList['email'])) {
                    $emailList = implode(', ', $contactList['email']);
                    $this->AlertLogs->insertStudentAdmissionAlertLog($method, $rule->feature, $emailList, $subject, $message);
                }

                if ($method === 'SMS' && !empty($contactList['phone'])) {
                    $phoneList = implode(', ', $contactList['phone']);
                    $this->AlertLogs->insertStudentAdmissionAlertLog($method, $rule->feature, $phoneList, $subject, $message);
                }
            }
        }
    }

    public function getStudentAdmissionContactList($recipient_ids)
    {
        $contactList = [
            'email' => [],
            'phone' => []
        ];
        if (empty($recipient_ids)) {
            return $contactList;
        }
        $options = ['recipients' => $recipient_ids];

            // all staff within securityRole and institution
            $recipientList = $this->Users
                ->find('recipientList', $options)
                ->toArray()
            ;
        if (empty($recipientList)) {
            return $contactList;
        }
            // combine all email to the email list
            if (!empty($recipientList)) {
                foreach ($recipientList as $recipient) {
                    if (!empty($recipient->email)) {
                        $recipient_mail = $recipient->name . ' <' . $recipient->email . '>';
                        if (!in_array($recipient_mail, $contactList['email'])) {
                            $contactList['email'][] = $recipient_mail;
                        }
                    }


                    if (!empty($recipient->mobile_number)) {
                        if (!in_array($recipient->mobile_number, $contactList['phone'])) {
                            $contactList['phone'][] = $recipient->mobile_number;
                        }
                    }
                }
            }
        return $contactList;
    }

}
