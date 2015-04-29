<?php

// operation on maze - find path, calculate move
class DL24Path {
    private $logger;
    
    public function __construct($logger) {
        $this->logger = $logger;
    }
    
    private function getTheBestPath($maze, $startPoint, $possibleEndPoints) {
         $theBestPath = [];
        
        foreach($possibleEndPoints as $endPoint) {
            $theBestPathScore = count($theBestPath);
            $pathToEndPoint = $this->getPath($maze, $startPoint, $endPoint);
            
            if ($pathToEndPoint && ($theBestPathScore === 0 || $theBestPathScore > count($pathToEndPoint))) {
                $theBestPath = $pathToEndPoint;
            }
        }
        
        return $theBestPath;
    }
    
    public function getPathToNearestTreasure($maze, $startPoint, $treasures) {
       return $this->getTheBestPath($maze, $startPoint, $treasures);
    }
    
    public function getPathToNearestExit($maze, $startPoint, $exits) {
        return $this->getTheBestPath($maze, $startPoint, $exits);
    }
    
    public function getPath($maze, $startPoint, $endPoint) {
        // $this->logger->log('>> Calculate path from '.$startPoint['X'].':'.$startPoint['Y'].' to '.$endPoint['X'].':'.$endPoint['Y']);
        $astar = new Astar($maze);
        
        $start = $astar->node($startPoint['X'] - 1, $startPoint['Y'] - 1);
        $target = $astar->node($endPoint['X'] - 1, $endPoint['Y'] - 1);

        $astarPath = $astar->findPath($start, $target);
        $path = [];
    
        if ($astarPath) {
            foreach ($astarPath as $point) {
                list ($x, $y) = $astar->coord($point);
                $path[] = ['X' => $x + 1, 'Y' => $y + 1];
            }
        }
        
        return $path;
    }
    
    public function getMove($startPoint, $endPoint) {
        $x = 0;
        $y = 0;
        
        if ($startPoint['X'] != $endPoint['X']) {
            if ($startPoint['X'] > $endPoint['X']) {
                $x = -1;
            } else {
                $x = 1;
            }
        }
        
        if ($startPoint['Y'] != $endPoint['Y']) {
            if ($startPoint['Y'] > $endPoint['Y']) {
                $y = -1;
            } else {
                $y = 1;
            }
        }
        
        return ['X' => $x, 'Y' => $y];
    }
}