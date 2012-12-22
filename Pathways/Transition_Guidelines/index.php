<?php
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
    <title>Perspective to Pathways Transition</title>
    <link rel="icon" href="../../../favicon.ico" />
    <link rel="stylesheet" type="text/css" href="css/guidelines.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="css/guidelines_print.css" media="print" />
    <script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
    <script type="text/javascript" src="js/guidelines.js"></script>
  </head>
  <body>
  <h1>Perspectives to Pathways Transition</h1>
    <div id='timestamp'>
      Working Draft<br/><?php echo date('F j, Y', filemtime($_SERVER['SCRIPT_FILENAME'])) . "\n"; ?>
    </div>

    <h2>Introduction</h2>
    <div>
      <p>
        Queens College is completing the transition from the 30 year old Liberal Arts and Science
        Area Requirements (LASAR) curriculum to the current Perspectives on the Liberal Arts and
        Sciences (PLAS) requirements at the same time we are gearing up for the CUNY-mandated,
        university-wide, Pathways to Degree Completion curriculum.
      </p>
      <ul>
        <li>
          The Queens College LASAR curriculum was, essentially, a set of lists of courses that
          satsified various categories. You can see the lists in any College Bulletin, such as the
          <a href="http://www.qc.cuny.edu/Academics/Documents/Undergrad_Bulletin_07_09.pdf">
          2007-2009 College Bulletin</a>.
        </li>
        <li>
          The Queens College Perspectives curriculum is described in a <a
          href="http://senate.qc.cuny.edu/Curriculum/Pathways/Documents/General_Education_V5.0.pdf">
          Report of the Undergraduate Curriculum Committee</a>, adopted by the Academic Senate in
          2006.  The Perspectives curriculum, also known as “the college major,” attempts to improve
          on LASAR by providing students with a coherent liberal arts structure rather than a
          check-list of arbitrary impediments to graduation.
        </li>
        <li>
          The CUNY Pathways curriculum is described in the <a
          href="http://www1.cuny.edu/mu/academic-news/files/2011/12/CommonCoreStructureFinalRec.pdf">
          Final Report of the CUNY Pathways Task Force</a>, approved by the Chancellor in December,
          2011).        </li>
        <li>
          On March 29, 2012, the Academic Senate adopted the <a
          href="docs/2012-03-30_Queens_College_Pathways_implementation_plan.pdf"> Queens College
          Implementation Plan</a> for the Pathways curriculum, which was subsequently accepted by
          the Chancellor. The plan is an attempt to maintain the educational principles that made
          the Perspectives curriculum strong, while adapting to the restrictions imposed by the
          CUNY-imposed mandate.
        </li>
      </ul>

      <p>
        This document is intended to help departments navigate the process of adjusting to the
        Pathways curriculum. <strong>You should be familiar with at least the last three of the
        above documents</strong> to make effective use of this one.
      </p>

    </div>
    <h2>CUNY and QC Requirements</h2>
    <div>
      <p>
        Native Queens College students complete a total of 42 general education credits by taking
        courses from the CUNY Common Core (30 credits) and the Queens College Option (12 credits).
      </p>

      <p>
        Transfer students satisfy 30 credits of the CUNY Common Core via courses taken at Queens or
        at other CUNY institutions, and in addition complete 0 to 12 credits at Queens by taking
        College Option courses.  The number of credits transfer students take depends on the number
        of credits earned at their previous institution; see below (section on Rules and
        Regulations, subsection on Transfer Students and the Queens College Option).
      </p>

      <p>
        The CUNY Common Core structure splits 30 credits of general education coursework into
        courses in a Required Core (12 credits, 4 courses) and a Flexible Core (18 credits, 6
        courses). A third set of credits (0-12) constitute the Queens College Option.
      </p>

      <p>
        Required Core (12 credits, 4 courses, 1 in each category)
      </p>
      <ul>
        <li>English Composition 1</li>
        <li>English Composition 2</li>
        <li>Mathematical and Quantitative Reasoning</li>
        <li>Life and Physical Sciences</li>
      </ul>
      <p>
        Flexible Core (18 credits, 6 courses, at least 1 in each category)
      </p>
      <ul>
        <li>World Cultures and Global Issues</li>
        <li>US Experience in its Diversity</li>
        <li>Creative Expression</li>
        <li>Individual and Society</li>
        <li>Scientific World</li>
      </ul>
      <p>
        College Option (up to 12 credits)
      </p>
      <ul>
        <li>Literature</li>
        <li>Language</li>
        <li>Science</li>
        <li>An additional Literature or Language or Science or Flexible
        Core or Life and Physical Sciences or Synthesis course</li>
      </ul>
    </div>

    <h2 class='page-break'>Where Does My Course Fit?</h2>
    <div>
      <p>
        There are rough correspondences between some Queens College Perspectives categories and CUNY
        Common Core categories, but the mapping is not perfect. To determine where an existing or
        planned course fits into the new structure, we recommend aligning the course's content to
        the prescribed learning outcomes (see below, section on Learning Outcomes: Justifications
        and Sample Syllabi).
      </p>
    </div>

    <h2>Course Submission Checklist</h2>
    <div>
      <p>To submit a course, you will need:</p>

      <ul>
        <li>Course number and title</li>
        <li>
          Any changes your department needs to make to the official catalogue description for the
          course, and a justification of these changes (for the Undergraduate Curriculum Committee)
        </li>
        <li>Sample syllabus</li>
        <li>
          Brief statements about how the course will meet each of the required learning outcomes for
          its category (for the Common Core Review Committee)
        </li>
        <li>
          For courses requiring a waiver to the 3-hour/3-credit standard
          (Mathematical and Quantitative Reasoning and Life and Physical Sciences courses only),
          explanation about why the course will not be 3-credits/3-hours and statement about the
          major requirements the course fulfills (for the Common Core Review Committee).
        </li>
      </ul>
    </div>

    <h2 class='page-break'>Syllabus Preparation</h2>
    <div>
      <p>
        All course proposals must be accompanied by a sample syllabus. The audience for the syllabus
        is students who are considering taking the course or already enrolled in it. But it is
        also an important document for the various review committees because it will serve as the
        basis for validating your proposal’s justifications.
      </p>

      <p>
        There is no page limit for a sample syllabus, but a recommended maximum length is 5 pages.
        Similarly, there is no strict structure required: any format that presents the course in
        a coherent fashion is acceptable. However, the following guidelines give an idea of the
        default meaning of “coherent.”
      </p>

      <p>
        Sample syllabi should include the following:
      </p>

      <ul>
        <li>Course title, department and number, number of credits and number of hours</li>
        <li>Catalogue description</li>
        <li>Pre-requisites for the course and requirements the course fulfills</li>
        <li>
          Learning outcomes for the course (see the section on Learning Outcomes below),
          and how these outcomes map onto assignments or activities
        </li>
        <li>
          Required and recommended texts and materials, including information on how to acquire or
          access these
        </li>
        <li>
          A tentative schedule, or at least information on assignment due dates and in-class exam
          dates
        </li>
        <li>
          Description of the mode of instruction: if the course is completely face-to-face,
          description of class format and explanation of attendance and participation expectations;
          if the course is partially or fully online, description of expectations and procedures for
          online work
        </li>
        <li>
          Description of how student grades will be determined, including all components that
          contribute to the final grade and their relative weight
        </li>
        <li>
          Information about any relevant policies or available services, including:
          <ul>
            <li>CUNY Policy on Academic Integrity</li>
            <li>Course policy on use of student work</li>
            <li>Course evaluations</li>
            <li>Services for students with disabilities</li>
            <li>Tutoring or other support services </li>
          </ul>
        </li>
      </ul>

      <p>
        None of the syllabus elements listed above is required by any of the review committees, but
        a syllabus that includes all of these elements will be easier to justify, and might also be
        more useful to students.
      </p>
      <p>
        Here is some suggested language for university- and college-wide policies and services:
      </p>

      <p><em>CUNY Policy on Academic Integrity</em></p>
      <p>
        The <a href="http://www.cuny.edu/about/info/policies/academic-integrity.pdf">CUNY Policy on
        Academic Integrity</a>
        (<a href="http://www.cuny.edu/about/info/policies/academic-integrity.pdf">
         http://www.cuny.edu/about/info/policies/academic-integrity.pdf</a>),
        as adopted by the Board, is available to all students. Academic dishonesty is prohibited in
        the City University of New York and is punishable by penalties, including failing grades,
        suspension, and expulsion.
      </p>

      <p><em>Use of Student Work</em></p>
      <p>
        All programs in New York State undergo periodic reviews by accreditation agencies. For these
        purposes, samples of student work are occasionally made available to those professionals
        conducting the review. Anonymity is assured under these circumstances. If you do not wish to
        have your work made available for these purposes, please let the professor know before the
        start of the second class. Your cooperation is greatly appreciated.
      </p>

      <p><em>Accommodations for Students with Disabilities</em></p>
      <p>
        Students with disabilities needing academic accommodation should register with and provide
        documentation to the Office of Special Services, Frese Hall, room 111. The Office of Special
        Services will provide a letter for you to bring to your instructor indicating the need for
        accommodation and the nature of it. This should be done during the first week of class. For
        more information about services available to Queens College students, contact the Office of
        Special Services (718-997-5870) or visit their website (<a
        href="http://sl.qc.cuny.edu/oss/">http://sl.qc.cuny.edu/oss/</a>).
      </p>

      <p><em>Course Evaluations</em></p>
      <p>
        During the final four weeks of the semester, you will be asked to complete an evaluation for
        this course by filling out an online questionnaire. Please remember to participate in these
        course evaluations. Your comments are highly valued, and these evaluations are an important
        service to fellow students and to the institution, since your responses will be pooled
        with those of other students and made available online, in the <a
        href="http://courses.qc.cuny.edu"> Queens College Course Information System</a>
        (<a href="http://courses.qc.cuny.edu">http://courses.qc.cuny.edu</a>).
        Please also note that all responses are completely anonymous; no identifying information is
        retained once the evaluation has been submitted.
      </p>
    </div>

    <h2 class='page-break'>Justification Guidelines</h2>
    <div>
       <p>
        Each general education course category has a set of prescribed learning outcomes (see
        below).
        In course proposals, each of the learning outcomes for a given course needs to be justified.
      </p>

      <p>
        Pathways submission instructions (sent to SharePoint campus liaisons) recommend that these
        learning outcomes justification statements be brief (between 2 and 5 sentences), even though
        there is no length restriction for these.
      </p>

      <p>
        All assignments (including writing assignments) should be appropriate for the discipline of
        the course. All kinds of pedagogies are invited, as the faculty so determine. In courses in
        the Flexible Core, students should be given opportunities to gather, use, and interpret data
        and to use written and oral language as faculty deem it appropriate to the learning goals
        and outcomes.
      </p>

      <p>Suggestions for incorporating writing in large-format Flexible Core courses:</p>

      <ul>
        <li>Include essay questions in exams</li>
        <li>
          Require a single graded formal writing assignment that uses one or more course readings as
          evidence to support a well-reasoned argument
        </li>
        <li>Develop a series of low-stakes writing assignments (e.g., one-minute papers, online
          discussion) that can then drive a more high-stakes measure of reasoning and use of
          evidence (e.g., a final exam which could draw from the low-stakes writing assignments as
          study material)
        </li>
      </ul>

      <p>
        There are variations of the suggestions above which involve oral rather than written work
        from students. For large-format courses, Internet-based technologies could serve to collect
        oral presentations from large numbers of students.
      </p>

      <p>
        We will provide some examples of justification statements soon, including more detailed
        recommendations for justifying the writing/oral components for Flexible Core courses.
      </p>
    </div>

    <h2 class='page-break'>Learning Outcomes</h2>
    <div>
    <p>
      This section lists the learning outcomes for courses that will satisfy various CUNY
      and Queens College General Education requirements.
    </p>

    <h3>All Courses</h3>
    <p>All Queens College courses approved for the CUNY Core or the QC College Option will:</p>
    <ul>
      <li>
        Address how, in the discipline (or disciplines) of the course, data and evidence are
        construed and knowledge is acquired; that is, how questions are asked and answered.
      </li>
      <li>
        Position the discipline(s) in the liberal arts curriculum and the larger society.
      </li>
    </ul>

    <h3>CUNY Required Core</h3>
      <h4>English Composition 1 &amp; 2</h4>
      <p>A course in this area must meet all of the following learning outcomes.</p>
      <p>A student will:</p>
      <ul>
        <li>Read and listen critically and analytically, including identifying an argument’s major
        assumptions and assertions and evaluating its supporting evidence.</li>
        <li>Write clearly and coherently in varied, academic formats (such as formal essays,
        research papers, and reports) using standard English and appropriate technology to
        critique and improve one’s own and others’ texts.</li>
        <li>Demonstrate research skills using appropriate technology, including gathering,
        evaluating, and synthesizing primary and secondary sources.</li>
        <li>Support a thesis with well-reasoned arguments, and communicate persuasively across a
        variety of contexts, purposes, audiences, and media.</li>
        <li>Formulate original ideas and relate them to the ideas of others by employing the
        conventions of ethical attribution and citation.</li>
      </ul>
      <p>Additional Queens College English Composition outcomes:</p>
      <p>A student will:</p>
      <ul>
        <li>Attend to writing in class, in one or more of the following forms: discussion of papers
        before they are written and after they are returned; reading aloud of successful papers or
        models; discussion of the rhetorical strategies or writerly qualities of course readings;
        the occasional use of informal, ungraded writing to stimulate class discussion.</li>
      </ul>

      <h4>Mathematical and Quantitative Reasoning</h4>
      <p>A course in this area must meet all of the following learning
      outcomes.</p>
      <p>A student will: </p>
      <ul>
        <li>Interpret and draw appropriate inferences from quantitative representations, such as
        formulas, graphs, or tables.</li>
        <li>Use algebraic, numerical, graphical, or statistical methods to draw accurate conclusions
        and solve mathematical problems.</li>
        <li>Represent quantitative problems expressed in natural language in a suitable mathematical
        format.</li>
        <li>Effectively communicate quantitative analysis or solutions to mathematical problems in
        written or oral form.</li>
        <li>Evaluate solutions to problems for reasonableness using a variety of means, including
        informed estimation.</li>
        <li>Apply mathematical methods to problems in other fields of study.</li>
      </ul>

      <h4>Life and Physical Sciences</h4>
      <p>A course in this area must meet all of the following learning
      outcomes.</p>
      <p>A student will:</p>
      <ul>
        <li>Identify and apply the fundamental concepts and methods of a life or physical
        science.</li>
        <li>Apply the scientific method to explore natural phenomena, including hypothesis
        development, observation, experimentation, measurement, data analysis, and data
        presentation.</li>
        <li>Use the tools of a scientific discipline to carry out collaborative laboratory
        investigations.</li>
        <li>Gather, analyze, and interpret data and present it in an effective written laboratory or
        fieldwork report.</li>
        <li>Identify and apply research ethics and unbiased assessment in gathering and reporting
        scientific data.</li>
      </ul>
      <p>(“Laboratory” may include traditional wet labs, simulations, or field experience.)</p>

      <h3>CUNY Flexible Core</h3>
      <p>All Flexible Core courses must meet the following three learning outcomes.</p>
      <p>A student will:</p>
      <ul>
        <li>Gather, interpret, and assess information from a variety of sources and points of
        view.</li>
        <li>Evaluate evidence and arguments critically or analytically.</li>
        <li>Produce well-reasoned written or oral arguments using evidence to support
        conclusions.</li>
      </ul>

      <h4>World Cultures and Global Issues </h4>
      <p>
        A course in this area must meet at least three of the following additional learning
        outcomes. Courses in this area come from disciplines or interdisciplinary fields such as
        anthropology, communications cultural studies, economics, ethnic studies, foreign languages,
        geography, history, political science, sociology, and world literature.
      </p>
      <p>A student will: </p>
      <ul>
        <li>Identify and apply the fundamental concepts and methods of the discipline or
        interdisciplinary field exploring world cultures or global issues.</li>
        <li>Analyze culture, globalization, or global cultural diversity, and describe an event or
        process from more than one point of view.</li>
        <li>Analyze the historical development of one or more non-U.S. societies.</li>
        <li>Analyze the significance of one or more major movements that have shaped the world’s
        societies.</li>
        <li>Analyze and discuss the role that race, ethnicity, class, gender, language, sexual
        orientation, belief, or other forms of social differentiation play in world cultures or
        societies.</li>
        <li>Speak, read, and write a language other than English, and use that language to respond
        to cultures other than one’s own. </li>
      </ul>

      <h4>U.S. Experience in its Diversity</h4>
      <p>
        A course in this area must meet at least three of the following additional learning
        outcomes. Courses in this area come from disciplines or interdisciplinary fields such as
        anthropology, communications, cultural studies, economics, history, political science,
        psychology, public affairs, sociology, and U.S. literature.
      </p>
      <p>A student will:</p>
      <ul>
        <li>Identify and apply the fundamental concepts and methods of a discipline or
        interdisciplinary field exploring the U.S. experience in its diversity.</li>
        <li>Analyze and explain one or more major themes of U.S. history from more than one informed
        perspective.</li>
        <li>Evaluate how indigenous populations, slavery, or immigration have shaped the development
        of the United States.</li>
        <li>Explain and evaluate the role of the United States in international relations.</li>
        <li>Identify and differentiate among the legislative, judicial, and executive branches of
        government and analyze their influence on the development of U.S. democracy.</li>
        <li>Analyze and discuss common institutions or patterns of life in contemporary U.S. society
        and how they influence, or are influenced by, race, ethnicity, class, gender, sexual
        orientation, belief, or other forms of social differentiation.</li>
      </ul>

      <h4>Creative Expression </h4>
      <p>
        A course in this area must meet at least three of the following additional learning
        outcomes. Courses in this area come from disciplines or interdisciplinary fields such as
        arts, communications, creative writing, media arts, music, and theater.
      </p>
      <p>A student will:</p>
      <ul>
        <li>Identify and apply the fundamental concepts and methods of a discipline or
        interdisciplinary field exploring creative expression.</li>
        <li>Analyze how arts from diverse cultures of the past serve as a foundation for those of
        the present, and describe the significance of works of art in the societies that created
        them.</li>
        <li>Articulate how meaning is created in the arts or communications and how experience is
        interpreted and conveyed.</li>
        <li>Demonstrate knowledge of the skills involved in the creative process.</li>
        <li>Use appropriate technologies to conduct research and to communicate.</li>
      </ul>

      <h4>Individual and Society</h4>
      <p>
        A course in this area must meet at least three of the following additional learning
        outcomes. Courses in this area come from disciplines or interdisciplinary fields such as
        anthropology, communications, cultural studies, history, journalism, philosophy, political
        science, psychology, public affairs, religion, and sociology.
      </p>
      <p>A student will:</p>
      <ul>
        <li>Identify and apply the fundamental concepts and methods of a discipline or
        interdisciplinary field exploring the relationship between the individual and society.</li>
        <li>Examine how an individual’s place in society affects experiences, values, or
        choices.</li>
        <li>Articulate and assess ethical views and their underlying premises.</li>
        <li>Articulate ethical uses of data and other information resources to respond to problems
        and questions.</li>
        <li>Identify and engage with local, national, or global trends or ideologies, and analyze
        their impact on individual or collective decision-making.</li>
      </ul>

      <h4>Scientific World </h4>
      <p>
        A course in this area must meet at least three of the following additional learning
        outcomes. Courses in this area come from disciplines or interdisciplinary fields such as
        computer science, history of science, life and physical sciences, linguistics, logic,
        mathematics, psychology, statistics, and technology related studies.
      </p>
      <p>A student will:</p>
      <ul>
        <li>Identify and apply the fundamental concepts and methods of a discipline or
        interdisciplinary field exploring the scientific world.</li>
        <li>Demonstrate how tools of science, mathematics, technology, or formal analysis can be used
        to analyze problems and develop solutions.</li>
        <li>Articulate and evaluate the empirical evidence supporting a scientific or formal
        theory.</li>
        <li>Articulate and evaluate the impact of technologies and scientific discoveries on the
        contemporary world, such as issues of personal privacy, security, or ethical
        responsibilities.</li>
        <li>Understand the scientific principles underlying matters of policy or public concern in
        which science plays a role.</li>
      </ul>

      <h3>QC College Option</h3>
      <h4>Literature</h4>
      <p>
        Literature courses satisfy the criteria for Reading Literature (RL) under the Perspectives
        requirements currently in effect at the College.  The following learning goals for
        Literature courses are based on the description of RL courses in the 2006 Senate document
        “Proposal 2: Area Requirements”.
      </p>
      <p>Students will be able to:</p>
      <ul>
        <li>Understand and be able to express the advantages of reading literature.</li>
        <li>Engage in the practice of reading.</li>
        <li>Appreciate different genres, including narratives, poetry, essays, or drama in their
        original language or in English translation.</li>
        <li>Through discussion and writing, develop and improve upon skills used in understanding and
        appreciating literature.</li>
      </ul>

      <h4>Language</h4>
      <p>
        Language courses must meet the learning objectives of the Language Area proposed by Queens
        College as an added Flexible Core area in its November 15, 2012 response to the CUNY
        Pathways Steering Committee.
      </p>
      <p>Students will be able to:</p>
      <ul>
        <li>Understand and use the concepts and methods of a discipline or interdisciplinary
        field.</li>
        <li>Gather, interpret, and assess information from various sources, and evaluate
        arguments critically.</li>
        <li>Solve problems, support conclusions, or defend insights.</li>
      </ul>
      <p>Courses must meet at least two of the following additional learning outcomes. Students
      will be able to:</p>
      <ul>
        <li>Differentiate types of language and appreciate their structures. </li>
        <li>Appreciate what is lost or gained in translations among languages. </li>
        <li>Relate language, thought, and culture.</li>
        <li>Compare natural languages, formal languages, and logic.</li>
        <li>Understand the processes involved in learning languages.</li>
      </ul>

      <h4>Science</h4>
      <p>
        Courses that contribute to the goal of understanding the methods, content, and role of the
        natural sciences should include familiarity with a body of knowledge in the physical or
        biological sciences, successful study of the methods of science, including the use of
        observation, the formation of hypotheses and the testing of models, experience and
        awareness of the impact of science on modern society.
      </p>

      <p><em><strong>Editorial remark about this:</strong> do these need separate justification?
      will there be courses in this category that are NOT categorized in "Scientific World" or "Life
      and Physical Sciences"?</em></p>

      <p>
        <em><strong>Suggestion from Steve Schwarz:</strong> let initial list of acceptable courses
        include the two Pathways categories (Life/Phys, SciWorld), and in addition any courses that
        currently meet NS/NS+L requirements (some are 4-credits). In the future, Life/Phys, SciWorld
        courses would automatically qualify, and new courses not in these categories would be asked
        to meet old Perspectives requirements.</em>
      </p>

      <h4>Synthesis</h4>
      <p>
        Synthesis courses should offer a culminating experience either in one discipline or across
        the disciplines. They should offer opportunities for rich intellectual experiences that
        allow students to integrate knowledge and make connections across cultural, philosophical,
        scientific, artistic, political, or other issues, while advancing their critical and
        creative abilities.  Synthesis courses should be open to all advanced students, regardless
        of their major.
      </p>
    </div>

    <h2 class='page-break'>Process and Timeline</h2>
    <div>
      <h3>Process</h3>
      <p>
        Departments submit courses using the <a
        href="http://senate.qc.cuny.edu/Curriculum/Pathways/Proposal/please_wait.html"> Queens
        College Pathways Proposal Form</a> on the Academic Senate website.
      </p>
      <p>
        Upon submission, a copy of the proposal will automatically be sent to:
      </p>
      <ul>
        <li>the appropriate review committees (see below)</li>
        <li>the chair of the department or program for the course</li>
        <li>the dean of the division</li>
        <li>the person submitting the proposal</li>
      </ul>
      <p>
        Proposals approved by the appropriate review committee are then sent for
        approval to the UCC and then to the Senate.
        There will be a site for tracking proposals, similar to
        <a href="http://senate.qc.cuny.edu/GEAC/Proposals">
          the one presently available on the GEAC website</a>.
      </p>
      <p>
        Proposals approved by the Senate will be forwarded to the CUNY-wide Pathways Proposal
        System, which will deliver proposals to the Common Course Review Committee. We don't
        know whether we will be able to track proposals once they are sent on to the Common Core
        Course Review Committee.
      </p>

      <h3>Timeline </h3>
      <p>
        Proposals will be taken on a rolling basis, and there are no submission deadlines. However,
        departments are urged to submit proposals as soon as possible, and preferably before the
        following target dates:
      </p>

      <ul>
        <li>April 25, 2012</li>
        <li>September 5, 2012</li>
      </ul>

      <p>
        The table below shows meetings of the review committees (projected meetings
        are marked by an asterisk).
      </p>
      <table>
        <tr>
          <td>Review Committees</td>
          <td>Undergraduate Curriculum Committee</td>
          <td>Senate Executive Committee</td>
          <td>Academic Senate</td>
          <td>Common Core Review Committee</td>
          <td>CUNY Board of Trustees</td>
        </tr>
        <tr>
          <td>3/28/12</td>
          <td>4/5/12</td>
          <td>4/19/12</td>
          <td>5/3/12</td>
          <td>7/13/12</td>
          <td>9/24/12</td>
        </tr>
        <tr>
          <td>4/25/12</td>
          <td>5/3/12</td>
          <td>8/30/12*</td>
          <td>9/13/12</td>
          <td>9/14/12</td>
          <td>11/26/12</td>
        </tr>
        <tr>
          <td>9/5/12</td>
          <td>9/13/12</td>
          <td>9/27/12*</td>
          <td>10/11/12</td>
          <td>11/2/12</td>
          <td>1/28/13</td>
        </tr>
      </table>
    </div>

    <h2 class='page-break'>Review Committees</h2>
    <div>
      <p>
        The rule has always been that all courses offered at Queens College must be approved by
        either the Undergraduate or Graduate Curriculum Committee, then the Academic Senate, and
        finally the CUNY Board of Trustees before they can be scheduled. Pathways introduces a new
        CUNY-wide committee between the Academic Senate and the Board of Trustees, the Common Core
        Course Review Committee, described at the end of this section.
      </p>
      <p>
        In addition, depending on the requirement, courses are reviewed by various other groups
        before being considered by the UCC, as described here:
      </p>

      <h4>General Education Advisory Committee (GEAC)</h4>
      <p>
        This subcommittee of the UCC is chaired by Christopher Vickery (Computer Science).
        GEAC will review proposals for courses in all categories (including College Option courses)
        <em>except</em> English Composition courses, Mathematical and Quantitative Reasoning
        courses, and courses designated as Writing Intensive.
      </p>

      <h4>Abstract and Quantitative Reasoning Advisory Committee (AQRAC)</h4>
      <p>
        This subcommittee of the UCC is chaired by Martin Braun (Mathematics). AQRAC will
        review Mathematical and Quantitative Reasoning courses.
      </p>

      <h4>Writing Intensive Sub-Committee (WISC)</h4>
      <p>
        This subcommittee of the UCC is co-chaired by Sue Goldhaber (English) and Murphy Halliburton
        (Anthropology). WISC will review courses with the W (writing intensive) designation.
      </p>

      <h4>Writing at Queens (WaQ)</h4>
      <p>
        The director of WaQ is Kevin Ferguson (English). WaQ will provide advice to the UCC on
        submissions for English Composition 2 courses.
      </p>

      <h4>Undergraduate Curriculum Committee (UCC)</h4>
      <p>
        The UCC is chaired by Ken Lord (Computer Science).
      </p>

      <h4>CUNY Common Core Course Review Committee (CCCRC)</h4>
      <p>
        The <a
        href="http://www1.cuny.edu/mu/academic-news/2012/03/02/cuny-wide-common-core-course-review-committee-established/">
        Common Core Course Review Committee</a> is the CUNY-wide committee that will review
        proposals for courses in the Common Core, to determine whether proposed courses will meet
        designated learning outcomes. The committee includes subcommittees for each of the eight
        Common Core curricular areas. The members of this committee are faculty from around CUNY;
        the committee chair is Philip Kasinitz (Sociology, Graduate Center).
      </p>

      <p>
        In a <a
        href="http://senate.qc.cuny.edu/Curriculum/Pathways/Documents/2012-01-24_Logue_Common_Core_Guidelines.pdf">
        memorandum dated January 24</a>, it was noted that courses may be submitted to the Common
        Core Course Review Committee at any stage of the campus review process. We don’t know the
        mechanisms for such informal review, but any department wishing to try this out should <a
        href="mailto:QC_GeneralEducation@qc.cuny.edu"> contact the Office of General Education</a>.
      </p>
    </div>

    <h2 class='page-break'>Rules and Regulations</h2>
    <div>
    <h3>Achieving Breadth in the Flexible Core</h3>

    <p>
      Unless it would require a student to take an extra Flexible Core course, all students must
      complete two Flexible Core courses from disciplines in the Arts and Humanities division, and
      two Flexible Core courses from disciplines in the Social Sciences division (see
      <a href="../Documents/2012-03-30_Queens_College_Pathways_implementation_plan.pdf">
      Queens College Pathways Implementation Plan</a>).
    </p>

    <h3>Transfer Students and the Queens College Option</h3>
    <p>
      All associate-degree students, including A.A.S. students, who transfer to baccalaureate
      programs will be required to complete the Queens College Option general education credits as
      follows:
    </p>

    <ul>
      <li>
        Students transferring with 30 or fewer credits from any college
        (including non-CUNY regionally accredited colleges), 12 credits, 4 courses:
        <ul>
          <li>Literature</li>
          <li>Language</li>
          <li>Science</li>
          <li>An additional Literature or Language or Science or
            Flexible Core or Life and Physical Sciences or Synthesis
            course</li>
        </ul>
      </li>
      <li>Students transferring with more than 30 total credits from any college
        (including non-CUNY regionally accredited colleges) but without an associate
        degree, 9 credits, 3 courses:
        <ul>
          <li>Literature</li>
          <li>Language</li>
          <li>Science </li>
        </ul>
      </li>
      <li>Students transferring with an associate degree from any college (including non-CUNY
          regionally accredited colleges), 6 credits, 2 courses:
        <ul>
          <li>Literature</li>
          <li>Language</li>
        </ul>
      </li>
      <li>Students transferring having completed 9 credits of the college option at another senior
      college, 3 credits, 1 course:
        <ul>
          <li>Literature</li>
        </ul>
      </li>
    </ul>

    <p>
      To receive an A.A. or A.S. degree, students complete 30 general education
      credits (the Common Core), and to receive the baccalaureate degree, students
      complete 36 to 42 general education credits depending on their transfer status.
    </p>
    <p>
      No matter how many CUNY colleges attended, if a student transfers from one CUNY college to
      another, all general education course credits of all types will be accepted as general
      education credits, without further evaluation.
    </p>

    <h3>Optional 4-Credit Math or Science Courses</h3>

     <p>
      Queens College cannot require students to take a 4-credit course to satisfy any area of the
      Common Core. However, if we offer enough 3-credit courses for all students in all areas of the
      Common Core, we may choose to offer optional 4-credit courses in the Mathematical and
      Quantitative Reasoning and the Life and Physical Sciences categories. Such 4-credit courses
      must also satisfy a major degree requirement.
    </p>
    <p>
      Formally, submitting a 4-credit course to CUNY requires submitting a request for a waiver from
      the 3-credits and 3-hours rule. The proposal form asks which major requires courses so that
      the waiver request can be generated automatically. When submitting a 4-credit course,
      submitters will be prompted to provide an explanation as to why the course will not be
      3-credits.
    </p>

  <!-- Excellent that the QC form will generate the waiver request automatically. Still, the
  CUNY-wide form includes a request for an explanation and a list of what major requirements the
  course meets.  Seems like listing major requirements can be database driven, but explanation will
  need to be collected. [EF] -->

    <h3>English Composition 2</h3>
    <p>
      The new Common Core structure requires two English Composition courses. At Queens, we have
      only had one composition course, English 110, <em>College Writing</em>. Our Implementation
      Plan indicates we will develop a second composition course, English Composition 2, to be
      called <em>College Writing 2</em>.
    </p>
    <p>
      <em>College Writing 2</em> will be designed to follow <em>College Writing 1</em>. <em>College
      Writing 1</em> introduces students to interdisciplinary college-level writing.  <em>College
      Writing 2</em> will teach students to identify and practice the scholarly conventions of
      writing in a particular discipline.
    </p>
    <p>
      To ensure that this discipline-specific work benefits students in every major, <em>College
      Writing 2</em> will be offered in departments and divisions across campus.  Students will be
      encouraged to take <em>College Writing 2</em> in a department in or institutionally near their
      major, so that they learn to gather and analyze evidence in the ways that their discipline
      values most. Appropriate topics for <em>College Writing 2</em> might be:
    </p>
    <ul>
      <li>Writing about History</li>
      <li>Writing about Biology</li>
      <li>Writing about Literature</li>
    </ul>

    <p>
      During fall 2012 and spring 2013, Writing at Queens will offer funded workshop opportunities
      for faculty from different departments to design <em>College Writing 2</em> courses.
    </p>
    <p>
      Please direct questions about <em>College Writing 2</em> to Kevin Ferguson, Director of
      Writing at Queens (<a href="kevin.ferguson@qc.cuny.edu">kevin.ferguson@qc.cuny.edu</a>,
      718-997-4695, <a href="http://writingatqueens.org/">http://writingatqueens.org/</a>).
    </p>

    <h3>W (Writing Intensive) Courses</h3>

    <p>
      Queens College students are required to take at least 3 courses designated as W (writing
          intensive). W courses are taken after completion of English Composition 1 &amp; 2.
    </p>

    <p>
      Proposals for W courses are submitted to WISC, which makes its recommendations to the UCC.
    </p>

    <p>
      As specified by the Academic Senate on May 16, 1996, A W course must meet the following
      four criteria :
    </p>

    <ul>
      <li>
        10-15 pages of evaluated writing in three or more assignments (separate papers or
        one term paper done in stages).
      </li>
      <li>
        Attention to writing in class.
      </li>
        <li>
        Exams (if given) that include essay questions.
      </li>
        <li>
          Maximum class size of 30 students.
          (As of Spring 2010, enrollment for W courses is capped at 25.)
        </li>
      </ul>
      <p>
        For more details and guidelines for proposing a W course, visit the <a
        href="http://writingatqueens.org/for-faculty/creating-a-w-course/">Creating a W Course</a>
        page at the <a href="http://writingatqueens.org">Writing at Queens</a> website.
      </p>
    </div>
  </body>
</html>

