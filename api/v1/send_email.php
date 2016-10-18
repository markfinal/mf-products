<?php
function send_email($to, $subject, $body, $attachments=array())
{
    $from = 'Licensing (Do not reply) <licensing_no_reply@markfinal.me.uk>';

    $boundary = uniqid('np');

    // note: for both header and message body, the EOL characters must be enclosed in
    // double quotes, in order for PHP to interpret these as carriage return and line feeds

    /* this is an alternative multipart message
    // the header
    $header = array();
    $header[] = 'MIME-Version: 1.0';
    $header[] = 'Content-Type: multipart/alternative; boundary="'.$boundary.'"';
    $header[] = 'From: '.$from;
    $header[] = 'Reply-To: '.$from;
    $header[] = 'X-Mailer: PHP/'.phpversion();

    // the message body
    $message = 'This is a MIME encoded message.'."\r\n\r\n";
    $message .= '--'.$boundary."\r\n";
    $message .= 'Content-type: text/plain;charset=utf-8'."\r\n";
    $message .= 'Content-Transfer-Encoding: 7bit'."\r\n";
    $message .= "\r\n";
    $message .= 'Some plain text here'."\r\n\r\n";
    $message .= '--'.$boundary."\r\n";
    $message .= 'Content-Type: text/html;charset=utf-8'."\r\n";
    $message .= 'Content-Transfer-Encoding: 7bit'."\r\n";
    $message .= "\r\n";
    $message .= '<html><body>Hello world</body></html>'."\r\n\r\n";
    $message .= '--'.$boundary.'--';
    */
    /*
    $header = array();
    $header[] = 'MIME-Version: 1.0';
    $header[] = 'Content-Type: multipart/mixed; boundary="'.$boundary.'"';
    $header[] = 'From: '.$from;
    $header[] = 'Reply-To: '.$from;
    $header[] = 'X-Mailer: PHP/'.phpversion();

    // the message body
    $message = 'This is a MIME encoded message.'."\r\n\r\n";
    $message .= '--'.$boundary."\r\n";
    $message .= 'Content-type: text/plain;charset=utf-8'."\r\n";
    $message .= 'Content-Transfer-Encoding: 7bit'."\r\n";
    $message .= "\r\n";
    $message .= $message."\r\n\r\n";
    foreach ($attachments as $key => $value)
    {
        $message .= '--'.$boundary."\r\n";
        $message .= 'Content-Type: text/plain;name="'.$key.'"'."\r\n";
        $message .= 'Content-Transfer-Encoding: base64'."\r\n";
        $message .= 'Content-Disposition: attachment'."\r\n";
        $message .= "\r\n";
        $content = chunk_split(base64_encode($value));
        $message .= $content."\r\n\r\n";
    }
    $message .= '--'.$boundary.'--';
    */

    $header = array();
    $header[] = 'MIME-Version: 1.0';
    $header[] = 'Content-Type: text/html; charset=utf-8';
    $header[] = 'From: '.$from;
    $header[] = 'Reply-To: '.$from;
    $header[] = 'X-Mailer: PHP/'.phpversion();

    // actually send the mail
    $mail_sent = mail(strip_tags($to), $subject, $body, implode("\r\n", $header), '-v');

    error_log($mail_sent ? 'Mail was sent' : 'Mail failed, '.error_get_last());
}
?>
