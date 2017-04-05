<?php
//  proposal_editor.php
//  -------------------------------------------------------------------------------------
/*
 *  The proposal editing module.
 *
 */

require_once('utils.php');
  // This code is included only on a page load with the user logged in
  assert('isset($_SESSION[person])');
  //  ... and a proposal selected
  assert('isset($_SESSION[proposal])');

  $proposal           = unserialize($_SESSION[proposal]);
  $cur_catalog        = unserialize($_SESSION[cur_catalog]);
  $new_catalog        = unserialize($_SESSION[new_catalog]);
  $discipline         = $proposal->discipline;
  $course_number      = $proposal->course_number;
  $course_str         = "$discipline $course_number";
  $proposal_id        = $proposal->id;
  $proposal_type_id   = $proposal->type_id;
  $proposal_type_abbr = $proposal_type_id2abbr[$proposal_type_id];

  //  Helper text for messages and labels that depend on whether this is an
  //  undergraduate course or not.
  $grad_level_letter    = 'G';
  $grad_level_word      = 'Graduate';
  $is_undergraduate     = ''; // Class to attach to submit button
  if ($cur_catalog->is_undergraduate)
  {
    $grad_level_letter  = 'U';
    $grad_level_word    = 'Undergraduate';
    $is_undergraduate   = ' is-undergraduate';
  }
  $proper_agent         = $grad_level_word . ' Curriculum Committee';
  if ('FIX' === $proposal->type_abbr) $proper_agent = 'Registrar';

  //  Display CF info for the course that is the subject of the proposal
  //  ====================================================================================
  echo <<<EOD
    <h2 id='catalog-info-section'>
      CUNYfirst Catalog Information for $course_str
    </h2>
    <div>

EOD;
  if ($cur_catalog->course_id === 0)
  {
    //  The course is not in the catalog:
    $cf_info = <<<EOD
    <p class='warning'>$course_str is currently not an active course in CUNYfirst.</p>

EOD;
  }
  else $cf_info = $cur_catalog->toHTML(with_approvals);
  echo <<<EOD

      <fieldset><legend>CUNYfirst Catalog information</legend>
        $cf_info
      </fieldset>
    </div>

EOD;

  //  Update $new_catalog with $_POST data, if available
  //  ===================================================================================
  /*  NOTE: Department approval is not required for all proposal types, and is handled
   *  separately from the other POST data. Search this file for dept_approval.
   */
  if ($form_name === edit_course)
  {
    //  The assertions make sure the form variables that are in $_POST match the form.
    assert('isset($_POST["justification"])');
    //  Course components array
    foreach ($components as $abbr => $full_name)
    {
      assert('isset($_POST[$abbr])');
      $hours = number_format(trim(sanitize($_POST[$abbr]), '0 ') - 0, 1);
      $new_catalog->components[$abbr]->hours = $hours;
    }

    //  Justifications array
    //  When editing a course proposal, there is only one, and it is called
    //  'justification'
    $justification = sanitize($_POST['justification']);
    $proposal->justifications->$proposal_type_abbr = $justification;

    //  All other editable fields
    foreach ($course_edit_fields as $edit_field)
    {
      assert('isset($_POST["'.$edit_field.'"])');
      $value = sanitize(trim($_POST[$edit_field]));
      $new_catalog->$edit_field = $value;
    }

    //  Update SESSION with changed new_catalog object
    $_SESSION[new_catalog] = serialize($new_catalog);

  }

  //  Save the proposal
  $proposal->save();
  $_SESSION[proposal] = serialize($proposal);

  //  Generate one of the two editor forms
  //  ===================================================================================
  /*  Both editor forms use the id 'editor' to simplify JavaScript. It works
   *  because only one of the two forms actually gets generated.
   */
  echo "    <h2 id='edit-proposal-section'>Edit Proposal for $course_str</h2>\n";
  if ($proposal->class_abbr === 'Course')
  {
    //  Course Proposal Editor
    //  =================================================================================

    //  Set up possible warnings to use in the instructions section.
    $course_title = $new_catalog->course_title;
    $check_title_caps = ($course_title === title_case($course_title)) ? "" : <<<EOD
    <p>
      <em class='warning'>Note: Suspicious course title capitalization.</em> Course titles
      that are all caps often indicate that the course was not converted properly from the
      QC course catalog to the CUNYfirst catalog. Even if the catalog information is
      correct, examine the title to be sure it is capitalized correctly and uses
      abbreviations only if actually desired.
    </p>

EOD;
    $prerequisites = $new_catalog->prerequisites;
    $check_prereq_caps = ($prerequisites !== strToupper($prerequisites)) ? '' : <<<EOD
    <p>
      <em class='warning'>Note: All upper-case prerequistes text.</em> Prerequisite
      text that is all caps may indicate that the course was not converted properly from
      the QC course catalog to the CUNYfirst catalog. In any event, check that the
      prerequistes text is worded and capitalized correctly.
    </p>

EOD;
    //  Generate the (course proposal editor) form
    echo <<<EOD
    <div>
      <form id='editor' action="." method="post">
        <fieldset><legend>Your Course Catalog Working Copy</legend>
        <input type='hidden' name='form-name' value='edit-course' />
        {$new_catalog->toHTML()}
        </fieldset>
        <fieldset>
          <legend>Make Changes Here</legend>
          <div class='instructions'>
            <h3>
              Use this section to create or change the College Bulletin information for a
              course
            </h3>
            <p>
              Skip to the <a href="#syllabus-section">Syllabus Upload</a> section or <a
              href="#requirement-section"> Requirement Proposal</a> section if you do not
              need to update the bulletin information for $course_str, or have already
              submitted the necessary proposal to do so.
            </p>
            <p>
              There are three independent reasons why you might make changes here:
            </p>
            <ol>
              <li>
                To fill in the information for a new course.
              </li>
              <li>
                To change an existing course in some way (title, hours, credits,
                prerequisites, description).
                <p>
                  These changes go to the appropriate Academic Senate curriculum committee
                  (UCC or GCC) for review before being submitted to the Senate for
                  approval.
                </p>
                <p>
                  If you want to change just the <em>number</em> of an existing course, send
                  email to the appropriate curriculum committee to determine the proper
                  procedure to follow.
                </p>
              </li>
              <li>
                To report problems where information about the course in CUNYfirst is wrong
                and needs to be brought into alignment with the course information printed
                in the <a href='' target='_blank'>Current Queens College Bulletin</a>. These
                changes are handled by the Registrar, who verifies the problem carefully
                before actually making changes to CUNYfirst.
              </li>

            </ol>
            <p>
              Note that both new courses and course changes require CUNY Board of Trustees
              (BOT) approval before the new or revised course can be scheduled. For example,
              to schedule a new or revised course for the fall semester, it must be approved
              by the Senate by December of the previous year in order to give the BOT time
              to meet and approve it before the fall schedule is made up during the first
              part of the spring semester.
            </p>
            <p>
              Check the course <em>Designation</em>: General Education courses must be
              designated “Regular Liberal Arts.”
            </p>
            <h3>
              Submitting information here is a multi-step process:
            </h3>
            <ul>
              <li>
                <strong>Make changes</strong>. Until you change something, the “Save
                Changes” button, below, will be inactive.
              </li>
              <li>
                <strong>Save your work</strong>.  Once you change anything, the “Save Course
                Changes” button below will become active. Click that button to save your
                work. Once you have saved your work, you can do anything you want, including
                shutting down your computer, and you will be able to come back and pick up
                where you left off.
              </li>
              <p>
                Repeat the previous two steps until you have made all the changes you want.
              </p>
              <li>
                <strong>Review your work</strong>.  Whenever you have made changes and saved
                them, the “Review Changes” button will become active.  Clicking that button
                will trigger a final chance to review your work before actually submitting
                it to the system. (You will be able to come back here if you see you need to
                change something.)
              </li>
              <li>
                <strong>Submit your proposal</strong>.  Entering the proposal into the
                system will cause copies of the proposal to be emailed to you, your
                department’s chair, and your division’s dean. The email will tell you how to
                get a printed copy of the proposal if you want one.
              </li>
            </ul>
          </div>

          <fieldset><legend>Course Title</legend>
            $check_title_caps
            <div>
              <label for='course-title'>Course Title:</label>
              <input type='text' id='course-title'
                     name='course_title' value='{$new_catalog->course_title}'
                     class='double-wide' />
            </div>
          </fieldset>
          <fieldset><legend>Hours and Credits</legend>
          <div class='instructions'>
            <p>
              Indicate the number of contact hours (time spent with an instructor) per week for
              the various components of the course.
            </p>
            <p>
              Use zero for components that are not used.
            </p>
          </div>
          <fieldset>

EOD;

    //  Edit course components. User supplies the number of contact hours for each.
    //  Zero hours for unused components.
    foreach ($components as $abbr => $full_name)
    {
      $pretty_abbr  = ucwords(strtolower($abbr));
      $hours = $new_catalog->components[$abbr]->hours;
      echo <<<EOD
          <div>
            <label for='$abbr-hours'>$pretty_abbr:</label>
            <input type='text' id='$abbr-hours'
                               class='quarter-wide'
                               name='$abbr'
                               value='$hours' /> $full_name hours per week
          </div>

EOD;

    }

    echo <<<EOD
        </fieldset>
        <div>
          <label for='credits'>Credits:</label>
          <input type='text' class='quarter-wide'
                 id='credits' name='credits' value='{$new_catalog->credits}' />
        </div>
        <div>
          $check_prereq_caps
          <label for='prerequisites'>Anti- Co- Pre-requisites:</label>
          <input type='text' class='triple-wide'
                 id='prerequisites'
                 name='prerequisites' value='{$new_catalog->prerequisites}' />
        </div>
        <div>
          <label for='catalog-description' id='description-label'>Catalog Description:</label>
          <textarea id='catalog-description'
                    name='catalog_description'>$new_catalog->catalog_description</textarea>
        </div>
        <div>
          <label for='designation'>Liberal Arts Designation:</label>
          <select id='designation' name='designation'>
EOD;
    foreach ($all_designations as $abbr => $full_name)
    {
      //  Hack awaiting definitive designation determination
      /*  For now, the full name of a designation starts with 'Regular' for undergraduate
       *  courses, and 'Graduate' for graduate-level courses. But the form uses the
       *  abbreviations RLA, GLA, etc. CUNY will be embedding RCC and FCC areas in these
       *  names, and this code will need to be adjusted to accommodate the new names.
       *  2014-02-28: We're using basic_ and common_core_ designations now. The two, merged,
       *  make up all_designations.
       */
      if (  ($cur_catalog->is_undergraduate && substr($full_name, 0, 7) === 'Regular') ||
            (!$cur_catalog->is_undergraduate && substr($full_name, 0, 8) === 'Graduate') )
      {
        $selected = ($new_catalog->designation === $abbr) ? " selected='selected'" : "";
        echo "            <option value='$abbr'$selected>$full_name</option>";
      }
    }
    echo <<<EOD
          </select>
        </div>
      </fieldset>

EOD;
    //  Handle proposal types that require dept approval
    //  ================================================================================
    $approval_date_msg        = '';
    $dept_approval_name_value = $proposal->dept_approval_name;
    $dept_approval_date_value = $proposal->dept_approval_date;
    if (in_array($proposal_type_abbr, $require_dept_approval))
    {
      $revising         = 'revising';
      $as_a_new_course  = '';
      if (substr($proposal_type_abbr, 0, 3) === 'NEW')
      {
        $revising         = '';
        $as_a_new_course  = ' as a new course';
      }
      $dept_abbr = $discp2dept[$discipline];
      $dept_name = $depts_by_abbr[$dept_abbr]['department_name'];
      $dept_approval_name_value = $dept_name;
      if (isset($_POST['dept_approval_name']))
      {
        $dept_approval_name_value = sanitize($_POST['dept_approval_name']);
        $dept_approval_date_value = sanitize($_POST['dept_approval_date']);
      }
      $proposal->dept_approval_name = $dept_approval_name_value;
      //  Approval date: might me Valid, Pending, or Invalid. If Invalid, may be bad
      //  date format or a valid date in the future.
      $is_pending = strtolower($dept_approval_date_value) === 'pending';
      $approval_time = strtotime($dept_approval_date_value);
      if ($approval_time && $approval_time < (time() - 60))
      {
        $dept_approval_date_value = date('F j, Y', $approval_time);
        $proposal->dept_approval_date = $dept_approval_date_value;
      }
      else
      {
        //  Date is not a valid, in the past, date.
        $dept_approval_date_value = 'Enter approval date';
        //  Determine whether it is pending, future, or bad format.
        if ($is_pending)
        {
          //  Pending
          $dept_approval_date_value = 'Pending'; //  Special allowed-value
          $proposal->dept_approval_date = 'Pending';
          $approval_date_msg = <<<EOD
      <div class='warning'>
        Remember: the proposal can be reviewed by Academic Senate committees while
        department approval is pending, but it will not be acted on by the Senate until
        the $proper_agent receives confirmation that the department has approved the
        proposal.
      </div>

EOD;
        }
        else if ($approval_time)
        {
          //  Future
          $proposal->dept_approval_date = null;
          $dept_approval_date_value = 'Enter approval date';
          $approval_date_msg = <<<EOD
    <div class='error'>
      <p>
        The department approval date cannot be in the future. Use “Pending” if the
        department has not approved the proposal yet, and notifify the $proper_agent when
        it has actually been approved by the department.
      </p>
      <p>
        Otherwise, enter the actual date when the proposal was approved by the department.
      </p>
    </div>

EOD;
        }
        else
        {
          //  Invalid date string
          $proposal->dept_approval_date = null;
          $dept_approval_date_value = 'Enter approval date';
          $approval_date_msg = <<<EOD
    <div class='error'>
      Invalid date.
    </div>

EOD;
        }
      }

      //  POST data processed: update proposal; generate remaining editor fields
      //  -------------------------------------------------------------------------------
      $proposal->save();
      $_SESSION[proposal] = serialize($proposal);
      echo <<<EOD
      <fieldset><legend>Department Approval</legend>
      <div class='instructions'>
        <ul>
          <li>
            Enter the date that $dept_name approved $revising $discipline
            $course_number$as_a_new_course.
          </li>
          <li>
            To avoid processing delays, you may enter "pending" as the approval date for
            now, but the proposal will not be submitted to the Senate for final approval
            until the $proper_agent receives confirmation that the department has approved
            the proposal.
          </li>
          <li>
            Fun fact: dates like, “yesterday” and “last Wednesday” will work. (But not
            “tomorrow!” Use “Pending” if the course hasn’t actually been approved by the
            department yet.)
          </li>
          <li>
            If the course was approved by some organization other than $dept_name,
            enter its name here.
          </li>
        </ul>
      </div>
      <p>
        <label  for='dept_approval_name'>Approved by </label>
        <input  type='text'
                id='dept_approval_name'
                name='dept_approval_name'
                value='$dept_approval_name_value' />
        <label  for='dept_approval_date'>on</label>
        <input  type='text'
                id='dept_approval_date'
                name='dept_approval_date'
                value='$dept_approval_date_value' />.
      </p>
      $approval_date_msg
      </fieldset>

EOD;
    }

    $justification = trim($proposal->justifications->$proposal_type_abbr);
    echo <<<EOD
      <fieldset><legend>Justification, Explanation, or Summary</legend>
        <div class='instructions'>
          <p>This field cannot be blank.</p>
          <p>
            <strong>For a curriculum change</strong>, provide a brief explanation for
            creating/changing $course_str.
          </p>
          <p>
            The audience for this this type of proposal is the appropriate curriculum
            committee.
          </p>
          <p>
            <strong>For a CUNYfirst data problem</strong>, you can provide the correct
            information using the editing area above if that is appropriate. Explain or
            summarize the issues in the box below.
          </p>
          <p>
            The Registrar’s Office is responsible for making sure the CUNYfirst course
            data agrees with what has been approved by the Academic Senate, so think of
            the Registrar as the audience for this type of proposal.
          </p>
        </div>
        <textarea name='justification'>$justification</textarea>
      </fieldset>
      <button id='save-changes'
              type='submit'
              class='centered-button'
              disabled='disabled'>
        Save Changes
      </button>
    </fieldset>
  </form>
    <form id='review-proposal' method='post' action='./review_course_proposal.php'>
      <fieldset>
        <input type='hidden' name='form-name' value='review-course' />
        <input type='hidden' id='agent' name='agent' value='$proper_agent' />
        <legend>Submit Proposal</legend>
        <div class='instructions'>
          <p>
            Once you have saved your changes using the “Save” button above, use the
            “Submit” button below to send the changes to the <span
            class='proper-agent'>$proper_agent</span>.
          </p>
          <p>
            Your changes will be displayed for your review, and a message will be emailed
            to you at <em>$person->email</em> for confirmation before the proposal is
            actually sent to the <span class='proper-agent'>$proper_agent</span>.
          </p>
        </div>
        <div>
          <h3>Proposal #$proposal_id. $discipline $course_number: $proposal_type</h3>
          <button id='submit-proposal'
                  type='submit'
                  class='centered-button$is_undergraduate'>
            Submit Proposal
            <img src="../images/external.png" alt='' />
          </button>
        </div>
      </fieldset>
    </form>
    </div>
EOD;

    }
    else
    {
      //  Designation Proposal Editor
      //  ===============================================================================
      echo <<<EOD
    <div>
      <form id='editor' action='.' method='post'>
        <fieldset><legend>Edit Designation Proposal</legend>
        <input type='hidden' name='form-name' value='edit-designation' />
        <ul class='instructions'>
          <li>
            There are two distinct types of justifications to supply: Course Attributes and
            Student Learning Outcomes (SLOs).
          </li>
          <li>
            QC-specific justifications are derived from the general education structure that
            preceded Pathways (the Perspectives structure). Those justifications focus on how the
            attributes of the course content position the course and its discipline within the
            liberal arts.
          </li>
          <li>
            Pathways required-core and flexible-core criteria are stated in terms of what students
            who complete the course successfully will have learned. Those justifications should
            explain how the grading for the course will demonstrate that students have actually
            achieved the desired SLO. <em>It is critical for these justifications to be clearly in
            alignment with the grading structure for coursework given in the syllabus.</em>
          </li>
          <li>
            The criteria for requirement designations occur in sets. For some sets, you have to
            provide justifications for all the criteria in the set. For other sets, a proposal
            only needs to supply justifications for some minimum number of criteria within the set.
            In those cases there is no particular advantage to supplying more justifications than
            are needed.
          </li>
          <li>
            You may paste the justifications here from a document you prepared separately, but text
            formatting such asitalics and boldface will be lost. However, special characters, such
            as accented or non-latin letters and curly quotes should transfer correctly.
          </li>
          <li>
            Save your work regularly. Once it is saved you can return to it later.
          </li>
          <li>
            Be sure to go through the complete process to submit the proposal for review. Until
            you actually get a confirmation email that the proposal was received, the reviewers
            will not know about it.
          </li>
        </ul>
EOD;
      $criteria_groups = $proposal_type_id2criteria_group[$proposal_type_id];
      foreach ($criteria_groups as $criteria_group)
      {
        echo <<<EOD
          <fieldset><legend>{$criteria_group_abbr2full_name[$criteria_group]}</legend>
            <p>$criteria_group_abbr2description[$criteria_group]</p>
EOD;
        if ($criteria_group === 'QC' || $criteria_group === 'LPS')
        {
          $pre_approved = '';
          foreach ($cur_catalog->plas_approvals as $approval)
          {
            if (isset($approval['prop_class']) && $approval['prop_class'] === 'PLAS')
            {
              $pre_approved = $approval['prop_type'];
              break;
            }
          }
        }
        $criteria = $criteria_group2criteria[$criteria_group];
        foreach ($criteria as $criterion)
        {
          /*
           * disable text area and do default value for preapprovals
           * use $_POST data if available.
           */
          $current_value = '';
          if (isset($proposal->justifications->$criterion))
          {
            $current_value = $proposal->justifications->$criterion;
          }
          if ($criteria_group === 'QC' && $pre_approved)
          {
            $current_value = <<<EOD
  Previously approved for the Perspectives $pre_approved Area of Knowledge.
EOD;
          }
          if ($criteria_group === 'LPS' &&
              $pre_approved   === 'NS+L' &&
              $cur_catalog->credits > 3)
          {
            $current_value = '  STEM Variant';
          }
          if (isset($_POST[$criterion]))
          {
            $current_value = sanitize($_POST[$criterion]);
          }
          $proposal->justifications->$criterion = $current_value;
          $full_text = $criteria_text[$criterion];
          echo <<<EOD
            <label for='$criterion' class='criterion-label'>$full_text</label>
            <textarea id='$criterion' name='$criterion'>$current_value</textarea>
EOD;
        }
        echo "          </fieldset>\n";
      }
      echo <<<EOD
        </fieldset>
        <button type='submit'
                id='save-changes'
                class='centered-button'
                disabled='disabled'>
          Save Changes
        </button>
      </form>
EOD;
      $proposal->save();
      $_SESSION[proposal] = serialize($proposal);
      echo <<<EOD
      <form id='review-proposal' method='post' action='./review_designation_proposal.php'>
        <fieldset><legend>Submit Proposal</legend>
          <input type='hidden' name='form-name' value='review-designation' />
          <h3>Proposal #$proposal_id. $discipline $course_number: $proposal_type</h3>
          <button type='submit'
                  id='submit-proposal'
                  class='centered-button'>
            Submit Proposal
            <img src="../images/external.png" alt='' />
          </button>
        </fieldset>
      </form>
    </div>
EOD;
    }
?>
