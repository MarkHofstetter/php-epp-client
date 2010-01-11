<?php

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

function rs($test) {
   return $test;
  }
?>