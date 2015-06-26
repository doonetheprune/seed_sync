<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 01/06/14
 * Time: 20:51
 */
echo 'Started '.date('Y-m-d H:i:s').PHP_EOL;

while(true){
    $files = scandir(__DIR__.'/pids_to_kill/');

    foreach($files as $file){
        if(substr($file,-4) != '.pid'){
            continue;
        }

        $pid = substr($file,0,-4);
        echo 'Killing pid ' . $pid.PHP_EOL;
        exec("kill -9 " . $pid);

        unlink(__DIR__.'/pids_to_kill/'.$file);
    }
    sleep(5);
}

