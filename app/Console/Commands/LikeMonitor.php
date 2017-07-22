<?php

namespace App\Console\Commands;

use App\Helpers\Emails;
use App\Helpers\Liker;
use App\Models\AccountsData;
use App\Models\LikesProcessed;
use App\Models\LikeTask;
use App\Models\Notifications;
use App\Models\Proxies;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LikeMonitor extends Command
{
    public $proxy;
    public $proxy_string;
    public $proxy_arr;
    public $client;
    public $api_id;
    public $api_secret_token;
    public $api_service_token;
    public $content;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'like:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
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
        while (true) {
            try {
                $this->proxy = Proxies::where(['valid' => 1])->inRandomOrder()->first();
                if ( ! isset($this->proxy) || empty($this->proxy->api_id) || empty($this->proxy->api_secret_token) || empty($this->proxy->api_service_token)) {
                    $from = AccountsData::inRandomOrder()->first();
                    if (isset($from)) {
                        $params = [
                            'from'    => $from,
                            'to'      => [config('app.adminEmail')],
                            'message' => [
                                'subject' => 'Уведомление от ВК монитора',
                                'body'    => "Нет доступных прокси или аккаунтов для работы с апи ВК"
                            ]
                        ];
                        $email  = new Emails($params);
                        $email->sendMessage();
                    }

                    return "Error, no proxy";
                }
                $this->proxy_arr    = parse_url($this->proxy->proxy);
                $this->proxy_string = $this->proxy_arr['scheme'] . "://" . (isset($this->proxy->login) ? ($this->proxy->login . ':' . $this->proxy->password . '@') : '') . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];

                $this->api_id            = $this->proxy->api_id;
                $this->api_secret_token  = $this->proxy->api_secret_token;
                $this->api_service_token = $this->proxy->api_service_token;

                $this->setGuzzleClient();

                $this->content['tasks'] = null;

                DB::transaction(function () {
                    $now        = Carbon::now(config('app.timezone'))->addSecond(-5);
                    $collection = LikeTask::where([
                        ['reserved', '=', 0],
                        ['checked', '=', 0],
                        ['date_post_publication', '<=', $now]
                    ])->orWhere([
                        ['reserved', '=', 0],
                        ['checked', '>', 0],
                        ['checked', '<', 7],
                        ['post_checked_time', '<=', $now]
                    ])->first();
                    if ( ! isset($collection)) {
                        return;
                    }

                    $collection->reserved = 1;
                    $collection->save();
                    $this->content['tasks'] = $collection;
                });

                $tasks = $this->content['tasks'];
                if ( ! isset($tasks)) {
                    sleep(1);
                    continue;
                }

                $arr_vk_link = parse_url($tasks->vk_link);

                $arr_vk_link["path"] = str_replace("/", "", $arr_vk_link["path"]);
                $id_group            = preg_replace('/(wall\-)|(\_\d*)/', '', $arr_vk_link["path"]);

                $data = "";
                try {
                    $request = $this->client->request("GET",
                        "https://api.vk.com/method/wall.get?access_token=" . $this->api_service_token . "&owner_id=-" . $id_group . "&count=" . (7) . "&filter=all&extended=1&v=5.65",
                        []);
                    $data    = $request->getBody()->getContents();
                } catch (\Exception $exception) {
                    $from = AccountsData::inRandomOrder()->first();
                    if (isset($from)) {
                        $params = [
                            'from'    => $from,
                            'to'      => [config('app.adminEmail')],
                            'message' => [
                                'subject' => 'Уведомление от ВК монитора',
                                'body'    => $exception->getMessage()
                            ]
                        ];
                        $email  = new Emails($params);
                        $email->sendMessage();
                    }

                    $tasks->reserved = 0;
                    $tasks->save();
                    continue;
                }

                if (stripos($data, "error_code") !== false) {
                    $from = AccountsData::inRandomOrder()->first();
                    if (isset($from)) {
                        $params = [
                            'from'    => $from,
                            'to'      => [config('app.adminEmail')],
                            'message' => [
                                'subject' => 'Уведомление от ВК монитора',
                                'body'    => "Ошибка в вк: " . $data
                            ]
                        ];
                        $email  = new Emails($params);
                        $email->sendMessage();
                    }

                    $this->proxy->valid = 0;
                    $this->proxy->save();

                    $tasks->reserved = 0;
                    $tasks->save();
                    continue;
                }
                $message = "";
                $json    = json_decode($data, true);
                $json    = $json["response"];
                if (isset($json["items"])) {
                    $checked = $this->findWords($json["items"], $tasks->find_query);
                    if ($checked != null) {
                        $dd = LikesProcessed::where(['vk_id' => $checked, 'task_id' => $tasks->id])->first();
                        if ( ! isset($dd)) {
                            Notifications::insert([
                                'task_id'     => $tasks->id,
                                'message'     => "Крутим лайки на пост " . "https://vk.com/public" . $id_group . "?w=wall-" . $id_group . "_" . $checked,
                                'email'       => $tasks->email,
                                'telegram_id' => $tasks->telegram_id,
                                'created_at'  => Carbon::now(config('app.timezone')),
                            ]);
                            LikesProcessed::insert([
                                'vk_id'   => $checked,
                                'task_id' => $tasks->id
                            ]);

                            $from   = AccountsData::where(['valid' => 1])->first();
                            $params = [
                                'from'    => $from,
                                'to'      => ['justvova@mail.ru'],
                                'message' => [
                                    'subject' => 'Крутим лайки',
                                    'body'    => "https://vk.com/wall-" . $id_group . "_" . $checked
                                ]
                            ];
                            $mail   = new Emails($params);
                            $mail->sendMessage();

                            //Liker::setApi($tasks->api_id, $tasks->api_key);
                            //file_put_contents(storage_path('app/likerinfo.txt'),
                            //  Liker::sendVKPub($tasks->id, "https://vk.com/wall-" . $id_group . "_" . $checked), 8);
                        }
                    }
                    LikeTask::where(['id' => $tasks->id])->update([
                        'checked'           => $tasks->checked + 1,
                        'post_checked_time' => Carbon::now(config('app.timezone'))->addMinutes(5)->addSeconds(5),
                        'reserved'          => 0,
                        'updated_at'        => Carbon::now(config('app.timezone'))->addMinutes(5)->addSeconds(5),
                    ]);
                }
            } catch (\Exception $ex) {
                echo $ex->getMessage() . " " . $ex->getLine();
            }
        }
    }

    public function setGuzzleClient()
    {
        $this->client = new Client([
            'headers'         => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate,sdch',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify'          => false,
            'cookies'         => false,
            'allow_redirects' => true,
            'timeout'         => 10,
            'proxy'           => $this->proxy_string,
        ]);
    }

    public function findWords($jsonItems, $find_str)
    {
        foreach ($jsonItems as $item) {
            if ($item["post_type"] == "post") {
                if (isset($item["text"])) {
                    if (strpos($item["text"], $find_str) !== false) {
                        return $item["id"];
                    }
                }
            }
        }

        return null;
    }
}
