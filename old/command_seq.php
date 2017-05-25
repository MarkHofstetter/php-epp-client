<?php 
require('Net/EPP/AT/Client.php');
require('xmlpp.php');
require('epp_extract_data_simple.php');

if ($argv[1]) {
  $config = $argv[1];}
else {
  $config = 'epp-config.xml';
}



$c = simplexml_load_file($config);

printf("Path %s\n", $c->settings->path);


$host = $c->server->host;
$port = (float)  $c->server->port;
$timeout = (int) $c->server->timeout;
$ssl = (boolean) $c->server->ssl;

global $epp, $frame, $debug;

$debug = 2;
if ($debug == 1) {print_r($c);}

$epp = new Net_EPP_AT_Client();

$greeting = $epp->connect($host, $port, $timeout, $ssl);

$domain = 'fsafasfasfdafd.at';

// login you will need this everytime 
echo "# 1. login + change pwd\n";

$param[] = array('login' => $c->user->login, 
                 'pwd'   => $c->user->pw, 
                 'trid'  => $c->user->trid,
                );

$epp_return = $epp->command('login', $param);

op_epp($epp_return);
unset($param);

echo "# 16. check domain 2\n";
$param[] = array('domain' => $domain,
                 'trid'  => $c->user->trid);

$epp_return = $epp->command('domain-check', $param);
$data = is_available($epp_return->xml); 

unset($param);
op_epp($epp_return);




$epp->disconnect();

function is_available($frame) {
  $xml = new SimpleXMLElement($frame);
  $data = array();
  echo "check XML\n";
  print_r( $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData 
   ->children('urn:ietf:params:xml:ns:domain-1.0')->infData->cd  );
}


function op_epp ($epp_return) {
  echo $epp_return->code ."\n";
  echo $epp_return->msg ."\n";
#  echo xmlpp($epp_return->xml) ."\n";
  if ($epp_return->error) {
    echo "Errors: \n"; 
    print_r($epp_return->error);
  }
}



?>

