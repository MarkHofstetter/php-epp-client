<?php
//error_reporting(-1);
session_start();

function get_config_no() {
  if (!isset($_SESSION['config_no'])) { $_SESSION['config_no'] = 0; }
  return $_SESSION['config_no'];
}
function set_config_no($config_no) {
  $_SESSION['config_no'] = $config_no;
}

require ('xajax/xajax_core/xajax.inc.php');
require_once('xml/Frame.php');

$xajax = new xajax();
//$xajax->setFlag('debug', true);
$xajax->configure('javascript URI', 'xajax');

function list_files($dir) {
  if (is_dir($dir) && $dentry = opendir($dir)) {
    while (false !== ($file = readdir($dentry))) {
      $path = $dir . DIRECTORY_SEPARATOR . $file;
      if (substr($file, 0, 1) == "." || is_dir($path)) { continue; } // if file starts with a . skip it
      $files[] = $file;
    }
    closedir($dentry);
    sort($files);
    return $files;
  }
}

function parse_config($dir, $config_no) {
  $configs = list_files($dir);
  $config  = $dir . DIRECTORY_SEPARATOR . $configs[$config_no];
  $c = simplexml_load_file($config);
  set_config_no($config_no);
  if (!isset($c->server->protocol))
    $c->server->protocol = "EPP";
  /*
  if (!isset $c->settings->templates)
    $c->settings->templates = array('.');
  */
  return $c;
}

function get_params() {
  $objResponse = new xajaxResponse();
  $objResponse->assign('command_params', 'innerHTML', 'n/a');
  return $objResponse;
}

function load_defaults($params, $command, $si) {
  $si = session_id();
  $command = str_replace(DIRECTORY_SEPARATOR, '_', $command);
  if (!file_exists('data' . DIRECTORY_SEPARATOR . $si . $command)) {
    $defaults['trid'] = time(); return $defaults;
  }
  $defaults = unserialize(join('', file('data' . DIRECTORY_SEPARATOR . $si . $command)));
  $defaults['trid'] = time();
  return $defaults;
}

function store_defaults($params, $command, $si) {
  $si = session_id();
  $command = str_replace(DIRECTORY_SEPARATOR, '_', $command);
  $fp = fopen('data' . DIRECTORY_SEPARATOR . $si . $command, 'w');
  fputs($fp, serialize($params));
  fclose($fp);
}

function switch_config($config_no) {
  $objResponse = new xajaxResponse();
  $configs = list_files('config');
  $config_list = 'Select Registrar ';
  $config_list .= '<select name="select_registrar" id="select_registrar"';
  $config_list .= ' onChange="xajax_event_toggle(this.value);return false;">';
  $i = 0;
  foreach ($configs as $config) {
    $config_list .= sprintf("<option value='%s'%s>%s</option>", $i, $i == $config_no ? ' selected' : '', $config);
    $i++;
  }
  $config_list .= '</select><br>';

  $c = parse_config('config', $config_no);
  $info = sprintf("Host [%s], Protocol [%s] Port [%s], User [%s]",
      $c->server->host,
      $c->server->protocol,
      $c->server->port,
      $c->user->login);
  $config_list .= $info;
  $objResponse->assign('registrar_info', 'innerHTML',  $config_list);

  return $objResponse;
}

function get_commands() {
  $objResponse = new xajaxResponse();
  $c = parse_config('config', get_config_no());
  $disabled_commands = array();
  if (isset($c->settings->disabled_commands)) {
    foreach ($c->settings->disabled_commands as $disabled)
      $disabled_commands[] = $disabled;
  }
  $commands = array();
  foreach ($c->settings->templates as $template) {
    $path = 'templates' . DIRECTORY_SEPARATOR . $template;
    $files = list_files($path);
    if (count($files) < 1)
      $objResponse->alert("WARNING!\n\nNo templates found in $path\nCheck your <templates> settings in the current XML!\n");
    foreach ($commands as $o_dir => $o_files)
      $files = array_diff($files, $o_files); /* Prune already known commands */
    if (count($disabled_commands)) {
      $files = array_diff($files, $disabled_commands);
    }
    $commands["" . $path] = $files;
  }
  $command_list = '';
  foreach ($commands as $path => $cmds) {
    foreach ($cmds as $command)
      $command_list .= sprintf('<a href=\'\' onclick=\'js_get_params("%s", "%s"); return false;\'>%s</a><br>', $path, $command, $command);
  }
  $objResponse->assign('commands_list', 'innerHTML', $command_list);
  return $objResponse;
}


function format_params($params, $path, $p_command) {
  $ret = sprintf("<h3>%s</h3>\n", $p_command);
  $ret .= '<form id="params" onSubmit="return false">';
  $si = 0;
  $full_command = $path .DIRECTORY_SEPARATOR . $p_command;
  $defaults = load_defaults($params, $full_command, $si);

  foreach ($params as $p => $b) {
    $ret .= sprintf('%s <input type="text" name="%s" id="%s" value="%s"><br>', $p, $p, $p, $defaults[$p]);
  }
  $ret .= sprintf('<input type="hidden" name="p_command" id="p_command" value="%s" ><br>', 
                   $full_command);
  $ret .= '<input type="button" name="submit" value="submit" onClick="set_class(\'command_out\', \'old_data\');xajax_dispatch_command(xajax.getFormValues(\'params\'));">';
  $ret .= '</form>';
  return $ret;
}
function print_params($path, $p_command) {
  $objResponse = new xajaxResponse();
  $frame = new xml_Frame();
  $params = $frame->get_params($path, $p_command);
  $fp = format_params($params, $path, $p_command);
  $objResponse->assign('command_params', 'innerHTML', $fp);
  return $objResponse;
}

function dispatch_command($params) {
  $si = 0;
  
  store_defaults($params, $params['p_command'], $si);
  $c = parse_config('config', get_config_no());
  if ($c->server->protocol == 'RRI') {
    return rri_command($params);
  } else { 
    return epp_command($params);
  }
  $objResponse = new xajaxResponse();
  $objResponse->alert(sprintf("Unknown protocol '%s'\n", $c->server->protocol));
  return $objResponse;
}

function rri_command($params) {
  global $rri, $debug;
  $command = $params['p_command'];
  $c = parse_config('config', get_config_no());
  $host = $c->server->host;
  $port = (float)  $c->server->port;
  $timeout = (int) $c->server->timeout;
  $ssl = (boolean) $c->server->ssl;
//trigger_error(sprintf("host=%s, port=%s ssl=%s", $c->server->host, $c->server->port, $c->server->ssl));
  require ('Net/RRI/Client.php');
  $rri = new Net_RRI_Client();
  $greeting = $rri->connect($host, $port, $timeout, $ssl);
  $param[] = array(
      'login' => $c->user->login,
      'pw'    => $c->user->pw,
      'trid'  => $c->user->trid
  );
  send_rri_frame('login', $param);
  unset($param);

  $param[] = $params;
  $rri_return = send_rri_frame($command, $param);
  $rri->disconnect();

  $pr = sprintf("command: [%s]<br>", $params['p_command']);
  $objResponse = new xajaxResponse();
  if (00 && $rri_return->code >= 2000)
    $pr .= '<div class="failed">';
  $pr .= sprintf("code: [%s]<br>", $rri_return->code);
  $pr .= sprintf("msg:  [%s]<br>", $rri_return->msg);
  if (00 && $rri_return->code >= 2000)
    $pr .= '</div>';
  if ($rri_return->handle)
    $pr .= sprintf("handle:  [%s]<br>", $rri_return->handle);
  if (count($rri_return->error))
    $pr .= sprintf("\nError(s): <pre id='errors'> [%s] </pre><br>\n", print_r($rri_return->error, true));
  $pr .= "<pre id='params'>" . print_r($params, true) . '</pre>';
  $pr .= "<pre id='response'>". htmlentities($rri_return->xml) . '</pre>';
  $pr .= '<h2>Request</h2>';
  $pr .= sprintf("Request length: %u", $rri_return->request_len);
  $pr .= "<pre id='request'>". htmlentities ($rri_return->request) . '</pre>';
  $objResponse->assign('command_out', 'className', '');
  $objResponse->assign('command_out', 'innerHTML', $pr);
  return $objResponse;
}

function epp_command($params) {
  $objResponse = new xajaxResponse();
  $epp_return = send_epp_frame($params);
## $objResponse->alert("XXX:" . print_r($epp_return, true) . "\n");
  $pr = sprintf("command: [%s]<br>", $params['p_command']);
  if ($epp_return->code >= 2000)
    $pr .= '<div class="failed">';
  $pr .= sprintf("code: [%s]<br>", $epp_return->code);
  $pr .= sprintf("msg:  [%s]<br>", $epp_return->msg);
  if ($epp_return->code >= 2000)
    $pr .= '</div>';
  if ($epp_return->handle)
    $pr .= sprintf("handle:  [%s]<br>", $epp_return->handle);
  if (count($epp_return->error))
    $pr .= sprintf("\nError(s): <pre id='errors'> [%s] </pre><br>\n", print_r($epp_return->error, true));
  $pr .= "<pre id='params'>" . print_r($params, true) . '</pre>';
  $pr .= "<pre id='response'>". htmlentities($epp_return->xml) . '</pre>';
  $pr .= '<h2>Request</h2>';
  $pr .= sprintf("Request length: %u", $epp_return->request_len);
  $pr .= "<pre id='request'>". htmlentities ($epp_return->request) . '</pre>';
  $objResponse->assign('command_out', 'className', '');
  $objResponse->assign('command_out', 'innerHTML', $pr);
  return $objResponse;
}

function send_epp_frame($params) {
  $command = $params['p_command'];
  $c = parse_config('config', get_config_no());
  $host = $c->server->host;
  $port = (float)  $c->server->port;
  $timeout = (int) $c->server->timeout;
  $ssl = (boolean) $c->server->ssl;

  global $epp, $frame, $debug;

  //  $debug = (int) $c->DEBUG;
  // if ($debug == 1) {print_r($c);}
//trigger_error(sprintf("config_no=%s", get_config_no()));
//trigger_error(sprintf("host=%s, port=%s ssl=%s", $c->server->host, $c->server->port, $c->server->ssl));
  require ('Net/EPP/AT/Client.php');
  $epp = new Net_EPP_AT_Client();
  $greeting = $epp->connect($host, $port, $timeout, $ssl);
  $param[] = array(
      'login' => $c->user->login,
      'pw'    => $c->user->pw,
      'trid'  => $c->user->trid
  );
  $epp->command('login', $param);
  unset($param);

  $frame = new xml_Frame();

  $param[] = $params;
  
  $path = dirname($command);
  $command = basename($command);
  $epp_return = $epp->command($command, $param, $path);
  $epp->disconnect();
  return $epp_return;
}

function send_rri_frame($command, $param) {
    global $rri, $frame, $debug;
    unset($rri_return);
    $frame = new xml_Frame();
    $request = $frame->create($command, $param);
    $response = $rri->request($request);
    $rri_return->xml         = $response;
    $rri_return->code        = ''; /* silence warnings on error */
    $rri_return->msg         = ''; /* silence warnings on error */
    $rri_return->handle      = ''; /* silence warnings on error */
    $rri_return->request     = ''; /* silence warnings on error */
    $rri_return->request_len = ''; /* silence warnings on error */
    $len = strpos($response, '<');
    if ($len === false) {
	    $rri_return->error = "Response is not an XML.";
	    return $rri_return;
    }
    $len = strpos($request, '<');
    $hdr = "";

    for ($i = 0; $i <= $len; $i++) {
	    $hdr .= $request[$i];
    }
    $req_len = unpack('N', $hdr);
    $rri_return->request_len = $req_len[1];
    $request = substr($request, $len);
    $rri_return->request = $request;
    try {
	    $xml = new SimpleXMLElement($response);
    } catch (Exception $e) {
	    $rri_return->error = $e->getMessage();
	    trigger_error($response);
	    return $rri_return;
    }
    $this->xml = $xml;
    $rri_return->code    = $xml->response->result[0]->attributes();
    $rri_return->msg     = $xml->response->result->msg;
    $rri_return->handle  = '';
    $rri_return->error   = array();
    // do rri_return->code / error handling here...
    return $rri_return;
}

$req_print_params   =& $xajax->register(XAJAX_FUNCTION, 'print_params');
$req_dispatch_command =& $xajax->register(XAJAX_FUNCTION, 'dispatch_command', array('callback' => 'staleData'));
$req_toggle = $xajax->register(XAJAX_EVENT, 'toggle', array("mode" => "synchronous"));
$req_toggle->setParameter(0, XAJAX_JS_VALUE, get_config_no());
$xajax->register(XAJAX_EVENT_HANDLER, 'toggle', 'switch_config');
$xajax->register(XAJAX_EVENT_HANDLER, 'toggle', 'get_commands');
$xajax->register(XAJAX_EVENT_HANDLER, 'toggle', 'get_params');
$xajax->processRequest();

?>
<html>
<head>
  <link rel="stylesheet" href="epp-client.css" type="text/css" />
  <title>EPP client</title>
<?php
$xajax->printJavascript();
?>
  <script>
function js_get_params(p_path, p_command) {
  xajax_print_params(p_path, p_command);
}
function set_class(id, str) {
  document.getElementById(id).className = str;
}
function add_class(id, str) {
  var elt = document.getElementById(id);
  var className = elt.className;
  var classes = (className || "").split(/\s+/);
  if (classes.indexOf(str) < 0) {
    classes.push = str;
    elt.className = classes.sort().join(" ");
  }
}
function remove_class(id, str) {
  var elt = document.getElementById(id);
  var className = elt.className;
  var classes = (className || "").split(/\s+/);
  var newclasses = new Array();
  for (var i=0, l=classes.length; i<l; i++) {
    if (classes[i] !== str) {
      newclasses.push(classes[i]);
    }
  }
  if (classes.length != newclasses.length) {
    elt.className = newclasses.sort().join(" ");
  }
}


xajax.callback.global.onRequest = function() {
  add_class('result', 'old_data');
};
xajax.callback.global.onComplete = function() {
  remove_class('result', 'failed');
  remove_class('result', 'old_data');
};
xajax.callback.global.onFailure = function(args) {
  add_class('result', 'failed');
  alert("Call failed; HTTP status code: " + args.request.status);
  remove_class('result', 'failed');
};
staleData = xajax.callback.create(100, 10000);
staleData.onRequest = function() {
  add_class('command_out', 'stale_data');
};
staleData.onComplete = function() {
  remove_class('command_out', 'stale_data');
};
staleData.onFailure = function(args) {
  add_class('command_out', 'failed');
  alert("Call failed! HTTP status code: " + args.request.status);
  remove_class('command_out', 'failed');
};
  </script>

</head>

<body onLoad='<?php $req_toggle->printScript(); ?>'>
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
      <h2>Command parameters</h2>
      <div id="command_params">
        <p>no parameters</p>
      </div>
    </div>
    <div id="c3">
      <h2>Command output</h2>
      <div id="command_out">
        <p>no output</p></div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
