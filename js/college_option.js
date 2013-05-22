//  college_option.js

/*  Radio groups
 *    other-cuny           Do you transfer CO courses in?
 *    bachelor-degree      Do you have a bachelor's degree?
 *    associate-degree     Do you have an associate's degree?
 *    31-or-more           Do you transfer more than 31 credits in?
 *
 *  Values
 *    num-other-cuny-courses    Number of CO courses transferred in.
 *
 *  Row ids
 *    other-cuny-courses        How many CO courses transferred in?
 */

$(function()
{
  $('input').change(function() 
    {
      var num_required    = 4;
      var num_other_cuny  = 0;
      var other_cuny = $('input[name="other-cuny"]:checked').val() === 'y';
      if (other_cuny)
      {
        $('#other-cuny-courses').show(250);
        $('#num-other-cuny-courses').focus();
      }
      else
      {
        $('#other-cuny-courses').hide(250);
        num_other_cuny = 0;
      }
      num_other_cuny = $('#num-other-cuny-courses').val() - 0;
      if (  isNaN(num_other_cuny) ||
            typeof(num_other_cuny) !== "number" ||
            num_other_cuny < 0 || num_other_cuny > 4 ||
            (num_other_cuny % 1 !== 0)
         )
      {
        num_other_cuny = 0;
        $('#num-other-cuny-courses').css('background-color', '#f66');
      }
      else
      {
        $('#num-other-cuny-courses').css('background-color', '#fff');
      }

      num_required -= num_other_cuny;
      switch (num_required)
      {
        case 0: msg = 'You do not need to take any College Option courses at Queens.';
                break;
        case 1: msg = 'You must take one Literature course.';
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
      $('#result').html(msg);
    });
  $('form').submit(function(evt) { evt.preventDefault(); });
});

