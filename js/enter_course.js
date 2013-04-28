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

    //  TODO; build the regexp from the value, not from all the disciplines??

    $('#prompt-list').empty();
    for (var i = 0; i < disciplines.length; i++)
    {
      var this_regex = disciplines[i].regex;
      if (this_regex.test(current_value))
      {
        var startTag = '<li>';
        if ( i == select_index ) startTag = "<li class='highlight'>";
        $(startTag + disciplines[i].prompt + '</li>').appendTo('#prompt-list');
      }
      else
      {
        console.log(current_value + ' does not match ' + this_regex);
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
      var regex = '^\s*' + code + '[\w\s]*';
      for (var n = 0; n < name.length; n++)
      {
        regex += name[n] + '[\w\s]*';
      }
      disciplines[i].regex = new RegExp(regex, 'i');
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
