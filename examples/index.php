<?php
require __DIR__ . '/../vendor/autoload.php';
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

$email = Email::i();
$email->set_mail_maps($mail_maps);

$email->send("contact", array(
    "subject" => "hey mate",
    "name" => "me",
    "email" => "someone@anyone.com",
    "message" => "how are you?",
    "extra_msg"=>"again?"
));
