<?php
require "messages.php";
require "config.php";
require "email.class.php";

$mail_maps = array(
    "contact" => array(
        "subject" => array("default" => "People Contacting"),
        "email_to" => array("default" => "me@domain.com"),
        "name" => array("id" => "from_name", "strip" => true, "required" => true),
        "email" => array("id" => "email_from", "strip" => true, "default" => 'me@domain.com', "validate" => "email"),
        "message" => array("strip" => true),
        "extra_msg" => array("strip" => true)
    )
);

$Email = Email::i();
$Email->set_mail_maps($mail_maps);

$Email->send(array("contact"), array(
    "subject" => "hey mate",
    "name" => "me",
    "email" => "someone@anyone.com",
    "message" => "how are you?",
    "extra_msg"=>"again?"
));
?>