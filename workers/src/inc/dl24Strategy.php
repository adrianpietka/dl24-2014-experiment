<?php

class DL24Strategy {
    const STATE_START = 'STATE_START';
    const STATE_GET_TREASURE = 'STATE_GET_TREASURE';
    const STATE_GO_TO_EXIT = 'STATE_GO_TO_EXIT';
    const STATE_STAY_ON_EXIT = 'STATE_STAY_ON_EXIT';
    
    const MAX_V = 1; //0.65;
    
    private $dl24Server;
    private $dl24Path;
    private $logger;
    
    private $states = [];
    private $plannedPaths = [];
    
    private $timeToChange;
    private $maze;
    private $explorer;
    private $treasures;
    private $exits;
    private $monsters;
    
    public function __construct($dl24Server, $dl24Path, $logger) {
        $this->dl24Server = $dl24Server;
        $this->dl24Path = $dl24Path;
        $this->logger = $logger;
    }
    
    private function setState($state) {
        $this->states[$this->explorer['ID']] = $state;
    }
    
    private function currentState() {
        return isset($this->states[$this->explorer['ID']])
            ? $this->states[$this->explorer['ID']]
            : self::STATE_START;
    }
    
    private function stateDecide() {
        $treasurePath = $this->dl24Path->getPathToNearestTreasure($this->maze, $this->explorer, $this->treasures);
        $exitPath = $this->dl24Path->getPathToNearestExit($this->maze, $treasurePath[count($treasurePath) - 1], $this->exits);
        $movesToTreasureAndExit = count($treasurePath) + count($exitPath);
        $explorerCanGetTreasure = ($this->explorer['C'] * self::MAX_V - $this->explorer['V']) > 0 ? true : false; // ile moze - ile ma
        
        $this->logger->log(">> EXPLORER ".$this->explorer['ID']);
        $this->logger->log("   movesToTreasureAndExit = ".$movesToTreasureAndExit);
        $this->logger->log("   toEndOfExperiment = ".($this->timeToChange['zawalenieLabiryntu'] + 10));
        $this->logger->log("   explorerCanGetTreasure = ".$explorerCanGetTreasure);
        
        if ($explorerCanGetTreasure) {
            if (($this->timeToChange['zawalenieLabiryntu'] + 10) > $movesToTreasureAndExit) {
                $this->setState(self::STATE_GET_TREASURE);
            } else {
                $this->setState(self::STATE_GO_TO_EXIT);
            }
        } else {
            $this->setState(self::STATE_GO_TO_EXIT);
        }
    }

    private function stateGetTreasure() {
        $path = $this->dl24Path->getPathToNearestTreasure($this->maze, $this->explorer, $this->treasures);
        
        // nie ma gdzie isc
        if (!$path) {
            $this->stateDecide();
            return;
        }
        
        $this->plannedPaths[] = $path;
        $newPosition = $path[0];
        $treasure = $path[count($path) - 1];
        $move = $this->dl24Path->getMove($this->explorer, $newPosition);
        
        // nie mozna wykonac ruchu
        if (!$this->dl24Server->move($this->explorer['ID'], $move['X'], $move['Y'])) {
            //$this->stateDecide();
            return;
        }
        
        $this->explorer['X'] = $newPosition['X'];
        $this->explorer['Y'] = $newPosition['Y'];
        
        // dotarlismy do skarbu
        if ($this->explorer['X'] == $treasure['X'] && $this->explorer['Y'] == $treasure['Y']) {
            // podnies skarb
            $value = ($this->explorer['C'] * self::MAX_V - $this->explorer['V']); // podnies 2/3 tego co moze - to co ma
            $this->dl24Server->takeTreasure($this->explorer['ID'], $value);
            // zastanow sie co robic
            //$this->stateDecide();
            $this->setState(self::STATE_GO_TO_EXIT);
        }
    }
    
    private function stateGoToExit() {
        // jezeli jestesmy juz na wyjsciu, to sie nie ruszamy
        foreach($this->exits as $exit) {
            if ($this->explorer['X'] == $exit['X'] && $this->explorer['Y'] == $exit['Y']) {
               $this->stateDecide();
               return;
            }
        }
        
        $path = $this->dl24Path->getPathToNearestExit($this->maze, $this->explorer, $this->exits);
        
        // nie ma gdzie isc
        if (!$path) {
            $this->stateDecide();
            return;
        }
        
        $this->plannedPaths[] = $path;
        $newPosition = $path[0];
        $exit = $path[count($path) - 1];
        $move = $this->dl24Path->getMove($this->explorer, $newPosition);
        
        // nie mozna wykonac ruchu
        if (!$this->dl24Server->move($this->explorer['ID'], $move['X'], $move['Y'])) {
            // $this->stateDecide();
            return;
        }
        
        $this->explorer['X'] = $newPosition['X'];
        $this->explorer['Y'] = $newPosition['Y'];
        
        // dotarlismy do wyjscia, zastanow sie co robic
        if ($this->explorer['X'] == $exit['X'] && $this->explorer['Y'] == $exit['Y']) {
            $this->stateDecide();
        }
    }
    
    private function stateStayOnExit() {
        // nothing
    }
    
    public function execute($timeToChange, $maze, $explorer, $treasures, $exits, $monsters) {
        $this->timeToChange = $timeToChange;
        $this->maze = $maze;
        $this->explorer = $explorer;
        $this->treasures = $treasures;
        $this->exits = $exits;
        $this->monsters = $monsters;
         
        $state = $this->currentState();
        
        $this->logger->log(">> EXPLORER ".$this->explorer['ID']);
        $this->logger->log("   state = ".$state);
        
        if ($state == self::STATE_START) {
            $this->stateDecide();
        }
        
        switch($state) {
            case self::STATE_GET_TREASURE:
                $this->stateGetTreasure();
            break;
            
            case self::STATE_GO_TO_EXIT:
                $this->stateGoToExit();
            break;
            
            case self::STATE_STAY_ON_EXIT:
                $this->stateStayOnExit();
            break;
        }
    }
    
    public function clearPlannedPaths() {
        $this->plannedPaths = [];
    }
    
    public function getPlannedPaths() {
        return $this->plannedPaths;
    }
    
    public function getStates() {
        return $this->states;
    }
}