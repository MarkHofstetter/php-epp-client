<?php

function epp_extract_data($frame) {
  $xml = new SimpleXMLElement($frame);
  $data = array();

  if ($xml->response->msgQ) {
    list($data['msgcount'], $data['msgid']) = $xml->response->msgQ[0]->attributes();
  }
  
  $data['pw'] = $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData
        ->children('urn:ietf:params:xml:ns:domain-1.0')->infData->authInfo->pw;
  $data['domain'] = $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData
        ->children('urn:ietf:params:xml:ns:domain-1.0')->infData->name;        
  
  $data['tech'] = $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData     
            ->children('urn:ietf:params:xml:ns:domain-1.0')->infData->contact;
  return $data;
  
 }
?>

