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
	 * Replace a "[sitetree_link id=n]" shortcode with a link to the page with the corresponding ID.
	 *
	 * @return string
	 */
	public static function absolute_link_shortcode_handler($arguments, $content = null, $parser = null) {
		if(!isset($arguments['id']) || !is_numeric($arguments['id'])) return;
		
		if (
			   !($page = DataObject::get_by_id('SiteTree', $arguments['id']))         // Get the current page by ID.
			&& !($page = Versioned::get_latest_version('SiteTree', $arguments['id'])) // Attempt link to old version.
			&& !($page = DataObject::get_one('ErrorPage', '"ErrorCode" = \'404\''))   // Link to 404 page directly.
		) {
			 return; // There were no suitable matches at all.
		}
		
		if($content) {
			return sprintf('<a href="%s">%s</a>', $page->AbsoluteLink(), $parser->parse($content));
		} else {
			return $page->AbsoluteLink();
		}
	}
	
	
	/**
	  * Build the different (HTML + PlainText) parts of the email
      * Also allows to select header and footer template.
      *
	  * @param array $content
	  * @param string $plainText
	  * @param string $mailHeader
	  * @param string $mailFooter
	  * 
	  * @return array
	  */
	
	function BuildMail($content, $plainText = '', $mailHeader = 'MailHeader',  $mailFooter = 'MailFooter')
	{
		// Change relative links to assets to absolute
		$content = str_replace('src="assets', 'src="' . Director::absoluteBaseURL() . 'assets', $content);
		$content = str_replace('href="assets', 'href="' . Director::absoluteBaseURL() . 'assets', $content);
		
		// Change internal links ([sitetree_link id=n]) to proper absolute links
		$parser = new ShortcodeParser();
		$parser->register('sitetree_link', array('Def_MailTools', 'absolute_link_shortcode_handler'));
		$content = $parser->parse($content);
		
		// construct the HTML mail
		$html = $this->renderWith($mailHeader);
		$html .= $content;
		$html .= $this->renderWith($mailFooter);
		
		// Make styles inline
		$convertedHTML = new CSSToInlineStyles($html, '');
		$convertedHTML->setUseInlineStylesBlock('true');
		$convertedHTML =  $convertedHTML->convert();
		
		// Create a plain text version if one wasn't specified
		if($plainText == '') { $plainText =  GetPlainTextMail::getPlainText($convertedHTML, true, true); }
		
		// Save both versions in an array and return it
		$myMail = array("htmlMail" => $convertedHTML, "textMail" => $plainText);
		return $myMail;
	}
	
	/**
	  * Check if the Admin also needs to be mailed. And filter out duplicate mail addresses.
      *
	  * @param array $recipients
	  * @param boolean $also_send_to_admin
	  * @param string $admin_mail
	  * 
	  * @return array
	  */
	
	private static function filter_recipients($recipients, $also_send_to_admin, $admin_mail)
	{
		// Check if there also needs to go a mail to the admin
		if($also_send_to_admin) { array_push($recipients, $admin_mail); }
		
		// Filter out duplicate recipients
		$recipients = array_unique($recipients);
		
		return $recipients;
	}
	
	/**
	  * A basic function for that sets up the SMTP Mail
      *
	  * @param array $recipients
	  * @param string $title
	  * @param array $content
	  * @param string $server
	  * @param integer $port
	  * 
	  * @return PHPMailer Object
	  */
	
	public static function setup_basic_smtp_mail($recipients, $title, $content, $server, $port)
	{
		$mail = new PHPMailer();
		$mail->IsSMTP();
		$mail->Host = $server . ";" . $port;
		$mail->Port = $port;
		
		// Get the admin mail
		$adminMail = self::get_admin_mail();
		
		// Get the admin name
		$adminName = self::get_admin_mail_name();
		
		// Add sender details
		$mail->From = $adminMail;
		$mail->FromName = $adminName;
		
		// Set the title
		$mail->Subject = $title;
		
		// Set the content
		$mail->AltBody = $content["textMail"];
		$mail->MsgHTML($content["htmlMail"]);
		
		return $mail;
	}
	
	/**
	  * Send emails through SMTP
	  *
	  * @param array $recipients
	  * @param string $title
	  * @param array $content
	  * @param boolean $also_send_to_admin
	  * @param string $server
	  * @param integer $port
	  * @param boolean $use_ssl
	  * @param boolean $use_auth
	  * 
	  * @return boolean
	  */
	public static function send_mail_SMTP($recipients, $title, $content, $also_send_to_admin = false, $server, $port = "25", $use_ssl = false, $use_auth = false)
	{
		$mail = self::setup_basic_smtp_mail($recipients, $title, $content, $server, $port);
		
		// See if authentitcation is needed
		if($use_auth) {
			// Return false if no username is set
			if(self::$smtp_username == "") { return false; }
			$mail->SMTPAuth = 'true';
			$mail->Username = self::$smtp_username;
			$mail->Password = self::$smtp_password;
		}
		
		// Set SSL if needed
		if($use_ssl) { $mail->SMTPSecure = 'ssl'; }
		
		$recipients = self::filter_recipients($recipients, $also_send_to_admin, $mail->From);
		
		// Add recipients
		foreach($recipients as $recipient) { $mail->AddAddress($recipient); }
		
		// Send the mail
		if(!$mail->Send())
		{
			return false;
		} else {
			return true;
		}
	}
	
	/**
	  * Send emails through Sendgrid
	  *
	  * @param array $recipients
	  * @param string $title
	  * @param array $content
	  * @param string $category
	  * @param boolean $also_send_to_admin
	  * @param string $server
	  * @param integer $port
	  * @param array $extraValues
	  * 
	  * @return boolean
	  */
	
	public static function send_mail_sendgrid($recipients, $title, $content, $category = "Uncategorized", $also_send_to_admin = false, $server ="smtp.sendgrid.com", $port = "465", $extraValues = "")
	{
		$mail = self::setup_basic_smtp_mail($recipients, $title, $content, $server, $port);
		
		// Fill in SendGrid credentials
		if(self::$sendgrid_username == "" || self::$sendgrid_password == "") { return false; }
		$mail->SMTPAuth = 'true';
		$mail->Username = self::$sendgrid_username;
		$mail->Password = self::$sendgrid_password;
		$mail->SMTPSecure = 'ssl';

		// Prepare the special header for SendGrid
		$hdr = new SmtpApiHeader();



		// Get recipients and add them to the header
		$recipients = self::filter_recipients($recipients, $also_send_to_admin, $mail->From);
		if($recipients != '') { $hdr->addTo($recipients); }
		
		// Add a SendGrid category
		$hdr->setCategory($category);
		
		/* add ExtraValues to be parsed by SendGrid if needed */
		if($extraValues)
		{
			foreach($extraValues as $key=>$value)
			{
				$hdr->addSubVal($key, $value);
			}
		}
		
		// Add the special API- header to the mail
		$mail->AddCustomHeader('X-SMTPAPI:' . $hdr->asJSON());
		
		// Add a To address - PHPMailer needs this, but SendGrid won't use it
		$mail->AddAddress($mail->From);
		
		// Send the mail
		if(!$mail->Send()) 
		{
			return false;
		} else {
			return true;
		}
	}
}