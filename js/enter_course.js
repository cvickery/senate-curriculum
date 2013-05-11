//  enter_course.js

$(function()
{
  //  Get complete list of discipline codes and names to use as selection items.
  var disciplines   = null;
  var prompts       = null;
  var prompt_list   = '';
  var select_list   = [];
  var select_index  = 0;

  //  build_prompt_list()
  //  -----------------------------------------------------------------------------------
  /*  Look for disciplines that match what the user has entered so far.
   *  The argument next_char, if not zero, is a char code to be appended to the input
   *  value, used when processing keydown events to handle the fact that the char has not
   *  become part of the value yet.
   *
   *  Perform up to two passes over all possible disciplines:
   *
   *    1.  If the current input matches any discipline codes, either fully or as a proper
   *    prefix, return the list of matched disciplines.
   *    2.  If each char in the current input matches a char in a discipline name, in
   *    sequence but not necessarily contiguously, the match list is all matched, with the
   *    first one selected.
   */
  var build_prompt_list = function(next_char)
  {
    var current_input = $('#discipline').val();
    if (next_char) current_input += String.fromCharCode(next_char);
    current_input = current_input.toLowerCase();

    $('#prompt-list').empty();
    select_list = [];

    //  Case 1: search for matching discipline codes
    for (var i = 0; i < disciplines.length; i++)
    {
      var this_code = disciplines[i].code.toLowerCase();
      if ( this_code.substring(0, current_input.length) === current_input )
      {
        select_list.push(disciplines[i]);
      }
    }

    //  Case 2: search for matching disipline names
    if (select_list.length < 1)
    {
      for (var i = 0; i < disciplines.length; i++)
      {
        //  See if all input chars appear in discipline name, in sequence
        var this_name = disciplines[i].name.toLowerCase();
        match = true; // until a mismatch
        for (var c = 0; c < current_input.length; c++)
        {
          var match_position = this_name.indexOf(current_input[c]);
          if (match_position > -1)
          {
            this_name = this_name.substr(match_position + 1);
            continue;
          }
          else
          {
            match = false;
            break;
          }
        }
        if (match)
        {
          select_list.push(disciplines[i]);
        }
      }
    }

    //  display each element (if any) in select_list
    for (var i = 0; i < select_list.length; i++)
    {
      $("<li>" + select_list[i].prompt + "</li>").appendTo('#prompt-list');
    }
    //  Highlight the first one
    select_index = 0;
    set_highlight();
  };

  //  set_highlight()
  //  -----------------------------------------------------------------------------------
  /*  Highlight the nth item in the select list after removing highlighting from any that
   *  are already highlighted.
   */
    var set_highlight = function()
    {
      $('#prompt-list li').removeClass('highlight');
      $('#prompt-list li:nth-child(' + (select_index + 1) +
            ')').addClass('highlight').scrollIntoView(false);
    };

  //  Get disciplines from db
  //  -----------------------------------------------------------------------------------
  $.ajax({url:'get_disciplines.php'}).done(function(data)
  {
    disciplines = $.parseJSON(data);
    for (var i = 0; i < disciplines.length; i++)
    {
      var code = disciplines[i].code;
      var name = disciplines[i].name;
      disciplines[i].prompt = code + ' - ' + name;
    }
    $("<ul id='prompt-list'></ul>").appendTo('form');
    build_prompt_list(0);
  });


  //  Event Handlers
  //  -----------------------------------------------------------------------------------
  /*  TODO: You still have to (update selected index and) move focus to next field.
   */
  $('#discipline').focus(function()
  {
    var input_offset = $('#discipline').offset();
    var input_height = $('#discipline').height();
    var where = { top: input_offset.top + input_height + 8, left: input_offset.left };
    $('#prompt-list')
      .show()
      .offset(where);
    $('#prompt-list li').on('mousedown', function(evt)
      {
        evt.preventDefault(); //  prevent blur on #discipline
        var prompt_str = $(this).html();
console.log("mousedown w/ this.html = " . prompt_str);
        var code = prompt_str.substr(0, prompt_str.indexOf(' '));
        //  Find code in disciplines, and make that the single item in select_list
        for (var i = 0; i < disciplines.length; i++)
        {
          if (code === disciplines[i].code)
          {
            select_list = [ disciplines[i] ];
            select_index = 0;
            break;
          }
        }
        $('#discipline').val(code);
        $('#prompt-list').hide();
      });
  });

  $('#discipline').blur(function()
  {
    $('#discipline').val(select_list[select_index].code);
    $('#prompt-list').hide();
  });

  /*    up/down arrows: Highlight next/previous list item.
   *    tab/enter:      If an element is highlighted, use its value. Otherwise
   *                    just dismiss the prompt list without
   *                    changing the value entered.
   *    other:          Use input value to filter
   */
    //  Keypress gives you ASCII chars, including enter
    $('#discipline').keypress(function(event)
    {
      if ( event.which > 31 && event.which < 123 )
      {
        build_prompt_list(event.which);
      }
    });

    //  Keyup gives you bs, with repeats
    $('#discipline').keyup(function(event)
    {
      if (event.which == 8) build_prompt_list(0);
    });
    //  Keydown gives you up, dn, with repeats
    $('#discipline').keydown(function(event)
    {
      switch (event.which)
      {
        //  Totally rebuild the select list, with proper element highlighted.
        //  TODO: this is a horribly inefficient way to handle arrows.
        case 38:  //  up arrow
                  if (select_index > 0)
                  {
                    select_index--;
                    set_highlight();
                  }
                  break;
        case 40:  //  down arrow
                  if ( select_index < (disciplines.length -1) )
                  {
                    select_index++;
                    set_highlight();
                  }
                  break;
        default:  break;
      }
    });
});
