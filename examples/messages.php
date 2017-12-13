<?php
function response_message($code="", $extra="") {
    $STATUSES = array(
        "HACKING"    => 500, 
        "EMAIL_FAIL" => 500,
        "SUCCESS" => 200
    );
    
    $status = $STATUSES[$code];
    
    if ($status != 200) {
        http_response_code($status);
        $response = "Error: " . $code . " Message: " . $extra;
        error_log($response);
    } else {
        $response = "Message: " . $extra;
    }
    
    echo $response;
    return $response;
}
?>