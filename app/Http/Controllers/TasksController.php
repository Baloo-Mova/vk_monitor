<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Tasks;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\Proxies;

class TasksController extends Controller
{
    //
    public function index()
    {
        return ['response' => str_random(60)];
    }

    public function addTask(Request $request)
    {
        try {
            $json = $request->getContent();

            $json = json_decode($json, true);

            if (isset($json["tasks"])) {
                for ($i = 0; $i < count($json["tasks"]); $i++) {

                    //$new_task = new Tasks;
                    // if(isset($task["id"]))intval($json["tasks"][$i]['date_post_publication'])
                    $json["tasks"][$i]['date_post_publication'] = Carbon::createFromTimestamp(intval($json["tasks"][$i]['date_post_publication'] / 1000), config('app.timezone'))->toDateTimeString();
                    $json["tasks"][$i]['created_at'] = Carbon::now(config('app.timezone'));
                    $json["tasks"][$i]['updated_at'] = Carbon::now(config('app.timezone'));

                }
                //dd($json["tasks"]);
                Tasks::insert($json["tasks"]);

            }
            return ['response' => 'OK'];
        } catch (\Exception $ex) {
            return ['response' => $ex->getMessage()];
        }
    }

    public function getTask(Request $request)
    {
        // try{


        $limit = 100;

        if (isset($request["offset"])) {
            $offset = $request["offset"];
        }
        if (isset($request["limit"])) {
            $req_limit = intval($request["limit"]);
            if ($req_limit <= 1000 && $req_limit >= 0) {
                if (intval($limit)) $limit = $request["limit"];
            }
        }
        $arr_filter = [];
        if (isset($request->task_id)) {
            $arr_filter['id'] = $request->task_id;
        }
        if (isset($request->user_id)) {
            $arr_filter['user_id'] = $request->user_id;
        }
        if (isset($request->vk_link)) {
            $arr_filter['vk_link'] = $request->vk_link;
        }

        $tasks_list = Tasks::where($arr_filter)->paginate($limit);

        if ($tasks_list->count() == 0) {

            return ['response' => null];
        }
        $arr_result = [];
        foreach ($tasks_list as $task) {
            //dd($task->getOriginal());
            $arr_result[] = $task->getOriginal();
        }
        return ['response' => 'OK', 'tasks' => $arr_result];
        //}catch (\Exception $ex){ return ['response' =>null];}
    }

    public function editTask(Request $request)
    {
        //  try {
        $json = $request->getContent();

        $json = json_decode($json, true);

        if (isset($json["tasks"])) {
            for ($i = 0; $i < count($json["tasks"]); $i++) {


                if (isset($json["tasks"][$i]["id"])) {
                    if (isset($json["tasks"][$i]['date_post_publication'])) {

                        $json["tasks"][$i]['date_post_publication'] = Carbon::createFromTimestamp(intval($json["tasks"][$i]['date_post_publication'] / 1000), config('app.timezone'))->toDateTimeString();
                    }
                    Tasks::where(['id' => $json["tasks"][$i]["id"]])->update($json["tasks"][$i]);
                }

            }
            //dd($json["tasks"]);
            //Tasks::insert($json["tasks"]);

        }
        return ['response' => 'OK'];
        //  } catch (\Exception $ex) {
        //     return ['response' => $ex->getMessage()];
        // }
    }

    public function removeTask(Request $request)
    {
        try {
            $arr_filter = [];
            if (isset($request->task_id)) {
                $arr_filter['id'] = $request->task_id;
            }
            if (isset($request->user_id)) {
                $arr_filter['user_id'] = $request->user_id;
            }
            if (isset($request->vk_link)) {
                $arr_filter['vk_link'] = $request->vk_link;
            }
            if (empty($arr_filter))
                return ['response' => null];
            Tasks::where($arr_filter)->delete();
            return ['response' => "OK"];
        } catch (\Exception $ex) {
            ['response' => null];
        }
    }


}
