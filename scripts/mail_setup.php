<?php
//  class Senate_Mail
//  -----------------------------------------------------------------------------------
/*    Interface to /Users/vickery/bin/mail.py
 */
class Senate_Mail
{

  //  Constructor
  //  --------------------------------------------------------------------------------------
  /*  From, To, Subject, and text message are required.
   *  Be sure to include "real name" in the From header.
   */
  function __construct($from_str, $to_str, $subject, $text_body, $html_body = null)
  {
    //  Save constructor params
    $this->error_message = 'Message created; no send operation attempted yet.';
    $this->from_addr = $this->sanitize($from_str);
    $this->to_addrs = array($this->sanitize($to_str));
    $this->subject = $this->sanitize($subject);
    $this->cc_addrs = array();
    $this->bcc_addrs = array();
    $this->plain_name = tempnam('/tmp/', 'plain');
    $plain_file = fopen($this->plain_name, 'w');
    fwrite($plain_file, $text_body);
    fclose($plain_file);
    chmod($this->plain_name, 0644);
    $this->html_name = NULL;
    if (! is_null($html_body))
    {
      $this->$html_name = tempnam('/tmp/', 'html');
      $html_file = fopen($this->html_name, 'w');
      fwrite($html_file, $html_body);
      fclose($html_file);
      chmod($this->html_name, 0644);
    }
  }

  // add_recipient()
  function add_recipient($recipient)
  {
    $this->to_addrs[] = $this->sanitize($recipient);
  }
  //  add_cc()
  function add_cc($recipient)
  {
    $this->cc_addrs[] = $this->sanitize($recipient);
  }
  //  add_bcc()
  function add_bcc($recipient)
  {
    $this->bcc_addrs[] = $this->sanitize($recipient);
  }

  //  get_message()
  //  --------------------------------------------------------------
  function getMessage()  { return $this->error_message; }
  function get_message() { return $this->error_message; }

  //  send()
  //  --------------------------------------------------------------
  /*  Create header args and send the email. Return true on success,
   *  false on failure. Use getMessage to find out what went wrong.
   */
  function send()
  {
    $cmd = "SMTP_SERVER=smtp.qc.cuny.edu /Users/vickery/bin/mail.py";
    $cmd .= " -f $this->from_addr";
    $cmd .= " -s '$this->subject'";
    $cmd .= " -p '$this->plain_name'";
    if (! is_null($this->html_name))
    {
      $cmd .= " -h $this->html_name";
    }
    if (count($this->cc_addr) > 0)
    {
      $cc_list = implode(', ', $this->cc_addrs);
      $cmd .= " -c $cc_list";
    }
    if (count($this->bcc_addr) > 0)
    {
      $bcc_list = implode(', ', $this->bcc_addrs);
      $cmd .= " -b $bcc_list";
    }
    $recipients = implode(', ', $this->to_addrs);
    $cmd .= " -- $recipients";
    error_log(">>>|$cmd|<<<");

    $msg_file = tempnam('/tmp/', 'msg');
    system("$cmd 2> $msg_file", $exit_status);

    unlink($this->plain_name);
    if (! is_null($this->html_name))
    {
      unlink($this->html_name);
    }

    if ($exit_status != 0)
    {
      $this->error_message = file_get_contents($msg_file);
      unlink($msg_file);
      return false;
    }
    else
    {
      $this->error_message = 'Message sent.';
      unlink($msg_file);
      return true;
    }
  }

  //  sanitize()
  //  ---------------------------------------------------------------
  /*  Make sure strings don’t contain single or double quotes.
   *  Uses prime symbols (rather than smart quotes) for replacements
   *  to keep things simple.
   */
  private function sanitize($str)
  {
    $sanitized = str_replace("'", '′', $str);
    $sanitized = str_replace('"', '″', $sanitized);
    return $sanitized;
  }
}
?>
