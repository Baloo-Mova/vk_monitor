<?php

namespace App\Helpers;
use GuzzleHttp\Client;

use App\Models\TelegramAccounts;

class Telegram
{
    public $arguments;
    public $client;
    public $chat_id;
    public $token;

    public function __construct()
    {
        //$this->arguments = $arguments;
        $this->client       = new Client([
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, lzma, sdch, br',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify'          => false,
            'cookies'         => true,
            'allow_redirects' => true,
            'timeout'         => 15
        ]);

        $this->token = config("telegram.token");
    }

    public function sendMessage($user_id, $text)
    {
        if(!isset($user_id) || !isset($text)){
            return "Error";
        }

        $chat_id = TelegramAccounts::where(["user_id" => $user_id])->first();

        if(isset($chat_id)){
            try{
                $request = $this->client->request("GET",
                    "https://api.telegram.org/bot".$this->token."/sendMessage?chat_id=".$chat_id->chat_id."&text=".$text);
                if($request){
                    return "Message sended";
                }
            }catch (\Exception $ex){
                return $ex;
            }

        }else{
            return false;
        }

    }

    public function getUpdates()
    {
        try{
            $request = $this->client->request("GET","https://api.telegram.org/bot".$this->token."/getUpdates");
            $data = json_decode($request->getBody()->getContents());

            if(!empty($data) && $data->ok){
                $results = $data->result;
            }else{
                $results = [];
            }

            if(count($results) > 0){
                $users = [];
                foreach ($results as $item){
                    $user = TelegramAccounts::where(['user_id' => $item->message->from->id])->first();
                    if(empty($user)){
                        $users[] = [
                            "user_id" => $item->message->from->id,
                            "chat_id" => $item->message->chat->id
                        ];
                    }
                }

                if(count($users) > 0){
                    TelegramAccounts::insert($users);
                }

            }
        }catch (\Exception $ex){
            return $ex;
        }









    }
}