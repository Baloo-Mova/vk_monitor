<?php

namespace App\Console\Commands;

use App\Helpers\Emails;
use App\Models\AccountsData;
use App\Models\Notifications;
use App\Models\Proxies;
use App\Models\ViewTask;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ViewMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:monitor';

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

                        $email = new Emails($params);
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
                    $collection = ViewTask::where([
                        ['reserved', '=', 0],
                        ['post_checked_time', '<=', $now],
                        ['created_at', '>', Carbon::now()->subDays(2)]
                    ])->orWhereNull('post_checked_time')->first();
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

                $post_id = trim(str_replace("https://vk.com/wall", "", trim($tasks->vk_link)));

                $data = "";
                try {
                    $request = $this->client->request("GET",
                        "https://api.vk.com/method/wall.getById?access_token=" . $this->api_service_token . "&posts=" . $post_id . "&extended=0&v=5.65",
                        []);

                    $data = $request->getBody()->getContents();
                    var_dump($data);
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
                $json    = json_decode($data, true)["response"];

                if (count($json) == 0) {
                    sleep(1);
                    continue;
                }

                $tasks->views_number = $json[0]['views']['count'];

                if ($tasks->post_checked_time == null) {
                    $tasks->post_checked_time = Carbon::now()->addMinutes(30);
                } else {
                    $time = Carbon::parse($tasks->post_checked_time);
                    $diff = $time->diffInDays(Carbon::now());
                    switch ($diff) {
                        case 0 :
                            $tasks->post_checked_time = Carbon::now()->addMinutes(30);
                            break;

                        case 1:
                            if ($tasks->checked == 0) {
                                Notifications::insert([
                                    'task_id'     => $tasks->id,
                                    'message'     => "Пост " . $tasks->vk_link . " набрал охват в " . $tasks->views_number . " просмотров за первый день",
                                    'email'       => $tasks->email,
                                    'telegram_id' => $tasks->telegram_id,
                                    'created_at'  => Carbon::now(),
                                ]);
                                $tasks->checked = 1;
                            }

                            $tasks->post_checked_time = Carbon::now()->addHour(1);
                            break;

                        default:
                            if ($tasks->checked == 1) {
                                Notifications::insert([
                                    'task_id'     => $tasks->id,
                                    'message'     => "Пост " . $tasks->vk_link . " набрал охват в " . $tasks->views_number . " просмотров за второй день",
                                    'email'       => $tasks->email,
                                    'telegram_id' => $tasks->telegram_id,
                                    'created_at'  => Carbon::now(),
                                ]);
                                $tasks->checked = 2;
                            }
                            break;
                    }
                    $tasks->reserved = 0;
                    $tasks->save();
                    sleep(1);
                }
            } catch (\Exception $ex) {
                $this->content['tasks']->reserved = 0;
                $this->content['tasks']->save();
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
}
