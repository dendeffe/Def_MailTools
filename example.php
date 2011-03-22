<?php

/****
/ CONFIG
***/

// You can do these config settings in mysite/_config.php


/* SETUP YOUR FROM MAIL */
// Set the admin email address and admin name that will appear in the from field.
// If you leave this blank, noreply@[yourdomain.com] will be used
Def_MailTools::set_admin_mail('info@mydomain.com', 'Bob Geldof');


/* SET YOUR CREDENTIALS FOR THE MAIL SERVER */

// Example 1: for an SMTP server, for example Gmail.
// If your SMTP server doesn't need credentials, you can skip this step
Def_MailTools::set_smtp_credentials('myname@gmail.com', 'password');

// -or- Example 2: for SendGrid.com:
Def_MailTools::set_sendgrid_credentials('myname@mydomain.com', 'password');



/***
/ SENDING MAIL
***/

// Use this anywhere in your code

/* LIST YOUR RECIPIENTS IN AN ARRAY */

$recipients = array(
	'jane@domain.com',
	'bob@domain.com',
	'george@domain.com'
);

/* SET THE HTML CONTENT */
// Links and images that are linked relatively to SilverStripe assets
// will be turned into absolute links
// CSS from templates will be turned to inline styles

$htmlContent = "
<h3>Welcome to this mail</h3>
<p>This is a paragraph…</p>
<img src=\"http://www.mydomain.com/widget.jpg\" alt=\"A most interesting widget.\">
<p>Here, I also provide a direct link to said widget: <a href=\"http://www.mydomain.com/widget.jpg\">Link to widget</a></p>";


/* SET PLAIN TEXT CONTENT */
// Choose an alternative for your HTML content
// If you don't provide this, a stripped version of the HTML content will be used.

$plainTextContent = "My plain text alternative.";


/* LET Def_MailTools BUILD the mailcontent */

$mailContent = Def_MailTools::BuildMail($htmlContent, $plainTextContent);

// Example 1: Send through a SMTP server
// These are the parameters: send_mail_SMTP($recipients, $title, $content, $also_send_to_admin = false, $server, $port = "25", $use_ssl = false, $use_auth = false)
Def_MailTools::send_mail_SMTP($recipients, 'My title', $mailContent, true, 'smtp.myserver.com', '25');

// Example 2: Send through Gmail
Def_MailTools::send_mail_SMTP($recipients, 'My title', $mailContent, true, 'smtp.gmail.com', 465, true, true);

// Example 3: Send through SendGrid
// These are the parameters: send_mail_sendgrid($recipients, $title, $content, $category = "Uncategorized", $also_send_to_admin = false, $server ="smtp.sendgrid.com", $port = "465")
Def_MailTools::send_mail_sendgrid($recipients, 'My title', $mailContent, 'Testing Def_MailTools', false);
