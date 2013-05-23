//  college_option.js

/*  Radio groups
 *    bachelor-degree   Do you have a bachelor's degree?
 *    began             Did you start in a 2-year or a 4-year program?
 *    associate-degree  Do you have an associate's degree?
 *    31-or-more        Do you transfer more than 31 credits in?
 *    prev-co           Do you transfer CO courses in?
 *
 *  Value ids
 *    num-prev-co      Number of CO courses transferred in.
 *
 *  Row ids
 *    ask-bachelor-degree
 *    ask-began
 *    ask-associate-degree
 *    ask-31-or-more
 *    ask-if-prev-co
 *    ask-num-prev-co
 */

$(function()
{
  //  Initial state on page load
  //  -----------------------------------------------------------------------------------
  //    All radio answers are no|4-year
  $('#began-4, #prev-co-n, #bachelor-degree-n, #associate-degree-n, #31-or-more-n')
      .attr('checked', 'checked');
  //    Hide irrelevant questions
  $('#ask-associate, #ask-31-or-more, #ask-num-prev-co').hide();

  //  Update display and recalculate number of required courses when anything changes
  //  -----------------------------------------------------------------------------------
  $('input').change(function()
    {
      //  Answers to questions
      var has_bachelor  = $('input[name="bachelor-degree"]:checked').val() === 'y';
      var has_associate = $('input[name="associate-degree"]:checked').val() === 'y';
      var start_4       = $('input[name="began"]:checked').val() === '4';
      var over_30       = $('input[name="31-or-more"]:checked').val() === 'y';
      var prev_co       = $('input[name="prev-co"]:checked').val() === 'y';
      var num_prev_co   = 0;
      if ( $('#ask-num-prev-co').is(':visible') )
      {
        num_prev_co = $('#num-prev-co').val() - 0;
        if (  isNaN(num_prev_co) ||
              typeof(num_prev_co) !== "number" ||
              num_prev_co < 0 || num_prev_co > 4 ||
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

      //  What to display and how much is required
      $('#result').removeClass('error');
      var num_required  = 4;
      if (has_bachelor)
      {
// console.log('bachelor');
        //  Has bachelor’s: no CO required; no other questions
        $('tr').hide();
        $('#ask-bachelor').show(250);
        num_required  = 0;
      }
      else if (start_4)
      {
// console.log('start 4');
        //  Must take 4 minus num-prev-co, even if they have an Associate's
        $('#ask-associate, #ask-31-or-more').hide();
        $('#ask-bachelor, #ask-began, #ask-if-prev-co').show(250);
        num_required  = 4;
      }
      else if (has_associate)
      {
// console.log('associate');
        $('#ask-31-or-more').hide();
        $('#ask-bachelor, #ask-began, #ask-associate, #ask-if-prev-co').show(250);
        num_required  = 2;
      }
      else if (over_30)
      {
// console.log('over 30');
        num_required  = 3;
      }
      else
      {
// console.log('else');
        $('#ask-bachelor, #ask-began, #ask-associate, #ask-31-or-more, ask-if-prev-co')
          .show(250);
        num_required  = 4;
      }
      if (prev_co)
      {
        $('#ask-num-prev-co').show(250);
        $('#num-prev-co').focus();
      }
      else
      {
        $('#ask-num-prev-co').hide(250);
        num_prev_co = 0;
      }

      //  Generate result
      //  -------------------------------------------------------------------------------
      num_required -= num_prev_co;
      if (num_required < 0) num_required = 0;
      var category = 'CO0' + (4 - num_required);

      if (num_prev_co < 0)
      {
        //  Invalid number of previous CO courses: generate error message
        category = 'Error';
        msg = "You must enter a number between 0 and 4 as the number of College " +
          "Option courses completed at another CUNY senior college.";
        $('#result').addClass('error');
      }
      else
      {
        //  Generate the appropriate message as the result
        switch (num_required)
        {
          case 0: msg = 'You do not need to take any College Option courses at Queens.';
                  break;
          case 1: msg = 'You must take a Literature course.';
                  break;
          case 2: msg = 'You must take a Literature and a Language course.';
                  break;
          case 3: msg = 'You must take a Literature, a Language, and a Science course.';
                  break;
          case 4: msg = 'You must take a Literature, a Language, a Science, and an ' +
                        'additional course.';
                  break;
          default:  msg = 'Program Error in ' + __FILE__ + ' line ' + __LINE__;
                    break;
        }
      }

      //  Display the result
      $('#result').html(msg + ' [' + category + ']');
    });

  //  Prevent data entry from generating any page loads.
  $('form').submit(function(evt) { evt.preventDefault(); });

});
