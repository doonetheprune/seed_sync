<?php
/**
 * Created by PhpStorm.
 * User: ShaunBetts
 * Date: 05/04/2015
 * Time: 17:19
 */

header('Content-Type: text/json');

require_once '/seedsync/vendor/autoload.php';

$db = \SeedSync\DbConn::getInstance()->get();
$hosts = \SeedSync\Host::getAllHosts($db);

switch($_GET['action']){
    case 'getHosts':
        $response = array();

        foreach($hosts as $host){
            $response[] = array('id' => $host->getHostId(),'host' => $host->getHost());
        }

        break;
    case 'getDownloads':
        $response = array();

        $criteria = array();

        if($_POST['host'] != 'all'){
            $criteria['HostID'] = array(
                'operator' => '=',
                'value' => $_POST['host']
            );
        }

        if($_POST['timePeriod'] != 'all'){
            $timePeriod = date('Y-m-d 00:00:00',strtotime('-1'.$_POST['timePeriod']));

            $criteria['LastModified'] = array(
                'operator' => '>=',
                'value' => "'$timePeriod'"
            );
        }

        $downloads = \SeedSync\Download::getAll($db,$criteria);

        foreach($downloads as $download){
            $file = new \SeedSync\File($hosts[$download->getHostId()],$db);

            if($download->getStatus() == 'DOWNLOADING'){
                $percentageComplete = $file->getPercentageComplete($download->getFileName(),$download->getFileSize());
            }else{
                $percentageComplete = '-';
            }

            if($download->getStatus() == 'COMPLETE'){
                $dateCompleted = date('H:i:s d/m/Y',$download->getDateComplete());
            }else{
                $dateCompleted = '-';
            }

            $response[] = array(
                'id' => $download->getId(),
                'priority' => $download->getPriority(),
                'file' => $download->getFileName(),
                'size' => $file->humanFileSize($download->getFileSize()),
                'percentageComplete' => $percentageComplete,
                'dateAdded' => date('H:i:s d/m/Y',$download->getDateAdded()),
                'dateComplete' => $dateCompleted,
                'lastModified' => date('H:i:s d/m/Y',$download->getLastModified()),
                'status' => $download->getStatus()
            );
        }
        break;
    case 'getConfig':
        $config = new \SeedSync\Config();
        $response = array(
            'mode' => $config->getMode()
        );
        break;
    case 'setConfig':
        $config = new \SeedSync\Config();
        $propertyName = $_POST['propertyName'];
        $propertyValue = $_POST['propertyValue'];

        $methodName = 'set'.ucfirst($propertyName);

        if(method_exists($config,$methodName)){
            $config->$methodName($propertyValue);
        }
        break;
    default:
        $response = $host;
        break;
}

echo json_encode($response);