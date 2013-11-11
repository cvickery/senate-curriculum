//  request_rd_change.js
$(function()
{
  var trim = function(str)
  {
    if (str !== '')
    {
      matches = /^\s*(\S*)\s*$/.exec(str);
      return(matches[1]);
    }
    return '';
  }
  var check_ok2submit = function()
  {
    if (
          (trim($('#email').val()).length > 2)  &&
          ($('#course').val() !== 'none')       &&
          ($('input[type=radio]:checked').length > 0)
        )
    {
      $('button').removeAttr('disabled').text('Submit');
    }
    else
    {
      $('button').attr('disabled', 'disabled').text('Incomplete')
    }
  }
  $('#email').change(function()
  {
    var email_str = trim($(this).val());
    if (email_str.length > 2)
    {
      if (email_str.indexOf('@') > -1)
      {
        email_str = email_str.substr(0, email_str.indexOf('@'));
      }
      $(this).val(email_str);
    }
    check_ok2submit();
  });
  $('#course').change(function()
  {
    var course = $(this).val();
    $('.course-div').hide(250);
    $('#' + course + '-div').show(250);
    $('input[type=radio').removeAttr('checked');
    check_ok2submit();
  });
  $('input[type=radio]').change(function()
  {
    check_ok2submit();
  });
});
