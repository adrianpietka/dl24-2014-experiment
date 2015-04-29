<?php

// Socket Input Output
class SIO {
    private $socket;
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
    }
    
    public function connect($server, $port) {
        $this->socket = @fsockopen($server, $port);
        
        if (!$this->socket) {
            throw new Exception("Nie mozna nawiazac polaczenia z: ".$server.":".$port);
        }
    }
    
    public function sendLine($command) {
        fputs($this->socket, $command."\n");
        $this->logger->log("< ".$command);
    }
    
    public function readLine() {
        $line = str_replace("\n", "", fgets($this->socket));
        $this->logger->log("> ".$line);
        
        return $line;
    }
    
    public function readLines($number) {
        $lines = [];
        
        for($i = 1; $i <= $number; $i++) {
            $lines[] = $this->readLine();
        }
        
        return $lines;
    }
}
