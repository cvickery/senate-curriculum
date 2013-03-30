// proposal_status.js
//  Manage changes to number of checkboxes checked in each table.

$().ready(function(e)
{
  //  update_submit_button()
  //  -----------------------------------------------------------------------------------
  /*  Update the number of proposals on the submit button of a form.
   *    which_button      query string to select the submit button
   *    which_table       query string to select a table; this function operates on all
   *                      checkboxes in that table.
   *    label             text string for the button: % will be replaced with
   *                      'n proposal(s)' where n is the number of elements selected
   *  If there are clear-all/set-all buttons, they will be enabled/disabled if either
   *  all or none of the elements are selected.
   */
    function update_submit_button(which_button, which_table, label)
    {
      var num_elements = $(which_table + ' :checkbox').length;
      var num_checked = $(which_table + ' :checked').length;
      var new_text = num_checked + ' proposal' + (num_checked === 1 ? '' : 's');
      $(which_button).text(label.replace('%', new_text));
      if (num_checked === 0)
      {
        $(which_button).attr('disabled', 'disabled');
        $('#clear-all-' + which_button.substring(1)).attr('disabled', 'disabled');
        $('#select-all-' + which_button.substring(1)).removeAttr('disabled');
      }
      else
      {
        $(which_button).removeAttr('disabled');
        $('#clear-all-' + which_button.substring(1)).removeAttr('disabled');
        if (num_checked === num_elements)
        {
          $('#select-all-' + which_button.substring(1)).attr('disabled', 'disabled');
        }
        else
        {
          $('#select-all-' + which_button.substring(1)).removeAttr('disabled');
        }
      }
    }

  $('#geac-approved-table input:checkbox').change(function(e)
  {
    update_submit_button( '#geac-approved-button',
                          '#geac-approved-table',
                          'GEAC approved %');
  });

  $('#ucc-approved-table input:checkbox').change(function(e)
  {
    update_submit_button( '#ucc-approved-button',
                          '#ucc-approved-table',
                          'UCC approved %');
  });

  $('#senate-approved-table input:checkbox').change(function(e)
  {
    update_submit_button( '#senate-approved-button',
                          '#senate-approved-table',
                          'The Senate approved %');
  });
  $('#clear-all-senate-approved-button').click(function(e)
    {
      $('#senate-approved-table input:checkbox').prop('checked', false);
      $('#select-all-senate-approved-button').removeAttr('disabled');
      $('#clear-all-senate-approved-button').attr('disabled', 'disabled');
      update_submit_button( '#senate-approved-button',
                            '#senate-approved-table',
                            'The Senate approved %')
    });
  $('#select-all-senate-approved-button').click(function(e)
    {
      $('#senate-approved-table input:checkbox').prop('checked', true);
      $('#clear-all-senate-approved-button').removeAttr('disabled');
      $('#select-all-senate-approved-button').attr('disabled', 'disabled');
      update_submit_button( '#senate-approved-button',
                            '#senate-approved-table',
                            'The Senate approved %')
    });


  $('#ccrc-submitted-table input:checkbox').change(function(e)
  {
    update_submit_button( '#ccrc-submitted-button',
                          '#ccrc-submitted-table',
                          'Submitted % to the CCRC')
  });
  $('#clear-all-ccrc-submitted-button').click(function(e)
    {
      $('#ccrc-submitted-table input:checkbox').prop('checked',false);
      $('#select-all-ccrc-submitted-button').removeAttr('disabled');
      $('#clear-all-ccrc-submitted-button').attr('disabled', 'disabled');
      update_submit_button( '#ccrc-submitted-button',
                            '#ccrc-submitted-table',
                            'Submitted % to the CCRC')
    });
  $('#select-all-ccrc-submitted-button').click(function(e)
    {
      $('#ccrc-submitted-table input:checkbox').prop('checked', true);
      $('#clear-all-ccrc-submitted-button').removeAttr('disabled');
      $('#select-all-ccrc-submitted-button').attr('disabled', 'disabled');
      update_submit_button( '#ccrc-submitted-button',
                            '#ccrc-submitted-table',
                            'Submitted % to the CCRC')
    });
});

