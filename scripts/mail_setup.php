<?php

require_once('../vendor/phpmailer/phpmailer/PHPMailerAutoload.php');

//  class Mail
//  -----------------------------------------------------------------------------------
/*    Try to simplify the interface to sending MIME mail using smpt to mail.qc.cuny.edu
 */
class Senate_Mail
{
  //  The PHPMailer object.
  private $mail;

  //  Message bodies
    private $text_body = null, $html_body = null;
    private $error_message = '';

  //  Constructor
  //  --------------------------------------------------------------------------------------
  /*  From, To, Subject, and text message are required.
   *  Be sure to include "real name" in the From header.
   */
  function __construct($from_str, $to_str, $subject, $text_body, $html_body = null)
  {
    //  Create the PHPMailer object and set the encoding
    $this->mail = new PHPMailer();
    $this->mail->CharSet = 'UTF-8';

    //  Extract name and address from from_str and to_str
    $from_array = $this->parse_address($from_str, 'Academic Senate');
    $this->mail->setFrom($from_array[0], $from_array[1]);
    $to_array = $this->parse_address($to_str, '');
    $this->mail->addAddress($to_array[0], $to_array[1]);
    $this->mail->Subject = $subject;
    $this->mail->AltBody = $text_body;
    if ($html_body)
    {
      $this->mail->msgHTML($html_body);
    }
    else
    {
      $this->mail->msgHTML(str_replace("\n", "<br/>", $text_body));
    }
  }

  //  Utilities
  private function parse_address($address, $name = '')
  {
    //  Return an array: element 0 is address; 1 is name
    $returnVal = array($address, $name);
    if (preg_match('/(.*)\s*<(.*)>/', $address, $matches))
    {
      $returnVal[0] = $matches[2];
      $returnVal[1] = $matches[1];
    }
    return $returnVal;
  }

  //  Setters
  function set_text($text_body)
  {
    //  Setting text generates html, but that can be overridden by
    //  setting html afterwards.
    $this->mail->AltBody = $text_body;
    $this->mail->msgHTML(str_replace("\n", "<br/>", $text_body));
  }
  function set_html($html_body)   { $this->mail->msgHTML($html_body); }
  function set_subject($subject)  { $this->mail->Subject = $subject; }
  //  add_recipient()
  function add_recipient($recipient)
  {
    $to_array = $this->parse_address($recipient);
    $this->mail->addAddress($to_array[0], $to_array[1]);
  }
  //  add_cc()
  function add_cc($cc)
  {
    $cc_array = $this->parse_address($cc);
    $this->mail->addCC($cc_array[0], $cc_array[1]);
  }
  //  add_bcc()
  function add_bcc($bcc)
  {
    $bcc_array = $this->parse_address($bcc);
    $this->mail->addBCC($bcc_array[0], $bcc_array[1]);
  }

  //  get_message()
  //  --------------------------------------------------------------
  function getMessage()  { return $this->error_message; }
  function get_message() { return $this->error_message; }

  //  send()
  //  --------------------------------------------------------------
  /*  Validate headers and send the email. Return true on success,
   *  false on failure. Use getMessage to find out what went wrong.
   */
  function send()
  {
    if (! $this->mail->send() )
    {
      $this->error_message = $this->mail->ErrorInfo;
      return false;
    }
    $this->error_message = '';
    return true;
  }
}
?>
