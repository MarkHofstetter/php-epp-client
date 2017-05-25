<?php

require('Net/EPP/AT/Client.php');

// getopt is a nightmare under php and windows so here comes this -> will be better with php 5.3

$config  = $argv[1];
$command = $argv[2];
$default = $argv[3];

$c = simplexml_load_file($config);

$host = $c->server->host;
$port = (float)  $c->server->port;
$timeout = (int) $c->server->timeout;
$ssl = (boolean) $c->server->ssl;

global $epp, $frame, $debug;

//$debug = (int) $c->DEBUG;
if ($debug == 1) {print_r($c);}

$epp = new Net_EPP_AT_Client();

//try {
  $greeting = $epp->connect($host, $port, $timeout, $ssl);
/* } catch (Exeption $e) {
  printf("A bubu happened [%s]\n", $e->getMessage());
  }
*/

$param[] = array('login' => $c->user->login, 
                 'pwd'   => $c->user->pw, 
                 'trid'  => $c->user->trid);

$epp->command('login', $param);
unset($param);

$frame = new Epp_Frame(); 
//$epp->TemplateDir('templates');
$params = $frame->get_params($command, $epp->TemplateDir);
## print_r($params);
 
foreach ($params as $p => $b) {
   printf("%s : ", $p); 
   $input = trim(fgets(STDIN));
   $values[$p] = $input;
   }

$param[] = $values;   
print_r($param);
$epp_return = $epp->command($command, $param);
echo $epp_return->xml ."\n";
echo $epp_return->code ."\n";
echo $epp_return->msg ."\n";
echo $epp_return->handle ."\n";

$epp->disconnect();

?>
