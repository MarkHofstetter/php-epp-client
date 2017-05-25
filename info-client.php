<?php

require('Net/EPP/AT/Client.php');



if ($argv[1]) {
  $config = $argv[1];}
else {
  $config = 'epp-conf.xml';
}



$c = simplexml_load_file($config);

printf("Path %s\n", $c->settings->path);


$host = $c->server->host;
$port = (float)  $c->server->port;
$timeout = (int) $c->server->timeout;
$ssl = (boolean) $c->server->ssl;

global $epp, $frame, $debug;

$debug = 0;
if ($debug == 1) {print_r($c);}

$epp = new Net_EPP_AT_Client();

$greeting = $epp->connect($host, $port, $timeout, $ssl);

print_r($greeting);
// login you will need this everytime 
$param[] = array('login' => $c->user->login, 
                 'pw'   => $c->user->pw, 
                 'trid'  => $c->user->trid);

$epp_return = $epp->command('login', $param);
unset($param);

echo $epp_return->request ."\n";
echo $epp_return->code ."\n";
echo $epp_return->msg ."\n";
echo $epp_return->xml ."\n";


// example for domain-info
$domain ='orf.at';

$param[] = array('domain' => $domain,                  
                 'trid'  => $c->user->trid);

$epp_return = $epp->command('domain-info', $param);
unset($param);


echo $epp_return->request ."\n";
echo $epp_return->code ."\n";
echo $epp_return->msg ."\n";
echo $epp_return->xml ."\n";



if ($epp_return->error) {
  echo "Errors: \n"; 
  print_r($epp_return->error);
}




$epp->disconnect();

 

?>
