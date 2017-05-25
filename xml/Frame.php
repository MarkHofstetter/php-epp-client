<?php
include_once('tbs/tbs_class_php5.php') ;

class xml_Frame {
  var $TemplateDir = "templates";
  var $type;

  private function parse($path="templates", $command) {
    $TBS = new clsTinyButStrong;
    $TBS->LoadTemplate(sprintf ('%s/%s', $path, $command));
    return $TBS;
  }

  function get_params($path, $command) {
    $TBS = $this->parse($path, $command);
    return $TBS->TplVars;
  }

  function create($path, $command, $param) {
    $TBS = $this->parse($path, $command);
    $TBS->MergeBlock('templ', $param) ;
    $TBS->Render = TBS_NOTHING;
    $frame = $TBS->Source;
    return $frame;
  }
}
?>
