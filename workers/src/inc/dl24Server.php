<?php

class DL24Server {
    private $sio;
    private $responseFormatter;
    
    private $lastMaze;
    
    public function __construct($sio, $responseFormatter) {
        $this->sio = $sio;
        $this->responseFormatter = $responseFormatter;
    }
    
    public function login($login) {
        $this->sio->readLine(); // empty line
        $this->sio->sendLine($login);
        $this->sio->readLine(); // ok
    }
    
    public function pass($pass) {
        $this->sio->sendLine($pass);
        $this->sio->readLine(); // pass
    }
    
    public function timeToChange() {
        $this->sio->sendLine("TIME_TO_CHANGE");
        $this->sio->readLine(); // ok
        $times = $this->sio->readLine();
        
        return $this->responseFormatter->oneLineToArrayWithNamedKeys($times, ['zmianaStruktury', 'zawalenieLabiryntu']);
    }
    
    public function maze() {
        $this->sio->sendLine("MAZE");
        $status = $this->sio->readLine(); // ok || failed
        
        if (strpos($status, "FAILED") === false) {
            $size = $this->responseFormatter->oneLineToArrayWithNamedKeys($this->sio->readLine(), ['W', 'H']); // size of maze
            $this->lastMaze = $this->sio->readLines((int)$size['H']);
        }
    
        return $this->lastMaze;
    }
    
    public function myExplorers() {
        $this->sio->sendLine("MY_EXPLORERS");
        $this->sio->readLine(); // ok
        $number = (int)$this->sio->readLine(); // number of explorers
        $explorers = $this->sio->readLines($number);
        $format = ["ID", "X", "Y", "A", "W", "U", "V", "C", "B"];
        
        return $this->responseFormatter->linesToArraysWithNamedKeys($explorers, $format);
    }
    
    public function monsters() {
        $this->sio->sendLine("MONSTERS");
        $this->sio->readLine(); // ok
        $number = (int)$this->sio->readLine(); // number of monsters
        $monsters = $this->sio->readLines($number);
        $format = ["X", "Y", "A", "V"];
        
        return $this->responseFormatter->linesToArraysWithNamedKeys($monsters, $format);
    }
    
    public function treasures() {
        $this->sio->sendLine("TREASURES");
        $this->sio->readLine(); // ok
        $number = (int)$this->sio->readLine(); // number of treasures
        $treasures = $this->sio->readLines($number);
        $format = ["X", "Y", "V"];
        
        return $this->responseFormatter->linesToArraysWithNamedKeys($treasures, $format);
    }
    
    public function move($explorerId, $x, $y) {
        $this->sio->sendLine("MOVE $explorerId $x $y");
        $status = $this->sio->readLine();
        
        return $status === 'OK';
    }
    
    public function takeTreasure($explorerId, $value) {
        $status = 'FAILED';
        
        while($status !== 'OK' && strpos($status, 'FAILED 104') === FALSE) {
            $this->sio->sendLine("TAKE_TREASURE $explorerId $value");
            $status = $this->sio->readLine();
            usleep(10000); // 10ms
        }
        
        return $status === 'OK';
    }
    
    public function wait() {
        $this->sio->sendLine("WAIT");
        $this->sio->readLine(); // ok
        $timeToWait = explode(' ', $this->sio->readLine())[1];
        usleep($timeToWait * 1000000);
        $this->sio->readLine();
    }
}