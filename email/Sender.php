<?php
namespace SAF\Framework\Email;

use Mail;
use Mail_smtp;
use PEAR_Error;
use SAF\Framework\Builder;
use SAF\Framework\Email;
use SAF\Framework\Plugin\Configurable;
use SAF\Framework\Tools\Date_Time;

/**
 * Sends emails
 *
 * This offers a SAF interface to the PHP PEAR Mail package
 */
class Sender implements Configurable
{

	//------------------------------------------------------------------------------------------- BCC
	const BCC      = 'bcc';

	//------------------------------------------------------------------------------------------ HOST
	const HOST     = 'host';

	//----------------------------------------------------------------------------------------- LOGIN
	const LOGIN    = 'login';

	//-------------------------------------------------------------------------------------- PASSWORD
	const PASSWORD = 'password';

	//------------------------------------------------------------------------------------------ PORT
	const PORT     = 'port';

	//-------------------------------------------------------------------------------------------- TO
	const TO       = 'to';

	//------------------------------------------------------------------------------------------ $bcc
	/**
	 * Configuration of blind-carbon-copy email address enable to send every email sent by this
	 * feature to a given addresses list.
	 *
	 * @var string[]
	 */
	public $bcc;

	//------------------------------------------------------------------------- $default_smtp_account
	/**
	 * @var Smtp_Account
	 */
	public $default_smtp_account;

	//------------------------------------------------------------------------------------------- $to
	/**
	 * Use this to override all to, cc, bcc recipients and replace them with these recipients only.
	 * Configuration of this property is recommended in development environment to avoid sending
	 * emails to production recipients when you test your application.
	 *
	 * @var string[]
	 */
	public $to;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * The constructor of the Sender plugin stores the configuration into the object properties.
	 *
	 * @param $configuration string[]|integer[]
	 */
	public function __construct($configuration = [])
	{
		if ($configuration) {
			$this->default_smtp_account = new Smtp_Account(
				isset($configuration[self::HOST]) ?     $configuration[self::HOST]     : '',
				isset($configuration[self::LOGIN]) ?    $configuration[self::LOGIN]    : '',
				isset($configuration[self::PASSWORD]) ? $configuration[self::PASSWORD] : '',
				isset($configuration[self::PORT]) ?     $configuration[self::PORT]     : null
			);
			if (isset($configuration[self::BCC])) $this->bcc = $configuration[self::BCC];
			if (isset($configuration[self::TO]))  $this->to  = $configuration[self::TO];
		}
	}

	//------------------------------------------------------------------------------------------ send
	/**
	 * Send an email using its account connection information
	 * or the default SMTP account configuration.
	 *
	 * @param $email  Email
	 * @return boolean|string true if sent, error message if string
	 */
	public function send(Email $email)
	{
		// email send configuration
		$params = $this->sendConfiguration($email);

		// mime encode of email (for html, images and attachments)
		/** @var $encoder Encoder */
		$encoder = Builder::create(Encoder::class, [$email]);
		$content = $encoder->encode();

		// send email using PEAR Mail and Net_SMTP features
		/** @var $mail Mail_smtp */
		$mail = (new Mail())->factory('smtp', $params);
		$error_reporting = error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
		$send_result = $mail->send(
			$email->getRecipientsAsStrings(), $email->getHeadersAsStrings(), $content
		);
		$mail->disconnect();
		error_reporting($error_reporting);

		// user error when errors
		if ($send_result instanceof PEAR_Error) {
			return $email->send_message = strval($send_result);
		}
		$email->send_date = new Date_Time();
		/** @noinspection PhpUndefinedFieldInspection */
		$email->uidl = $mail->queued_as;
		return true;
	}

	//----------------------------------------------------------------------------- sendConfiguration
	/**
	 * Configure email send process : prepares the email recipients and get smtp server connection
	 * parameters.
	 *
	 * @param $email Email email account is used, email recipients may be changed by the configuration
	 * @return string[] PEAR Net_SMTP parameters : host, port, auth, username, password
	 */
	private function sendConfiguration(Email $email)
	{
		// get connection parameters from email account or default smtp account
		$account = ($email->account && $email->account->smtp_accounts)
			? $email->account->smtp_accounts[0]
			: $this->default_smtp_account;
		$params['host'] = $account->host;
		$params['port'] = $account->port;
		if ($account->login) {
			$params['auth']     = true;
			$params['username'] = $account->login;
			$params['password'] = $account->password;
		}

		// dev / preprod parameters to override 'To' and/or 'Bcc' mime headers
		if (isset($this->to)) {
			$email->blind_copy_to = [];
			$email->copy_to       = [];
			$email->to            = [];
			foreach ($this->to as $to) {
				array_push($email->to, new Recipient($to));
			}
		}
		if (isset($this->bcc)) {
			foreach ($this->bcc as $bcc) {
				array_push($email->blind_copy_to, new Recipient($bcc));
			}
		}

		return $params;
	}

}
