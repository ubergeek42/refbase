function confirmDelete(submitAction)
{
	if (submitAction.value == 'Delete Record')
	{
		Check = confirm("Really DELETE this record?");
		if(Check == false)
			return false;
		else
			return true;
	}
	else
		return true;
}
