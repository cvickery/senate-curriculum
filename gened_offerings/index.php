<?php
//  Curriculum/gened_offerings/index.php

  session_start();
  date_default_timezone_set('America/New_York');
  require_once('scripts/utils.php');

  /*  This is a 3-way merge of:
   *    (a) the PostgreSQL 'gened' database,
   *    (b) a CSV download from a CU_SR_CLASS_ENRL_BY_TERMS query, externally saved as
   *    table offerings in SQLite3 database, hist_enrollments.db
   *    (c) a SQLite3 capture of current registration data from
   *    octsims.erp805_class_section, built by cron job curr_enrollments.php and saved in
   *    YYYY-MM-DD_enrollments.db, again using table name 'offerings.'
   *
   *    The main content difference between the two databases is that the curr (current)
   *    database includes section size (seats) information, and the hhist (history)
   *    database does not.
   *
   *  HEADS UP: 'curr' always means "current," not "curriculum." That is, it's related to
   *  the 805 dump data.
   */

  //  History db
  //  -----------------------------------------------------------------------------------
  /*  Scan the history database to create a list, hist_semesters, of English language
   *  translations of terms. Term codes are in a chronologically sequenced column derived
   *  from CUNYfirst term and session codes, as documented in scripts/utils.php, where the
   *  Term class is implemented.
   *
   *  There is precisely one history database, and it's name is hist_enrollments.db. But
   *  there may be multiple CU_SR_CLASS_ENRL_BY_TERMS lying around, each prefixed with the
   *  date the query was run.
   */
    $hist_db = new SQLite3('db/hist_enrollments.db')
        or die("Unable to open history database.\n");
    $hist_semesters = array();
    $result = $hist_db->query("SELECT term_code, term_name, term_abbr " .
        "FROM offerings GROUP BY term_code ORDER BY term_code");
    while ($row = $result->fetchArray())
    {
      $hist_semesters[$row['term_abbr']] = $row['term_name'];
    }
    $hist_date = date('F j, Y', filemtime('db/hist_enrollments.db'));

  //  Current db
  //  ------------------------------------------------------------------------------------
  /*  Create the list of semesters available in the current YYYY-MM-DD_enrollments.db
   *  database.
   *
   *  Don't get confused by the date prefix: we keep a series of current enrollment data
   *  so we can look (elsewhere) at the time course of registrations, but only the most
   *  recent one is used for this web page.  See above about the date-prefixed CUNYfirst
   *  queries, which actually could all be discarded and regenerated on demand.
   */
    $dir = opendir('db');
    $file = '';
    while ($candidate = readdir($dir))
    {
      //  overkill regex to recognize the file name as a candidate
      if (preg_match('/^\s*\d{4}-\d{2}-\d{2}_enrollments.db\s*$/', $candidate))
      {
        if (substr($candidate, 0, 10) > substr($file, 0, 10))
        {
          $file = $candidate;
        }
      }
    }
    $curr_date = new DateTime(substr($file, 0, 10));
    $curr_date = $curr_date->format('F j, Y');
    $curr_db = new SQLite3("./db/$file");
    $curr_semesters = array();
    $result = $curr_db->query("SELECT term_code, term_name, term_abbr " .
        "FROM offerings GROUP BY term_code ORDER BY term_code");
    while ($row = $result->fetchArray())
    {
      $curr_semesters[$row['term_abbr']] = $row['term_name'];
    }

  //  GenEd db
  //  ------------------------------------------------------------------------------------
    require_once('credentials.inc');
    $gened_db = gened_connect();
    //  Set up discipline/dept/division cache
    $query =  "SELECT disciplines.abbreviation AS discp,"                 .
              "departments.short_name as dept,"                   .
              " deans.division_abbreviation as div"               .
              "  FROM disciplines, departments, deans"            .
              " WHERE departments.id = disciplines.department_id" .
              "   AND deans.id = disciplines.dean_id";
    $result = pg_query($gened_db, $query);
    $depts  = array();
    $divs   = array();
    $discps = array();
    while ( $row = pg_fetch_assoc($result) )
    {
      $discps[]             = $row['discp'];
      $depts[$row['discp']] = $row['dept'];
      $divs[$row['discp']]  = $row['div'];
    }

    //  Set up areas cache
    $areas = array();
    $query = <<<EOD
  SELECT abbreviation
    FROM requirements
   WHERE requirement_type_id = (SELECT id
                                  FROM requirement_types
                                  WHERE abbreviation = 'Area')
   ORDER BY abbreviation

EOD;
    $result = pg_query($gened_db, $query);
    while ($row = pg_fetch_assoc($result))
    {
      $areas[] = $row['abbreviation'];
    }

    //  Get date of latest approval event
    $query = "SELECT max(date) as gened_date FROM events " .
      "WHERE agent_id = (SELECT id FROM actors WHERE name = 'Academic Senate') " .
      "  AND action_id = (SELECT id FROM actions WHERE name = 'Approved') ";
    $result = pg_query($gened_db, $query);
    $row = pg_fetch_assoc($result);
    $gened_date = new DateTime($row['gened_date']);
    $gened_date = $gened_date->format('F j, Y');

  //  sims2cf()
  //  -----------------------------------------------------------------------------------
  /*  Convert a sims course number (as found in events.number) to a CF course number (no
   *  leading zeros, no decimal points), mostly for cosmetic reasons.
   */
    function sims2cf($sims)
    {
      return ltrim(str_replace('.', '', $sims), '0');
    }

  //  Class Course
  //  -----------------------------------------------------------------------------------
  /*  All the information for one row of the generated table.  Use setters for
   *  assigning/updating members; read them directly.  Note: counters (sections, seats,
   *  and enrollments) are initialized to empty strings to indicate 'not scheduled'.
   *  Incrementing the empty string makes it into a number because the PHP language is so
   *  awsome.
   */
    class Course
    {
      public $division, $department, $discipline, $number, $course, $link, $is_new, $area,
        $context_1, $context_2, $is_w, $is_qr, $capsyn,
        $curr_schedules, $hist_schedules, $proposal_id;

      function __construct($discp, $num, $is_new, $proposal_id = null)
      {
        global $depts, $divs, $proposal_ids;
        preg_match("/(\d+[WH]?)/", $num, $matches);
        $padded_num = str_pad($matches[1], 4, ' ', STR_PAD_LEFT);
        $padded_discp = str_pad($discp, 6);

        $this->department   = $depts[$discp];
        $this->division     = $divs[$discp];
        $this->discipline   = $discp;
        $this->number       = $num;
        $this->course       = $padded_discp . ' ' . $padded_num;
        $this->proposal_id  = $proposal_id;
        if ($proposal_id)
        {
          $href = "href='http://senate.qc.cuny.edu/GEAC/Proposals?p=$proposal_id'";
          $this->link       = "<a $href>{$this->course}</a>";
        }
        else
        {
          $this->link = $this->course;
        }
        $this->is_new     = $is_new;
        $this->is_w = (substr($num, -1) === 'W');
        $this->is_qr      = false;
        $this->area = $this->context_1 = $this->context_2 = $this->capsyn = '';
        $this->curr_schedules = array(); // of Schedule
        $this->hist_schedules = array(); // of Schedule
        $this->curr_sums = array(); // of Schedule
        $this->hist_sums = array(); // of Schedule
      }
      function set_qr() { $this->is_qr = true; }
      function set_area($a) { $this->area = $a; }
      function set_context_1($c) { $this->context_1 = $c; }
      function set_context_2($c) { $this->context_2 = $c; }
      function set_capsyn($c)
      {
        //  Expected forms: '', 'Cap', 'Syn', 'Cap/Syn'
        if ($this->capsyn === '') $this->capsyn = $c;
        else if ($this->capsyn === 'Cap') $this->capsyn .= '/' . $c;
        else $this->capsyn = $c . '/' . $this->capsyn;
      }

      //  Current schedules setters
      function inc_curr_seats($n)
      {
        if (! isset($this->curr_schedules[$term]))
        {
          $this->curr_schedules[$term] = new Schedule();
        }
        if (! isset($this->curr_sums[$term]))
        {
          $this->curr_sums[$term] = new Schedule();
        }
        $this->curr_schedules[$term]->seats += $n;
        $this->curr_sums[$term]->seats += $n;
      }
      function inc_curr_enroll($term, $n)
      {
        if (! isset($this->curr_schedules[$term]))
        {
          $this->curr_schedules[$term] = new Schedule();
        }
        if (! isset($this->curr_sums[$term])) $this->curr_sums[$term] = new Schedule();
        $this->curr_schedules[$term]->enrollment += $n;
        $this->curr_sums[$term]->enrollment += $n;
      }
      function inc_curr_sects($term)
      {
        if (! isset($this->curr_schedules[$term]))
        {
          $this->curr_schedules[$term] = new Schedule();
        }
        if (! isset($this->curr_sums[$term])) $this->curr_sums[$term] = new Schedule();
        $this->curr_schedules[$term]->sects++;
        $this->curr_sums[$term]->sects++;
      }
      //  History schedules setters
      function inc_hist_seats($n)
      {
        if (! isset($this->hist_schedules[$term]))
        {
          $this->hist_schedules[$term] = new Schedule();
        }
        if (! isset($this->hist_sums[$term])) $this->hist_sums[$term] = new Schedule();
        $this->hist_schedules[$term]->seats += $n;
        $this->hist_sums[$term]->seats += $n;
      }
      function inc_hist_enroll($term, $n)
      {
        if (! isset($this->hist_schedules[$term]))
        {
          $this->hist_schedules[$term] = new Schedule();
        }
        if (! isset($this->hist_sums[$term])) $this->hist_sums[$term] = new Schedule();
        $this->hist_schedules[$term]->enrollment += $n;
        $this->hist_sums[$term]->enrollment += $n;
      }
      function inc_hist_sects($term)
      {
        if (! isset($this->hist_schedules[$term]))
        {
          $this->hist_schedules[$term] = new Schedule();
        }
        if (! isset($this->hist_sums[$term])) $this->hist_sums[$term] = new Schedule();
        $this->hist_schedules[$term]->sects++;
        $this->hist_sums[$term]->sects++;
      }
    }

  //  Class Schedule
  //  -----------------------------------------------------------------------------------
  /*  Number of sections, seats, and enrollment for a course, broken down by component
   *  type, for one term. Corresponds to scheduled column groups in the generated table.
   *  A component is a type of class meeting: lecture, lab, etc. One course could have
   *  multiple components, but this is not implemented in CF at this time, so the logic to
   *  list all the components for a course is untested.
   */
  class Schedule
  {
    public $components;
    function __construct()
    {
      global $components;
      $this->components = array();
      foreach ($components as $component)
      {
        $this->components[$component] =  new Component();
      }
    }

    //  init()
    //  ---------------------------------------------------------------------------------
    /*  These objects get re-used across rows of the generated table, so here's a reset
     *  for them:
     */
    function init()
    {
      foreach ($this->components as $type => $component)
      {
        $this->components[$type]->sections =
            $this->components[$type]->seats =
            $this->components[$type]->enrollment = '';
      }
    }
  }

  //  Class Component
  //  -----------------------------------------------------------------------------------
  /*  "Component" is the CUNYfirst name for a course component, such as lecture, lab, etc.
   *  QC does not use this feature correctly: all courses consist of a single component
   *  with jerry-built co-requisites to link the components of a single course. This needs
   *  to change, and when it does, this code will be ready to celebrate the happy day.
   *
   *  This class holds counts of instances, seats, and enrollment for a course component.
   *  It is used both by Schedule (above) to hold counts for a single scheduled course and
   *  by Sums (below) to hold sums across departments and requirement areas.
   */
    class Component
    {
      public $sections, $seats, $enrollment;
      function __construct()
      {
        $this->sections = $this->seats = $this->enrollment = '';
      }
    }

  //  Class Sum
  //  ---------------------------------------------------------------------------------
  /*  For summing scheduling/enrollment data by discipline and area.
   *    Sums by division and department can be derived from here the two arrays of Sums
   *    (sums_by_area and sums_by_discipline);
   */
    class Sum
    {
      public  $which_item;  // Name of area or discipline
      private $components;  // Sums for Components

      //  __construct()
      function __construct($item)
      {
        //  Sanity check: the name of the discipline or area has to be given when the sum
        //  is instantiated; the same name has to be given when updating the component
        //  counts.

        global $components;

        $this->which_item = $item;
        $this->components = array(); // of components
        foreach ($components as $component)
        {
          $this->components[$component] = new Component();
        }
      }
      //  Getters
      function get_component($component)
      {
        return $this->components[$component];
      }
      function get_components()
      {
        return $this->components;
      }
      //  Setters
      function inc_sections($which_item, $component, $amount)
      {
        if ($which_item !== $this->which_item)
        {
          throw new Exception("inc_sections: $which_item is not {$this->which_item}");
        }
        $this->components[$component]->sections += $amount;
      }
      function inc_seats($which_item, $component, $amount)
      {
        if ($which_item !== $this->which_item)
        {
          throw new Exception("inc_sections: $which_item is not {$this->which_item}");
        }
        $this->components[$component]->seats += $amount;
      }
      function inc_enrollment($which_item, $component, $amount)
      {
        if ($which_item !== $this->which_item)
        {
          throw new Exception("inc_sections: $which_item is not {$this->which_item}");
        }
        $this->components[$component]->enrollment += $amount;
      }
    }

    //  Class Semester
    //  ---------------------------------------------------------------------------------
    /*  Holds a semester's worth of Sums. Includes a flag to indicate whether it is a
     *  current semster (has seats info) or a history semester (no seats info).
     */
      define("IS_HIST", 0);
      define("IS_CURR", 1);
      class Semester
      {
        public $sums_by_area, $sums_by_dept, $semester_type;

        function __construct($type)
        {
          global $areas, $discps;
          if ($type === IS_HIST) $this->semester_type = IS_HIST;
          else if ($type === IS_CURR) $this->semester_type = IS_CURR;
          else throw Exception("Invalid semester type: $type");
          $this->sums_by_area = array();
          foreach ($areas as $area)
          {
            $this->sums_by_area[$area] = new Sum($area);
          }
          $this->sums_by_discipline = array();
          foreach ($discps as $discipline)
          {
            $this->sums_by_discipline = new Sum($discipline);
          }
        }
      }
    //  Master arrays of Semesters' data
    $curr_semester_sums = array();
    $hist_semester_sums = array();

  //  Generate the page
  //  -----------------------------------------------------------------------------------
  $mime_type = "text/html";
  $html_attributes="lang=\"en\"";
  if ( array_key_exists("HTTP_ACCEPT", $_SERVER) &&
        (stristr($_SERVER["HTTP_ACCEPT"], "application/xhtml") ||
         stristr($_SERVER["HTTP_ACCEPT"], "application/xml") )
       ||
       (array_key_exists("HTTP_USER_AGENT", $_SERVER) &&
        stristr($_SERVER["HTTP_USER_AGENT"], "W3C_Validator"))
     )
  {
    $mime_type = "application/xhtml+xml";
    $html_attributes = "xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\"";
    header("Content-type: $mime_type");
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
  }
  else
  {
    header("Content-type: $mime_type; charset=utf-8");
  }
?>
<!DOCTYPE html>
<html <?php echo $html_attributes;?>>
  <head>
    <title>Perspective Course Offerings</title>
    <link rel="icon" href="../../GEAC/images/GEAC_Logo.ico" />
    <link rel="stylesheet" type="text/css" href="../css/blue/style.css" />
    <link rel="stylesheet" type="text/css" href="../css/gened_offerings.css" />
    <script type="text/javascript" src="../../js/jquery-current.js"></script>
    <script type="text/javascript" src="../scripts/jquery.tablesorter.js"></script>
    <script type="text/javascript" src="js/gened_offerings.js"></script>
  </head>
  <body>
    <h1>Perspectives (PLAS) Course Offerings</h1>
    <div id="instructions">
      <h2>
        Use this page to examine scheduling and enrollment information for Perspectives
        courses.
      </h2>
      <p>
        It might help to understand that the information available here comes from three
        different sources, which are updated in different ways:
      </p>
      <ol>
        <li>
          <h4>The QC Curriculum Database</h4>
          <p>
            After the Academic Senate approves a course for a GenEd requirement, the
            database is updated (manually) to add the course to the list of courses
            displayed here. If it is a new course, it also requires Board of Trustees
            (BOT) approval before it can be scheduled. Courses that require BOT approval
            are marked with “yes” in the “New?” column.  Entering BOT approvals is another
            manual process, so a course that has been approved by the Senate but not
            scheduled could either be awaiting BOT approval and/or it might simply be that
            the department has elected not to offer it.
          </p>
          <h5>
            Last Academic Senate approval recorded: <?php echo $gened_date;?>
          </h5>
        </li>
        <li class='curr-info'>
          <h4>Current Enrollment Data</h4>
          <p>
            Enrollment data for the current semester includes information about the total
            number of seats for each course and the number of sections, in addtion to the
            current total enrollment.
          </p>
          <p>
            This information is taken from CUNYfirst registration data that is transferred
            to the college each night during the week.  This page always uses the latest
            version of that information that is available.
          </p>
          <p>
            “Current” data is a relative term: information is available for the current
            semester as well as others, both past and future. The current range of
            semesters available is indicated by the list of checkboxes below.
          </p>
          <h5>
            Last updated: <?php echo $curr_date;?>
          </h5>
        </li>
        <li class='hist-info'>
          <h4>Historical Data</h4>
          <p>
            Historical data is like current data, only it’s historical. And, it doesn’t
            include “seats” information (the maximum class sizes). The range of historical
            semesters overlaps the range of current semesters, which can be used for basic
            consistency checking.
          </p>
          <p>
            Historical data is imported directly from CUNYfirst, but it requires human
            intervention to get updated here. Again, the range of semesters available to
            choose from is given in the list of checkboxes below.
          </p>
          <h5>
            Last updated: <?php echo $hist_date; ?>
          </h5>
        </li>
      </ol>
      <button id='hide-show-instructions' disabled='disabled'>
        Hide These Instructions
      </button>
    </div>
    <form id='display-options' method='post' action='.'>
      <fieldset class="curr-info has-checkboxes">
      <p>
        <strong>Current Data</strong> are available for the following terms: click the
        ones you want to include:
      </p>
      <?php
        $csv_schedule_cols = '';

        $curr_schedules       = array(); // of type Schedule
        $curr_schedule_cols   = '';
        $curr_schedule_terms  = '';
        foreach ($curr_semesters as $abbr => $semester)
        {
          $value = 'curr' . str_replace(' ', '-' , strtolower($semester));
          $semester = str_replace(' ', '&#160;', $semester);
          $checked = '';
          if (isset($_POST[$value]))
          {
            $csv_schedule_cols .= ", $abbr Sects, $abbr Seats, $abbr Enroll";
            $curr_schedules[$abbr] = new Schedule();
            $curr_semester_sums[$abbr] = new Semester(IS_CURR);
            $checked = " checked='checked'";
            $curr_schedule_cols .=
              "<th>$abbr Sects</th><th>$abbr Seats</th><th>$abbr Enroll</th>";
            $curr_schedule_terms = ($curr_schedule_terms === '')
                ? " term_abbr = '$abbr'" : " OR term_abbr = '$abbr' ";
          }
          echo<<<END_SEM
          <div>
            <input type='checkbox' name='$value' id='$value'$checked/>
            <label for='$value'>$semester</label>
          </div>

END_SEM;
        }
        ?>
      </fieldset>
      <fieldset class="hist-info has-checkboxes">
      <p>
        <strong>Historical Data</strong> are available for the following terms: click the
        ones you want to include:
      </p>
      <?php
        $hist_schedules = array(); // of type Schedule
        $hist_schedule_cols = '';
        $hist_schedule_terms  = '';
        foreach ($hist_semesters as $abbr => $semester)
        {
          $value = 'hist' . str_replace(' ', '-' , strtolower($semester));
          $semester = str_replace(' ', '&#160;', $semester);
          $checked = '';
          if (isset($_POST[$value]))
          {
            $csv_schedule_cols .= ", $abbr Sects, $abbr Enroll";
            $hist_schedules[$abbr] = new Schedule();
            $hist_semester_sums[$abbr] = new Semester(IS_HIST);
            $checked = " checked='checked'";
            $hist_schedule_cols .= "<th>$abbr Sects</th><th>$abbr Enroll</th>";
            $hist_schedule_terms = ($hist_schedule_terms === '')
                ? " term_abbr = '$abbr'" : " OR term_abbr = '$abbr' ";
          }
          echo<<<END_SEM

          <div>
            <input type='checkbox' name='$value' id='$value'$checked/><label
            for='$value'>$semester</label>
          </div>
END_SEM;
        }
        //  Heuristic: if more than 3 semesters, let script run longer
        if (count($hist_schedules) > 3)
        {
          set_time_limit(60);
          echo "<p class='warning'>This could take a while. Please be patient.</p>\n";
        }
        ?>
      </fieldset>
      <fieldset class='has-checkboxes'>
        <legend>Options</legend>
        <?php
        $is_e110_checked = isset($_POST['include-e110']) ? " checked='checked'" : '';
        $is_m110_checked = isset($_POST['include-m110']) ? " checked='checked'" : '';
        $exclude_no_proposal =
                     isset($_POST['exclude-no-proposal']) ? " checked='checked'" : '';
        ?>
        <div>
          <input type='checkbox'
                 id='include-e110' name='include-e110'<?php echo $is_e110_checked;?>/>
          <label for='include-e110'>Include English 110</label>
        </div>
        <div>
          <input type='checkbox'
                 id='include-m110' name='include-m110'<?php echo $is_m110_checked;?>/>
          <label for='include-m110'>Include Math 110</label>
        </div>
        <div>
          <input type='checkbox' id='exclude-no-proposal' name='exclude-no-proposal'
            <?php echo $exclude_no_proposal;?>/>
          <label for='exclude-no-proposal'>
            Exclude courses without proposals
          </label>
        </div>
      </fieldset>
      <button type='submit'>Regenerate Tables</button>
    </form>
  <div id='detail-table'>
    <form method="post" action="../scripts/download_csv.php">
      <button type="submit" name="download">Download this table as a CSV file</button>
    </form>
    <table id="the-list" class="tablesorter">
      <caption>Details</caption>
      <thead class='vickery'>
        <tr>
          <th>Division</th>
          <th>Department</th>
          <th>Course</th>
          <th>New?</th>
          <th>Area</th>
          <th>Context-1</th>
          <th>Context-2</th>
          <th>W?</th>
          <th>QR?</th>
          <th>Cap/Syn</th>
          <?php echo "$curr_schedule_cols$hist_schedule_cols"; ?>
        </tr>
      </thead>
      <tbody>
    <?php
      $csv =  "Division, Department, Discipline, Number, New?, Area, Context-1, " .
              "Context-2, W?, QR?, Cap/Syn" .
              "$csv_schedule_cols\n";

      //  Populate array of Course objects from Senate approval events
      //  ---------------------------------------------------------------------
      /*  Contrary to original plans, area-free contexts are always included.
       */
      $courses = array(); //  Course objects, one per GenEd course.
      if (isset($_POST['include-e110']))
      {
        $courses['ENGL 110'] = new Course('ENGL', '110', false);
      }
      if (isset($_POST['include-m110']))
      {
        $courses['MATH 110'] = new Course('MATH', '110', false);
      }
      $query = <<<END_QUERY
SELECT  p.id,
        e.discipline,
        e.number              AS course_number,
        q.abbreviation        AS requirement,
        t.abbreviation        AS requirement_type,
        requires_bot_approval AS bot
 FROM   events            e LEFT JOIN proposals p ON p.id = proposal_id,
        requirements      q,
        requirement_types t
WHERE  action_id = (SELECT id FROM actions WHERE name = 'Approved')
  AND  agent_id  = (SELECT id FROM actors  WHERE name = 'Academic Senate')
  AND  q.id      = requirement_id
  AND  t.id      = requirement_type_id

END_QUERY;
      $result = pg_query($gened_db, $query);
      while ( $row = pg_fetch_assoc($result) )
      {
        $discipline = $row['discipline'];
        $course_number = sims2cf($row['course_number']);
        $course = "$discipline $course_number";
        $is_new = ($row['bot'] === 't');
        $proposal_id = $row['id'];
        if (! isset($courses[$course]) )
        {
          $courses[$course] =
            new Course($discipline, $course_number, $is_new, $proposal_id);
        }
        $requirement_abbr = $row['requirement'];
        $requirement_type = $row['requirement_type'];
        switch ($requirement_type)
        {
          case 'Area':
            $courses[$course]->set_area($requirement_abbr);
            break;
          case 'Context':
            $courses[$course]->set_context_1($requirement_abbr);
            break;
          case 'Combinable':
            $courses[$course]->set_context_2($requirement_abbr);
            break;
          case 'A/QR':
            $courses[$course]->set_qr();
            break;
          case 'CAPSYN':
            $courses[$course]->set_capsyn($requirement_abbr);
            break;
          //  Ignore these approvals because we have no data for them:
          case 'W':
          case 'HEGIS':
          case 'Other':
            break;
          default:
            die ("Unrecognized requirement type: $requirement_type");
        }
      }

      //  Generate a table row for each approved GenEd course
      //  ------------------------------------------------------------------
      foreach ($courses as $course)
      {
        if ($exclude_no_proposal && $course->proposal_id === null) continue;
        //  Info from GenEd database
        $is_new = $course->is_new ? 'yes' : '';
        $is_w = $course->is_w ? 'W' : '';
        $is_qr = $course->is_qr ? 'QR' : '';
        echo <<<END_GENED
          <tr>
            <td>$course->division</td>
            <td>$course->department</td>
            <td class='mono'>$course->link</td>
            <td>$is_new</td>
            <td>$course->area</td>
            <td>$course->context_1</td>
            <td>$course->context_2</td>
            <td>$is_w</td>
            <td>$is_qr</td>
            <td>$course->capsyn</td>
END_GENED;
        $csv .= "$course->division, $course->department, $course->discipline, " .
                "$course->number, " .
                "$is_new, $course->area, $course->context_1 , $course->context_2, " .
                "$is_w, $is_qr, $course->capsyn";


        //  Curr columns
        //  ------------------------------------------------------------------------------
        /*  Note: Honors College courses corresponding to GEAC-approved courses (ENGL 165H
         *  for 165W, etc. are not stored in the database.  It might be possible to make
         *  up a rule to display them here, but (a) there are only a few of them, and (b)
         *  the actual supporting data structure has not been thought through at this
         *  time.
         */
        foreach ($curr_schedules as $abbr => $schedule)
        {
          $schedule->init();
          $query = <<<END_QUERY
 SELECT * FROM offerings
  WHERE term_abbr     = '$abbr'
    AND discipline    = '{$course->discipline}'
    AND course_number = '{$course->number}'
    AND status        = 'A'
END_QUERY;
          $result = $curr_db->query($query);
          while ( $row = $result->fetchArray())
          {
            $component = $row['component'];
            if (! isset($schedule->components[$component]))
            {
              die("unexpected course component: $component");
            }
            $schedule->components[$component]->sections++;
            $schedule->components[$component]->seats += $row['seats'];
            $schedule->components[$component]->enrollment += $row['enrollment'];
          }
          $cell_sections = $cell_seats = $cell_enrollment = '' ;
          $csv_sections = $csv_seats = $csv_enrollment = '';
          foreach ($components as $component)
          {
            if ($schedule->components[$component]->sections)
            {
              $value = $schedule->components[$component]->sections;
              $value_str =
                  str_pad($value, 2, '0', STR_PAD_LEFT);
              $cell_sections .= "<div><span class='cell-component'>$component </span>" .
                                "<span class='cell-value'>$value_str</span></div>";
              $nl = $csv_sections ? "\n" : '';
              $csv_sections .= "$component $value_str$nl";
              if ($course->area)
              {
                $curr_semester_sums[$abbr]->sums_by_area[$course->area]->
                    inc_sections($course->area, $component, $value);
              }
            }
            if ($schedule->components[$component]->seats)
            {
              $value      = $schedule->components[$component]->seats;
              $value_str  = str_pad($value, 4, 0, STR_PAD_LEFT);
              $cell_seats .= "<div><span class='cell-component'>$component</span>" .
                             "<span class='cell-value'>$value_str</span></div>";
              $nl = $csv_seats ? "\n" : '';
              $csv_seats .= "$component $value_str$nl";
              if ($course->area)
              {
                $curr_semester_sums[$abbr]->sums_by_area[$course->area]->
                    inc_seats($course->area, $component, $value);
              }
            }
            if ($schedule->components[$component]->enrollment)
            {
              $value      = $schedule->components[$component]->enrollment;
              $value_str  = str_pad($value, 4, '0', STR_PAD_LEFT);
              $cell_enrollment .= "<div><span class='cell-component'>$component</span>" .
                                  "<span class='cell-value'>$value_str</span></div>";
              $nl = $csv_enrollment ? "\n" : '';
              $csv_enrollment .= "$component $value_str$nl";
              if ($course->area)
              {
                $curr_semester_sums[$abbr]->sums_by_area[$course->area]->
                    inc_enrollment($course->area, $component, $value);
              }
            }
          }
          echo "<td class='curr-info'>$cell_sections</td>\n";
          echo "<td class='curr-info'>$cell_seats</td>\n";
          echo "<td class='curr-info'>$cell_enrollment</td>\n";
          $csv .= ",\"$csv_sections\",\"$csv_seats\",\"$csv_enrollment\"";
        }

        //  Hist Columns
        //  -------------------------------------------------------------------
        foreach ($hist_schedules as $abbr => $schedule)
        {
          $schedule->init();
          $query = <<<END_QUERY
 SELECT * FROM offerings
  WHERE term_abbr     = '$abbr'
    AND discipline    = '{$course->discipline}'
    AND course_number = '{$course->number}'
    AND status        = 'Active'
END_QUERY;
          $result = $hist_db->query($query);
          while ($row = $result->fetchArray())
          {
            $component = $row['component'];
            if (! isset($schedule->components[$component]))
            {
              die("unexpected course component: $component");
            }
            $schedule->components[$component]->sections++;
            $schedule->components[$component]->enrollment += $row['enrollment'];
          }
          $cell_sections = $cell_enrollment = "" ;
          $csv_sections = $csv_enrollment = "";
          foreach ($components as $component)
          {
            if ($schedule->components[$component]->sections)
            {
              $value      = $schedule->components[$component]->sections;
              $value_str  = str_pad($value, 2, '0', STR_PAD_LEFT);
              $cell_sections .= "<div><span class='cell-component'>$component</span>" .
                                "<span class='cell-value'>$value_str</span></div>";
              $nl = $csv_sections ? "\n" : "";
              $csv_sections .= "$component $value_str$nl";
              if ($course->area)
              {
                $hist_semester_sums[$abbr]->sums_by_area[$course->area]->
                    inc_sections($course->area, $component, $value);
              }
            }
           if ($schedule->components[$component]->enrollment)
            {
              $value      = $schedule->components[$component]->enrollment;
              $value_str  = str_pad($value, 4, '0', STR_PAD_LEFT);
              $cell_enrollment .= "<div><span class='cell-component'>$component</span>" .
                                  "<span class='cell-value'>$value_str</span></div>";
              $nl = $csv_enrollment ? "\n" : "";
              $csv_enrollment .= "$component $value_str$nl";
              if ($course->area)
              {
                $hist_semester_sums[$abbr]->sums_by_area[$course->area]->
                    inc_enrollment($course->area, $component, $value);
              }
            }
          }
          echo "<td class='hist-info'>$cell_sections</td>\n";
          echo "<td class='hist-info'>$cell_enrollment</td>\n";
          $csv .= ",\"$csv_sections\",\"$csv_enrollment\"";
        }
        $csv .= "\n";
        echo "</tr>\n";
      }
      $_SESSION['csv'] = $csv;
      echo <<<END_TABLE
      </tbody>
    </table>
  </div>
END_TABLE;

    //  Generate summary tables ... if there are enrollment data.
    //  --------------------------------------------------------------------------------
    /*  Here is a model for how the summary data is structured for one set of three
     *  columns in one row:
     *    curr_semester_sums['Fall11']->sums_by_area['CV']->components['LEC']->sections
     *    curr_semester_sums['Fall11']->sums_by_area['CV']->components['LEC']->seats
     *    curr_semester_sums['Fall11']->sums_by_area['CV']->components['LEC']->enrollment
     */
      if (count($curr_semester_sums) + count($hist_semester_sums) > 0)
      {
        echo "<div id='summary-tables'>\n";
        //  Sums by Area
        //  ----------------------------------------------------------------
        echo "<table id='sums-by-area'>\n<caption>Sums By Area</caption>\n";
        //  Header rows
        echo "<thead><tr><th rowspan='2'>Area</th>\n";
        $second_row = "";
        foreach (array_keys($curr_semester_sums) as $sem)
        {
          echo "<th class='curr-info' colspan='3'>$sem</th>\n";
          $second_row .=  "<th class='curr-info'>Sections</th>\n" .
                          "<th class='curr-info'>Seats</th>\n" .
                          "<th class='curr-info'>Enrollment</th>\n";
        }
        foreach (array_keys($hist_semester_sums) as $sem)
        {
          echo "<th class='hist-info' colspan='2'>$sem</th>\n";
          $second_row .=  "<th class='hist-info'>Sections</th>\n" .
                          "<th class='hist-info'>Enrollment</th>\n";
        }
        echo "</tr>\n<tr>$second_row</tr>\n</thead>\n<tbody>\n";
        //  Body rows
        foreach ($areas as $area)
        {
          echo "<tr><th scope='row'>$area</th>";
          foreach ($curr_semester_sums as $semester)
          {
            $semest_components = $semester->sums_by_area[$area]->get_components();
            $sects_str = $seats_str = $enroll_str = '';
            foreach ($components as $component)
            {
              if ($semest_components[$component]->enrollment)
              {
                $sects_str .= "<div><span class='cell-component'>$component</span>" .
                              "<span class='cell-value'>" .
                              $semest_components[$component]->sections .
                              "</span></div>";
                $seats_str .= "<div><span class='cell-component'>$component </span>" .
                              "<span class='cell-value'>{$semest_components[$component]->seats}" .
                              "</span></div>";
                $enroll_str .= "<div><span class='cell-component'>$component </span>" .
                               "<span class='cell-value'>" .
                               $semest_components[$component]->enrollment .
                               "</span></div>";
              }
            }
            echo "<td class='curr-info'>$sects_str</td>\n";
            echo "<td class='curr-info'>$seats_str</td>\n";
            echo "<td class='curr-info'>$enroll_str</td>\n";
          }
          foreach ($hist_semester_sums as $semester)
          {
            $semest_components = $semester->sums_by_area[$area]->get_components();
            $sects_str = $enroll_str = '';
            foreach ($components as $component)
            {
              if ($semest_components[$component]->enrollment)
              {
                $sects_str .= "<div><span class='cell-component'>$component </span>" .
                              " <span class='cell-value'>" .
                              $semest_components[$component]->sections .
                              "</span></div>";
                $enroll_str .= "<div><span class='cell-component'>$component</span>" .
                               "<span class='cell-value'>" .
                               $semest_components[$component]->enrollment .
                               "</span></div>";
              }
            }
            echo "<td class='hist-info'>$sects_str</td>\n";
            echo "<td class='hist-info'>$enroll_str</td>\n";
          }
          echo "</tr>\n";
        }
        echo "</tbody></table></div>\n";

        //  Sums by Department/Division
        //  ------------------------------------------------------------------------
        /*  TODO
         */
      }
      echo <<<END_DOC
  </body>
  <script type='text/javascript'>
  $('.warning').hide(0.5);
  </script>
</html>
END_DOC;
    pg_close($gened_db);
    $curr_db->close();
    $hist_db->close();
?>

