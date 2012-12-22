// Review/js/review.js

$(
  function()
  {
    $('td button').attr('disabled', true);
    $('select, textarea').change(function()
    {
      $(this)
        .parent()
        .parent()
        .children(':last-child')
        .children('button')
        .removeAttr('disabled')
        .css('color', 'red')
				.focus();
    });
  }
 );
