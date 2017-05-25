<?php

$file = $argv[1];
$xml =  join('', file($argv[1]));
## print $xml;
$data = epp_extract_data($xml);
print $data['tech'] ."\n";
print $data['domain']."\n";
print $data['pw']."\n";
print $data['msgid']."\n";

function epp_extract_data($frame) {
  $xml = new SimpleXMLElement($frame);
  $data = array();
  
 
 # print_r($xml->response->msgQ[0]->attributes());
  if ($xml->response->msgQ) {
    list($data['msg_count'], $data['msgid']) = $xml->response->msgQ[0]->attributes();
  }
  #printf("count: %s  id: %s\n", $count, $id);  
  ##[0]->attributes()
  
  /*
  print_r( $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData     
            ->children('urn:ietf:params:xml:ns:domain-1.0')->infData->contact);
  */
  
  $data['pw'] = $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData
        ->children('urn:ietf:params:xml:ns:domain-1.0')->infData->authInfo->pw;

  $data['domain'] = $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData
        ->children('urn:ietf:params:xml:ns:domain-1.0')->infData->name;        
  
  $data['tech'] = $xml->response->children('urn:ietf:params:xml:ns:epp-1.0')->resData     
            ->children('urn:ietf:params:xml:ns:domain-1.0')->infData->contact;
            
  return $data;
  
  }

/*


<?xml version="1.0" encoding="UTF-8"?> 
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"> 
  <response> 
    <result code="1301"> 
      <msg lang="en">Command completed successfully; ack to dequeue</msg> 
    </result> 
    <msgQ count="1" id="1139047"> 
      <qDate>2007-09-26T00:00:00+02:00</qDate> 
      <msg>Domain transfercode</msg> 
    </msgQ> 
    <epp:resData xmlns:epp="urn:ietf:params:xml:ns:epp-1.0"> 
      <domain:infData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0"> 
        <domain:name>test-partner-a-domain-1.ch</domain:name> 
        <domain:roid>D1234567-SWITCH</domain:roid> 
        <domain:clID>SWITCH-PARTNER</domain:clID> 
        <domain:authInfo> 
          <domain:pw>the-domain-tranfercode</domain:pw> 
        </domain:authInfo> 
      </domain:infData> 
    </epp:resData> 
    <trID> 
      <clTRID>Partner_00_2</clTRID> 
      <svTRID>20071008.13688.27039</svTRID> 
    </trID> 
  </response> 
</epp> 

<?xml version="1.0" encoding="UTF-8"?>
<epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
    <response>
        <result code="1000">
            <msg lang="en">Command completed successfully</msg>
        </result>
        <resData>
            <domain:infData xmlns:domain="urn:ietf:params:xml:ns:domain-1.0">
                <domain:name>test-partner-domain-1.ch</domain:name>
                <domain:roid>D3337848-SWITCH</domain:roid>
                <domain:status s="ok"/>
                <domain:registrant>TEST-PARTNER-14</domain:registrant>
                <domain:contact type="tech">TEST-PARTNER-14</domain:contact>
                <domain:ns>
                    <domain:hostObj>ns2.bla.at</domain:hostObj>
                    <domain:hostObj>ns1.bla.at</domain:hostObj>
                </domain:ns>
                <domain:host>ns3.test-partner-domain-1.ch</domain:host>
                <domain:clID>TEST-PARTNER-B</domain:clID>
                <domain:exDate>2011-04-30T00:00:00+02:00</domain:exDate>
                <domain:authInfo>
                    <domain:pw>123456</domain:pw>
                </domain:authInfo>
            </domain:infData>
        </resData>
        <trID>
            <clTRID>1270814402</clTRID>
            <svTRID>20100409.4382425.49757957</svTRID>
        </trID>
    </response>
</epp>
*/
  
?>


