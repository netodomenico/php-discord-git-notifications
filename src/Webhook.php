<?php

namespace ArsDigitalia;

class Webhook {

    var $id = null;
    var $webhook = null;

    function __construct($id, $webhook) {
        $this->id = $id;
        $this->webhook = $webhook;
    }

}
