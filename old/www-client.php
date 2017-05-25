<?php 

session_start();

require ('xajax_core/xajax.inc.php');
require ('Net/EPP/AT/Client.php');
# require ('get_commands.php');

//FTT2699354

$xajax = new xajax();
# $xajax->setFlag('debug',true);
$xajax->configure('javascript URI', '.');

function get_commands_from_dir($dir) {

  if ($handle = opendir($dir)) {
    //return $dir;
    while (false !== ($file = readdir($handle))) {
        if (substr($file, 0, 1) == "." ) { continue; }  // if file starts with a . skip it
        // return $file;
        $files[] = $file;
    }  
  closedir($handle);
  sort($files);
  return $files;
  }
  return "Error no commands found at $dir";
}

function get_config($dir, $cf) {
  $configs = get_commands_from_dir($dir); // misuse and rape me, wrong name for function 
  $config  = sprintf('%s/%s', $dir, $configs[$cf]);
  $c = simplexml_load_file($config);
  return $c; 
}

function get_config_list($dir) {
  $configs = get_commands_from_dir($dir); // misuse and rape me, wrong name for function 
  return $configs; 
}


function format_command($command, $dir) {   
   return sprintf('<a href=\'\' onclick=\'js_get_params("%s"); return false;\'>%s</a><br>', $command, $command);
   }


function get_params() {
  $objResponse = new xajaxResponse();
  $objResponse->assign('command_params', 'innerHTML', 'test');	
  return $objResponse;
}


function load_defaults($params, $command, $si) {
  $si = session_id();
  if (!file_exists('data/'.$si.$command)) { $c['trid'] = time(); return $c; }
  $c = unserialize(join('', file('data/'.$si.$command)));
  $c['trid'] = time();
  return $c;
}

  
function store_defaults($params, $command, $si) {
  $si = session_id();
  $fp = fopen('data/'.$si.$command,'w'); 
  fputs($fp,serialize($params));
  fclose($fp);
}

function get_reg_config($config_no) {
  $objResponse = new xajaxResponse();  
  
  $configs = get_config_list('config');
  $config_list .= '<form name="" onSubmit="return false">';
 /* $config_list = 
  'Select Registrar <SELECT NAME="select_registrar" ID="select_registrar" 
  onChange="xajax_get_reg_config(xajax.getFormValues(\'select_registrar\')); return false;"><br>'; */
  $i = 0;
  foreach ($configs as $config) {
    $config_list .= sprintf("<OPTION VALUE='%s'>%s</OPTION>", $i++, $config);
  }
  $config_list .= '</SELECT><br>';
  
  $c = get_config('config', $config_no);
  
  $objResponse->assign('registrar_info', 'innerHTML',  $config_list . $info . $config_no);    
  ##$info .= sprintf("Host [%s], Port [%s], User [%s]",
  ##   $c->server->host,
  ##   $c->server->port,
  ##   $c->user->login);
  ##
  return $objResponse;  
}
  
function get_commands() {
  //try {
   ## get_reg_config();
   $objResponse = new xajaxResponse();   
    
    $configs = get_config_list('config');
    //$config_list .= '<form name="" onSubmit="return false">';
    $config_list = 'Select Registrar <SELECT NAME="select_registrar" ID="select_registrar"';
    #onChange="xajax_get_reg_config(\'1\'); return false;"><br>';    
    $i = 0;
    foreach ($configs as $config) {
      $config_list .= sprintf("<OPTION VALUE='%s'>%s</OPTION>", $i++, $config);
    }
    $config_list .= '<input type="button" name="set_registrar" value="set_registrar"';
    $config_list .= 'onClick="xajax_get_commands(xajax.getFormValues(\'select_registrar\'));">';
    $config_list .= '</SELECT></form><br>';
    
    $c = get_config('config', 0);
    
    $info .= sprintf("Host [%s], Port [%s], User [%s]",
       $c->server->host,
       $c->server->port,
       $c->user->login);
    $objResponse->assign('registrar_info', 'innerHTML',  $config_list . $info);  
  
    $commands = get_commands_from_dir('./templates');
    if (count($commands) < 1) {throw new Exception("no templates found in $dir");}
    foreach ($commands as $command) {
      $command_list .= format_command($command, './templates');
    }
    $objResponse->assign('commands_list', 'innerHTML', $command_list);	
    return $objResponse;
  /*} catch (Exception $e) {
    $objResponse->assign('commands_list', 'innerHTML', $e->getMessage());
    return $objResponse;
  }*/
}


function format_params($params, $p_command) {
  $ret .= sprintf("<h3>%s</h3>\n", $p_command);
  $ret .= '<form id="params" onSubmit="return false">';
  $si = 0;
  $defaults = load_defaults($params, $p_command, $si);
  foreach ($params as $p => $b) {
    $ret .= sprintf('%s <input type="text" name="%s" id="%s" value="%s"><br>', $p, $p, $p, $defaults[$p]);
  }
  $ret .= sprintf('<input type="hidden" name="p_command" id="p_command" value="%s" ><br>', $p_command);
  $ret .= '<input type="button" name="submit" value="submit" onClick="xajax_send_frame(xajax.getFormValues(\'params\'));">';
  $ret .= '</form>';
  return $ret;
}

function print_params($p_command) {
  $objResponse = new xajaxResponse();
  $frame = new Epp_Frame(); 
  $params = $frame->get_params($p_command, './templates');
  $fp = format_params($params, $p_command);
  $objResponse->assign('command_params', 'innerHTML', $fp);
  return $objResponse;  
}

function send_frame($params) {
  $si = 0;
  store_defaults($params, $params['p_command'], $si);
  $objResponse = new xajaxResponse();
  $epp_return = epp_command($params);
  $pr .= sprintf("command: [%s]<br>\n", $params['p_command']);
  $pr .= sprintf("code: [%s]<br>\n", $epp_return->code);
  $pr .= sprintf("msg:  [%s]<br>\n", $epp_return->msg);
  $pr .= sprintf("handle:  [%s]<br>\n", $epp_return->handle);
  $pr .= sprintf("Error(s): <pre> [%s] </pre> <br>\n", print_r($epp_return->error, true));
  $pr .= '<pre>' . print_r($params, 1) . '</pre>';
  $pr .= '<pre width="180">'. htmlentities($epp_return->xml) . '</pre>';
  $pr .= '<h2>Request</h2>';
  $pr .= '<pre width="180">'. htmlentities ($epp_return->request) . '</pre>';
  $objResponse->assign('command_out', 'innerHTML', $pr);
  return $objResponse;  
}


function epp_command($params) {
  
  $command = $params['p_command'];
  $c = get_config('config', 0);
  $host = $c->server->host;
  $port = (float)  $c->server->port;
  $timeout = (int) $c->server->timeout;
  $ssl = (boolean) $c->server->ssl;
  
  global $epp, $frame, $debug;
  
  //  $debug = (int) $c->DEBUG;
  // if ($debug == 1) {print_r($c);}
  
  $epp = new Net_EPP_AT_Client();
    
  
  $greeting = $epp->connect($host, $port, $timeout, $ssl);
  
  $param[] = array('login' => $c->user->login, 
                   'pwd'   => $c->user->pw, 
                   'trid'  => $c->user->trid);
  
  $epp->command('login', $param);
  unset($param);
  
  $frame = new Epp_Frame(); 
    
  $param[] = $params;
  $epp_return = $epp->command($command, $param);
  $epp->disconnect();
  return $epp_return;
}

$req_get_commands   =& $xajax->registerFunction('get_commands');
$req_get_params     =& $xajax->registerFunction('get_params');
$req_print_params   =& $xajax->registerFunction('print_params');
$req_send_frame     =& $xajax->registerFunction('send_frame');
$req_get_reg_config =& $xajax->registerFunction('get_reg_config');
//$req_print_params->setParameter(0, XAJAX_INPUT_VALUE, 'p_command');
$xajax->processRequest();


?>
<html>
<head>
   <link rel="stylesheet" href="epp-client.css" type="text/css" />
	<title>epp client</title>
	
	<script>	
	function js_get_params(p_command) {
	  xajax_print_params(p_command);
	}	
  </script>
		
	<script>
	<?php
	$xajax->printJavascript();	
  ?>  
  </script>
</head>

<body onload='xajax_get_commands();'>
<div id="result">
 <h3>Registrar Info</h3>
 <div id="registrar_info">
  <p>no data</p>
 </div>
 <div>  
   <div id="c1">
     <h2>EPP commands</h2>
     <div id="commands_list">
     	<p>no commands</p>
     </div>
   </div>
   <div id="c2">
     <h2>Command params</h2>
     <div id="command_params">   
      <p>no params</p>
     </div>
   </div>
   <div id="c3">
     <h2>Command out</h2>                                                         
      <div id="command_out">
      <p>no output</p></div>    
     </div>
   </div>                                                   
 </div>
<div>
	
</body>
</html>