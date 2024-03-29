<?php
//  class Senate_Mail
//  ---------------------------------------------------------------
/*    Interface to /Users/vickery/bin/mail.py
 */
class Senate_Mail
{

  //  Constructor
  //  --------------------------------------------------------------
  /*  From, To, Subject, and text message are required.
   */
  function __construct($from_str, $to_str, $subject, $text_body, $html_body = null)
  {
    //  Save constructor params
    $this->error_message = 'Message created; no send operation attempted yet.';
    $this->from_addr = $this->parse_email($from_str);
    $this->to_addrs = array($this->parse_email($to_str));
    $this->subject = $this->sanitize($subject);
    $this->cc_addrs = array();
    $this->bcc_addrs = array();
    $this->reply_to_addr = NULL;
    $this->plain_name = tempnam('/tmp/', 'plain');
    $plain_file = fopen($this->plain_name, 'w');
    fwrite($plain_file, $text_body);
    fclose($plain_file);
    chmod($this->plain_name, 0644);
    $this->html_name = NULL;
    if (! is_null($html_body))
    {
      $this->html_name = tempnam('/tmp/', 'html');
      $html_file = fopen($this->html_name, 'w');
      fwrite($html_file, $html_body);
      fclose($html_file);
      chmod($this->html_name, 0644);
    }
  }

  // add_recipient()
  function add_recipient($email, $name=null)
  {
    $recipient = $this->parse_email($email, $name);
    $this->to_addrs[] = $recipient;
  }
  //  add_cc()
  function add_cc($email, $name=null)
  {
    $recipient = $this->parse_email($email, $name);
    $this->cc_addrs[] = $recipient;
  }
  //  add_bcc()
  function add_bcc($email, $name=null)
  {
    $recipient = $this->parse_email($email, $name);
    $this->bcc_addrs[] = $recipient;
  }
  //  set_reply_to()
  function set_reply_to($email, $name=null)
  {
    $recipient = $this->parse_email($email, $name);
    $this->reply_to_addr = $recipient;
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
    $cmd .= " -t '$this->plain_name'";

    // Optional options
    if (! is_null($this->html_name))
    {
      $cmd .= " -h '$this->html_name'";
    }
    if (count($this->cc_addrs) > 0)
    {
      $cc_list = implode(' ', $this->cc_addrs);
      $cmd .= " -c $cc_list";
    }
    if (count($this->bcc_addrs) > 0)
    {
      $bcc_list = implode(' ', $this->bcc_addrs);
      $cmd .= " -b $bcc_list";
    }
    if (! is_null($this->reply_to_addr))
    {
      $cmd .= " -r $this->reply_to_addr";
    }

    // Required positional argument
    $recipients = implode(' ', $this->to_addrs);
    $cmd .= " -- $recipients";

    $msg_file = tempnam('/tmp/', 'msg');
    error_log($cmd);
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

  //  parse_email()
  //  ---------------------------------------------------------------
  /*  Extract the email address from addr_str. If there is a real_name,
   *  extract that too, but override it with the real_name argument, if
   *  given. Use username (titlecased) as real_name if not otherwise
   *  available.
   */
  private function parse_email($addr_str, $real_name=null)
  {
    // Extract the username and domain parts of the address
    $v = preg_match('/([^ @\>\<\'\"]+)@([^ @\>\<\'\"]+)/', $addr_str, $matches);
    if (! $v)
    {
      die("<h1 class='error'>“{$addr_str}” is not a valid email address.</h1>\n");
    }
    $username = $matches[1];
    $domain = $matches[2];

    if (is_null($real_name))
    {
      // See if there was a real_name in addr_str
      $worker_str = str_replace('<', '', $addr_str);
      $worker_str = str_replace('>', '', $worker_str);
      $worker_str = trim(str_replace("{$username}@{$domain}", '', $worker_str));
      if ($worker_str === '')
      {
        $real_name = str_replace('.', ' ', ucwords($username));
      }
      else
      {
        $real_name = ucwords($worker_str);
      }
    }
    $real_name = trim($this->sanitize($real_name));
    $return_str = "'{$real_name} <{$username}@{$domain}>'";
    return $return_str;
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
