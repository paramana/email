<?php
/**
 * A class for sending email with PHPMailer
 */
class Email
{

    /**
     * if not present, nothing will be executed - Fatal error
     */
    private static $instance;

    /*
     * The mapping of mailing types and fields
     */
    private $mail_maps = [];

    /*
     * The mapping of SMTP configuration
     */
    private $smtp_config_map = [];

    /*
     * The default name
     */
    private $default_name = "";

    /*
     * The default email address
     */
    private $default_email = "";

    /*
     * The location of the email templates
     */
    private $template_dir = "";

    private $use_smtp = false;

    public $print_output = true;

    public $debug = 0;

    private $smtp_auth;
    private $smtp_secure;
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;

    final private function __construct()
    {
        if (isset(static::$instance)) {
            throw new Exception("An instance of " . get_called_class() . " already exists.");
        }

        if (defined("EMAIL_DEBUG") && EMAIL_DEBUG == true) {
            $this->debug = 3;
        }

        if (defined("EMAIL_DEFAULT_NAME")) {
            $this->default_name = EMAIL_DEFAULT_NAME;
        }

        if (defined("EMAIL_DEFAULT_ADDRESS")) {
            $this->default_email = EMAIL_DEFAULT_ADDRESS;
        }

        $this->template_dir = defined("EMAIL_TEMPLATES_DIR") ? EMAIL_TEMPLATES_DIR : __DIR__ . "/templates/";

        if (defined("MAIL_SMTP_CONFIG") && !empty(MAIL_SMTP_CONFIG)) {
            $this->use_smtp = true;

            if (file_exists(MAIL_SMTP_CONFIG)) {
                require_once(MAIL_SMTP_CONFIG);

                $this->smtp_auth = $smtp_auth;
                $this->smtp_secure = $smtp_secure;
                $this->smtp_host = $smtp_host;
                $this->smtp_port = $smtp_port;
                $this->smtp_username = $smtp_username;
                $this->smtp_password = $smtp_password;
            }
        } else if (defined("SMTP_ENABLED") && !empty(SMTP_ENABLED) && defined("SMTP_USERNAME")) {
            $this->use_smtp = true;
            $this->smtp_auth = SMTP_AUTH;
            $this->smtp_secure = SMTP_SECURE;
            $this->smtp_host = SMTP_HOST;
            $this->smtp_port = SMTP_PORT;
            $this->smtp_username = SMTP_USERNAME;
            $this->smtp_password = SMTP_PASSWORD;
        }
    }

    /**
     * No clone allowed,
     * both internally and externally
     */
    final private function __clone()
    {
        throw new Exception("An instance of " . get_called_class() . " cannot be cloned.");
    }

    /**
     * the common sense method to retrieve the instance
     */
    final public static function i()
    {
        return isset(static::$instance) ? static::$instance : static::$instance = new static;
    }

    /**
     * PHP5 style destructor
     *
     * @return bool true
     */
    function __destruct()
    {
        return true;
    }

    /**
     *
     * Sets the mail maps
     *
     * @param array $mail_maps
     */
    public function set_mail_maps($mail_maps = [])
    {
        $this->mail_maps = $mail_maps;
    }

    public function set_smtp_config_map($smtp_config_map = [])
    {
        if (empty($smtp_config_map)) {
            return;
        }

        $this->use_smtp = true;
        $this->smtp_config_map = $smtp_config_map;
    }

    private function _response_output($status = "SUCCESS", $message = "", $opt = [])
    {
        if (!$this->print_output) {
            if ($status != "SUCCESS") {
                return ["status" => $status, "message" => $message];
            }

            return $message;
        }

        return response_message($status, $message, $opt);
    }

    /**
     * Sends an email
     *
     * @param array $param email parameters
     * @param array $attachments
     *
     * @return boolean true on success
     * @throws phpmailerException
     */
    private function _send_email($param, $attachments)
    {
        if (!isset($param["email_to"])) {
            return false;
        }

        $email_to = (!defined('APP_ENV') || APP_ENV != "production") ? $this->default_email : $param["email_to"];
        $email_cc = !empty($param['email_cc']) ? $param['email_cc'] : '';
        $email_bcc = !empty($param['email_bcc']) ? $param['email_bcc']: '';
        $email_from = !empty($param["email_from"]) ? $param["email_from"] : $this->default_email;
        $email_reply = !empty($param["email_reply"]) ? $param["email_reply"] : $email_from;
        $from_name = !empty($param["from_name"]) ? $param["from_name"] : $this->default_name;
        $subject = !empty($param["subject"]) ? ("=?UTF-8?B?" . base64_encode($param["subject"]) . "?=") : "";
        $message = !empty($param["message"]) ? $param["message"] : "";
        $smtp_account_id = !empty($param["email_account_id"]) ? $param["email_account_id"] : $email_from;
        $from_name_encoded = "=?UTF-8?B?" . base64_encode($from_name) . "?=";
        $from_reply_name = !empty($param["from_reply_name"]) ? $param["from_reply_name"] : $from_name;
        $from_reply_name_enc = "=?UTF-8?B?" . base64_encode($from_reply_name) . "?=";
        $html_message = $message;

        if (isset($param["template"])) {
            ob_start();
            require $param["template"];
            $message = ob_get_contents();
            ob_end_clean();

            $message = $this->_parse_template($message);
            $html_message = empty($param["html_template"]) ? preg_replace('/\\n/', '<br/>', $message) : $message;
        }

        if (isset($param["template_plaintext"])) {
            ob_start();
            require $param["template_plaintext"];
            $message = ob_get_contents();
            ob_end_clean();

            $message = $this->_parse_template($message);

            $plaintext_message = strip_tags($message);
        }

        $mail = new PHPMailer();

        if ($this->use_smtp) {
            $mail->SMTPDebug = $this->debug;
            $mail->isSMTP();

            if ($this->smtp_config_map && !empty($this->smtp_config_map[$smtp_account_id])) {
                $config_map = $this->smtp_config_map[$smtp_account_id];

                $mail->SMTPAuth = $config_map["auth"];
                $mail->SMTPSecure = $config_map["secure"];
                $mail->Host = $config_map["host"];
                $mail->Port = $config_map["port"];
                $mail->Username = $config_map["username"];
                $mail->Password = $config_map["password"];
            } else if (isset($this->smtp_username) && $email_from == $this->smtp_username) {
                $mail->SMTPAuth = $this->smtp_auth;
                $mail->SMTPSecure = $this->smtp_secure;
                $mail->Host = $this->smtp_host;
                $mail->Port = $this->smtp_port;
                $mail->Username = $this->smtp_username;
                $mail->Password = $this->smtp_password;
            }
        }

        array_map(function ($email_address) use ($mail) {
            if (!empty(trim($email_address))) {
                $mail->AddAddress(trim($email_address));
            }
        }, explode(",", $email_to));

        array_map(function ($email_address) use ($mail) {
            if (!empty(trim($email_address))) {
                $mail->addCC($email_address);
            }
        }, explode(',', $email_cc));

        array_map(function ($email_address) use ($mail) {
            if (!empty(trim($email_address))) {
                $mail->addBCC(trim($email_address));
            }
        }, explode(',', $email_bcc));

        $mail->SetFrom($email_from, $from_name_encoded);
        $mail->AddReplyTo($email_reply, $from_reply_name_enc);

        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        // NOTE: After updating to  PHPMailer > 6 check if this is still necessary
        $html_message = preg_replace('/\s+/', ' ', $html_message);
        // This automatically sets Body and AltBody, that's why we override the plaintext message next
        $mail->MsgHTML($html_message);
        if (isset($plaintext_message)) {
            $mail->AltBody = $plaintext_message;
        }

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $mail->AddAttachment($attachment["path"], $attachment["name"]);
            }
        }

        $email_res = $mail->Send();

        $mail->clearAddresses();

        if (!$email_res) {
            // mail($email_to, $subject, $message, "From: $email_from\r\nReply-To: $email_from\r\nX-Mailer: DT_formmail");
            // we return the error, if mailer failed that's not good...
            error_log("Email not send: " . $mail->ErrorInfo);
            return $mail->ErrorInfo;
        }

        return true;
    }

    /*
     * Checks the email parameters based on the list of options * from the mail_maps
     *
     * @param array $param the parameters passed
     *
     * @return mixed depends on the handle of your response function
     */
    private function validate($type = "", array $param = [])
    {
        if (!array_key_exists($type, $this->mail_maps) || empty($this->mail_maps[$type])) {
            return "No email map found";
        }

        $config_map = $this->mail_maps[$type];
        $mail_param = [];

        foreach ($config_map as $key => $value) {
            if (!empty($value["id"]) && $key != $value["id"]) {
                $param[$key] = $param[$value["id"]];
            }

            if (!empty($value["value"])) {
                $param[$key] = $value["value"];
            }

            if (empty($param[$key]) || strlen(trim($param[$key])) <= 0) {
                if (!empty($value["required"]) && $param[$key] !== 0) {
                    return $key . " is required";
                }

                if (!isset($value["default"])) {
                    $param[$key] = "";
                    continue;
                }

                $param[$key] = $value["default"];
            }

            if (!empty($value["strip"])) {
                $param[$key] = stripslashes(strip_tags($param[$key]));
            }

            if (!empty($value["validate"])) {
                if ($value["validate"] == "email") {
                    $email_param = explode(",", $param[$key]);

                    foreach ($email_param as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            return $email . " is not valid";
                        }
                    }
                }
            }

            $mail_param[$key] = $param[$key];
        }

        if (file_exists($this->template_dir . $type . ".html.php")) {
            $mail_param["template"] = $this->template_dir . $type . ".html.php";

            if (!empty($config_map["html_template"])) {
                $mail_param["html_template"] = true;
            }

            if (!empty($config_map["email_account_id"])) {
                $mail_param["email_account_id"] = $config_map["email_account_id"];
            }
        }

        if (file_exists($this->template_dir . $type . ".txt.php")) {
            $mail_param["template_plaintext"] = $this->template_dir . $type . ".txt.php";

            if (!empty($config_map["plaintext_template"])) {
                $mail_param["plaintext_template"] = true;
            }
        }

        return $mail_param;
    }

    public static function send($param = "", $extra = [], $attachments = [])
    {
        $that = static::$instance;
        $request = !empty($_REQUEST) ? $_REQUEST : [];

        if (empty($param)) {
            return $that->_response_output("EMAIL_FAIL", "no parameters passed");
        }

        $extra = array_merge($extra, $request);

        $valid = $that->validate($param, $extra);

        if (!is_array($valid)) {
            return $that->_response_output("EMAIL_FAIL", $valid);
        }

        $mail_response = $that->_send_email($valid, $attachments);

        if ($mail_response !== true) {
            return $that->_response_output("EMAIL_FAIL", "email not send: " . $mail_response);
        }

        return $that->_response_output("SUCCESS", "email send");
    }

    public static function view($type)
    {
        $that = static::$instance;
        $request = !empty($_REQUEST) ? $_REQUEST : [];
        $accepts = $_SERVER['HTTP_ACCEPT'] ?? 'text/html';

        if (empty($type)) {
            return $that->_response_output("EMAIL_FAIL", "Email view not found");
        }

        $param = $that->validate($type, $request);

        if (!is_array($param)) {
            return $that->_response_output("NOT_FOUND", $param);
        }

        if (empty($param["template"])) {
            return $that->_response_output("NOT_FOUND", "Email template not found");
        }

        $param["view_mode"] = true;

        ob_start();
        if (strrpos($accepts, "text/plain") !== false) {
            require $param["template_plaintext"];
            $contentType = "text";
        } else {
            require $param["template"];
            $contentType = "html";
        }
        $message = ob_get_contents();
        ob_end_clean();

        $message = $that->_parse_template($message);

        return $that->_response_output("SUCCESS", $message, ["content_type" => $contentType]);
    }

    private function _parse_template($template)
    {
        if (!function_exists('parse_email_template')) {
            return $template;
        }

        return parse_email_template($template);
    }
}
