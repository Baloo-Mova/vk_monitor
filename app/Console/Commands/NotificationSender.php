<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PHPMailer;

use App\Models\Notifications;
use App\Models\AccountsData;
use App\Helpers\Emails;
class NotificationSender extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send a notifications message';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public $content;
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        try {
            while (true) {
                $from = AccountsData::where(['valid' => 1, 'type' => 0])->first();
                if (!isset($from)) {
                    sleep(10);
                    continue;
                }
                $this->content['notifications']=null;
                DB::transaction(function () {

                    $collection = Notifications::where(['reserved' => 0, 'email_sended'=>0])->limit(10);   //для тестов

                    $notifications = $collection->get();

                    if ($notifications->count()==0) {
                        return;
                    }
                    //$collection->update(['reserved' => 1]);
                    $this->content['notifications'] = $notifications;
                });
                $notifications = $this->content['notifications'];
                if(!isset($notifications)){
                    sleep(10);
                    continue;
                }
                foreach ($notifications as $notification) {
                    $params = [
                        'from' => $from,
                        'to'=>[$notification->email],
                        'message'=>[
                            'subject'=>'Уведомление от ВК монитора',
                            'body'=>$notification->message
                        ]
                    ];
                    $mailSender = new Emails($params);
                    if($mailSender->sendMessage()){
                        $notification->email_sended=1;
                        $notification->reserved=1;
                        $notification->save();
                    }
                    else{
                        $notification->reserved=0;
                        $notification->save();
                    }

                }
            }
        }
        catch (\Exception $ex){
                dd($ex->getLine() . ": " . $ex->getMessage());
            }
    }

    public function sendMessage($arguments)
    {

        $mail = new PHPMailer;

        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host       = $arguments['from']->smtp_address;                   // Specify main and backup SMTP servers 'smtp.gmail.com';
        $mail->SMTPAuth   = true;                               // Enable SMTP authentication
        $mail->Username   = $arguments['from']->login;                // SMTP username $arguments['from']->login;
        $mail->Password   = $arguments['from']->password;                        // SMTP password $arguments['from']->password;
        $mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port       = $arguments['from']->smtp_port;    // TCP port to connect to 465
        $mail->CharSet    = "UTF-8";

        $mail->setFrom($arguments['from']->login);

        foreach ($arguments['to'] as $email) {
            if (!empty(trim($email))) {
                $mail->addAddress($email);     // Add a recipient
            }
        }
        $mail->isHTML(true);                                  // Set email format to HTML

        $mail->Subject = $arguments["message"]["subject"];
        $mail->Body    = $arguments["message"]["body"];
        if(isset($arguments["message"]["altbody"])){
        $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        }
        else{
            $mail->AltBody    = $arguments["message"]["body"];
        }


        if(!$mail->send()) {
            echo "\n Message could not be sent.";
            echo "\n Mailer Error: " . $mail->ErrorInfo;
            return false;
        } else {
            echo "\n Message has been sent";
            return true;
        }
    }
}
