<?php
require('Net/EPP/Client.php');
require('Epp/Frame.php');     

class Net_EPP_AT_Client extends Net_EPP_Client {
  function command($command, $param) {
	 global $debug;
	 unset($epp_return);
   $frame = new Epp_Frame();
   //$frame->TemplateDir = 'templates';

   $request = $frame->create($command, $param);
   if ($debug == 1) {
     echo "Command: $command: \n"; 
     echo "\n*** Client ***\n";     
     echo $request;
     return 1;
    }
    
   $this->sendFrame($request);
   $answer = $this->getFrame();
   $xml = new SimpleXMLElement($answer);
   
   $this->xml = $xml;
   $epp_return->request = $request;
   $epp_return->xml     = $answer;
   $epp_return->code    = $xml->response->result[0]->attributes();
   $epp_return->msg     = $xml->response->result->msg;
      
   // the next part has to go into a factory class 
   // for the moment we only care for parsing 
   // 1. person_create
   // 2. domain-transfer
    
   if (($epp_return->code == 1000) && (ereg('person-create', $command))) {     
     $epp_return->handle = $this->parse_person_create($xml);
   }
   elseif (($command == 'domain-transfer') and  ($epp_return->code == 1001)) {
   /// just check for tranfer maybe we do something specific here in the future
   }
        
   if ($epp_return->code > 1001) {
     $i = 0;     
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
      echo $answer;
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
   
     /* if ($handle) {      
      $epp_return->handle = $handle;
      return 1;
     } else {
      return 0;
     }*/
  }

} // end of class   
            
?>
