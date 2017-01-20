<?php
namespace App\Shell;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Console\Shell;
use Cake\Mailer\Email;

class SendingAlertShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Alert.AlertLogs');
        $this->loadModel('Alert.AlertRules');
    }

    public function main()
    {
        // sending the email to the destination and update the alertLogs status.
        $today = Time::now();
        $todayDate = Date::now();

        $alertLogsList = $this->AlertLogs->find()
            ->where([
                'status' => 0, // pending
                // 'created' . ' >= ' => $todayDate, // Date only
                // 'created' . ' <= ' => $today //date and now() timing
            ])
            ->all();

        foreach ($alertLogsList as $key => $obj) {
            if ($obj->destination != 'No Email' || $obj->destination != 'No Security Role') {
                // sending Email if the destination email is exist
                $email = new Email('openemis');
                $email
                    ->to($obj->destination)
                    ->subject($obj->subject)
                    ->send($obj->message);

                // update the alertLog
                $this->AlertLogs->query()
                    ->update()
                    ->set([
                        'status' => 1,
                        'created' => $today
                    ])
                    ->execute();
            }
        }
    }
}
