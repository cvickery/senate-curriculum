//  guidelines.js
//  Keyboard and mouse navigation of the Guidelines document.
$(function()
  {
    //  Will be used for keyboard navigation (tab key; up/down arrows)
    var num_tabs = 0;
    //  Hide all sections initially; click on an open section to hide it, too.
    $('h2+div').hide().click(function(){$(this).hide(200);});
    
    //  For all h2 elements
    $('h2').
    
    //  Show/Hide the div when heading is clicked
    click(function()
    {
      if ($(this).next().css('display') === 'none')
			{
				$(this).next().show(200);
				$(this).addClass('open');
				$(this).removeClass('closed');
			}
      else
			{
				$(this).next().hide(200);
				$(this).addClass('closed');
				$(this).removeClass('open');
			}
    }).
    
    //  Keyboard navigation
    keydown(function(evt)
    {
      switch (evt.which)
      {
        //  Enter: toggle visibility
        case 13:
          if ($(this).next().css('display') === 'none')
					{
            $(this).next().show(200);
						$(this).addClass('open');
						$(this).removeClass('closed');
					}
          else
					{
						$(this).next().hide(200);
						$(this).addClass('closed');
						$(this).removeClass('open');
					}
          break;
					
				//  Left arrow: close displayed section
        case 37:
          if ($(this).next().css('display') === 'block')
					{
						$(this).next().hide(200);
						$(this).addClass('closed');
						$(this).removeClass('open');
					}
					break;
				//  Right arrow: open closed section
        case 39:
          if ($(this).next().css('display') === 'none')
					{
            $(this).next().show(200);
						$(this).addClass('open');
						$(this).removeClass('closed');
					}
          break;

        //  Up arrow: move to previous section
        case 38:
            var tabindex = $(this).attr('tabindex');
            if (tabindex > 0)
					  {
							tabindex--;
              $('[tabindex="'+tabindex+'"]').focus();
					  }
            break;
            
        //  Down arrow: move to next section
        case 40:
          {
            var tabindex = $(this).attr('tabindex');
            if (tabindex < num_tabs) tabindex++;
            $('[tabindex='+tabindex+']').focus();
          }
          break;
        //  Ignore all other keys
        default:
          break;
      }
    }).
    
    //  Add tabindex to each heading
    each(function()
      {
        $(this).attr('tabindex', num_tabs++);
      }
      )[0].focus();
  });
