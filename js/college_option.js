//  college_option.js

/*  Radio groups
 *    bachelor-degree   Do you have a bachelor's degree?
 *    began             Did you start in a 2-year or a 4-year program?
 *    associate-degree  Do you have an associate's degree?
 *    over-30           Do you transfer in over 30 credits?
 *    prev-co           Do you transfer in any CO courses?
 *
 *  Value ids
 *    num-prev-co      Number of CO courses transferred in.
 *
 *  Row ids
 *    ask-bachelor-degree
 *    ask-began
 *    ask-associate-degree
 *    ask-over-30
 *    ask-if-prev-co
 *    ask-num-prev-co
 */

$(function()
{
  //  Initial state on page load
  //  -----------------------------------------------------------------------------------

  //    All radio answers are no|4-year
  $('#began-4, #prev-co-n, #bachelor-degree-n, #associate-degree-n, #over-30-n')
      .attr('checked', 'checked');

  //    Hide irrelevant questions
  $('#ask-associate, #ask-over-30, #ask-num-prev-co').hide();

  //    Is this a student-group report?
  var do_explain              = $('form').hasClass('explain');
  var student_group_msg       = '';

  //  Update display and recalculate number of required courses when anything changes
  //  -----------------------------------------------------------------------------------
  $('input').change(function()
    {
      //  Answers to questions
      var need_student_group  = true;
      var has_bachelor        = $('input[name="bachelor-degree"]:checked').val() === 'y';
      var has_associate       = $('input[name="associate-degree"]:checked').val() === 'y';
      var start_4             = $('input[name="began"]:checked').val() === '4';
      var over_30             = $('input[name="over-30"]:checked').val() === 'y';
      var has_prev_co         = $('input[name="prev-co"]:checked').val() === 'y';
      var num_prev_co         = 0;
      if ( $('#ask-num-prev-co').is(':visible') )
      {
        num_prev_co = $('#num-prev-co').val() - 0;
        if (  isNaN(num_prev_co) ||
              typeof(num_prev_co) !== "number" ||
              num_prev_co < 0 ||
              (num_prev_co % 1 !== 0)
           )
        {
          num_prev_co = -1; //  Flag value to generate error message for "result"
          $('#num-prev-co').css('background-color', '#f66');
        }
        else
        {
          $('#num-prev-co').css('background-color', '#fff');
        }
      }
      else
      {
        $('#num-prev-co').val('0');
      }

      //  Basic situation: native students, started at baccalaureate, or transferred with
      //  fewer than 31 credits.
      var num_required  = 4;
      var reduce_co_by  = Math.min(4, num_prev_co);
      var num_remaining = num_required - reduce_co_by;

      var suffix        = (reduce_co_by === 1) ? '' : 's';

      //  What to display and how much is required
      $('#result').removeClass('error');
      if (has_bachelor)
      {
// console.log('has bachelor’s');
        //  Has bachelor’s: no CO required; no other questions
        $('tr').hide();
        $('#ask-bachelor').show(250);
        num_required        = 0;
        num_remaining       = 0;
        student_group_msg   = "Bachelor’s degree bridge: no student-group";
        need_student_group  = false;
      }
      else if (start_4)
      {
// console.log('start at 4-year');
        //  Must take 4 minus num-prev-co, unless they have an Associate's
        //  Includes incoming freshmen
        $('#ask-over-30').hide();
        $('#ask-bachelor, #ask-began, #ask-associate, #ask-if-prev-co').show(250);

        student_group_msg   = "Started at 4-year; " + reduce_co_by +
            " previous CO course" + suffix;
        need_student_group  = true;
      }
      else if (has_associate)
      {
// console.log('has associate’s');
        $('#ask-over-30').hide();
        $('#ask-bachelor, #ask-began, #ask-associate, #ask-if-prev-co').show(250);
        num_required        = 2;
        reduce_co_by        = Math.min(2, num_prev_co);
        num_remaining       = num_required - reduce_co_by;
        suffix              = (reduce_co_by === 1) ? '' : 's';
        if (reduce_co_by > 0)
        {
          student_group_msg = "Associates’s degree; " + reduce_co_by +
            " previous CO course" + suffix;
          need_student_group  = true;
        }
        else
        {
          student_group_msg   = "Associate’s degree: 0 previous CO courses";
          need_student_group  = false;
        }
      }
      else if (over_30)
      {
// console.log('over 30 credits');
        $('#ask-bachelor, #ask-began, #ask-associate, #ask-over-30, #ask-if-prev-co')
          .show(250);
        num_required        = 3;
        reduce_co_by        = Math.min(3, num_prev_co);
        num_remaining       = num_required - reduce_co_by;
        suffix = (reduce_co_by === 1) ? '' : 's';
        student_group_msg   = "Started at 2-year; over 30 credits; " +
          reduce_co_by + " previous CO course" + suffix;
        need_student_group  = true;
        if (reduce_co_by === 0) need_student_group = false;
      }
      else
      {
// console.log('else');
        $('#ask-bachelor, #ask-began, #ask-associate, #ask-over-30, #ask-if-prev-co')
          .show(250);
        student_group_msg   = "Started at 2-year; fewer than 31 credits; " +
          reduce_co_by + " previous CO course" + suffix;
        need_student_group  = true;
        if (reduce_co_by === 0) need_student_group = false;
      }

      //  Be sure the num-prev-co field is visible if appropriate
      if (has_prev_co && ! has_bachelor)
      {
        $('#ask-num-prev-co').show(250);
        $('#num-prev-co').focus();
      }
      else
      {
        $('#ask-num-prev-co').hide(250);
        num_prev_co = 0;
      }

      //  Generate report
      //  -------------------------------------------------------------------------------
      var msg         = '';
      var note        = "";

      if (num_prev_co < 0)
      {
        //  Invalid number of previous CO courses: generate error message
        msg = "You must enter a non-negative number as the number of College " +
          "Option courses completed at another CUNY senior college.";
        $('#result').addClass('error').html(msg);
      }
      else
      {
        //  Alert if extra CO courses previously taken
        if ( num_prev_co > num_required )
        {
          note = "<p class='error'>More college option courses previously completed (" +
            num_prev_co + ") than needed (" + num_required + "): extras have no effect." +
            "</p>";
        }
        //  Statement of courses needed
        switch (num_remaining)
        {
          case 0:   student_report_msg  =
                      'You do not need to take any College Option courses at Queens.';
                    break;
          case 1:   student_report_msg  =
                      'You must take a Literature course.';
                    break;
          case 2:   student_report_msg  =
                      'You must take a Literature and a Language course.';
                    break;
          case 3:   student_report_msg  =
                      'You must take a Literature, a Language, and a Science course.';
                    break;
          case 4:   student_report_msg=
                      'You must take a Literature, a Language, a Science, and an ' +
                          'additional course.';
                    break;
          default:  student_report_msg= 'Program Error in ' + __FILE__ + ' line ' + __LINE__;
                    break;
        }
        student_report_msg  = '<p>' + student_report_msg+ '</p>';
        var student_group_str = ' [no student group code]';
        if (need_student_group)
        {
          var effective_num_xfer = num_required - num_remaining;
          if (num_required < 4) effective_num_xfer += (4 - num_required);
          student_group_str = ' [CO0' + (effective_num_xfer) + ']';
        }
        if (do_explain)
        {
          student_group_msg   = '<p>' + student_group_msg + student_group_str + '</p>';
        }
        else
        {
          student_group_msg   = '';
        }

        //  Assemble the report
        var report = student_report_msg + student_group_msg + note;
        //  Display the result
        $('#result').html(report);
      }
    });

  //  Prevent data entry from generating any page loads.
  $('form').submit(function(evt) { evt.preventDefault(); });

  //  Trigger bachelor-degree-n to initialize result section.
  $('#bachelor-degree-n').trigger('change');

});

