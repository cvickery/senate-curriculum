// proposal_status.js

$().ready(function(e)
{
	var cur_num_checked = 0;
	$('input[type=checkbox]').each(function(index, element) {
    cur_num_checked++;
  });
	$('input[type=checkbox]').change(function(e)
	{
		if (this.checked)
		{
			cur_num_checked++;
		}
		else
		{
			cur_num_checked--;
		}
		$('#num-accept').text('Approve ' + cur_num_checked + ' Proposals');
	});
});