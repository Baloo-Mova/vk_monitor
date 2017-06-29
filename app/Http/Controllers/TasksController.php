<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Tasks;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use App\Models\Proxies;
use League\Flysystem\Exception;

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
            $json   = $request->getContent();
            $result = [];
            $json   = json_decode($json, true);
            if (count($json) > 0) {
                for ($i = 0; $i < count($json); $i++) {
                    $data = $json[$i];
                    try {
                        $model                         = new Tasks();
                        $data['date_post_publication'] = Carbon::createFromTimestamp($data['date_post_publication'],
                            config('app.timezone'))->toDateTimeString();
                        $data['created_at']            = Carbon::now(config('app.timezone'));
                        $data['updated_at']            = Carbon::now(config('app.timezone'));
                        $model->fill($data);
                        $model->save();
                        $result[] = $model;
                    } catch (\Exception $ex) {
                        $result[] = [
                            'vk_link' => $data['vk_link'],
                            'error'   => $ex->getMessage()
                        ];
                    }
                }
            }

            return ['response' => 'OK', 'result' => $result];
        } catch (\Exception $ex) {
            return ['response' => 'Error', 'message' => $ex->getMessage()];
        }
    }

    public function getTask(Request $request)
    {
        $limit = 100;

        if (isset($request["offset"])) {
            $offset = $request["offset"];
        }
        if (isset($request["limit"])) {
            $req_limit = intval($request["limit"]);
            if ($req_limit <= 1000 && $req_limit >= 0) {
                if (intval($limit)) {
                    $limit = $request["limit"];
                }
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
            $arr_result[] = $task->getOriginal();
        }

        return ['response' => 'OK', 'tasks' => $arr_result];
    }

    public function editTask(Request $request)
    {

        $json = $request->getContent();

        $json         = json_decode($json, true);
        $responseType = "OK";
        $response     = [];
        if (count($json) > 0) {
            for ($i = 0; $i < count($json); $i++) {
                if (isset($json[$i]["id"])) {

                    if (isset($json[$i]['date_post_publication'])) {
                        $json[$i]['date_post_publication'] = Carbon::createFromTimestamp(intval($json[$i]['date_post_publication']),
                            config('app.timezone'))->toDateTimeString();
                    }

                    $task = Tasks::find($json[$i]['id']);
                    if ( ! isset($task)) {
                        $responseType = "Error";
                        $response[]   = [
                            'message' => 'ID_NOT_FOUND',
                            'data'    => $json[$i]['id']
                        ];
                        continue;
                    }

                    $task->fill($json[$i]);
                    $task->save();

                    $response[] = [
                        'message' => "EDITED",
                        'data'    => $task
                    ];
                } else {
                    $responseType = "Error";
                    $response[]   = [
                        'message' => 'ID_NOT_SET',
                        'data'    => $json[$i]
                    ];
                }
            }
        }

        return ['response' => $responseType, 'result' => $response];
    }

    public function removeTask(Request $request)
    {
        $json    = $request->getContent();
        $json    = json_decode($json, true);
        $result  = [];
        $execute = false;

        try {
            $tasks = Tasks::select();
            if (isset($json['ids'])) {
                $tasks->whereIn('id', $json['ids']);
                $execute = true;
            }

            if (isset($json['userIds'])) {
                $tasks->whereIn('user_id', $json['userIds']);
                $execute = true;
            }

            if (isset($json['links'])) {
                $tasks->whereIn('vk_link', $json['links']);
                $execute = true;
            }

            if ($execute) {
                $tasks = $tasks->get();
                foreach ($tasks as $item) {
                    $item->delete();
                    $result[] = $item->id;
                }
            } else {
                return ['response' => 'ERROR', 'result' => ['message' => 'NO_FILTER_FOUND']];
            }

            return ['response' => "DELETED", 'result' => $result];
        } catch (\Exception $ex) {
            return ['response' => 'ERROR', 'result' => ['message' => $ex->getMessage()]];
        }
    }

}
