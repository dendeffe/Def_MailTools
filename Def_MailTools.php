<?php
/**
 * A class to provide SMTP-mailing functionality and access to select 
 * mail services.
 *
 * @package Def_MailTools
 */

class Def_MailTools extends ContentController
{
	protected static $admin_mail = '';
	protected static $admin_mail_name = '';
	protected static $sendgrid_username = '';
	protected static $sendgrid_password = '';
	protected static $smtp_username = '';
	protected static $smtp_password = '';


	/**
	  * Set the default admin email and name
	  *
	  * @param string $mail
	  * @param string $name
	  * @return void
	  */

	public static function set_admin_mail($mail, $name)
	{
		self::$admin_mail = $mail;
		self::$admin_mail_name = $name;
	}

	/**
	  * Get the Admin email adress
	  *
	  * @return string
	  */
	
	public static function get_admin_mail()
	{
		if (self::$admin_mail == '')
		{
			return 'noreply@' . parse_url(Director::absoluteBaseURL(), PHP_URL_HOST);
		} else {
			return self::$admin_mail;
		}
	}
	
	/**
	  * Get the Admin name
	  *
	  * @return string
	  */
	
	public static function get_admin_mail_name()
	{
		if (self::$admin_mail_name == '')
		{
			return self::$admin_mail;
		} else {
			return self::$admin_mail_name;
		}
	}

	/**
	  * Set the SendGrid (http://sendgrid.com/) credentials
	  *
	  * @param string $username
	  * @param string $password
	  * @return void
	  */

	public static function set_sendgrid_credentials($username, $password)
	{
		self::$sendgrid_username = $username;
		self::$sendgrid_password = $password;
	}
	
	/**
	  * Set the SMTP credentials
	  *
	  * @param string $username
	  * @param string $password
	  * @return void
	  */

	public static function set_smtp_credentials($username, $password)
	{
		self::$smtp_username = $username;
		self::$smtp_password = $password;
	}
	
	/**
	  * Get the SendGrid username
	  *
	  * @return string
	  */

	public static function get_sendgrid_username()
	{
		return self::$sendgrid_username;
	}
	
	
	/**
	  * Get the SendGrid password
	  *
	  * @return string
	  */
	
	public static function get_sendgrid_password()
	{
		return self::$sendgrid_password;
	}
	
	/**
	  * Get the SendGrid username
	  *
	  * @return string
	  */

	public static function get_smtp_username()
	{	
		return self::$smtp_username;
	}
	
	
	/**
	  * Get the SendGrid password
	  *
	  * @return string
	  */
	
	public static function get_smtp_password()
	{
		return self::$smtp_password;
	}
	
	/**
	  * Build the different (HTMl + PlainText) parts of the email
      * Also allows to select header and footer template.
      *
	  * @param string $content
	  * @param string $title
	  * @param array $to
	  * @param string $category
	  * @param boolean $for_admin_only
	  * @param boolean $for_recipient_only
	  * 
	  * @return boolean
	  */
	
	function BuildMail($content, $plainText = '', $mailHeader = 'MailHeader',  $mailFooter = 'MailFooter')
	{
		// Change relative links to assets to absolute
		$content = str_replace('src="assets', 'src="' . Director::absoluteBaseURL() . 'assets', $content);
		$content = str_replace('href="assets', 'href="' . Director::absoluteBaseURL() . 'assets', $content);
		
		// construct the HTML mail
		$html = $this->renderWith($mailHeader);
		$html .= $content;
		$html .= $this->renderWith($mailFooter);
		
		// Make styles inline
		$convertedHTML = new CSSToInlineStyles($html, '');
		$convertedHTML->setUseInlineStylesBlock('true');
		$convertedHTML =  $convertedHTML->convert();
		
		if($plainText == '')
		{
			// Create a plain text version if one wasn't specified
			$plainText =  GetPlainTextMail::getPlainText($convertedHTML, true, true);
		}
		
		// Save both versions in an array and return it
		$myMail = array("htmlMail" => $convertedHTML, "textMail" => $plainText);
		return $myMail;
	}
	
	
	/**
	  * Send emails through Sendgrid
	  *
	  * @param array $recipients
	  * @param string $title
	  * @param array $content
	  * @param string $category
	  * @param boolean $also_send_to_admin
	  * 
	  * @return boolean
	  */
	
	public static function send_mail_sendgrid($recipients, $title, $content, $category = "Uncategorized", $also_send_to_admin = false)
	{
		// Get the SendGrid username
		if (self::get_sendgrid_username() == '')
		{
			return false;
		} else {
			$sendGridUsername = self::get_sendgrid_username();
		}
		
		// Get the SendGrid password
		if (self::get_sendgrid_password() == '')
		{
			return false;
		} else {
			$sendGridPassword = self::get_sendgrid_password();
		}
		
		// Get the admin mail
		$adminMail = self::get_admin_mail();
		
		// Get the admin name
		$adminName = self::get_admin_mail_name();
		
		// Check if there also needs to go a mail to the admin
		if($also_send_to_admin)
		{
			array_push($recipients, $adminMail);
		}
		
		// Prepare the special header for SendGrid
		$hdr = new SmtpApiHeader();
		if($recipients != '')
		{
			$hdr->addTo($recipients);
		}
		$hdr->setCategory($Cat);
				
		// Create a new phpmailer and set the correct settings
		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->Host = "smtp.sendgrid.net;465";
		$mail->Port = 465;
		$mail->SMTPSecure = 'ssl';
		$mail->SMTPAuth = 'true';
		$mail->Username = $sendGridUsername;
		$mail->Password = $sendGridPassword;
		
		// Add the from to and subject
		$mail->From = $adminMail;
		$mail->FromName = $adminName;
		$mail->AddAddress($adminMail);
		$mail->Subject = $title;
		$mail->AddCustomHeader('X-SMTPAPI:' . $hdr->asJSON());
		
		// Set the content
		$mail->AltBody = $content["textMail"];
		$mail->MsgHTML($content["htmlMail"]);
		
		// Send the mail
		if(!$mail->Send()) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	  * Send emails through SMTP
	  *
	  * @param array $recipients
	  * @param string $title
	  * @param array $content
	  * @param boolean $also_send_to_admin
	  * 
	  * @return boolean
	  */
	public static function send_mail_SMTP($recipients, $title, $content, $also_send_to_admin = false, $server, $port = "25", $use_ssl = false, $use_auth = false)
	{
		
		if($use_auth)
		{
			// Get the SMTP username
			if (self::get_smtp_username() == '')
			{
				return false;
			} else {
				$SMTPUsername = self::get_smtp_username();
			}
			
			// Get the SMTP password
			if (self::get_smtp_password() == '')
			{
				return false;
			} else {
				$SMTPPassword = self::get_smtp_password();
			}
		}
		
		// Get the admin mail
		$adminMail = self::get_admin_mail();
		
		// Get the admin name
		$adminName = self::get_admin_mail_name();
		
		// Check if there also needs to go a mail to the admin
		if($also_send_to_admin)
		{
			array_push($recipients, $adminMail);
		}
		
		// Filter out duplicate recipients and implode the array to a comma seperated string
		$recipients = array_unique($recipients);
		$recipients = implode(', ', $recipients);
		
		// Create a new phpmailer and set the correct settings
		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->Host = $server . ";" . $port;
		$mail->Port = $port;
		
		if($use_ssl)
		{   echo 'ssl';
			$mail->SMTPSecure = 'ssl';
		}
		if($use_auth)
		{
			$mail->SMTPAuth = 'true';
			$mail->Username = $SMTPUsername;
			$mail->Password = $SMTPPassword;
			echo 'auth';
			echo $SMTPUsername . " - " . $SMTPPassword;exit();
			
		}
		if($use_auth)
		{
			$mail->SMTPAuth = 'true';
			$mail->Username = $SMTPUsername;
			$mail->Password = $SMTPGridPassword;
		}
		
		// Add the from to and subject
		$mail->From = $adminMail;
		$mail->FromName = $adminName;
		$mail->AddAddress($adminMail);
		$mail->Subject = $title;
		
		// Set the content
		$mail->AltBody = $content["textMail"];
		$mail->MsgHTML($content["htmlMail"]);
		
		
		// Send the mail
		if(!$mail->Send()) {
			return false;
		} else {
			return true;
		}
	}
}