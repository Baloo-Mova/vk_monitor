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
    public $api_id = "4170615";
    public $api_secret_token = "iRUyrnNQrPMbOuhwIpHb";
    public $api_service_token = "ef5a2fc0ef5a2fc0ef5a2fc088ef658cb7eef5aef5a2fc0b60870e97b6b95d35684d640";
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
    public function setGuzzleClient(){
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
            'proxy' => $this->proxy_string,
        ]);
    }
    public function handle()
    {
        try {
            //
            $this->proxy = Proxies::where(['valid' => 1])->first();//inRandomOrder()->first();
            $this->proxy_arr = parse_url($this->proxy->proxy);
            $this->proxy_string = $this->proxy_arr['scheme'] . "://" . $this->proxy->login . ':' . $this->proxy->password . '@' . $this->proxy_arr['host'] . ':' . $this->proxy_arr['port'];
            $this->setGuzzleClient();
//dd(Carbon::parse('2017-06-26 20:11:51'));
            while (true) {

                $this->content['tasks'] = null;
                DB::transaction(function () {
                    $now = Carbon::now(config('app.timezone'))->addSeconds(-5);
                    print_r($now);
                     $tasks = Tasks::where(['date_post_publication' => $now, 'reserved'=>0])
                                    ->orWhere(['updated_at' => $now, 'reserved'=>0])->limit(1)->get();
                    //$tasks = Tasks::where(['reserved' => 0])->limit(1)->get();                          //для тестов
                    //dd($tasks->count());
                    if ($tasks->count()==0) {
                        return;
                    }

                    $this->content['tasks'] = $tasks;
                });
                $tasks = $this->content['tasks'];
                //dd($tasks->count());
                sleep(3);

                // print_r($tasks);
                if (isset($tasks)) {

                    foreach ($tasks as $task) {
                        $arr_vk_link = parse_url($task->vk_link);
                        $arr_vk_link["path"] = str_replace("/", "", $arr_vk_link["path"]);
                        $request = $this->client->request("GET", "https://api.vk.com/method/groups.getById?access_token=" . $this->api_service_token . "&group_ids=" . $arr_vk_link["path"] . "&v=5.65", []);
                        $data = $request->getBody()->getContents();
                        //$json = $request->getContent();

                        $json = json_decode($data, true);
                        $json = $json["response"][0];
                        $id_group = $json["id"];

                        $request = $this->client->request("GET", "https://api.vk.com/method/wall.get?access_token=" . $this->api_service_token . "&owner_id=-" . $id_group . "&count=" . (7) . "&filter=owner&extended=1&v=5.65", []);
                        $data = $request->getBody()->getContents();
                        //$json = $request->getContent();

                        $json = json_decode($data, true);

                        $json = $json["response"];
                        if (isset($json["items"])) {
                            $checked = $this->findWords($json["items"], $task->find_query);
                            if ($checked == null) {

                                $message = "Пост в группе " . $task->vk_link . " не опубликован";
                                if ($task->checked == 0) {
                                    $task->update([
                                        'checked' => $task->checked + 1,
                                        'updated_at' => Carbon::now(config('app.timezone'))->addMinutes(1)->addSeconds(5),
                                    ]);
                                continue;
                                } else {

                                    if ($task->checked != 7) {

                                        $task->update([
                                            'checked' => $task->checked + 1,
                                            'updated_at' => Carbon::now(config('app.timezone'))->addMinutes(5)->addSeconds(5),
                                        ]);
                                    }else{
                                        $task->delete();

                                    }

                                }

                            } else {
                                $message = "Пост " . $task->vk_link . "?w=wall-" . $id_group . "_" . $checked . " в группе " . $task->vk_link . " опубликован";

                            }
                            if ($task->notification_mode == 2) {

                                Notifications::insert([
                                    'task_id' => $task->id,
                                    'message' => $message,
                                    'created_at' => Carbon::now(config('app.timezone')),
                                ]);
                            }

                            if ($task->notification_mode == 0 && $checked != null) {
                                // Tasks::
                                Notifications::insert([
                                    'task_id' => $task->id,
                                    'message' => $message,
                                    'created_at' => Carbon::now(config('app.timezone')),
                                ]);
                            }

                            if ($task->notification_mode == 1 && $checked == null) {
                                Notifications::insert([
                                    'task_id' => $task->id,
                                    'message' => $message,
                                    'created_at' => Carbon::now(config('app.timezone')),
                                ]);

                            }


                        }
                        //dd("stop");
                    }
                }


            }
        }catch(\Exception $ex){
            dd($ex->getLine()."    ".$ex->getMessage());
        }
    }
    public function findWords($jsonItems,$find_str)
    {
        foreach ($jsonItems as $item){
            if($item["post_type"]=="post"){
                if(isset($item["text"])){
                  //  echo "\n".$item["id"];

                    if (strpos($item["text"],$find_str)!==false){
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
