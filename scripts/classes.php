<?php
//  .../Curriculum/scripts/classes.php

/*  Note: This module depends on .../Curriculum/include/atoms.inc
 */

if (! isset($curric_db))
{
  $curric_db = curric_connect() or die('Unable to access curriculum db');
}

//  Contact
//  ===========================================================================
/*  Contact information (name and email) for someone, such as a dean or dept
 *  chair.
 */
  class Contact
  {
    public $name, $email;
    function __construct($name, $email)
    {
      $this->name   = $name;
      $this->email  = $email;
    }
  }

//  Person
//  =====================================================================================
/*  Used to manage the login process for a person.
 *
 *  A Person is conceived when he or she provides a valid QC email address, the other
 *  members are filled in at various stages of the login process.
 *
 *  The department_names array is used only if the person has to be looked up in the
 *  octsims.erp856 table, and multiple departments are returned.
 *
 *  The affiliations array is used to determine which roles the person may assume; each
 *  row comes from a query of the curric.agency_members table.
 *
 *  The has_reviews boolean tells whether the person will see the proposal review editor.
 *
 *  The set_name(), set_dept(), and complete_login() functions must all be called to log a
 *  person in. The append_dept() function is used only if the octsims.erp856 table has to
 *  be used.
 */
  class Person
  {
    public $name, $email, $dept_name, $affiliations,
           $last_login_time, $last_login_ip, $has_reviews, $roles;

    //  __construct()
    //  ---------------------------------------------------------------------------------
    function __construct($email)
    {
      $this->email            = $email;
      $this->name             = null;
      $this->dept_name        = null;
      $this->affiliations     = array();
      $this->last_login_time  = null;
      $this->last_login_ip    = null;
      $this->has_reviews      = null;
      $this->roles            = array();
    }

    //  set_name()
    //  ---------------------------------------------------------------------------------
    function set_name($name)
    {
      $this->name = $name;
    }

    //  set_dept()
    //  ---------------------------------------------------------------------------------
    function set_dept($dept)
    {
      $this->dept_name = $dept;
    }

    //  finish_login()
    //  ---------------------------------------------------------------------------------
    /*  Set agency memberships and resulting role(s). Update last login info.
     */
      function finish_login()
      {
        global $curric_db;

        //  Get last login audit info
        //  -------------------------
        $query = <<<EOD
  SELECT last_login_time, last_login_ip
    FROM people
   WHERE lower(qc_email) = lower('{$this->email}')

EOD;
        $result = pg_query($curric_db, $query) or die("Query failed: " .
          pg_last_error($curric_db) . ' at ' . basename( __FILE__) . ' ' . __LINE__);
        //  Exactly one row must be returned: you can't get here unless the email address
        //  is in curric.people, where it is the primary_key.
        assert('pg_num_rows($result) === 1');
        $row = pg_fetch_assoc($result);
        $this->last_login_time  = substr($row['last_login_time'], 0, 16);
        $this->last_login_ip    = $row['last_login_ip'];

        //  Get agency affiliations
        //  -----------------------
        $query = <<<EOD
  SELECT agencies.abbr      agency,
         agency_members.is_member,
         agency_members.is_chair,
         agency_members.is_staff,
         agency_members.is_ex_officio
    FROM agencies, agency_members
   WHERE lower(person_email) = lower('{$this->email}')
     AND agencies.id = agency_id

EOD;
        $result = pg_query($curric_db, $query) or die("Agency query failed: " .
          pg_last_error($curric_db) . ' at ' . basename( __FILE__) . ' ' . __LINE__);
        while ($row = pg_fetch_assoc($result))
        {
          $this->affiliations[$row['agency']] = $row;
        }

        //  Determine whether person has review assignments
        $query = <<<EOD
  SELECT *
    FROM reviews
   WHERE lower(reviewer_email) = lower('{$this->email}')

EOD;
        $result = pg_query($curric_db, $query) or die("Review query failed: " .
          pg_last_error($curric_db) . ' at ' . basename( __FILE__) . ' ' . __LINE__);
        $this->has_reviews = (pg_num_rows($result) > 0);
        
        //  Roles, if any
        $query = <<<EOD
select role_abbr
from roles
where id in ( select role_id from person_role_mappings
              where lower(qc_email) = lower('{$this->email}'))

EOD;
        $result = pg_query($curric_db, $query) or die("Role query faile: " .
            pg_last_error($curric_db) . ' at ' . basename(__FILE__) . ' ' . __LINE__);
        while ($row = pg_fetch_assoc($result))
        {
          $this->roles[] = $row['role_abbr'];
        }

        //  Set login audit info
        $ip = 'Unknown';
        if (isset($_SERVER['REMOTE_ADDR']))
        {
          //  Hmmm...
          $ip = sanitize($_SERVER['REMOTE_ADDR']);
        }
        $query = <<<EOD
  UPDATE people
     SET last_login_time  = now(),
         last_login_ip    = '$ip'
   WHERE lower(qc_email) = lower('{$this->email}')

EOD;
        $result = pg_query($curric_db, $query) or die ("Update failed: " .
          pg_last_error($curric_db) . ' at ' . __FILE__ . ' ' . __LINE__);
      }

    //  to_string()
    //  -----------------------------------------------------------------------
    /*  There is always an email; there might be a name.
     */
    function __toString()
    {
      $returnVal = $this->email;
      if ($this->name)
      {
        $returnVal = $this->name . "($returnVal)";
      }
      return $returnVal;
    }
  }


//  Class Proposal
//  ===========================================================================
/*  In-memory copy of current record from proposals table.
 */
  class Proposal
  {
    public  $id, $class_id, $type_id,
            $type_abbr, $class_abbr,
            $guid,
            $dept_approval_date, $dept_approval_name,
            $created_date, $saved_date, $submitted_date, $closed_date,
            $agency_id,
            $discipline, $course_number,
            $submitter_name, $submitter_email,
            $dept_id, $div_id,
            $cur_catalog, $new_catalog,
            $justifications, $num_justifications_needed;

    //  __construct()
    //  ----------------------------------------------------------------------------------
    /*  Proposal instances are created from two different contexts: a genuinely new
     *  proposal or to create a PHP object from a database record for an existing
     *  proposal. But even if the user says its new, the database has to be checked to be
     *  sure someone (possibly the user) doesn't already have an overlapping proposal
     *  open.
     *
     *  If the instance is being initialized from the database, the Course objects
     *  (cur_catalog and new_catalog) and the Justifications array come from there. But
     *  for new proposals, these fields have to be initialized.
     *
     *  Dates in the proposals table:
     *    created   Date the proposal was created.
     *              Note: the person who creates a proposal is the only one who can edit
     *              and/or submit it. Thus, the column/field names submitter_name and
     *              submitter_email could also have been named creator_name and
     *              creator_email or editor_name and editor_email.
     *    submitted Timestamp when the proposal was first submitted. This never changes.
     *              However, proposal_histories records have a submitted_date field that
     *              indicate when _that_ version of the proposal was submitted.  There is
     *              an event each time a proposal is submitted or resubmitted, and the
     *              event_date matches the history records' submitted_date.
     *    saved     Timestamp of the most recent save. If this is later than the most
     *              recent history record, the proposal needs to be resubmitted in order
     *              for anyone to see the changes.
     *    closed    Date the proposal was withdrawn, received final approval, final
     *              rejection, or (for proposals of type FIX) CUNYfirst was fixed.
     *
     *  Throws:
     *    "Invalid type" If proposal_type abbr is missing
     *    "Wrong level"  If proposing grad course for undergrad designation
     *    "Already open" If another person has a proposal of the same type for
     *                   the same course already open.
     */
    function __construct($discipline, $course_number, $type_abbr = '')
    {
      global $curric_db, $proposal_type_abbr2type_id, $proposal_type_id2class_id,
             $proposal_type_id2agency_id, $proposal_classes, $num_justifications_needed,
             $proposal_type_abbr2name;

      //  Make sure the type is legitimate
      if ($type_abbr === '' || !isset($proposal_type_abbr2type_id[$type_abbr]))
      {
        throw new Exception('Missing or invalid proposal type');
      }
      $this->type_id    = $proposal_type_abbr2type_id[$type_abbr];
      $this->class_id   = $proposal_type_id2class_id[$this->type_id];
      $this->agency_id   = $proposal_type_id2agency_id[$this->type_id];
      $this->type_abbr  = $type_abbr;
      $this->class_abbr = $proposal_classes[$this->class_id]['abbr'];
      $this->num_justifications_needed = 0;
      if (isset($num_justifications_needed[$type_abbr]))
      {
        $this->num_justifications_needed = $num_justifications_needed[$type_abbr];
      }

      //  Get all open proposals of this type for this course.
      $person = unserialize($_SESSION[person]);
      $query = <<<EOD
    SELECT * FROM proposals
     WHERE discipline             = '$discipline'
       AND course_number          = '$course_number'
       AND type_id                = {$this->type_id}
       AND closed_date            IS NULL
EOD;
      $result = pg_query($curric_db, $query) or die('Proposal query failed: ' .
          basename(__FILE__) . ' ' . __LINE__);

      $num = pg_num_rows($result);
      switch ($num)
      {
        case 0:
          //  No un-closed proposals of this type: create a new one
          $this->init_new($discipline, $course_number);
          break;

        case 1:
          //  There is a proposal: check it's not by a different person
          $row = pg_fetch_assoc($result);
          $submitter_email = strtolower($row['submitter_email']);
          $submitter_name  = $row['submitter_name'];
          $existing_id = $row['id'];
          if (strtolower($person->email) !== $submitter_email)
          {
            $type_name = $proposal_type_abbr2name[$type_abbr];
            $msg = <<<EOD
     Unable to create proposal:
     $submitter_name ($submitter_email) has already opened a
     “{$type_name}” proposal for $discipline $course_number. You can view that proposal
     at <a href="../Proposals?id=$existing_id">Proposal #$existing_id</a>.

EOD;
            throw new Exception($msg);
          }
          //  Initialize this Proposal from the one the user opened previously.
          foreach ($row as $field => $value)
          {
            $this->$field = $value;
          }
          //  Justifications and catalog info were serialized in the db.
          $this->justifications = unserialize($this->justifications);
          $this->cur_catalog    = unserialize($this->cur_catalog);
          $this->new_catalog    = unserialize($this->new_catalog);
          break;

        default:
          //  If you get here, a programming error has allowed multiple proposals of the
          //  same type for the same course, to be open and not yet submitted in the
          //  proposals table.
          die("Bad switch ($num): " . basename(__FILE__) . ' ' . __LINE__);
          break;
      }
    }

    //  init_new()
    //  ----------------------------------------------------------------------------------
    /*  Comnpletes initialization of a new Proposal. The type, class, and agency have
     *  already been set.
     */
    private function init_new($discipline, $course_number)
    {
      global $discp2dept, $dept_ids, $discp2div, $div_ids;
      $person                   = unserialize($_SESSION[person]);
      $this->id                 = 0;      //  Not saved to db yet
      $this->guid               = trim(`uuidgen`);
      $this->dept_approval_date = 'default';
      $this->dept_approval_name = 'default';
      $this->created_date        = date('Y-m-d');
      $this->submitted_date     = 'default';
      $this->closed_date        = 'default';
      $this->discipline         = $discipline;
      $this->course_number      = $course_number;
      $this->submitter_name     = $person->name;
      $this->submitter_email    = $person->email;
      $this->dept_id            = $dept_ids[$discp2dept[$discipline]];
      $this->div_id             = $div_ids[$discp2div[$discipline]];
      $this->justifications     = new stdClass();
      //  Need list of criteria to be justified
      if ($this->class_abbr === 'Course')
      {
        //  Course proposals have only one criterion to justify, and its abbr is the
        //  same as the proposal type abbr.
        $type_abbr = $this->type_abbr;
        $this->justifications->$type_abbr = '';
      }
      else
      {
        //  This is a Requirement Designation proposal: the course must be undergraduate.
        $numeric_part = trim($course_number, ' W');
        $numeric_part = ltrim($numeric_part, '0');
        if ( (strlen($numeric_part) > 2) && ($numeric_part[0] > '4') )
        {
          $msg = <<<EOD
    Unable to create {$this->class_abbr} proposal for $discipline $course_number:
    Only undergraduate courses may satisfy General Education requirements.
EOD;
          throw new Exception($msg);
        }
      }
    }

    //  save()
    //  ------------------------------------------------------------------------
    /*  Save or update, as the case may be.
     */
    function save()
    {
      global $curric_db, $dept_ids, $div_ids, $disciplines;
      //  Verify that old code, which allowed deferred proposal type setting, is not
      //  causing mischief
      assert('$this->type_id  != 0');
      assert('$this->agency_id != 0');
      assert('$this->class_id != 0'); // not actually part of db record

      //  Prepare for db
      $cur_catalog    = $_SESSION[cur_catalog];
      $new_catalog    = $_SESSION[new_catalog];
      $justifications = serialize($this->justifications);

      if ($this->id == 0)
      {
        //  No id available yet: insert into db
        $query = <<<EOD
  INSERT INTO proposals VALUES (
              default,                        -- id
              '{$this->type_id}',             -- type_id
              '{$this->guid}',                -- guid
              '{$this->dept_approval_date}',  -- dept_approval_date
              '{$this->dept_approval_name}',  -- dept_approval_name
               now(),                         -- created_date
               NULL,                          -- closed_date
               {$this->agency_id},             -- agency_id
              '{$this->discipline}',          -- discipline
              '{$this->course_number}',       -- course_number
              '{$this->submitter_name}',      -- submitter_name
              '{$this->submitter_email}',     -- submitter_email
               {$this->dept_id},              -- dept_id
               {$this->div_id},               -- div_id
              '$cur_catalog',                 -- cur_catalog
              '$new_catalog',                 -- new_catalog
              '$justifications',              -- justifications
              now(),                          -- saved_date
              NULL                            -- submitted_date)
    RETURNING id
EOD;
        $result = pg_query($curric_db, $query) or die('Save failed: ' .
            basename(__FILE__) . ' ' . __LINE__ . "\n$query");
        $row = pg_fetch_assoc($result);
        $this->id = $row['id'];
      }
      else
      {
        //  The id has been set: update the db
        /*    Fields that can change are:
         *      dept_approval_date
         *      dept_approval_name
         *      new_catalog
         *      justifcations
         */
        $query = <<<EOD
  UPDATE proposals
     SET  dept_approval_date  = '{$this->dept_approval_date}',
          dept_approval_name  = '{$this->dept_approval_name}',
          new_catalog         = '$new_catalog',
          justifications      = '$justifications',
          saved_date          = now()
   WHERE id = $this->id
EOD;
        pg_query($curric_db, $query) or die('Save failed');
        $this->is_opened = true;
      }
    }
  }


//  Class Component
//  =====================================================================================
/*
 *  A Course has an array of these, indexed by component type (LEC, REC, etc);
 */
class Component
{
  public $hours, $section_size, $separately_repeatable;
  function __construct($hours, $section_size = 0, $separately_repeatable = false)
  {
    $this->hours = $hours;
    $this->section_size = $section_size;
    $this->separately_repeatable = $separately_repeatable;
  }
}

//  Class Course
//  =====================================================================================
/*  This class supports course change proposals (proposal class: Course; types: FIX,
 *  NEW_U, NEW_G, REV-U, and REV-G) by encapsulating the the catalog information about a
 *  course.  During a Web session, instances of this class are kept in PHP serialized form
 *  in two $_SESSION variables: 'cur_catalog', for the current CUNYfirst course catalog
 *  data (or a skeleton version of that for a new course), and 'new_catalog' for the copy
 *  that the user is changing by POSTing the course-edit form.
 *
 *  Two serialized instances of this class occur in proposals:
 *    $cur_catalog  For any existing course: current CUNYfirst catalog data.
 *    $new_catalog  For Course proposals: edited version of catalog data.
 */
  //  The following Course fields will be updated from $_POST data each time the course
  //  edit_course form is submitted.
  $course_edit_fields = array(
      course_title, credits, prerequisites, catalog_description, designation
      );
  class Course
  {
    //  Array of course components: user-editable in course_edit form
    public  $components;
    //  Array of designations the course was approved for at the time the Proposal
    //  was created
    public  $designation_approvals;
    //  Catalog information for the course
    public  $course_id, $offer_nbr, $effective_date, $discipline, $course_number,
            $course_title, $credits,
            $prerequisites,
            $catalog_description,
            $career, $is_undergraduate,
            $seats_in_spring, $seats_in_fall,
            $cross_listed_with,
            $designation,
            $cf_info_date;

  //  __construct()
  //  ------------------------------------------------------------------------------------
  /*
   *  Initialize catalog information for a course from an object that has properly named
   *  fields: either a row from a cf_catalog query or an instance retrieved from the
   *  database.
   *
   *  NOTE:
   *    The cf_catalog table has only one component per course, called component, and a
   *    separate column called hours for the number of contact hours for that component.
   *    But the proposal system supports an array of Component objects, each with its own
   *    number of contact hours, section size limits, and separate schedulability. The
   *    cf_catalog needs to be redesigned to support multiple components per course, but
   *    until that day, this code has to examine the "row" to see if it has "components"
   *    (the array) or "component" from cf_catalog and respond appropriately.
   *
   *  If no object is provided, as when creating a proposal for a new course, initialize a
   *  placeholder instance.
   */
    function __construct($row = null)
    {
      global $components, $designations, $curric_db, $geac_db, $cf_update_date;

      $this->components = array();
      foreach ($components as $abbr => $full_name)
      {
        $this->components[$abbr] = new Component(0.0);
      }
      $this->designation_approvals = array();

      if ($row)
      {
        //  Convert object (returned from a proposal) to an array, like what a
        //  cf_catalog query returns, if necessary.
        if (is_object($row))
        {
          $new_row = array();
          foreach ($row as $key => $value)
          {
            $new_row[$key] = $value;
          }
          unset($row);
          $row = $new_row;
        }

        $this->course_id                      = $row['course_id'];
        $this->offer_nbr                      = $row['offer_nbr'];
        $this->effective_date                 = $row['effective_date'];
        $this->discipline                     = $row['discipline'];
        $this->course_number                  = $row['course_number'];
        $this->course_title                   = sanitize($row['course_title']);
        $this->credits                        = number_format($row['credits'], 1);
        $this->prerequisites                  = sanitize($row['prerequisites']);
        if ('' === $this->prerequisites)
        {
          $this->prerequisites = 'None';
        }
        $this->catalog_description            = $row['catalog_description'];
        if ('' === $this->catalog_description)
        {
          $this->catalog_description = "No catalog description available.";
        }
        $this->career                         = $row['career'];
        $this->is_undergraduate               = $this->career === 'UGRD';
        $this->designation                    = 'Unknown';
        if (isset($row['designation']) && trim($row['designation']) !== '')
        {
          $this->designation                  = $row['designation'];
        }
        //  See NOTE above for explanation of how to handle course components
        assert(isset($row['component']) ^ isset($row['components']));
        if (isset($row['components']))
        {
          // from proposals
          foreach ($row['components'] as $abbr => $component)
          {
            $this->components[$abbr] = $component;
          }
        }
        else
        {
          //  from cf_catalog
          $this->components[$row['component']]->hours = number_format($row['hours'], 1);
        }

        //  Use proposal's cf_info_date if available
        if ( isset($row['cf_info_date']) )
        {
          $this->cf_info_date = $row['cf_info_date'];
        }
        else
        {
          $this->cf_info_date = date('F j, Y', $cf_update_date);
        }

        //  Look up requirement designations approved by Academic Senate
        $query = <<<EOD
    SELECT proposal_classes.abbr  prop_class,
           proposal_types.abbr    prop_type,
           event_date             approval_date
      FROM proposals,
           proposal_classes,
           proposal_types,
           events
     WHERE events.discipline = '$this->discipline'
       AND ltrim(events.course_number, '0') = '$this->course_number'
       AND events.agency_id = (SELECT id FROM agencies WHERE abbr = 'Senate')
       AND events.action_id = (SELECT id FROM actions WHERE full_name = 'Approve')
       AND proposals.id = events.proposal_id
       AND proposal_types.id = proposals.type_id
       AND proposal_classes.id = proposal_types.class_id

EOD;
        $result = pg_query($curric_db, $query);
        while ($desig = pg_fetch_assoc($result))
        {
          $this->designation_approvals[] = $desig;
        }
      }
      else
      {
        //  Create placeholder for a new course
        /*  Use zero value for course_id as flag to indicate this is a new course.
         */
        $this->course_id            = 0;
        $this->offer_nbr            = 0;
        $this->discipline           = '';
        $this->course_number        = '';
        $this->course_title         = 'No Title';
        $this->credits              = '0.0';
        $this->prerequisites        = 'No prerequisites.';
        $this->catalog_description  = 'No description.';
        $this->designation          = 'No designation';
        $this->career               = 'UGRD';
        $this->is_undergraduate     = $this->career === 'UGRD'; // Note previous line
        $this->cf_info_date         = date('F j, Y');
      }
    }

  //  prettify()
  //  --------------------------------------------------------------------------
  /*  Convert a pre/corequisite description from CUNYfirst, and make it pretty.
   *  Actually, these descriptions are a mess, so they are returned as-is,
   *  except for empty descriptions and missing period at the end.
   */
    function prettify()
    {
      if (trim($this->prerequisites) === '') $this->prerequisites = 'None.';
      if ($this->prerequisites[strlen($this->prerequisites) - 1] !== '.')
      {
        $this->prerequisites .= '.';
      }
    }

  //  Setters
  //  --------------------------------------------------------------------------
  /*  For editing the course's catalog info. Also to initialize a placeholder.
   */
    function set_discipline($arg)  { $this->discipline    = $arg; }
    function set_number($arg)
    {
      $this->course_number = $arg;
      /*  If course number is < 500 or > 800 it's undergrad, otherwise it's grad.  The
       *  deal with > 800 is that QC grad courses go up to 799, but 80.3 became 803 in
       *  conversion to CUNYfirst. This heuristic does have its limitations: 60.1 is going
       *  to come in as grad anyway.
       *
       *  arg is a string, which might need trimming and which might end in W or H
       */
      $num = trim($arg, ' W');
      $num = ltrim($num, '0');
      $this->is_undergraduate = ((strlen($num) > 2) && ($num[0] > '4')) ? false : true;
      $this->career = $this->is_undergraduate ? 'UGRD' : 'GRAD';
    }


  //  toHTML()
  //  --------------------------------------------------------------------------
  /*  Returns an html-formatted representation of one copy of the course object.
   *  Optional arguments:
   *    with_approvals: Append list of Senate-approved requirements
   *    with_radios:    Include a radio button for selecting the course from
   *                    a list. Must be followed by an index to use for the
   *                    value of the button.
   */
    function toHTML($with_approvals = false, $with_radio = false, $index = null)
    {
      global $components, $designations;
      //  If with_approvals is true, this call is to show the current information for
      //  the course in CUNYfirst. If course_id is 0, there is nothing to report.
      if ($this->course_id === 0 and $with_approvals)
      {
        $returnVal = <<<EOD
        <p class='line-one'>
          $this->discipline $this->course_number is a new course, not in CUNYfirst.
        </p>
EOD;
      }
      //  Generate "line one" info for the course.
      $line_one =
          "{$this->discipline} {$this->course_number}. {$this->course_title}. ";
      //  Contact hours by course component
      foreach ($components as $abbr => $full_name)
      {
        $hours = $this->components[$abbr]->hours;
        if ($hours > 0)
        {
          $line_one .=  $hours .' '.
                        strtolower($abbr) . '.; ';
        }
      }
      //  Credits and prerequisites complete "line one"
      $line_one .= "{$this->credits} cr.; ";
      $this->prettify();
      $line_one .=  "<span class='prereq'>Co- Anti- Pre-requisites: " .
                    "{$this->prerequisites}</span>";

      if ($with_radio)
      {
        //  Generate radio button with line one as its label
        $returnVal = <<<EOD
      <input  type='radio'
              id='course-index-$index'
              name='course_index' value='$index' />
      <label for='course-index-$index' class='line-one'>
        $line_one
      </label>

EOD;
      }
      else
      {
        //  Line one is a paragraph
        $returnVal = "<p class='line-one'>$line_one</p>\n";
      }

      //  Designation. Draw attention if not 'Regular Liberal Arts'
      $designation_class = '';
      $designation_msg   = '';
      if ($this->is_undergraduate && $this->designation !== 'RLA')
      {
        $designation_class = " class='warning'";
        $designation_msg = <<<EOD
    <p class='designation-msg warning'>
      <strong>Note: </strong>courses submitted for General Education designations must be
      “Regular Liberal Arts” (RLA).
    </p>

EOD;
        if (strpos(getcwd(), 'Proposal_Manager') !== false)
        {
          $designation_msg .= <<<EOD
    <p class='designation-msg warning'>
      If you have not already done so, you need to create another proposal to request the
      Registrar to fix the CUNYfirst data for this course so it has the RLA designation.
      “Fix CUNYfirst catalog data” is one of the proposal types in the Create Proposal
      section above.
    </p>

EOD;
        }
      }
      if (isset($designations[$this->designation]))
      {
        $designation        = sanitize($designations[$this->designation]);
      }
      else $designation     = 'Unknown Designation';
      $catalog_description  = sanitize($this->catalog_description);
      $returnVal .= <<<EOD
    <p class='catalog-description'>
      {$catalog_description}
    </p>
    <p><strong>Liberal Arts Designation: </strong><span$designation_class>{$designation}</span></p>
    $designation_msg

EOD;
      //  Display designation approvals if asked and appropriate to do so
      if ($with_approvals && ($this->course_id !== 0) && $this->is_undergraduate)
      {
        if (count($this->designation_approvals) > 0)
        {
          $returnVal .= <<<EOD
      <p>
        {$this->discipline} {$this->course_number} has been approved for the following
        College or University requirement designations:
      </p>
      <table id='designation-approvals'>
        <tr><th>Designation</th><th>Senate approval</th></tr>

EOD;

          foreach ($this->designation_approvals as $desig)
          {
            $desig_str = "{$desig['prop_type']} ({$desig['prop_class']})";
            $date = $desig['approval_date'];
            $date = new DateTime($date);
            $date = $date->format('F j, Y');
            $returnVal .= "      <tr><td>$desig_str</td><td>$date</td></tr>\n";
          }
          $returnVal .= "    </table>\n";
        }
        else
        {
          $returnVal .= <<<EOD
        <p>
          {$this->discipline} {$this->course_number} has not been approved by the Academic
          Senate for any College or University requirement designation.
        </p>
EOD;
        }
      }
      if ($with_approvals)
      {
        $returnVal = '<div><em>Based on CUNYfirst information as of ' .
                      $this->cf_info_date . '.</em></div>' . $returnVal;
      }
      return $returnVal;
    }
    //  diffs_to_html()
    //  ----------------------------------------------------------------------------------
    /*  Returns an HTML description of the changes between two Course objects.
     */
      public static function diffs_to_html($cur_catalog, $new_catalog)
      {
        global $components, $designations;
        $edit_diffs = '';
        // title
        if ($cur_catalog->course_title !== $new_catalog->course_title)
        {
          $edit_diffs .= "<h3>Title</h3>\n" .
              htmlDiff($cur_catalog->course_title, $new_catalog->course_title);
        }

        // components [TODO: section sizes and separately schedulable(?) hours only for now]
        $first_component_diff = true;
        foreach ($components as $abbr => $full_name)
        {
          //  Sign of $diff tells whether component increased or decreased in hours
          $diff = $new_catalog->components[$abbr]->hours -
                  $cur_catalog->components[$abbr]->hours;
          if ($diff !== 0.0)
          {
            if ($first_component_diff)
            {
              $edit_diffs .= "<h3>Component Hours</h3>\n";
              $first_component_diff = false;
            }
            $direction = ($diff > 0) ? 'increased' : 'decreased';
            $edit_diffs .= <<<EOD
          <p>
            $full_name hours <strong>$direction</strong> from
            <del>{$cur_catalog->components[$abbr]->hours}</del> to
            <ins>{$new_catalog->components[$abbr]->hours}</ins>.
          </p>
EOD;
          }
        }

        // course credits
        $diff = $new_catalog->credits - $cur_catalog->credits;
        if ($diff !== 0.0)
        {
          $direction = ($diff) > 0 ? 'increased' : 'decreased';
          $edit_diffs .= <<<EOD
          <h3>Credits</h3>
          <p>
            Credits <strong>$direction</strong> from
            <del>{$cur_catalog->credits}</del> to
            <ins>{$new_catalog->credits}</ins>.
          </p>
EOD;
        }

        // pre-/co-anti-requisites
        if ($cur_catalog->prerequisites !== $new_catalog->prerequisites)
        {
          $edit_diffs .= "<h3>Co- Anti- Prequisites</h3>\n" .
            htmlDiff($cur_catalog->prerequisites, $new_catalog->prerequisites);
        }

        // catalog description
        if ($cur_catalog->catalog_description !== $new_catalog->catalog_description)
        {
          $edit_diffs .= "<h3>Catalog Description</h3>\n" .
            htmlDiff($cur_catalog->catalog_description, $new_catalog->catalog_description);
        }

        // designation
        if ($cur_catalog->designation !== $new_catalog->designation)
        {
          $cur_designation = $designations[$cur_catalog->designation];
          $new_designation = $designations[$new_catalog->designation];
          $edit_diffs .= <<<EOD
          <h3>Course Designation</h3>\n
          <div>
            Designation <strong>changed</strong> from
            <del>$cur_designation</del> to
            <ins>$new_designation</ins>
          </div>
EOD;
        }
        if ($edit_diffs === '') $edit_diffs = "<h3>No Differences</h3>\n";
        return $edit_diffs;
      }
  }
?>
