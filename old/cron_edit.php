<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 21/06/14
 * Time: 11:43
 */

$additionalArgs = '';

for($a = 5; $a <= 15; $a++){
    if(isset($argv[$a]) == true){
        $additionalArgs .= " ".$argv[$a];
    }
}

$additionalArgs = trim($additionalArgs);

switch($argv[1]){
    case 'add':
    case '+':
        echo date('d/m/Y -').' Adding'.PHP_EOL;
        echo "$argv[2] $argv[3] $argv[4] $argv[5] $argv[6]".PHP_EOL;
        shell_exec("crontab -l | awk '{print} END {print \"$argv[2] $argv[3] $argv[4] $additionalArgs\"}' | crontab");
        break;
    case 'remove':
    case '-';
        echo date('d/m/Y -').' Removing   '."crontab -l | sed '\\!$argv[3] $argv[4]!d' | crontab".PHP_EOL;
        shell_exec("crontab -l | sed '\\!$argv[3] $argv[4] $additionalArgs!d' | crontab");
        break;
    default:
        echo 'Unknown action'.PHP_EOL;
        break;
}