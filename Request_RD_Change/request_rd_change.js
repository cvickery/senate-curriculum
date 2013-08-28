//  request_rd_change.js
$(function()
{
$('#course').change(function(evt)
  {
    var course = $(this).val();
    $('.course-div').hide(250);
    $('#' + course + '-div').show(250);
  }
 );
});
