<?php
// Activate assert() but make it quiet
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
//assert_options(ASSERT_QUIET_EVAL, 1);

// Create a handler function
function assertion_handler($file, $line, $code)
{
  die( "Assertion Failed:\nFile '$file'\nLine '$line'\nCode '$code'\n");
}

// Set up the callback
assert_options(ASSERT_CALLBACK, 'assertion_handler');

//  And track errors
ini_set('track_errors', true);
?>