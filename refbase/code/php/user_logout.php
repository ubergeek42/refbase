<?php
	// This script logs a user out and redirects 
	// to the calling page. If the script is called
	// unexpectedly, an error message is generated.

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'include.inc'; // include common functions

	// --------------------------------------------------------------------

	// Restore the session
	session_start();

	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// --------------------------------------------------------------------

	// Is the user logged in?
	if (session_is_registered("loginEmail"))
	{
		session_unregister("loginEmail"); // remove the user's email address (as a result the user will be logged out)

		// clear other session variables we've registered on login:
		session_unregister("loginUserID"); // clear the user's user ID
		session_unregister("loginFirstName"); // clear the user's first name
		session_unregister("loginLastName"); // clear the user's last name
		session_unregister("abbrevInstitution"); // clear the user's abbreviated institution name

		if (session_is_registered("HeaderString"))
			session_unregister("HeaderString"); // clear any previous messages
	}
	else
	{
		session_register("HeaderString"); // save an error message
		$HeaderString = "<b><span class=\"warning\">You cannot logout since you were not logged in anymore!</span></b>";
	}

	if (!preg_match("/.*user_(details|receipt)\.php.*/", $HTTP_REFERER))
		header("Location: $HTTP_REFERER"); // redirect the user to the calling page
	else
		header("Location: index.php"); // back to main page

	// a more smart solution would be something like the code below:
	// (but '$referer' isn't registered yet across all pages!)

//	// Redirect the browser back to the calling page
//	if (session_is_registered("referer"))
//	{  
//		// Delete the redirection session variable
//		session_unregister("referer");
//		
//		// Then, use it to redirect to the calling page
//		header("Location: $referer");
//		exit;
//	}
//	else
//		header("Location: index.php");
?>
