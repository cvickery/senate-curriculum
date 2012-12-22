<?php

require_once('Mail.php');
require_once('Mail/mime.php');
require_once('Mail/mail.php');
require_once('credentials.inc');

//  class Mail
//  -----------------------------------------------------------------------------------
/*    Try to simplify the interface to sending MIME mail using smpt to mail.qc.cuny.edu
 */
class Senate_Mail
{
  /*  Default headers; may be replaced
   *    From, Reply_to, Date, Message-ID
   */
    private $headers = null;
  /*  Required headers for sending; no default values
   *    To, Subject
   */
    private $recipients = '';
  /*
   *  Optional headers, values will be appended to To if necessary
   *    Cc, Bcc
   */
  //  Message bodies
    private $text_body = null, $html_body = null;
    private $mime, $smtp_mail;

    private $message = '';
  //  Constructor
  //  --------------------------------------------------------------------------------------
  /*  From, To, Subject, and text message are required.
   *  Be sure to include "real name" in the From header.
   */
  function __construct($from, $to, $subject, $text_body, $html_body = null, $headers = null)
  {
    global $smtp_user, $smtp_pass;
    $this->headers = array(
        'From'        =>  $from,
        'To'          =>  $to,
        'Subject'     =>  $subject,
        'Reply-to'    =>  $from,
        'Date'        =>  date('r'),
        'Message-ID'  =>  '<' . uniqid() . '@senate.qc.cuny.edu>'
        );
    $this->recipients = $to;
    $this->subject = $subject;
    $this->text_body = $text_body;
    if ($html_body) $this->html_body = $html_body;
    else
    {
      $this->html_body = str_replace("\n", "<br/>", $text_body);
    }
    if ($headers)
    {
      foreach ($headers as $name => $value)
      {
        $this->headers[$name] = $value;
        if (strtolower($name) === 'cc' || strtolower($name === 'bcc'))
        {
          $this->add_recipient($value);
        }
      }
    }
    //  Initialize the Mail_mime obect
    $crlf = "\n";
    $this->mime = new Mail_mime(
        array
        (
          'eol' => $crlf,
          'text_charset' => 'utf-8',
          'html_charset' => 'utf-8'
        ));

    //  Set up the smtp Mail object
    $smtpinfo["host"] = "smtp.qc.cuny.edu";
    $smtpinfo["port"] = "25";
    $smtpinfo["auth"] = true;
    $smtpinfo["username"] = $smtp_user;
    $smtpinfo["password"] = $smtp_pass;
    $smtpinfo["localhost"] = 'senate.qc.cuny.edu';
    $smtpinfo["persist"] = false;
    $this->smtp_mail =& Mail::factory('smtp', $smtpinfo);
  }

  //  Setters
  function set_text($text_body)
  {
    //  Setting text generates html, but that can be overridden by
    //  setting html afterwards.
    $this->text_body = $text_body;
    $this->html_body = str_replace("\n", "<br/>", $text_body);
  }
  function set_html($html_body)   { $this->html_body = $html_body; }
  function set_subject($subject)  { $this->headers['Subject'] = $subject; }
  //  add_recipient()
  function add_recipient($recipient)
  {
    //  Do not duplicate
    if (! strstr($this->recipients, $recipient))
    {
      $this->recipients .= ",$recipient";
    }
  }
  //  add_cc()
  function add_cc($cc)
  {
    if (isset($this->headers['Cc']))
    {
      //  Do not duplicate
      if (! strstr($this->headers['Cc'], $cc))
      {
        $this->headers['Cc'] .= ",$cc";
      }
    }
    else
    {
      $this->headers['Cc'] = $cc;
    }
    $this->add_recipient($cc);
  }
  //  add_bcc()
  function add_bcc($bcc)
  {
    if (isset($this->headers['Bcc']))
    {
      //  Do not duplicate
      if (! strstr($this->headers['Bcc'], $bcc))
      {
        $this->headers['Bcc'] .= ",$bcc";
      }
    }
    else
    {
      $this->headers['Bcc'] = $bcc;
    }
    $this->add_recipient($bcc);
  }

  //  get_message()
  //  --------------------------------------------------------------
  function getMessage()  { return $this->message; }
  function get_message() { return $this->message; }

  //  send()
  //  --------------------------------------------------------------
  /*  Validate headers and send the email. Return true on success,
   *  false on failure. Use getMessage to find out what went wrong.
   */
  function send()
  {
    //  Validate headers
    //  NOTE From    required in constructor
    //  NOTE To      required in constructor
    //  NOTE Subject required in constructor
    //  NOTE Text body required in constructor; html generated
    //       from it automatically.
    //  NOTE Recipient list generated automatically

    //  Prep and send
    $this->mime->setTXTBody($this->text_body);
    $this->mime->setHTMLBody($this->html_body);
    $body = $this->mime->get();
    $hdrs = $this->mime->headers($this->headers);
    $mime_status = $this->smtp_mail->send($this->recipients, $hdrs, $body);
    if ( PEAR::isError($mime_status) )
    {
      $this->message = $mime_status->getMessage();
      return false;
    }
    return true;
  }
}
?>
