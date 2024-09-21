<?php
/**
 * twitter space
 */

require_once __DIR__ .'/index.php';

use Workerman\Worker;
use Workerman\Timer;
use Workerman\Connection\AsyncTcpConnection;

$task_worker = new Worker();

$task_worker->onWorkerStart = function ($task_worker) {
    $context_option = array(
        'ssl' => array(
            'allow_self_signed' => true,
            'verify_peer' => false
        )
    );

    //space info from webpage of twitter space
    $ws = 'ws://xxxxxxxxxx.pscp.tv:443/chatapi/v1/chatnow';
    $access_token = 'xxxxxxxxxxxxxxxxxx';
    $space_id = 'xxxxxxxxxx';

    $auth_msg = json_encode([
        'payload' => json_encode([
            'access_token' => $access_token,
        ]),
        'kind' => 3,
    ]);

    $room_info = json_encode([
        'payload' => json_encode([
            'body' => json_encode([
                'room' => $space_id,
            ]),
            'kind' => 1
        ]),
        'kind' => 2,
    ]);

    $con = new AsyncTcpConnection($ws, $context_option);
    $con->transport = 'ssl';

    $con->onConnect = function($con) use ($task_worker, $auth_msg, $room_info) {

        $con->send($auth_msg);
        $task_worker->timerid = Timer::add(1, function () use ($con, $room_info) {
            $con->send($room_info);
        }, [], false);

    };

    $con->onMessage = function ($con, $message) use ($task_worker) {
        echo $message . PHP_EOL;
    };

    $con->onError = function ($con, $code, $msg) {
        echo $code ." - ". $msg . PHP_EOL;
    };

    $con->onClose = function ($con) use ($task_worker) {
        echo 'Trigger on close' . PHP_EOL;
        $con->reconnect(5);
    };
    $con->connect();

};

Worker::runAll();
