<?php 
//  include/page_title.php

//  get_title()
//  -------------------------------------------------------------------------------------
/*  Based on:
 *  http://www.code4gold.com/
 *       /forums/Programming/13324-0-Get-html-title-of-remote-page-in-PHP.html
 *
 *  Not used: need to put it in a common include file, but that leads to page reference
 *  loops.
 */
  function get_title( $url )
  {
    $title = '';
    $handle = @fopen( "$url", "r" );
  
    if ( $handle ) 
    {
      while ( !feof($handle) ) 
      {
        $buffer = fread($handle, 8192);
        $buffer = preg_replace("/\n|\r/", "", $buffer);
        if ( preg_match('/<title>(.*?)<\/title>/i', $buffer, $matches) )
        {
          $title = $matches[1];
          break;
        }
      }
      fclose($handle);
    }
    return $title;
}

