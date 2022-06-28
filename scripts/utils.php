<?php

//  .../scripts/utils.php

/*  General purpose utilities. Mostly designed for Proposal Editor, but used by any page
 *  that accesses the db.
 *    First part generates global variables on page load.
 *    Second part is utility functions.
 */

require_once('sanitize.php');

//  db Setup
//  --------------------------------------------------------------------------------------
$curric_db      = curric_connect() or die('Unable to access curriculum db');
$result = pg_query($curric_db, "select * from update_log where table_name = 'cf_catalog'")
    or die("Unable to query update_log");
$row = pg_fetch_assoc($result);
$cf_update_date_raw = new DateTime($row['updated_date']);
$cf_update_date = $cf_update_date_raw->format('F j, Y');

//  The discp_dept_div table
//  --------------------------------------------------------------------------------------
$disciplines  = array();
$discp2dept   = array();
$discp2div    = array();
$result = pg_query($curric_db, "SELECT * FROM discp_dept_div") or
    die('Unable to access discp-dept-div table' . basename(__FILE__) . ' ' . __LINE__);
while ($row = pg_fetch_assoc($result))
{
  $disciplines[$row['discipline']]  = $row;
  $discp2dept[$row['discipline']]   = $row['department'];
  $discp2div[$row['discipline']]    = $row['division'];
}

//  The enrollment_terms table
//  --------------------------------------------------------------------------------------
$enrollment_terms = array();
$result = pg_query($curric_db, "SELECT * FROM enrollment_terms ORDER BY term_code") or
    die('Unable to access enrollment_terms table' . basename(__FILE__) . ' ' . __LINE__);
while ($row = pg_fetch_assoc($result))
{
  $enrollment_terms[$row['term_code']]  = $row['term_name'];
}

//  $discp2dept[] and $discp2div[] contain only valid disciplines, depts, and divisions
//  --------------------------------------------------------------------------------------
/*  Populate dept_ids and chairs arrays with all entries in cf_organizations, knowing that
 *  only valid disciplines will be used to produce department abbreviations.  If "Unknown
 *  Chair" or "Unknown Dean" shows up, it's either because the contact info for that dept
 *  or division is not available or, more likely, because of a failure to use the
 *  $discp2dept or $discp2div array.
 */
$depts_by_id    = array();
$dept_ids       = array();
$depts_by_abbr  = array();
$chairs_by_abbr = array();
$result         = pg_query($curric_db, 'SELECT * FROM cf_academic_organizations')
                  or die('Unable to get departments');
while ($row = pg_fetch_assoc($result))
{
  $dept                     = $row['abbr'];
  $depts_by_id[$row['id']]  = $dept;
  $dept_ids[$dept]          = $row['id'];
  $depts_by_abbr[$dept]     = $row;
  if ($row['chair_name'] !== '')
  {
    $chairs_by_abbr[$dept]    = new Contact($row['chair_name'], $row['chair_email']);
  }
  else
  {
    $chairs_by_abbr[$dept]    = new Contact('Unknown Chair', '');
  }
}

/* Likewise for divs, div_ids, and deans
 */
$divs_by_id     = array();
$div_ids        = array();
$divs_by_abbr   = array();
$deans_by_abbr  = array();
$result         = pg_query($curric_db, 'SELECT * FROM cf_academic_groups')
                      or die('Unable to get divisions');
while ($row = pg_fetch_assoc($result))
{
  $div                    = $row['abbr'];
  $divs_by_id[$row['id']] = $div;
  $div_ids[$div]          = $row['id'];
  $divs_by_abbr           = $row;
  if ($row['dean_name'] !== '')
  {
    $deans_by_abbr[$div] = new Contact($row['dean_name'], $row['dean_email']);
  }
  else
  {
    $deans_by_abbr[$div] = new Contact('Unknown Dean', '');
  }
}

//  agencies: committees and other bureaucracies that do things with proposals
//  -------------------------------------------------------------------------------------
$agency_ids   = array();
$agency_names = array();
$result = pg_query($curric_db, 'SELECT * FROM agencies ORDER BY display_order')
    or die('Unable to get agencies');
while ($row = pg_fetch_assoc($result))
{
  $agency_ids[$row['abbr']] = $row['id'];
  $agency_names[$row['id']] = $row['full_name'];
}

//  proposal_type lookup tables.
//  -------------------------------------------------------------------------------------
$require_dept_approval        = array('REV-U', 'REV-G', 'NEW-U', 'NEW-G');
$proposal_type_id2abbr        = array();
$proposal_type_id2name        = array();
$proposal_type_id2agency_id   = array();
$proposal_type_abbr2type_id   = array();
$proposal_type_abbr2class_id  = array();
$proposal_type_abbr2name      = array();

$result = pg_query($curric_db, 'SELECT * FROM proposal_types ORDER BY id')
    or die('Unable to get proposal types: ' . basename(__FILE__) . ' ' . __LINE__);
while ($row = pg_fetch_assoc($result))
{
  $proposal_type_id2abbr        [$row['id']]    = $row['abbr'];
  $proposal_type_id2name        [$row['id']]    = $row['full_name'];
  $proposal_type_id2agency_id   [$row['id']]    = $row['agency_id'];
  $proposal_type_id2class_id    [$row['id']]    = $row['class_id'];
  $proposal_type_abbr2type_id   [$row['abbr']]  = $row['id'];
  $proposal_type_abbr2class_id  [$row['abbr']]  = $row['class_id'];
  $proposal_type_abbr2name      [$row['abbr']]  = $row['full_name'];
}

//  Total number of justifications needed for each proposal type
//  -------------------------------------------------------------------------------------
$query = <<<EOD
--  Query that gives the number of criteria that must be satisfied for each
--  proposal type.

SELECT proposal_types.abbr              proposal_type_abbr,
       sum(criteria_groups.num_needed)  num_needed
 FROM proposal_types, criteria_groups
 WHERE criteria_groups.abbr IN (
       SELECT criteria_group FROM proposal_type_criteria_group_mappings
        WHERE proposal_type_id = proposal_types.id
        )
 GROUP BY proposal_types.abbr
 ORDER BY proposal_types.abbr

EOD;
$result = pg_query($curric_db, $query)
    or die('Unable to count justifications: ' . basename(__FILE__) . ' ' . __LINE__);
$num_justifications_needed = array();
while ($row = pg_fetch_assoc($result))
{
  $num_justifications_needed[$row['proposal_type_abbr']] = $row['num_needed'];
}

//  proposal_classes
//  -------------------------------------------------------------------------------------
$proposal_classes         = array();
$proposal_class_abbr2name = array();
$result = pg_query($curric_db, 'SELECT * FROM proposal_classes ORDER BY id')
    or die('Unable to get proposal classes: ' . basename(__FILE__) . ' ' . __LINE__);
while ($row = pg_fetch_assoc($result))
{
  $proposal_classes[$row['id']] = $row;
  $proposal_class_abbr2name[$row['abbr']] = $row['full_name'];
}


//  proposal_type_options
//  -------------------------------------------------------------------------------------
/*  HTML entities used in the proposal selection table.
 *  Filters out inactive and ADMIN options.
 */
 $proposal_type_options = '';
 foreach ($proposal_classes as $proposal_class)
 {
   //  Accepting only Course and Pathways proposals for now
   if ($proposal_class['is_active'] === 't')
   {
     $label = "{$proposal_class['full_name']} ({$proposal_class['abbr']})";
     $proposal_type_options .= "          <optgroup label='$label'>\n";
     foreach ($proposal_type_id2class_id as $type_id => $class_id)
     {
       if ($class_id === $proposal_class['id'])
       {
         $abbr = $proposal_type_id2abbr[$type_id];
         if ($abbr === 'ADMIN') continue;
         $name = $proposal_type_id2name[$type_id];
         $proposal_type_options .= "            <option value='$abbr'>$name</option>\n";
       }
     }
     $proposal_type_options .= "          </optgroup>\n";
   }
 }

//  Basic designations (Not CUNY core)
$basic_designations = array(
  'Unknown' => 'Unknown Designation',
  'RLA'     => 'Regular Liberal Arts',
  'RNL'     => 'Regular Non-Liberal Arts',
  'GLA'     => 'Graduate Liberal Arts',
  'GNL'     => 'Graduate Non-Liberal Arts'
  );
//  CUNY core designations
$common_core_designations = array(
  'RECR'    =>  'EC-1 or EC-2 (English Composition)',
  'RMQR'    =>  'MQR (Mathematics and Quantitative Reasoning)',
  'RLPR'    =>  'LPS (Life and Physical Sciences)',
  'FCER'    =>  'CE (Creative Expression)',
  'FISR'    =>  'IS (Individual and Society)',
  'FSWR'    =>  'SW (Scientific World)',
  'FUSR'    =>  'USED (US Experience in its Diversity)',
  'FWGR'    =>  'WCGI (World Cultures and Global Experience)'
  );
$all_designations = array_merge($basic_designations, $common_core_designations);
//  "regular" components (lecture, lab, etc)
$query = "SELECT * FROM cf_components WHERE type = 'regular' ORDER BY display_order";
$result = pg_query($curric_db, $query) or die('Unable to get components list');
$components = array();
while ($row = pg_fetch_assoc($result))
{
  $components[$row['abbr']] = $row['full_name'];
}

//  Justifications Needed
//  ============================================================================
/*  Different proposal types need different sets of justifications. Some sets need to have
 *  subsets of a certain size ("three of the following six learning outcomes" for
 *  example). The database structures to support this model are:
 *
 *    + The criteria table has descriptions (full_text) and abbreviatins of everything
 *    that might have to be justified. (They are loaded from a text file by a script.)
 *
 *    + The criteria_groups table gives names to sets of criteria and the (minimum) number
 *    of them that need to be justified. A single graduation requirment might specify
 *    groups of criteria that a course must satisfy. This table includes a display_order
 *    and description for the group.
 *
 *    The criterion_criteria_group_mappings table assigns criteria to criteria groups. In
 *    principle, the same criterion could be placed in multiple groups, but in practice
 *    each criterion appears on a single group.
 *
 *    The proposal_type_criteria_group_mappings table assigns criteria groups to proposal
 *    types.
 *
 *    For Course Proposals (new, revise, fix), the only justification needed is a generic
 *    one, identified by the abbreviation, 'Course', and these tables are not used.
 *
 *    For Designation Proposals, the proposal type determines the list of criteria groups
 *    needed, which in turn determines the full list of criteria that need to be
 *    justified.
 *
 * When the type of a Proposal is known, an array of justification criteria, keyed by
 * group and number needed from the group, gets built from the group and criteria tables
 * saved here.
 *
 */

 //  Critera
  $criteria_text    = array();
  $result = pg_query($curric_db, "SELECT * FROM criteria") or
      die('Unable to get criteria: ' . basename(__FILE__) . ' ' . __LINE__);
  while ($row = pg_fetch_assoc($result))
  {
    $criteria_text[$row['abbr']] = $row['full_text'];
  }

  //  Criterion groups
  $criteria_group_abbr2num_needed  = array();
  $criteria_group_abbr2full_name   = array();
  $criteria_group_abbr2description = array();
  $result = pg_query($curric_db, "SELECT * FROM criteria_groups ORDER BY display_order")
      or die('Unable to get criteria groups: ' . basename(__FILE__) . ' ' . __LINE__);
  while ($row = pg_fetch_assoc($result))
  {
    $criteria_group_abbr2num_needed[$row['abbr']]   = $row['num_needed'];
    $criteria_group_abbr2full_name[$row['abbr']]    = $row['full_name'];
    $criteria_group_abbr2description[$row['abbr']]  = $row['description'];
  }

  // Map designation to criteria groups
  $query = <<<EOD
  SELECT *
    FROM proposal_type_criteria_group_mappings
ORDER BY proposal_type_id, display_order

EOD;
  $result = pg_query($curric_db, $query)
      or die('Unable to get designation to group mappings: '
            . basename(__FILE__) . ' ' . __LINE__);
  $proposal_type_id2criteria_group = array();
  while ($row = pg_fetch_assoc($result))
  {
    if (!isset($proposal_type_id2criteria_group[$row['proposal_type_id']]))
    {
      $proposal_type_id2criteria_group[$row['proposal_type_id']] = array();
    }
    $proposal_type_id2criteria_group[$row['proposal_type_id']][] = $row['criteria_group'];
  }

  // Map criteria groups to criteria
  $result = pg_query($curric_db, "SELECT * FROM criteria_group_criterion_mappings")
      or die('Unable to get criteria_group-criteria mappings: ' . basename(__FILE__) .
              ' ' . __LINE__);
  $criteria_group2criteria = array();
  while ($row = pg_fetch_assoc($result))
  {
    $group      = $row['criteria_group'];
    $criterion  = $row['criterion_abbr'];
    if (!isset($criteria_group2criteria[$group]))
    {
      $criteria_group2criteria[$group] = array();
    }
    $criteria_group2criteria[$group][] = $criterion;
  }

//  Grammar
//  =====================================================================================
$past_tense = array(
  'Submit'    => 'Submitted',
  'Resubmit'  => 'Resubmitted',
  'Update'    => 'Updated',
  'Fix'       => 'Fixed',
  'Withdraw'  => 'Withdrew',
  'Approve'   => 'Approved',
  'Reject'    => 'Rejected',
  'Revise'    => 'Returned',
  'Table'     => 'Tabled',
  'Close'     => 'Closed',
  'Accept'    => 'Accepted',
  'Reopen'    =>  'Reopened',
  );


//  Utility functions
//  =====================================================================================

//  lookup_course()
//  --------------------------------------------------------------------------------------
/*  Returns a possibly-empty array of Course objects, given a discipline course-number
 *  pair.
 */
  function lookup_course($discipline, $course_number)
  {
    global $curric_db, $disciplines;
    $returnVal = array();

    //  Get any/all records for the course
    $query = <<<EOD
  SELECT *
    FROM cf_catalog
   WHERE discipline = '$discipline'
     AND course_number = '$course_number'
ORDER BY course_id

EOD;

    $result = pg_query($curric_db, $query) or die('Unable to access course catalog');

    //  It's possible there will be multiple rows returned, either because of multiple
    //  components for a single ID, or because of duplicated unique course IDs in CF.
    //  Merge records for a single ID, and return the resulting array.
    $course_ids = array();
    while ($row = pg_fetch_assoc($result))
    {
      $this_course_id = $row['course_id'];
      $this_component = $row['component'];
      $this_hours     = $row['hours'];
      if ( isset($course_ids[$this_course_id]) )
      {
        //  existing course id: merge component into it
        if ( 0.0 !==
             $returnVal[$course_ids[$this_course_id]]->components[$this_component]->hours)
        {
        //  Component is repeated, it is a CUNYfirst problem unless there was a mistake in
        //  generating cf_catalog: this should not stop the user, so we just send email to
        //  webmaster and overwrite existing component value.
          $file = basename(__FILE__);
          $line = __LINE__;
          $msg = <<<EOD
Error:
File $file line $line: multiple cf_catalog records for $discipline $course_number
$this_component.

Please report this problem to $webmaster_email.

EOD;
          error_log($msg, 1, $webmaster_email, "From: $webmaster_email\r\n");
        }
        $returnVal[$course_ids[$this_course_id]]->components[$this_component]->hours =
            $this_hours;
      }
      else
      {
        //  new-found course id: create a new Course object for it
        $course_ids[$this_course_id] = count($returnVal);
        $returnVal[] = new Course($row);
      }
    }
    return $returnVal;
  }

//  lookup_offerings()
//  ---------------------------------------------------------------------------
/*  Return a string of table rows giving the number of sections, seats, and
 *  enrollment for terms in which the course has been or is scheduled to be
 *  offered.
 *  Returns an empty string if there are none.
 */
 function lookup_offerings($discipline, $course_number)
 {
    global $curric_db, $enrollment_terms;
    $returnVal = '';
    $query = <<<EOD
select * from offered_courses
where discipline = '$discipline'
  and to_char(course_number, 'FM999999') ~* '^$course_number\$'
  order by course_number, term_code
EOD;
    $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query failed: "
        . basename(__FILE__) . " line " . __LINE__
        . "</h1></body></html>\n");
    while ($row = pg_fetch_assoc($result))
    {
      $term_name = $enrollment_terms[$row['term_code']];
      $returnVal .= "<tr><td style='text-align:left;'>$term_name</td>"
                    . "<td>{$row['course_number']}</td>"
                    . "<td>{$row['sections']}</td>"
                    . "<td>{$row['seats']}</td>"
                    . "<td>{$row['enrollment']}</td></tr>";
    }

      return $returnVal;
   }

//  lookup_designations()
//  ---------------------------------------------------------------------------
function lookup_designations($discipline, $course_number)
{
  global $curric_db;
  //  Look up all designations for which this course has already been approved.
  //  Return a string with table rows for each one, if any.
  $returnVal = '';
  $pure_course_number = trim($course_number, " WHwh");
  $query = <<<EOD
select t.abbr, t.full_name, m.reason
from course_designation_mappings m, proposal_types t
where m.designation_id = t.id
and m.discipline = '$discipline'
and m.course_number = '$pure_course_number'

EOD;
  $result = pg_query($curric_db, $query) or die("<h1 class='error'>Query failed: "
        . basename(__FILE__) . " line " . __LINE__
        . "</h1></body></html>\n");
  while ($desig = pg_fetch_assoc($result))
  {
    $desig_abbr = $desig['abbr'];
    $desig_name = $desig['full_name'];
    if (in_array($desig_abbr, array('LIT', 'LANG', 'SCI', 'COPT4')))
    {
      $desig_name = 'College Option ' . $desig_name;
    }
    switch ($desig['reason'])
    {
      case 'LIST':  $desig_reason = 'Senate-approved list';
                    break;
      case 'STEM':  $desig_reason = 'STEM variant';
                    break;
      case 'PLAS':  $desig_reason = 'Perspectives (PLAS)';
                    break;
      case 'CCRC':
      case 'OAA':   $desig_reason = 'CUNY-approved';
                    break;
      case 'LANG':
      case 'LIT':
      case 'SCI':
      case 'SYN':   $desig_reason = 'Senate-approved College Option proposal';
                    break;
      case 'RL':    $desig_reason = 'Perspectives RL course';
                    break;
      case 'NS':
      case 'NS+L':  $desig_reason = 'Perspectives NS or NS+L course';
                    break;
      case 'LPS':
      case 'SW':    $desig_reason = 'LPS or SW course';
                    break;

      default: die('Bad switch at ' . basename(__FILE__) . ' line ' . __LINE__ .
                   ". Please notify $webmaster_email");
    }

    $returnVal .= "<tr><th>$desig_abbr</th><td>$desig_name</td><td>$desig_reason</td></tr>\n";
  }
  return $returnVal;
}



//  title_case()
//  ---------------------------------------------------------------------------
/*  Capitalize first letter of words except common articles, prepositions, and
 *  conjunctions that are surrounded by spaces.
 *  Note: quick, dirty, and incomplete! Needs full substitution list and more
 *  efficient correction algorithm to be useful.
 */
  function title_case($str)
  {
    $articles_etc = array(
      ' a ', ' an ', ' the ',
      ' of ', ' in ', ' on ',
      ' and ', ' or ',
      ' to ', ' for ', ' with ', ' into ', ' and/or '
    );
    $returnVal = ucwords(strtolower($str));
    foreach ($articles_etc as $word)
    {
      $returnVal = str_replace(ucwords($word), $word, $returnVal);
    }
    return $returnVal;
  }

  //  and_list()
  //  ----------------------------------------------------------------------
  /*  Returns a comma-separated list of array elements, with the last item
   *  preceded by "and."
   */
   function and_list($elements)
   {
     $n = count($elements);
     switch ($n)
     {
       case 0:
           return "";
           break;
       case 1:
           return $elements[0];
           break;
       case 2:
           return $elements[0] . " and " . $elements[1];
           break;
       default:
           $str = $elements[0];
           for ($i = 1; $i < $n - 1; $i++)
           {
             $str .= ", " . $elements[$i];
           }
           return $str . ", and " . $elements[$n -1];
     }
   }

?>
