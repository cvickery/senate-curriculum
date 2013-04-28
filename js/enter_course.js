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
  var build_prompt_list = function(next_char)
  {
    var current_value = $('#discipline').val();
    if (next_char) current_value += String.fromCharCode(next_char);

    //  RegExp
    /*    Proper prefix of actual code
     *    All characters appear in name, in order
     */

    $('#prompt-list').empty();
    for (var i = 0; i < disciplines.length; i++)
    {
      match = false;
      var this_code = disciplines[i].code;
      if ( disciplines[i].code.indexOf(current_value) > -1) match = true;
      else
      {
        //  look for all input chars to mach name chars, in order, but not necessarily
        //  contiguously
        var last_match_position = -1;
        for (var c = 0; c < current_value.length; c++)
        {
          ;
        }
      }
      if (match)
      {
        var startTag = '<li>';
        if ( i == select_index ) startTag = "<li class='highlight'>";
        $(startTag + disciplines[i].prompt + '</li>').appendTo('#prompt-list');
      }
    }
  }

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
                    build_prompt_list(0);
                  }
                  break;
        case 40:  //  down arrow
                  if ( select_index < (disciplines.length -1) )
                  {
                    select_index++;
                    build_prompt_list(0);
                  }
                  break;
        default:  break;
      }
    });
});
