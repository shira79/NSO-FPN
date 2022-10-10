<?php

require('nso/client.php');
require('friend.php');
require('notification/line.php');

// ini_set( "error_log", "storage/error/php.log" );

// .env登録
$envs = parse_ini_file('.env');
foreach($envs as $name => $value){
    putenv("$name=$value");
}

$NSO = new NSO();
$friendsData = $NSO->getFriendList();

$line = new Line();

foreach($friendsData as $friendData) {
    $friend = new Friend($friendData);
    // var_dump($friend->generateMessage());

    if($friend->isFavoriteFriend() && $friend->isSwitched()){
        $friend->notify($line, ['message' => $friend->generateMessage()]);
    }
}
