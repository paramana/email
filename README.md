php email
===

A simple php email class
It validates email parameters based on a set of rules
and sends it with phpmailer

Use it like:

```php
require "messages.php";
require "config.php";
require "class.email.php";

$mail_maps = array(
  "contact"=>array(
    "subject"=>array("id"=>"subject", "default"=>"People Contacting"),
    "email_to"=>array("id"=>"email_to", "default"=>"me@domain.com"),
    "name"=>array("id"=>"from_name", "strip"=>true, "required"=>true),
    "email"=>array("id"=>"email_from", "strip"=>true, "default"=>'me@domain.com', "validate"=>"email"),
    "message"=>array("id"=>"message", "strip"=>true)
  )
);

$Email = Email::i();
$Email->set_mail_maps($mail_maps);

$Email->execute(array(
  "cmd"=>"contact", 
  "type"=>"contact", 
  "subject"=>"hey mate", 
  "name"=>"me", 
  "email"=>"someone@anyone.com",
  "message"=>"how are you?"
));
```

You can pass the smtp configuration as a array like

```
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
