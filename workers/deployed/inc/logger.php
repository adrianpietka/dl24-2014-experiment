<?php

class Logger {
    public function log($message) {
        echo date("H:i:s")." ".str_replace("\n", "", $message)."\n";
        flush();
    }
}