<?php
/**
 * A class for sending email with phpmailer
 *
 * Started: 02-02-2013
 * Updated: 28-10-2015
 * @author Giannis Panagiotou <bone.jp@gmail.com>
 * @version 1.0
 * @source https://github.com/giannis/email
 * @package phpmailer
 * 
 */
class Email {

    /**
     * private static $instance variable
     * if not present, nothing will
     * be executed - Fatal error
     * 
     */
    private static $instance;

    /*
     * The maping of mailing types and fields
     * 
     */
    private $mail_maps = array();

    /*
     * The default name
     */
    private $default_name = "";

    /*
     * The default email address
     * 
     */
    private $default_email = "";

    /*
     * The location of the email templates
     * 
     */
    private $template_dir = "";

    /*
     * The phpmailer class path
     * 
     */
    private $phpmailer_loc = "phpmailer.class.php";

    public $print_output = true;

    /**
     * Class constructor
     * 
     */
    final private function __construct() {
        // if called twice ....
        if (isset(static::$instance))
        // throws an Exception
            throw new Exception("An instance of " . get_called_class() . " already exists.");

        if (defined("MAIL_DEFAULTS_NAME"))
            $this->default_name = MAIL_DEFAULTS_NAME;

        if (defined("MAIL_DEFAULTS_EMAIL"))
            $this->default_email = MAIL_DEFAULTS_EMAIL;

        if (defined("MAIL_DEFAULTS_TEMPLATES_DIR"))
            $this->template_dir = MAIL_DEFAULTS_TEMPLATES_DIR;
        else
            $this->template_dir = __DIR__ . "/templates/";

        if (defined("PHPMAILER_LOC"))
            $this->phpmailer_loc = PHPMAILER_LOC;

        if (defined("MAIL_SMTP_CONFIG"))
            $this->use_smtp = true;
    }

    /**
     * No clone allowed, 
     * both internally and externally
     * 
     */
    final private function __clone() {
        throw new Exception("An instance of " . get_called_class() . " cannot be cloned.");
    }

    /**
     * the common sense method to retrieve the instance
     * 
     */
    final public static function i() {
        return isset(static::$instance) ? static::$instance : static::$instance = new static;
    }
    
    /**
     * PHP5 style destructor
     * 
     * @return bool true
     * 
     */
    function __destruct() {
        return true;
    }

    /**
     * 
     * Sets the mail maps
     * 
     * @param array $mail_maps
     * 
     */
    public function set_mail_maps($mail_maps = array()) {
        $this->mail_maps = $mail_maps;
    }

    private function _response_output($status="SUCCESS", $message="", $opt=array()){
        if (!$this->print_output)
            return $message;

        return response_message($status, $message, $opt);
    }

    /**
     * Sends an email
     * 
     * @param <type> $email_to to send to
     * @param <type> $email_from
     * @param <type> $subject
     * @param <type> $message 
     * 
     * @return boolean true on success
     */
    private function _send_email($param, $attachments) {
        if (!isset($param["email_to"]))
            return false;

        $email_to   = $param["email_to"];
        $to_name    = !empty($param["to_name"]) ? $param["to_name"] : "";
        $email_from = !empty($param["email_from"]) ? $param["email_from"] : $this->default_email;
        $from_name  = !empty($param["from_name"]) ? $param["from_name"] : $this->default_name;
        $subject    = !empty($param["subject"]) ? ("=?UTF-8?B?" . base64_encode($param["subject"]) . "?=") : "";
        $message    = !empty($param["message"]) ? $param["message"] : "";
        $from_name_encoded = "=?UTF-8?B?" . base64_encode($from_name) . "?=";
        $html_message = $message;

        if (isset($param["template"])) {
            ob_start();
            require_once $param["template"];
            $message = ob_get_contents();
            ob_end_clean();

            $message = $that->_parse_templates($message);
            
            if (empty($param["html_template"]))
                $html_message = preg_replace('/\\n/', '<br/>', $message);
            else
                $html_message = $message;
        }

        require_once($this->phpmailer_loc);

        $mail = new PHPMailer();

        if ($this->use_smtp && isset($smtp_username) && $email_from == $smtp_username) {
            require_once(MAIL_SMTP_CONFIG);

            $mail->isSMTP();
            $mail->SMTPDebug  = 0;
            $mail->SMTPAuth   = $smtp_auth;
            $mail->SMTPSecure = $smtp_secure;
            $mail->Host       = $smtp_host;
            $mail->Port       = $smtp_port;
            $mail->Username   = $smtp_username;
            $mail->Password   = $smtp_password;
        }

        $mail->From = $email_from;
        $mail->FromName = $from_name_encoded;
        $mail->AddReplyTo($email_from, $from_name_encoded);
        $mail->SetFrom($email_from, $from_name_encoded);

        $mail->AddAddress($email_to, $to_name);
        $mail->Subject = $subject;
        $mail->AltBody = $message;
        $mail->MsgHTML($html_message);
        $mail->CharSet = 'UTF-8';

        if (!empty($attachments)) {
            foreach($attachments as $attachment) {
                $mail->AddAttachment($attachment["path"], $attachment["name"]);
            }
        }

        if (!$mail->Send()) {
            //mail($email_to, $subject, $message, "From: $email_from\r\nReply-To: $email_from\r\nX-Mailer: DT_formmail");
            //we return false, if mailer failed thats not good...
            return $mail->ErrorInfo;
        }

        return true;
    }

    /*
     * Checks the email parameteres based on the list of options
     * from the mail_maps
     * 
     * @param array $param the parameteres passed
     * 
     * @return mixed depends on the handle of your response function
     * 
     */
    private function validate($type="", $param=array()) {
        if (empty($this->mail_maps[$type]))
            return "No email map found";

        $mail_param = array();

        foreach ($this->mail_maps[$type] as $key => $value) {
            if (empty($param[$key]) || strlen(trim($param[$key])) <= 0) {
                if (!empty($value["required"]))
                    return $key . " is required";

                if (!isset($value["default"])) {
                    $param[$key] = "";
                    continue;
                }

                $param[$key] = $value["default"];
            }

            if (!empty($value["strip"]))
                $param[$key] = stripslashes(strip_tags($param[$key]));

            if (!empty($value["validate"])) {
                if ($value["validate"] == "email" && !filter_var($param[$key], FILTER_VALIDATE_EMAIL))
                    return $key . " is not valid";
            }
            
            $mail_param[isset($value["id"]) ? $value["id"] : $key] = $param[$key];
        }
        
        if (file_exists($this->template_dir . $type . ".php")) {
            $mail_param["template"] = $this->template_dir . $type . ".php";

            if (!empty($this->mail_maps[$type]["html_template"])) {
                $mail_param["html_template"] = true;
            }
        }

        return $mail_param;
    }
    
    static public function send($param="", $extra=array(), $attachments=array()) {
        $that = static::$instance;
        
        if (empty($param))
            return $that->_response_output("EMAIL_FAIL", "no parameters passed");
        
        $extra = array_merge($extra, $_REQUEST);
        
        $valid = $that->validate($param, $extra);
        
        if (!is_array($valid))
            return $that->_response_output("EMAIL_FAIL", $valid);

        $mail_response = $that->_send_email($valid, $attachments);

        if ($mail_response !== true)
            return $that->_response_output("EMAIL_FAIL", "email not send: " . $mail_response);

        return $that->_response_output("SUCCESS", "email send");
    }

    static public function view($type) {
        $that = static::$instance;
        
        if (empty($type))
            return $that->_response_output("EMAIL_FAIL", "Email view not found");
        
        $param = $that->validate($type, $_REQUEST);

        if (!is_array($param))
            return $that->_response_output("NOT_FOUND", $param);

        if (empty($param["template"]))
            return $that->_response_output("NOT_FOUND", "Email template not found");
        
        $param["view_mode"] = true;

        ob_start();
        require_once $param["template"];
        $message = ob_get_contents();
        ob_end_clean();

        $message = $that->_parse_templates($message);

        return $that->_response_output("SUCCESS", $message, array("content_type"=>"html"));
    }

    private function _parse_templates($template){
        $replacement   = array();
        $language_json = json_decode(get_language_json(visitor_language()));

        foreach($language_json->texts as $key=>$value){
            $replacements[$key] = $value;
        }

        foreach($replacements as $key=>$value) {
            $replacement = isset($value) ? $value : "";

            $template = preg_replace("#[']*<%" . $key . "%>[']*#", json_encode($replacement), $template);
            $template = preg_replace("#[']*\[%" . $key . "%\][']*#", $replacement, $template);
        }

        return $template;
    }
}
?>