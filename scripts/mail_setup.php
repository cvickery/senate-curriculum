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
    $this->from_addr = $from_str;
    $this->to_addrs = array($to_str);
    $this->subject = $subject;
    $this->cc_addrs = array();
    $this->bcc_addrs = array();
    $this->plain_name = tempnam('/tmp/', 'plain');
    $plain_file = fopen($this->plain_name, 'w');
    fwrite($plain_file, $text_body);
    fclose($plain_file);
    chmod($this->$plain_name, 0644);
    $this->html_name = NULL;
    if (! is_null($html_body))
    {
      $this->$html_name = tempnam('/tmp/', 'html');
      $html_file = fopen($this->html_name, 'w');
      fwrite($html_file, $html_msg);
      fclose($html_file);
      chmod($this->html_name, 0644);
    }
  }

  //   //  Extract name and address from from_str and to_str
  //   $from_array = $this->parse_address($from_str, 'Academic Senate');
  //   $this->mail->setFrom($from_array[0], $from_array[1]);
  //   $to_array = $this->parse_address($to_str, '');
  //   $this->mail->addAddress($to_array[0], $to_array[1]);
  //   $this->mail->Subject = $subject;
  //   $this->mail->AltBody = $text_body;
  //   if ($html_body)
  //   {
  //     $this->mail->msgHTML($html_body);
  //   }
  //   else
  //   {
  //     $this->mail->msgHTML(str_replace("\n", "<br/>", $text_body));
  //   }
  // }

  //  Setters
  // function set_text($text_body)
  // {
  //   //  Setting text generates html, but that can be overridden by
  //   //  setting html afterwards.
  //   $this->mail->AltBody = $text_body;
  //   $this->mail->msgHTML(str_replace("\n", "<br/>", $text_body));
  // }
  // function set_html($html_body)   { $this->mail->msgHTML($html_body); }
  // function set_subject($subject)  { $this->mail->Subject = $subject; }
  //  add_recipient()
  function add_recipient($recipient)
  {
    $this->to_addrs[] = $recipient;
  }
  //  add_cc()
  function add_cc($recipient)
  {
    $this->cc_addrs[] = $recipient;
  }
  //  add_bcc()
  function add_bcc($recipient)
  {
    $this->bcc_addrs[] = $recipient;
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
    error_log($cmd);

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

    // if (! $this->mail->send() )
    // {
    //   $this->error_message = $this->mail->ErrorInfo;
    //   return false;
    // }
    // $this->error_message = '';
    // return true;
  }
}
?>
