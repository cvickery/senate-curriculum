// Curriculum/gened_offerings/js/gened_offerings.js

/*  The summary tables are generated after the detail table, but is to appear above the latter.
 *  To save screen space, the user has a "Hide These Instructions" that removes a div from the
 *  top of the page, causing the output tables to move up.
 */
$(document).ready(function()
{
  //  position_elements()
  //  -----------------------------------------------------------------------------------------
  /*  Separate function to do the positioning, called once when the page loads (and the table
   *  geometries are known), and again if the user hides the instructions.
   */
    function position_elements()
    {
      var instructions_height = $('#instructions').height();
      var display_options_position = $('#display-options').position();
      var display_options_height = 8 + $('#display-options').height();
      var summary_tables_position = {top:  display_options_position.top + display_options_height,
                                     left: display_options_position.left};
      var summary_tables_height = 8 + $('#summary-tables').height();
      $('#summary-tables').offset(summary_tables_position);
      var detail_table_position = {top:  summary_tables_position.top + summary_tables_height,
                                    left: summary_tables_position.left};
      $('#detail-table').offset(detail_table_position);
    }

  //  Set up the sort controls for the detail table's columns 
  $('#the-list').tablesorter();
  
  //  Enable the Hide Instructions button, and set up the click handler
  $('#hide-show-instructions').removeAttr('disabled');
  $('#hide-show-instructions').click(function(evt)
  {
    $('#instructions').hide();
    position_elements();
  });
  
  //  Initial positioning 
  position_elements();
});
