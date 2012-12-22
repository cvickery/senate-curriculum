<?php  /* Documents/index.php */
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
    <title>Documents</title>
    <link rel="icon" href="../../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="../css/pathways.css" />
    <style type='text/css'>
      dt { margin: 1em; }
      dd { margin-left: 2em; }
    </style>
  </head>
  <body>
    <ul id='nav'>
      <li><a href='../' title='Main page'>Home</a></li>
      <li><a href='http://senate.qc.cuny.edu/pways'
             title="Forum for discussing issues related to the committee's work">
        Question/Answer Forum</a>
      </li>
      <li>
        <a href="../Writing">Writing-Related Documents</a>
      </li>
    </ul>
    <h1>Documents</h1>
    <dl>
      <dt>
        <a
        href="2012-03-30_Queens_College_Pathways_implementation_plan.pdf">Final
        Queens College Implementation Plan</a>
      </dt>
      <dd>
        <p>
          The Pathways Implementation Plan that President Muyskens submitted to the
          Chancellor on March 30, 2012. Includes the curriculum structure approved by the
          Academic Senate on March 29, 2012, an implementation timetable, and background
          material.
        </p>
      </dd>
      <dt>
        <a href="2012-03-19_Common_Core_Course_Submission_Form.pdf">
        Draft Common Core Course Submission Form</a>
      </dt>
      <dd>
        <p>
          March 19, 2012 draft of the Common Core Submission Form that will be generated
          automatically after the Academic Senate approves courses for the Common Core at
          Queens.  This form is a subset of the Queens College proposal form that is
          available at the Senate’s <a href="../../Proposal">Curriculum Proposal</a> web
          page.
        </p>
        <p>
          The big difference between this version of the submissions form and the previous
          one is the lack of an associated discipline list. There is an existing list of
          discipline mappings across campuses that will be used instead, in the TIPPS
          system. The issue is that Pathways does not allow students to take more than two
          courses within a discipline in the Common Core.
        </p>
      </dd>
      <dt>
        <a href="2012-03-14_QC_Writing_Curriculum.pdf">Writing Curriculum Proposal</a>
      </dt>
      <dd>
        March 14, 2012 proposal for the structure of the writing curriculum at Queens.
        Covers the two English Composition courses in the Pathways Required Core, and
        recommends a more structured approach to the existing W requirement.
      </dd>
      <dt>
        <a href="2012-03-12_Pathways_Implementation_Final.pdf">
          Implementation Plan for March 29
        </a>
      </dt>
      <dd>
        This is the Queens College curriculum structure that the Undergraduate Curriculum
        Committee submitted to the Academic Senate for approval on March 29.
      </dd>
      <dt><a href='2012-03-08_UCC_Summary.pdf'>Draft Document from UCC</a></dt>
      <dd>
        March 8, 2012 Document approved by the UCC. Based on previous drafts from this
        committee, with some changes recommended by the UCC.
        <p>
          <a href="2012-03-08_UCC_Pathways_Timetable.pdf">UCC Draft Pathways
          Timetable</a>
        </p>
      </dd>
      <dt>Three Items From Academic Affairs</dt>
      <dd>
        <p>
          The CUNY Office of Academic Affairs distributed the following items on March 8,
          2012:
        </p>
        <ol>
          <li>
            <a href="2012-03-08_Submission_of_Common_Core_Courses_Sample_Timeline.pdf">
              Sample Implementation Timeline
            </a>
          </li>
          <li>
            <a href="2012-03-08_Common_Core_Course_Submission_Form.pdf">Revised Submission Form
            </a>
            <p>
              Departments may use this form as a guideline, but <em>do not try to submit
              it anywhere</em>.  Queens College will be providing its own mechanism for
              submitting Pathways courses to the Undergraduate Curriculum committee and
              Academic Senate. That mechanism will take care of the mechanics of
              generating this form for you.
            </p>
            <p>
              Contact Eva Fernández if you have urgent questions about submitting course
              proposals (questions which can't wait until after CUNY Central informs us
              about how their system will work, so that we can then set up our own
              mechanism for submission).
            </p>
          </li>
          <li>
            <a href="2012-03-08_Subject_Area_Categories_for_Common_Core.pdf">Revised list
            of subject areas</a>
          </li>
        </ol>
      </dd>
      <dt>
        <a href="2012-02-23_Logue_Possibilities.pdf">Pathways Possibilities Revised</a>
      </dt>
      <dd>
        February 23, 2012 revised and expanded version of the February 14 document from VC
        Logue listing possibilites for developing the College’s curriculum structure under
        Pathways.
      </dd>
      <dt><a href="2012-02-22_Draft.pdf">Working Draft</a></dt>
      <dd>
        February 22, 2012 Working draft of the committee’s report, with additional notes
        and comments.
        <p>
          <a href="2012-02-22_Draft_Revised.pdf">Revised version reflecting decisions
          about the College Option made at the February 22 meeting.</a>
        </p>
      </dd>
      <dt><a href="2012-02-21_WaQ_Pathways_Plan.pdf">Writing at Queens Plan</a></dt>
      <dd>
        February 21, 2012 document from the Writing at Queens Program. The document lays
        out the current writing requirements at the College very clearly, explores options
        for how to incorporate writing into the curriculum, and puts forward a “Sophomore
        Seminar” as a possible structure.
      </dd>
      <dt><a href="2012-02-16_English-110_to_EC1.pdf">English-110 To EC1</a></dt>
      <dd>
        February 16, 2012 document from Writing at Queens suggesting a plan for migrating
        English 110 to the first English Composition course under Pathways.
      </dd>
      <dt>Faculty Governance Memo and Logue-Schaffer Response</dt>
      <dd>
        <a href="2012-02-15_CFGL_letter.pdf">February 15, 2012 Letter from the Council of
        Faculty Governance Leaders</a> concerning various issues, and the <a
        href="2012-02-20_CFGL_Logue-Schaffer_Response.pdf">February 20 response from VCs
        Logue and Schaffer</a>.
      </dd>
      <dt><a href="EDC_Response.html">CUNY Academic Affairs on English Composition</a></dt>
      <dd>
        February 17, 2012 Email from VC Logue responding to the English Discipline
        Council’s concerns about moving from 4-hour to 3-hour English Composition
        courses.
      </dd>
      <dt><a href="2012-02-16_Logue_Science.pdf">Lab Science Possibilities</a></dt>
      <dd>
        February 16, 2012 Document from VC Logue outlining some options available for
        structuring lab science courses within the Pathways framework.
      </dd>
      <dt><a href="2012-02-14_Logue_Possibilities.pdf">Pathways Possibilities</a></dt>
      <dd>
        February 14, 2012 Document from VC Logue outlining some options available for
        working within the framework of the Pathways structure. <strong>As noted above,
        this document was <a href="2012-02-23_Logue_Possibilities.pdf">revised</a> on
        February 23.</strong>
      </dd>
      <dt><a href="2012-02-06_Draft.pdf">Second Draft</a></dt>
      <dd>
        February 6, 2012 Draft College Implementation Plan.
      </dd>
      <dt>
        <a href="2012-01-31_CommonCoreCourseSubmissionForm.pdf">
          DRAFT Course Submission Form
        </a>
      </dt>
      <dd>
        January 31, 2012 Draft version of the form for submitting Common Core courses to
        the University. <em>Please</em> do not try to use this form. It <em>will</em>
        change, and the procedure for submitting courses from QC will not use this form as
        it stands.
        <p>
          However, this document <em>does</em> give an idea of what information the
          University will be requiring when we submit courses, which is useful for this
          committee’s deliberations.
        </p>
      </dd>
      <dt>
        <a href="2012-01-31_Subject_Area_Categories_for_Common_Core.xls.pdf">
          Subject Area Categories
        </a>
      </dt>
      <dd>
        The January 31 draft submission form above requires that each course must select
        exactly one Subject Area from “the attached list.” This is “the attached list.”
      </dd>
      <dt><a href="2012-01-30_Science.pdf">Science Group Report</a></dt>
      <dd>
        January 30 report from the science subgroup. This report was drafted before the
        chancellery clarified the structural requirements for Pathways courses. All
        Pathways courses must carry three credits, but courses are not required to be
        lecture-only. Rather, they must simply require the same amount of work as an
        equivalent lecture-only course. The traditional structure, in which two hours of
        lab work per week is equivalent to one hour of classroom time, can be used for
        Pathways courses.
        <p>
          It remains the case that the college may also allow students to satisfy a
          Pathways requirement such as the Life or Physical Sciences requirement using
          4-credit courses, provided the college also provides sufficient seats for
          students who prefer to satisfy the requirement using 3-credit courses.
        </p>
      </dd>
      <dt>
        <a href="2012-01-28_SUNY_Resolution.pdf">SUNY Faculty Senate Resolution</a>
      </dt>
      <dd>
        January 28, 2012 Resolution adopted by the SUNY Faculty Senate “on
        CUNY’s Failure to Use the Principle of Shared Governance in Establishing
        a New Curriculum.”
      </dd>
      <dt><a href="2012-01-27_Pathways_Writing.pdf">Writing Group Report</a></dt>
      <dd>
        January 27, 2012 report from the writing subgroup.
      </dd>
      <dt><a href="Credits_and_Hours_at_CUNY.pdf">Credits and Hours at CUNY</a></dt>
      <dd>
        On January 25, Vice Chancellor Logue sent out a letter asking whether anyone knew
        of regulations that would apply to CUNY with respect to how hours and credits are
        assigned to courses. This document presents the case that Federal regulations on
        financial aid apply to CUNY, and that those regulations define the relationship
        between “credit hours” and the amount of time (“clock hours”) students are
        required to work for those credits (3 credits require 9 hours of work per week),
        but that faculty are free to divide those 9 clock hours among lecture, lab, study
        time, etc. in any way that has equivalent time requirements to the 3 hours; 3
        credits structure for conventional lecture courses.  <br/><strong>Update:
        </strong>On January 28 VC Logue, in a letter addressing a faculty member’s
        concerns about the Life and Physical Sciences area said, “...(which can consist of
        all lecture, all lab, or a mixture, as long as learning outcomes specified for
        this category are satisfied and the total amount of work required of students
        inside and outside of class is appropriate for a 3-credit 3-hour class)...”
      </dd>
      <dt id='draft'><a href="2012-01-26_Draft.pdf">Working Draft</a></dt>
      <dd>
        January 26, 2012 draft of the Queens College Implementation Plan.
      </dd>
      <dt id='guidelines'>
        <a href="2012-01-24_Logue_Common_Core_Guidelines.pdf">Common Core Guidelines</a>
      </dt>
      <dd>
        January 24, 2012 guidelines for implementing the Pathways common core
        requirements. In general, this document clarifies a number of question people have
        asked about the Common Core and College Option requirements. However, the vice
        chancellor’s January 28 letter (see above) changes some important wording with
        regard to course structures.
      </dd>
      <dt>
        <a href="2012-01-23_Goldstein_AAUP.pdf">Goldstein Response to AAUP Letter</a>
      </dt>
      <dd>
        January 23, 2012 letter from Chancellor Goldstein in response to the AAUP letter
        below.
      </dd>
      <dt><a href="2012-01-12_AAUP_Letter.pdf">AAUP Letter</a></dt>
      <dd>
        January 12, 2012 letter from the AAUP to Chancellor Goldstein and Trustees Chair
        Schmidt objecting to the Pathways initiative.
      </dd>
      <dt><a href="2011-12-22_Tougaw.pdf">The College Writing Curriculum</a></dt>
      <dd>
        December 22, 2011 Proposal from Jason Tougaw for English Composition I, English
        Composition II, and W courses.
      </dd>
      <dt>
        <a href="2011-12-14_Language_Requirements.pdf">Language Requirements at Queens
        College</a>
      </dt>
      <dd>
        December 14, 2011 summary of a presentation to the College Personnel and Budget
        Committee by the Dean of Arts and Humanities, Bill McClure.
      </dd>
      <dt id='plan-guidelines'><a href="2011-12-13_Logue-Presidents.pdf">
        Implementation Plan Guidelines</a>
      </dt>
      <dd>
        December 13, 2011 letter from Vice Chancellor Logue to college presidents.
        Guidelines for the college implementation plans begin half way down page 3 and
        continue onto page 4.
      </dd>
      <dt>
        <a href="2011-12-01_CommonCoreStructureFinalRec.pdf">Final Report of CUNY Pathways
        Steering Committee</a>
      </dt>
      <dd>
        December 1, 2011 report of the CUNY-wide Pathways Steering Committee to the
        chancellor. The Chancellor may make changes before accepting it.
      </dd>
      <dt>
        <a href="2011-12-01_MJAtoPresidents.pdf">Michelle Anderson’s letter to
        presidents</a>
      </dt>
      <dd>
        December 1, 2011: The chair of the CUNY-wide Pathways Steering Committee describes
        the rationale for some of the features of that committee’s report.
      </dd>
      <dt>
        <a href="2011-11-15_Full_Response.pdf">Final Report of previous Senate <em>ad
        hoc</em> committee</a>
      </dt>
      <dd>
        November 15, 2011: The document the Senate submitted to President Muyskens, which
        served as the basis for his response to the November 1, 2011 response of the
        Pathways Steering Committee.
      </dd>
      <dt>
        <a href="2011-09-20_ForeignLanguageInTheCommonCore.pdf">Foreign Language in the
        Common Core</a>
      </dt>
      <dd>
        September 20, 2011 “white paper” from CUNY on ways in which foreign language
        requirements could be required, pre-dating the Pathways Report submitted to the
        Chancellor.
        <p>
          Includes a description of existing foreign language requirements across CUNY,
          and a table showing how many students take one or more foreign language courses
          at various colleges.
        </p>
      </dd>
      <dt>
        <a href="General_Education_V5.0.pdf">Current QC General Education
        Requirements</a>
      </dt>
      <dd>
        Also known as "Perspectives" or "Perspectives on the Liberal Arts and Sciences
        (PLAS)," all Area courses must:
        <ol>
          <li>
            Address how, in the discipline (or disciplines) of the course, data and
            evidence are construed and knowledge acquired; that is, how questions are
            asked and answered;
          </li>
          <li>
            Position the discipline(s) in the liberal arts curriculum and the larger
            society; <br/>and
          </li>
          <li>
            Address the goals defined in Parts B.1 and C [see document] as appropriate for
            their subject matter.
          </li>
        </ol>
      </dd>
      <dt>
        <a href="Liberal_Arts_and_Sciences_Content_for_NY_Degrees.pdf">NYS Liberal Arts
        Guidelines</a>
      </dt>
      <dd>
        The Department of Education explains what courses may and may not be used for
        general education.
      </dd>
    </dl>
  </body>
</html>

