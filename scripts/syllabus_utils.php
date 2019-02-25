<?php
/*  syllabus_utils.php
 */

//  Recognized syllabus file types
//  ======================================================================================
    $valid_extensions = array('pdf', 'docx', 'doc', 'rtf', 'odf', 'html', 'md', 'txt');

//  num2str()
//  -------------------------------------------------------------------------
/*  Numbers from 0 to 12 are returned as words. Anything else is unchanged.
 */
  function num2str($arg)
  {
    $strings = array('zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight',
        'nine', 'ten', 'eleven', 'twelve');
    if (is_numeric($arg) && ($arg > -1) && ($arg < 13)) return $strings[$arg];
    else return $arg;
  }

//  matches2datestr()
//  --------------------------------------------------------------------------------------
/*  Takes the output of preg_match() on a YYYY-MM-DD string and generates a human-readable
 *  date from the year, month, day parts.
 */
  function matches2datestr($matches)
  {
    assert('count($matches) === 4');
    $day = ltrim($matches[3], '0');
    $months = array('January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December');
    $returnVal = $months[$matches[2] - 1] . " $day, " . $matches[1];
    return $returnVal;
  }

//  humanize_num()
//  --------------------------------------------------------------------------------------
/*  Converts a file size to a human-readable string. File size suffix one of bytes, KB,
 *  MB, GB, or TB.
 */
  function humanize_num($val)
  {
    if ($val > pow(2, 40)) return number_format($val / pow(2, 40), 1) . ' TB';
    if ($val > pow(2, 30)) return number_format($val / pow(2, 30), 1) . ' GB';
    if ($val > pow(2, 20)) return number_format($val / pow(2, 20), 1) . ' MB';
    if ($val > pow(2, 10)) return number_format($val / pow(2, 10), 1) . ' KB';
    return number_format($val) . ' bytes';
  }

//  get_current_syllabus()
//  --------------------------------------------------------------------------------------
/*  Returns pathname of the most recent syllabus for a course, or null if there is none.
 */
  function get_current_syllabus($course_str)
  {
    $syllabi = get_syllabi($course_str);
    $keys = array_keys($syllabi);
    if (count($syllabi) < 1) return null;
    return $syllabi[$keys[0]];
  }

//  get_syllabi()
//  --------------------------------------------------------------------------------------
/*  Returns possibly empty array of pathnames to syllabi for a course, sorted in reverse
 *  order of modification time; keyed by printable representation of the file modification
 *  times. Ignore syllabi that were uploaded more than once in a day.
 */
  function get_syllabi($course_str)
  {
    $pathnames = array();
    $result = preg_match(course_str_re, $course_str, $matches);
    if ($result < 1)
    {
      throw new Exception('Invalid course string: '
          . basename(__FILE__) . ' line ' . __LINE__);
    }
    $syllabus_dir = opendir('../Syllabi');
    //  Only the latest syllabus on a day matches the following regular expression
    //  If there are multiple ones in a day, the earlier one(s) will have a dot-number
    //  between the date and the .pdf suffix, and won't be recognized.
    $syllabus_re = '/^' . strtoupper($matches[1]) . '-' . strtoupper($matches[2]) .
      '_\d{4}-\d{2}-\d{2}\.pdf/';
    while ($syllabus = readdir($syllabus_dir))
    {
      if (preg_match($syllabus_re, $syllabus))
      {
        $pathname = "../Syllabi/$syllabus";
        $key = date('Y-m-d \a\t h:i a', filemtime($pathname));
        $pathnames[$key] = $pathname;
      }
    }
    krsort($pathnames);
    return $pathnames;
  }
?>
