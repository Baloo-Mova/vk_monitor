<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\Proxies;
use App\Models\Tasks;
use App\Models\Notifications;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VKMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vkmonitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'monitoring vk posts';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public $proxy;
    public $proxy_string;
    public $proxy_arr;
    public $client;
    public $api_id;
    public $api_secret_token;
    public $api_service_token;
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
    public function setGuzzleClient()
    {
        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.99 Safari/537.36 OPR/41.0.2353.69',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate,sdch',
                'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            ],
            'verify' => false,
            'cookies' => false,
            'allow_redirects' => true,
            'timeout' => 10,
            'proxy' => $this->proxy_string,
        ]);
    }

    public function handle()
    {
        try {
            //
            $this->proxy = Proxies::orderBy(DB::raw('RAND()'))->where(['valid' => 1])->first();//inRandomOrder()->first();
            if (!isset($this->proxy) || empty($this->proxy->api_id) || empty($this->proxy->api_secret_token) || empty($this->proxy->api_service_token)) {
                return "Error, no proxy";
            }
            $this->proxy_arr = parse_url($this->proxy->proxy);
            $this->proxy_string = $this->proxy_arr['scheme']
                . "://" . $this->proxy->login
                . ':' . $this->proxy->password
                . '@' . $this->proxy_arr['host']
                . ':' . $this->proxy_arr['port'];


            $this->api_id = $this->proxy->api_id;
            $this->api_secret_token = $this->proxy->api_secret_token;
            $this->api_service_token = $this->proxy->api_service_token;

            $this->setGuzzleClient();
//dd(Carbon::parse('2017-06-26 20:11:51'));
            while (true) {
                $this->content['tasks'] = null;
                DB::transaction(function () {
                    $now = Carbon::now(config('app.timezone'))->addSecond(-5);
                    var_dump($now);
                    $collection = Tasks::where([
                        ['reserved', '=', 0],
                        ['checked', '=', 0],
                        ['date_post_publication', '<=', $now]
                    ])
                        ->orWhere([
                            ['reserved', '=', 0],
                            ['checked', '>', 0],
                            ['checked', '<', 7],
                            ['post_checked_time', '<=', $now]
                        ])
                        ->first();
                    if (!isset($collection)) {
                        return;
                    }

                    $collection->reserved = 1;
                    $collection->save();
                    $this->content['tasks'] = $collection;
                });

                $tasks = $this->content['tasks'];
                if (!isset($tasks)) {
                    sleep(1);
                    continue;
                }


                    $arr_vk_link = parse_url($tasks->vk_link);

                    $arr_vk_link["path"] = str_replace("/", "", $arr_vk_link["path"]);
                    $id_group = preg_replace('/(wall\-)|(\_\d*)/', '', $arr_vk_link["path"]);
                    //dd($id_group);

                    $request = $this->client->request("GET", "https://api.vk.com/method/wall.get?access_token=" . $this->api_service_token . "&owner_id=-" . $id_group . "&count=" . (7) . "&filter=all&extended=1&v=5.65", []);
                    $data = $request->getBody()->getContents();
                    //$json = $request->getContent();
                    $json = json_decode($data, true);

                    $json = $json["response"];
                    if (isset($json["items"])) {
                        $checked = $this->findWords($json["items"], $tasks->find_query);
                        if ($checked == null) {

                            $message = "В паблике https://vk.com/public" . $id_group . " не обнаружен пост с ключевыми словами: " . $tasks->find_query . ". Плановое время выхода поста - " . $tasks->date_post_publication;
                            if ($tasks->checked == 0) {
                                Tasks::where(['id' => $tasks->id])->update([
                                    'checked' => $tasks->checked + 1,
                                    'post_checked_time' => Carbon::now(config('app.timezone'))->addMinutes(1)->addSeconds(5),
                                    'updated_at' => Carbon::now(config('app.timezone'))->addMinutes(1)->addSeconds(5),
                                    'reserved' => 0,
                                ]);

                                continue;
                            } else {

                                if ($tasks->checked != 7) {

                                    Tasks::where(['id' => $tasks->id])->update([
                                        'checked' => $tasks->checked + 1,
                                        'post_checked_time' => Carbon::now(config('app.timezone'))->addMinutes(5)->addSeconds(5),
                                        'reserved' => 0,
                                        'updated_at' => Carbon::now(config('app.timezone'))->addMinutes(5)->addSeconds(5),
                                    ]);

                                } else {
                                    $tasks->delete();

                                }

                            }

                        } else {
                            $message = "Пост " . "https://vk.com/public" . $id_group . "?w=wall-" . $id_group . "_" . $checked . " в паблике https://vk.com/club" . $id_group . " опубликован в".Carbon::now(config('app.timezone'));
                            $tasks->delete();
                        }
                        if ($tasks->notification_mode == 2) {

                            Notifications::insert([
                                'task_id' => $tasks->id,
                                'message' => $message,
                                'email' => $tasks->email,
                                'telegram_id' => $tasks->telegram_id,
                                'created_at' => Carbon::now(config('app.timezone')),
                            ]);
                        }

                        if ($tasks->notification_mode == 0 && $checked != null) {
                            // Tasks::
                            Notifications::insert([
                                'task_id' => $tasks->id,
                                'message' => $message,
                                'email' => $tasks->email,
                                'telegram_id' => $tasks->telegram_id,
                                'created_at' => Carbon::now(config('app.timezone')),
                            ]);
                        }

                        if ($tasks->notification_mode == 1 && $checked == null) {
                            Notifications::insert([
                                'task_id' => $tasks->id,
                                'message' => $message,
                                'email' => $tasks->email,
                                'telegram_id' => $tasks->telegram_id,
                                'created_at' => Carbon::now(config('app.timezone')),
                            ]);

                        }


                    }

            }
        } catch (\Exception $ex) {
            Tasks::whereIn('id', array_column($tasks->toArray(), 'id'))->update([
                'reserved' => 0
            ]);
            echo("\n" . $ex->getLine() . "    " . $ex->getMessage());
        }
    }

    public function findWords($jsonItems, $find_str)
    {
        foreach ($jsonItems as $item) {
            if ($item["post_type"] == "post") {
                if (isset($item["text"])) {
                    //  echo "\n".$item["id"];
                    if (strpos($item["text"], $find_str) !== false) {
                        //echo "  .true";
                        return $item["id"];
                    }
                    // else  echo "  .false";

                }
            }

        }
        return null;
    }
}

