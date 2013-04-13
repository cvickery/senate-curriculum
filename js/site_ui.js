//  site_ui.js

/*  Handle initial focus; hiding/displaying instructions; opening/closing sections.
 */
$(function()
{
  //  Get rid of the "need JS" message
  $('#need-javascript').hide();

  //  Put focus on first input element in tab sequence, if there is one
  $('[tabindex="1"]').first().focus();

  //  Function to set height of scrollable section of page based on window size
  $(window).resize(function()
  {
    var window_height = $(window).height();
    var status_height = $('#status-bar').outerHeight();
    var div_height    = window_height - status_height - 60; // 60 "works"
    $('#status-bar + div').outerHeight(div_height);
  });

  //  Trigger window resize to initialize scrollable size
  $(window).resize();

  //  Check whether browser supports localStorage
  if (typeof window.localStorage === 'undefined')
  {
    if (typeof window.sessionStorage != 'undefined')
    {
      //  Session storage is better than no storage at all
      window.localStorage = window.sessionStorage;
    }
    else
    {
      //  Worst case: provide a noop version so the code doesn’t have to test for it all
      //  the time.
      window.localStorage             = {};
      window.localStorage.getItem     = function()            { return null; }
      window.localStorage.setItem     = function(key, value)  { return null; }
      window.localStorage.removeItem  = function(key)         { return null; }
      window.localStorage.clear       = function()            { return null; }
    }
  }

  //
  //  Show/hide instructions when page is loaded
  var shi = 'show_hide_instructions';
  if (! localStorage.getItem(shi))
  {
    localStorage.setItem(shi, 'Show Instructions');
  }

  if (localStorage.getItem(shi) === 'Hide Instructions')
  {
    $('#show-hide-instructions-button').text('Hide Instructions');
    $('.instructions').show();
  }
  else
  {
    $('#show-hide-instructions-button').text('Show Instructions');
    $('.instructions').hide();
    localStorage.setItem(shi, 'Show Instructions');
  }

  //  Respond to show/hide instructions button clicks
  $('#show-hide-instructions-button').click(function(evt)
  {
    var text = $(this).text();
    if ('Hide Instructions' === text)
    {
      text = 'Show Instructions';
      $(this).text(text);
      $('.instructions').hide(250);
    }
    else
    {
      text = 'Hide Instructions';
      $(this).text(text);
      $('.instructions').show(250);
    }
    localStorage.setItem(shi, text);
  });

  //  Insert heading for instructions
  $('.instructions').each(function()
  {
    var h3node = document.createElement('h3');
    h3node.textContent = 'Instructions:';
    this.insertBefore(h3node, this.firstChild);
  });

});

