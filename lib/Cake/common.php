<?php

if (!function_exists('T')) {
   function T($string, $escape = FALSE) {
      return ($escape == FALSE)?__($string):addslashes(__($string));
   }
}


?>
