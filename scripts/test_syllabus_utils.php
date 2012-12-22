<?php
//  test_syllabus_utils.php
//  Regression tests for syllabus_utils

require_once('atoms.inc');
require_once('syllabus_utils.php');

class Test_String
{
  public $string, $is_valid;
  function __construct($string, $is_valid)
  {
    $this->string   = $string;
    $this->is_valid = $is_valid;
  }
}

$test_strings = array(
  new Test_String('CSCI',         false),
  new Test_String('CSCI 0000',    false),
  new Test_String('CSCI 0W',      false),
  new Test_String('CSCI 1W',      true),
  new Test_String('CSCI 1',       true),
  new Test_String('CSCI 1234',    true),
  new Test_String('CSCI 1234W',   true),
  new Test_String('CSCI 12345',   false),
  new Test_String('CSCI 12345W',  false),
  new Test_String('CSCI 1X',      false),
  new Test_String('E&ES 701',     false),
  new Test_String('123',          false),
  new Test_String('CSCI-01',      true),
  new Test_String('CSCI01',       true),
  new Test_String('CSCI - 01',    true),
  );

foreach ($test_strings as $test_string)
{
  $msg_str = str_pad($test_string->string, 12, ' ', STR_PAD_LEFT);
  $result = preg_match(course_str_re, $test_string->string, $matches);
  echo "'$msg_str' should " . (($test_string->is_valid) ? 'pass' : 'fail');
  if ($result == $test_string->is_valid) echo "\tok\n";
  else
  {
    echo "\tNOT OK\n";
  }
  if ($matches and count($matches > 2)) echo "  {$matches[1]} {$matches[2]}\n";
}

//  Interactive search for Syllabus files.
while (true)
{

  echo "Course: ";
  $course = fgets(STDIN);
  if (! $course) exit();
  try
  {
    $syllabi = get_syllabi($course);
    $n = count($syllabi);
    if ($n > 0)
    {
      foreach($syllabi as $syllabus)
      {
        echo "$syllabus (".filesize($syllabus)." bytes)\n";
      }
    }
    else
    {
      echo "There are no syllabi for $course\n";
    }

  }
  catch (Exception $e)
  {
    echo $e->getMessage() . "\n";
  }
}

