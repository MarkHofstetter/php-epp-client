<?php
include_once('tbs/tbs_class_php4.php') ;

class Epp_Frame {
	var $TemplateDir = "templates";
	var $type;
  
  private function parse($type, $templ) {
    $TBS = new clsTinyButStrong ;
    $TBS->LoadTemplate(sprintf ('%s/%s', $this->TemplateDir, $type)) ;    
    return $TBS;
  }
  
  function get_params($type, $templ) {
    $TBS = $this->parse($type, $templ);
    return $TBS->TplVars;    
  } 
   
  function create($type, $templ) {
    $TBS = $this->parse($type, $templ);
    $TBS->MergeBlock('templ',$templ) ;    
    $TBS->Render = TBS_NOTHING;        
    $frame = $TBS->Source; 
    $TBS->Show;
    return $frame;
	}	
}
?>