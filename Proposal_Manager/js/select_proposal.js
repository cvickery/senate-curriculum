//  select_proposal.js

/*  Handle the Select or Create Proposal fields in select_proposal.php, as well as the
 *  syllabus upload form (because it needs the same discipline prompting code).
 *    #proposal-selection-table lets user select an open proposal using a set of radios
 *    #course-and-proposal-entry has course-str (input) and proposal-type (select) for
 *      creating a new proposal.
 *    #syllabus-upload has course-str and file-name, inputs of type text and file.
 */

//  Suggestion list for disciplines (TODO: keyboard navigation through the list)
//  -------------------------------------------------------------------------------------
$(function() {

//  Valid course string detector
var course_str_re = /^\s*([a-z]{2,6})\s*\-?\s*([1-9]\d{0,3}[wh]?)\s*$/i;

//  check_course_and_type()
//  -------------------------------------------------------------------------------------
/*  Used for creating a proposal: discipline must be in disciplines; course number must be
 *  1-4 decimal digits with no leading zeros, followed by optional W or H.
 *  Proposal type must select a value.
 *  Otherwise, return false.
 */
  var check_course_and_type = function()
  {
    if ( '' === $('#proposal-type').val()) return false;
    var course_str = $('#course-str-p').val();
    var matches = course_str_re.exec(course_str);
    if (!matches || matches.length !== 3) return false;
    var discipline_code = matches[1].toUpperCase();
    for (var discp in disciplines)
    {
      if (discipline_code === discp) return true;
    }
    return false;
  };

//  check_course_and_file()
//  -------------------------------------------------------------------------------------
/*  Used for syllabus uploads: discipline and coures number as above; file name must have
 *  one of the acceptable extensions.
 */
  var check_course_and_file = function()
  {
    if ('' === $('#syllabus-file').val()) return false;
    var course_str = $('#course-str-s').val();
    var matches = course_str_re.exec(course_str);
    if (!matches || matches.length !== 3) return false;
    var discipline_code = matches[1].toUpperCase();
    for (var discp in disciplines)
    {
      if (discipline_code === discp) return true;
    }
    return false;

  };

//  Selection Table management
//  =====================================================================================
$('#proposal-selection-table input').change(function(evt)
    {
      $('#proposal-selection-table tr').removeClass('selected-row');
      if (0 == $(this).val())
      {
        //  None of the above: deselect current one (perhaps so it can be deleted).
        $('#select-proposal').text('Deselect Current Proposal').removeAttr('disabled');
      }
      else
      {
        //  Show the selected course in the submit button and enable it
        /*  Row structure:
         *  radio | id   | opened    | submitted | type | course | syllabus | delete
         *  [0]   | [1]  | [2]       | [3]       | [4]  | [5]    | [6]      | [7]
         *  this  | next | next-next | etc
         */
        var row     = $(this).parent().parent().addClass('selected-row').children();
        var id      = row[0].childNodes[0].value;
        var course  = row[5].childNodes[0].nodeValue;
        $('#select-proposal').text('Edit proposal #' + id + ' for ' + course)
            .removeAttr('disabled');
      }
    });

//  Clicking on any cell in a row (except the Syllabus link?) is the same as clicking on
//  the radio in the first column.
//  -----------------------------------------------------------------------------------
$('#proposal-selection-table tr').click(function(evt)
  {
    //  TODO: not working consistently.
    //    All clicks detected, but sometimes the radio doesn't change. Reliability
    //    degrades with repeated use: resource exhaustion?
    $('input', this).change().attr('checked', 'checked');
  });

//  TODO: the change() function above and the click() function below use 'row' in
//  inconsistent ways, sometimes as the row and sometimes as the collection of td
//  children. Conistentificization would be appropriate.

//  Proposal deletion from selection table
//  -------------------------------------------------------------------------------------
$('.delete-proposal').click(function(evt)
  {
    var row = $(this).parent().children('td');
    var old_bg = row.css('background-color');
    row.css('background-color', '#fee');
    var id = row[0].childNodes[0].value;
    var course = row[5].childNodes[0].nodeValue;
    var msg = 'Are you sure you want to delete proposal #' + id + ' for ' + course + '?';
    var reply = confirm(msg);
    if (reply)
    {
      $.ajax(
      {
        type:     'POST',
        url:      'scripts/delete_proposal.php',
        data:     'proposal_id=' + id,
        dataType: 'text',
        success:  function(response, status, jqXHR)
                  {
                    if (response === 'fail') alert('Deletion failed');
                    else
                    {
                      //  Delete the proposal from the selection table
                      row.hide();
                      //  If the proposal's radio was checked, the select-proposal button
                      //  has to be disabled
                      var button_text = $('#select-proposal').text();
                      if (button_text.indexOf(id) !== -1)
                      {
                        $('#select-proposal').text('Select a Proposal').attr('disabled',
                          'disabled');
                      }
                    }
                  }
      });
    }
    row.css('background-color', old_bg);
  });

  //  Prompt list for disciplines
  //  ===================================================================================
  var disciplines     = [];
  var num_suggestions = 0;
  var cur_suggestion  = -1;
  var this_val        = '';
  var this_prefix     = '';
  $.getJSON('scripts/get_disciplines.php',
    function(x)
    {
      disciplines = x;
    });

  //  gen_list()
  //  -----------------------------------------------------------------------------------
  /*  Generate list of discipline codes or discipline names for which currrent value is a
   *  valid prefix.
   *  Differntiate between valid discipline prefix followed by nothing and exact
   *  discipline followed by allowable suffix.
   */
    function gen_list()
    {
      var allowed_suffix = '';
      var must_match = false;
      num_suggestions = 0;
      this_prefix     = '';
      var returnHTML = '';
      var matches = /^\s*([a-z]+)(.+)?$/i.exec(this_val);
      if (!matches || (matches.length < 2)) return ''; // Invalid prefix
      this_prefix = matches[1].toUpperCase();
      if (matches[2]) allowed_suffix = '$';
      re = new RegExp('^' + this_prefix + allowed_suffix);
      for (discp in disciplines)
      {
        if (re.test(discp))
        {
          num_suggestions++;
          returnHTML += "<li>" + discp + " (" + disciplines[discp] + ")</li>";
        }
      }
      cur_suggestion = -1;
      if (num_suggestions < 1) return '';
      return returnHTML;
    }

  $('.course-str').attr('autocomplete', 'off');

  /*  Process key strokes in the two course-str input fields. They both have class
   *  course-str: #course-str-p is in the form for creating a proposal, and #course-str-s
   *  is in the form for uploading a syllabus.
   *  $(this) is the text input field; the next element is a span for displaying a message
   *  about the nature of what's been typed so far; and the next element after that is the
   *  ul for displaying discipline possibilities.
   */
  $('.course-str').keyup(
  function(evt)
  {

    this_val = $.trim($(this).val());
    if (this_val === '')
    {
      //  Nothing typed, nothing to suggest
      $(this).next().html('');
      $(this).next().next().hide();
    }
    else
    {
      //  Display new suggestion list, if there is more than one
      $(this).next().next().html(gen_list(this_val)).show();
      //  Update status message: num_suggestions is set as a side effect of gen_list.
      switch (num_suggestions)
      {
        case 0:
          //  Non-blank value with no suggestions: invalid discipline
          $(this).next()  .removeClass('is-good warning')
                          .addClass('error')
                          .html('invalid discipline');
          break;
        case 1:
          //  Exactly one suggestion:
          /*  Only prefix has been entered: keep typing
           *  Valid discipline
           *    No number:      valid discipline; keep typing
           *    Valid number:   valid course string
           *    Invalid number: invalid course number
           */
          //  Is the prefix actually the full discipline code?
          var the_discipline = ''
          for (discp in disciplines)
          {
            if (discp === this_prefix)
            {
              the_discipline = discp;
              break;
            }
          }
          if (! the_discipline)
          {
            //  Incomplete discipline
            $(this).next()  .removeClass('is-good error')
                            .addClass('warning')
                            .html('Keep typing discipline code');
          }
          else
          {
            //  Have discipline: is course number missing, (possibly) valid, or invalid?
            $(this).next().next().hide();
            //  trim leading spaces and hyphen; trailing spaces
            this_val = this_val.substr(the_discipline.length)
              .replace(/^\s+|\s+$/g, '').replace(/^-\s*/, '');
            if (this_val === '')
            {
              // No number yet
              $(this).next()  .removeClass('is-good error')
                              .addClass('warning')
                              .html('Now enter course number');
            }
            else if ( /^[1-9]\d{0,3}[wh]?$/i.test(this_val) ) // Check course number
            {
              //  Valid string
              $(this).next()  .removeClass('warning error')
                              .addClass('is-good')
                              .html('Valid course string');
            }
            else
            {
              $(this).next()  .removeClass('is-good warning')
                              .addClass('error')
                              .html('Invalid course number');
            }
          }
          break;
        default:
          //  Multiple suggestions
          $(this).next()  .removeClass('error is-good')
                          .addClass('warning')
                          .html('Keep typing discipline code');
          break;
      }
    }
  });

  //  Enable/disable Create Proposal button
  $('#course-and-proposal-entry input, #course-and-proposal-entry select')
  .change(function(evt)
  {
    if (check_course_and_type())
    {
      $('#create-proposal').removeAttr('disabled').focus();
    }
    else
    {
      $('#create-proposal').attr('disabled', true);
    }
  });

  //  Enable/disable Upload Syllabus button
  $('#syllabus-upload input').change(function(evt)
  {
    if (check_course_and_file())
    {
      $('#upload-syllabus').removeAttr('disabled').focus();
    }
    else
    {
      $('#upload-syllabus').attr('disabled', true);
    }
  });
});

