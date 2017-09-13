<?php
/**
 * Run the reverse function.
 *
 * @link http://de2.php.net/manual/en/gearman.examples-reverse.php
 */
$gmclient= new GearmanClient();

# Add default server (localhost).
$gmclient->addServer('192.168.1.201','4730');
$gmclient->setCompleteCallback('abc');
$gmclient->setStatusCallback('abc1');
$gmclient->setWorkloadCallback('WorkloadCallback');
$gmclient->setCreatedCallback('setCreatedCallback');
//$gmclient->addServer();
$function = 'example_Avg';
$data     = array(
        'request' => array(
            'p'  => array(1,2,3,4,5),
            'c'  =>'', 
            'm'=>'',
            ),
        'header'=>array('uid'=>1, 'uname'=>'zyh', 'version'=>1, 'signid'=>2132, 'provider'=>'2c', 'ip'=>1232321)
        );

do {

    $result = $gmclient->doBackground ($function, msgpack_pack($data));
//    $result = $gmclient->do($function, msgpack_pack($data));

    switch($gmclient->returnCode()) {
    case GEARMAN_WORK_DATA:
        echo "Data: $result\n";
        break;
    case GEARMAN_WORK_STATUS:
        list($numerator, $denominator)= $gmclient->doStatus();
        echo "Status: $numerator/$denominator complete\n";
        break;
    case GEARMAN_WORK_FAIL:
        echo "Failed\n";
        exit;
    case GEARMAN_SUCCESS:
    var_dump($gmclient->returnCode(), $result);
    //var_dump(msgpack_unpack($result));
        break;
    default:
        echo "RET: " . $gmclient->returnCode() . "\n";
        exit;
    }
}
while($gmclient->returnCode() != GEARMAN_SUCCESS);
//$job_handle = $result;
//$done = false;
//do
//{
//    sleep(3);
//    $stat = $gmclient->jobStatus($job_handle);
//    if (!$stat[0]) // the job is known so it is not done      
//        $done = true;
//    echo "Running: " . ($stat[1] ? "true" : "false") . ", numerator: " . $stat[2] . ", denomintor: " . $stat[3] . "\n";
//}
//                while(!$done);

function abc1($task){
    echo "eeeee";
    var_Dump($task,1);
}
function abc($task){
    echo "eeeee1";
    var_Dump($task,0);
}
function WorkloadCallback($task){
    echo "eeeee12";
    var_Dump($task,3);
}
function setCreatedCallback($task){
    echo "eeeee13";
    var_Dump($task,4);
}
