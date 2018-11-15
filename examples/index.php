<?php
require __DIR__ . '/../vendor/autoload.php';
require "messages.php";
require "config.php";
require "../email.class.php";

$mail_maps = [
    "contact" => [
        "subject" => ["default" => "People Contacting"],
        "email_to" => ["default" => "me@domain.com"],
        "email_cc" => ["default" => "me@domain.com"],
        "email_bcc" => ["default" => "me@domain.com"],
        "name" => ["id" => "from_name", "strip" => true, "required" => true],
        "email" => ["id" => "email_from", "strip" => true, "default" => 'me@domain.com', "validate" => "email"],
        "message" => ["strip" => true],
        "extra_msg" => ["strip" => true]
    ]
];

$email = Email::i();
$email->set_mail_maps($mail_maps);

$email->send("contact", [
    "subject" => "hey mate",
    "name" => "me",
    "email" => "someone@anyone.com",
    "message" => "how are you?",
    "extra_msg"=>"again?"
]);
