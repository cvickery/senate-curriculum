$(function()
{
  $('input').bind('change', function(evt)
  {
    var names = this.name.split(':');
    var query = "UPDATE proposal_course_mappings set cf_number = '" + this.value +
     "' WHERE discipline = '" + names[0] + "' AND number = '" + names[1] + "'";
     var msg_cell = this.parentNode.nextSibling;
     msg_cell.style.color = 'red';

    $.ajax('scripts/translate_num.php', {
      async: false,
      data : 'query=' + query,
      success: function(data, status, xhr)
      {
        msg_cell.textContent = data;
      }
      });
  });
});
