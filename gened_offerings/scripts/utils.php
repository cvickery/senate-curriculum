<?php

//  Globals
    $months = array(
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
      );
      
    // TODO: find a CUNYfirst table that defines all possible values for this column
    $components = array(
          'LEC','LAB', 'MSG', 'REC', 'SEM', 'TUT'
    );
//  Class Term
//  --------------------------------------------------------------------------
class Term
{
  public $code, $name, $abbr;
  
  // Constructor
  //  ------------------------------------------------------------------------
  /*  Convert CUNYfirst term code (CYYM) and session code (1, WIN,
   *  4W1, etc) into a QC term_code, YYYYTTTT.
   *    cyymmm Name            Abbreviation
   *       010 Winter          Win
   *       020 Spring          Spr
   *       041 Summer 1 short  S1s
   *       042 Summer 1 long   S1l
   *       061 Summer 2 short  S2s
   *       062 Summer 2 long   S2l
   *       009 Fall            Fall
   *       003, 004, 005, etc. Mon
   *
   *  Note: CUNYfirst used to associate Winter with previous Fall (9 for the
   *  month). In that case add 1 to the year in addition to converting the month
   *  to 1.
   *
   *  name:  'Name 4-digit-year'
   *  abbr:  'Abbreviation2-digit-year'
   */
 
  function __construct($cf_term, $cf_sess)
  {
    global $months;
    $year = 1900 + 100 * substr($cf_term, 0, 1) + substr($cf_term, 1, 2);
    $month = '0'.substr($cf_term, 3, 1);
    switch ($cf_sess)
    {
      case '1':
        //  Regular Session (Spring or Fall)
        $term = $year.$month.'0';
        if ($month === '02')
        {
          $term_name = 'Spring '  . $year;
          $term_abbr = 'Spr'      . substr($year, 2,  2);
        }
        else if ($month === '09')
        {
          $term_name = 'Fall '  . $year;
          $term_abbr = 'Fall'   . substr($year, 2,  2);
        }
        else
        {
          //  Probably Summer Session, but just give the month
          $term_name = $months[-1 + $month] . ' '. $year;
          $term_abbr = substr($months[-1 + $month], 0, 3) 
              . substr($year, 2, 2);
        }
        break;
      case 'WIN':
        if ($month === '09') $year++;
        $term = $year . '010';
        $term_name = 'Winter ' . $year;
        $term_abbr = 'Win' . substr($year, 2,  2);
        break;
      case '4W1':
        $term = $year . '041';
        $term_name = 'Summer 1 short ' . $year;
        $term_abbr = 'S1s' . substr($year, 2,  2);
        break;
      case '4W2':
        $term = $year . '042';
        $term_name = 'Summer 1 long ' . $year;
        $term_abbr = 'S1l' . substr($year, 2,  2);
        break;
      case '6W1':
        $term = $year . '061';
        $term_name = 'Summer 2 short ' . $year;
        $term_abbr = 'S2s' . substr($year, 2,  2);
        break;
      case '6W2':
        $term = $year . '062';
        $term_name = 'Summer 2 long ' . $year;
        $term_abbr = 'S2l' . substr($year, 2,  2);
        break;
      default:
        //  Pending knowledge of all possible session codes, default to
        //  using the month, as in the regular session case (1) above.
        $term = $year.$month.'0';
        $term_name = $months[-1 + $month] . ' '. $year;
        $term_abbr = substr($months[-1 + $month], 0, 3) 
            . substr($year, 2, 2);
        //die("Bad switch on $cf_sess\n");
        break;
    }
    $this->code = $term;
    $this->name = $term_name;
    $this->abbr = $term_abbr;
  }
}

?>
