// proposal_editor.js

/*  Give focus to whichever  button on the edit form is enabled
 *  Enable the Save button when form data changes.
 *  Enable the Review button when there are saved changes.
 *  Note that both the course and designation editors use the same ids: only one of them
 *  is ever generated at a time.
 *  Manage open/close state of each h2+* section.
 */

$(function()
{
  //  Show/hide sections
  //  ===================================================================================
  /*  Persistence: If localStorage is available, save state using 'h2-t' as the key,
   *  where 't' is the tabindex (that we set) for the heading.
   *
   *  Function: Max one section open at a time, the last one selected here.
   */
    var state_key = '';
    var index     = 1;

    $('h2')
    //  each()
    //  ---------------------------------------------------------------------------------
    /*  Set up tab indices and close all sections except, possibly, one that was marked
     *  open (display:block) in localStorage.
     */
    .each(function()
    {
      state_key = 'h2-' + index;
      $(this).attr('tabindex', index++);
      if (localStorage)
      {
        if (localStorage.getItem(state_key) !== 'block')
        {
          $(this).next().hide();
        }
      }
    })

    //  Mouse
    //  ---------------------------------------------------------------------------------
    /*  Toggle the one clicked on.
     */
    .click(function()
    {
      state_key = 'h2-' + $(this).attr('tabindex');
      if ('none' === $(this).next().css('display'))
      {
        $(this).next().show();
        if (localStorage) localStorage.setItem(state_key, 'block');
        var input = $(this).next().find('input,textarea')[0];
        if (input) input.focus()
      }
      else
      {
        $(this).next().hide(250);
        if (localStorage) localStorage.setItem(state_key, 'none');
      }
    })

  //  Keyboard
  //  ----------------------------------------------------
  //  Respond to arrow and enter keys
    .keydown(function(evt)
    {
      state_key = 'h2-' + $(this).attr('tabindex');
      switch (evt.which)
      {
        //  Enter: toggle
        case 13:
          if ('none' === $(this).next().css('display'))
          {
            $(this).next().show(250);
            if (localStorage) localStorage.setItem(state_key, 'block');
            var input = $(this).next().find('input,textarea')[0];
            if (input) input.focus()
          }
          else
          {
            $(this).next().hide(250);
            if (localStorage) localStorage.setItem(state_key, 'none');
          }
          break;

        //  Left arrow: close
        case 37:
          $(this).next().hide(250);
          if (localStorage) localStorage.setItem(state_key, 'none');
          break;

        //  Right arrow: open
        case 39:
          $('h2 + *').hide(250);
          $(this).next().show(250);
          if (localStorage) localStorage.setItem(state_key, 'block');
          var input = $(this).next().find('input,textarea')[0];
          if (input) input.focus()
          break;

        //  Up arrow: tab backward
        case 38:
          {
            var tabindex = $(this).attr('tabindex');
            tabindex--;
            $('[tabindex="' + tabindex + '"]').focus();
          }
          break;

        //  Dn arrow: tab forward
        case 40:
          {
            var tabindex = $(this).attr('tabindex');
            tabindex++;
            $('[tabindex="' + tabindex + '"]').focus();
          }
          break;
        default:
          break;
      }
    });

    //  Escape from an open section
    $('h2+*').keydown(function(evt)
    {
      if (evt.which === 27)
      {
        $(this).hide('slow');
        $(this).prev().focus();
        var state_key = 'h2-' + $(this).prev().attr('tabindex');
        if (localStorage) localStorage.setItem(state_key, 'none');
      }
    });

    //  <F2> to show hide instructions
    $('body').keydown(function(evt)
    {
      if (evt.which === 113)
      {
        var text = $('#show-hide-instructions-button').text();
        if ('Hide Instructions' === text)
        {
          text = 'Show Instructions';
          $('#show-hide-instructions-button').text(text);
          $('.instructions').hide(250);
        }
        else
        {
          text = 'Hide Instructions';
          $('#show-hide-instructions-button').text(text);
          $('.instructions').show(250);
        }
        localStorage.setItem(shi, text);
        evt.preventDefault();
      }
    });


  //  Syllabus upload
  //  -----------------------------------------------------------------------------------
  /*  Manage the state of the Upload button based on whether a file with the correct
   *  extension has been selected or not.
   *  TODO: Check for valid course string.
   */
    $('#syllabus-file').change(function(evt)
    {
      var file_name = $.trim($(this).val());
      var matches = /\.([a-z]{3,5})$/i.exec(file_name);
      //  Woo-hoo! Figured out how to use "in" instead of looping through an array of
      //  strings:
      if (matches && matches[1] in {pdf:'', doc:'', docx:'', pages:'', rtf:'', txt:''})
      {
        $('#syllabus-submit').removeAttr('disabled');
      }
      else
      {
        $('#syllabus-submit').attr('disabled', true);
        if (file_name)
        {
          alert("'" + file_name + "' does not have a recognized file type for syllabi.\n" +
          "Recognized extensions are .pdf, .doc, .docx, .pages, .rtf, and .txt");
          $(this).val('');
        }
      }
    });

    var status_bar_height = $('#status-bar').height();
    //  Don't show "unsaved edits" message on initial page load
    $('.unsaved-edits').css('visibility', 'hidden');

    //  Focus management
    var scroll_to_editor = false;
    if ($('#save-changes').is(':enabled'))
    {
      $('#save-changes-nav').removeAttr('disabled');
      $('#save-changes').focus();
      scroll_to_editor = true;
      }
    if ($('#submit-proposal').is(':enabled'))
    {
      $('#submit-proposal-nav').removeAttr('disabled');
      $('submit-proposal').focus();
      scroll_to_editor = true;
    }
    if (scroll_to_editor)
    {
      var top_of_section = $('#edit-proposal-section').offset().top;
      $('html,body').scrollTop(top_of_section - status_bar_height - 20);
    }

    //  Warn user when any input value changes
    $('#editor input, #editor textarea, #editor select')
    .change(function(evt)
    {
      //  Enable Save; disable Submit
      $('#save-changes,#save-changes-nav')
        .css('color', 'red')
        .removeAttr('disabled');
      $('.unsaved-edits').css('visibility', 'visible');

      //  Disable Submit Proposal: that has to be enabled by PHP when the proposal has
      //  been saved.
      $('#submit-proposal,#submit-proposal-nav')
        .attr('disabled', 'disabled');
    });

    //  submit()
    //  ----------------------------------------------------------------------------------
    /*  Disable both Save and Submit when form is submitted.
     *
     *  Note: Submitting the #editor form saves the form data in the database. To the
     *  user, this is how to "save your work."
     *  The #submit-proposal form is used to submit a proposal by sending a verification
     *  email that the use must verify.
     */
    $('#editor').submit(function(evt)
    {
      $('#save-changes,#save-changes-nav').attr('disabled', 'disabled');
      $('#submit-proposal,#submit-proposal-nav').attr('disabled', 'disabled');
      $('.unsaved-edits').css('visibility', 'hidden');
    });

    //  nav buttons
    //  ----------------------------------------------------------------------------------
    /*  The second nav bar has buttons for scrolling to different sections.
     *  (See also -nav buttons below.)
     *  When clicked, close all sections, open the selected one, and scroll it to the top
     *  of the page.
     */
    $('.nav-button').click(function(evt)
    {
      //  Close all sections
      $('h2+*').hide();
      //  The id of the button is xxx-nav, where xxx is the id of the section to open and
      //  scroll to.
      var id = $(this).attr('id');
      id = id.substr(0, id.length -4);
      var is_hidden = ($('#' + id + '+*').css('display') === 'none');
      if (is_hidden)
      {
        $('#' + id).click();
      }
      //  scroll section heading to just below the status bar
      var top_of_section = $('#' + id).offset().top;
      $('html,body').scrollTop(top_of_section - status_bar_height - 20);
    });

    //  -nav buttons
    //  ----------------------------------------------------------------------------------
    /*  There are redundant save and submit buttons in the second nav bar: when the user
     *  clicks on save, submit the editor form. When the user clicks on submit, do a page
     *  load to the review_xxx_proposal.php page.
     */
    $('#save-changes-nav').click(function(evt)
    {
     $('#editor').submit();
    });
    $('#submit-proposal-nav').click(function(evt)
    {
      $('#review-proposal').submit();
    });

  });

