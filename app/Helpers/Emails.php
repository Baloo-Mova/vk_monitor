<?php

namespace App\Helpers;
use PHPMailer;

class Emails
{
    public $arguments;
    public $mail;

    public function __construct($arguments)
    {
        $this->mail = new PHPMailer;
        $this->arguments = $arguments;
    }

    public function sendMessage()
    {
        $this->mail->isSMTP();                                      // Set mailer to use SMTP
        $this->mail->Host       = $this->arguments['from']->smtp_address;                   // Specify main and backup SMTP servers 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;                               // Enable SMTP authentication
        $this->mail->Username   = $this->arguments['from']->login;                // SMTP username $arguments['from']->login;
        $this->mail->Password   = $this->arguments['from']->password;                        // SMTP password $arguments['from']->password;
        $this->mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
        $this->mail->Port       = $this->arguments['from']->smtp_port;    // TCP port to connect to 465
        $this->mail->CharSet    = "UTF-8";

        $this->mail->setFrom($this->arguments['from']->login);

        foreach ($this->arguments['to'] as $email) {
            if (!empty(trim($email))) {
                $this->mail->addAddress($email);     // Add a recipient
            }
        }
        $this->mail->isHTML(true);                                  // Set email format to HTML

        $this->mail->Subject = $this->arguments["message"]["subject"];
        $this->mail->Body    = $this->arguments["message"]["body"];
        if(isset($this->arguments["message"]["altbody"])){
            $this->mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
        }
        else{
            $this->mail->AltBody    = $this->arguments["message"]["body"];
        }


        if(!$this->mail->send()) {
            echo "\n Message could not be sent.";
            echo "\n Mailer Error: " . $this->mail->ErrorInfo;
            return false;
        } else {
            echo "\n Message has been sent";
            return true;
        }
    }
}