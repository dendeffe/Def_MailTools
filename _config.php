<?php

/****************************************************
* IT'S BEST TO SET THESE UP IN mysite/_config.php
* SO THEY WON't BE OVERWRITTEN WHEN YOU UPDATE
* THIS MODULE
****************************************************/

/* SETUP YOUR FROM MAIL */
// Set the admin email address and admin name that will appear in the from field.
// If you leave this blank, noreply@[yourdomain.com] will be used
//Def_MailTools::set_admin_mail('info@mydomain.com', 'Bob Geldof');


/* SET YOUR CREDENTIALS FOR THE MAIL SERVER */

// Example 1: for an SMTP server, for example Gmail.
// If your SMTP server doesn't need credentials, you can skip this step
//Def_MailTools::set_smtp_credentials('myname@gmail.com', 'password');

// -or- Example 2: for SendGrid.com:
//Def_MailTools::set_sendgrid_credentials('myname@mydomain.com', 'password');