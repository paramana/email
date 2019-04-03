PHP email
===

A simple PHP email class that validates email parameters based on a set of rules
and sends it with PHPMailer

Use it like:

```php
require "messages.php";
require "config.php";
require "class.email.php";

$mail_maps = [
    "contact" => [
        "subject" => [ "id" => "subject", "default" => "People Contacting" ],
        "email_to" => [ "id" => "email_to", "default" => "me@domain.com" ],
        "name" => [ "id" => "from_name", "strip" => true, "required" => true ],
        "email" => [ "id" => "email_from", "strip" => true, "default" => 'me@domain.com', "validate" => "email" ],
        "message" => [ "id" => "message", "strip" => true ]
    ]
];

$Email = Email::i();
$Email->set_mail_maps($mail_maps);

$Email->execute([
    "cmd" => "contact",
    "type" => "contact",
    "subject" => "hey mate",
    "name" => "me",
    "email" => "someone@anyone.com",
    "message" => "how are you?"
]);
```

You can pass the SMTP configuration as an array with a similar structure to

```txt
array(2) {
  ["email@platform"]=>
  array(6) {
    ["username"]=>
    string(0) ""
    ["password"]=>
    string(0) ""
    ["host"]=>
    string(20) "smtp-relay.gmail.com"
    ["port"]=>
    string(3) "587"
    ["secure"]=>
    string(3) "tls"
    ["auth"]=>
    string(1) "1"
  }
  ["email@platform2"]=>
  array(6) {
    ["username"]=>
    string(0) ""
    ["password"]=>
    string(0) ""
    ["host"]=>
    string(20) "smtp-relay.gmail.com"
    ["port"]=>
    string(3) "587"
    ["secure"]=>
    string(3) "tls"
    ["auth"]=>
    string(1) "1"
  }
}
```
