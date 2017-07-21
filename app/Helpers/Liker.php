<?php
/**
 * Created by PhpStorm.
 * User: Мова
 * Date: 21.07.2017
 * Time: 18:45
 */

namespace App\Helpers;

class Liker
{

    private static $api_id = '';
    private static $api_key = '';
    private static $active = false;

    //Функция обращения к апи
    /*
     * В случае успеха возвращает JSON
     * {"status":"ok","zakaz_id": (int)id, "message":"REQUEST ACCEPTED"}
     *
     * */
    private static function sendApi($action, $data)
    {
        if(!self::$active) return false;

        $data['action'] = $action;

        array_walk($data, function (&$item1, $key) {
            $item1 = $key."=".$item1;
        });
        ksort($data);

        $data['sig'] = 'sig='.md5(implode('',$data).self::$api_key);
        $data['api_id'] = 'api_id='.self::$api_id;

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => ['Content-Type: application/x-www-form-urlencoded'],
                'content' => implode('&',$data),
                'timeout' => 10
            ]
        ];
        $opts['http']['header'][] = "Content-Length: " . mb_strlen($opts['http']['content']);

        $ctx = stream_context_create($opts);
        $ret = file_get_contents(config('app.liker_api_url'), null, $ctx);
        restore_error_handler();

        if ($ret == FALSE) {
            return "ERROR: [REQUEST ERROR]";
        }
        $o = json_decode($ret, true);
        if (is_array($o) && count($o)) {
            return $o;
        } else {
            return $ret;
        }
    }

    private static function preSendApi($type, $id, $value, $bytime = 0, $callback = ''){
        $send = ['id' => $id, 'value' => $value];
        if($bytime) $send['bytime'] = $bytime;
        if($callback) $send['callback'] = $callback;

        return self::sendApi($type, $send);
    }

    public static function setApi($api_id, $api_key){
        if($api_id && $api_key){
            self::$api_id = $api_id;
            self::$api_key = $api_key;
            self::$active = true;
        }
    }

    public static function sendYouVideo($id, $value, $bytime = 0, $callback = ''){
        return self::preSendApi('yvideo', $id, $value, $bytime, $callback);
    }
    public static function sendInstPub($id, $value, $bytime = 0, $callback = ''){
        return self::preSendApi('instpublication', $id, $value, $bytime, $callback);
    }
    public static function sendVKPub($id, $value, $bytime = 0, $callback=''){
        return self::preSendApi('vkpub', $id, $value, $bytime, $callback);
    }
    public static function sendOKPub($id, $value, $bytime = 0, $callback = ''){
        return self::preSendApi('opub', $id, $value, $bytime, $callback);
    }

    public static function delYouVideo($id){
        return self::sendApi('remove', ['id' => $id, 'type' => 'yvideo']);
    }
    public static function delInstPub($id){
        return self::sendApi('remove', ['id' => $id, 'type' => 'instpublication']);
    }
    public static function delVKPub($id){
        return self::sendApi('remove', ['id' => $id, 'type' => 'vkpub']);
    }
    public static function delOKPub($id){
        return self::sendApi('remove', ['id' => $id, 'type' => 'opub']);
    }
}

//Функции обращения к апи
/*
 * В случае успеха возвращают JSON
 * {"status":"ok","zakaz_id": (int)id, "message":"REQUEST ACCEPTED"}
 * При попытке добавить повторно, до окончании предыдущего задания с таким же id
 * {"status":"error ", "message":"Previous work not ended"}
 * Ошибка параметров запроса
 * {"status":"error ", "message":"REQUEST ERROR"}
 *
 * В callback передается через POST
 * zakaz_id' => Номер заказа
 * host_id => Публикация,
 * val => итоговое кол-во,
 * date => дата завершения (формат: 'Y-m-d H:i:s'),
 * state => "done" - успешно, "removed" - удалено/отменено по не соответствию
 * */

//Отправить видео Youtube (id страницы, кол-во >= 100, bytime in seconds)
//$res = apiBase::sendYouVideo('test', 100);
//print_r($res);

//Отправить публикацию Instagram (id публикации, кол-во >= 100, bytime in seconds)
//$res = apiBase::sendInstPub('sttts', 100);
//print_r($res);

//Отправить публикацию VK (публикация, кол-во >= 100, bytime in seconds)
//$res = apiBase::sendVKPub('tproger?w=wall-30666517_1483520', 100, 86400);
//print_r($res);

//Отправить публикацию OK (публикация, кол-во >= 100, bytime in seconds)
//$res = apiBase::sendOKPub('newssmi/topic/66809125707911', 100);
//print_r($res);

