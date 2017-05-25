<?php
require('Net/EPP/Client.php');
require_once('xml/Frame.php');

class Net_EPP_AT_Client extends Net_EPP_Client {
  function command($command, $param, $path='templates') {
    global $debug;
    unset($epp_return);
    $epp_return = new StdClass(); /* shutup interp */
    $frame = new xml_Frame();

    $request = $frame->create($path, $command, $param);
    if ($debug == 1) {
      echo "Command: $command: \n";
      echo "\n*** Client ***\n";
      echo $request;
      return 1;
    }
    $response = $this->request($request);
    $epp_return->xml         = $response;
    $epp_return->code        = ''; /* silence warnings on error */
    $epp_return->msg         = ''; /* silence warnings on error */
    $epp_return->handle      = ''; /* silence warnings on error */
    $epp_return->request     = ''; /* silence warnings on error */
    $epp_return->request_len = ''; /* silence warnings on error */

    $epp_return->request_len = strlen($request);
    if ($epp_return->request_len > 0)
        $epp_return->request = $request;

    $len = strpos($response, '<');
    if ($len === false) {
	    $epp_return->error = "Response is not an XML.";
	    return $epp_return;
    }
    try {
	    $xml = new SimpleXMLElement($response);
    } catch (Exception $e) {
	    $epp_return->error = $e->getMessage();
	    trigger_error($response);
	    return $epp_return;
    }
    $this->xml = $xml;
    $epp_return->code    = $xml->response->result[0]->attributes();
    $epp_return->msg     = $xml->response->result->msg;
    $epp_return->handle  = '';
    $epp_return->error   = array();

    // the next part has to go into a factory class
    // for the moment we only care for parsing
    // 1. person_create
    // 2. domain-transfer
    if (($epp_return->code == 1000) && (preg_match('/person-create/', $command))) {
      $epp_return->handle = $this->parse_person_create($xml);
    }
      /// elseif (($command == 'domain-transfer') and  ($epp_return->code == 1001)) {
      /// just check for tranfer maybe we do something specific here in the future
      /// }

    if ($epp_return->code >= 2000) {
      $i = 0;
      if ($xml->response->extension)
      foreach ($xml->response->extension->conditions->condition as $condition) {
        $epp_return->error[$i]->code    = (string) $condition->attributes();
        $epp_return->error[$i]->details = (string) $condition->details;
        $i++;
      }
    }
    if ($debug == 1 ) {
      echo $epp_return->code ."\n";
      echo $epp_return->msg  ."\n";
      echo "\n*** SERVER ***\n";
      echo $response;
    }
    return $epp_return;
  }

  function parse_person_create($xml) {
    global $debug;

    if ($debug == 1) {
      print_r( $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData
          ->children('urn:ietf:params:xml:ns:contact-1.0'));
    }

    return
      $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData
      ->children('urn:ietf:params:xml:ns:contact-1.0')->creData->id;
  }

} // end of class

?>
