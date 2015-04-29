<?php 

class DL24Game {
    public function exits($maze) {
        $exits = [];
        
        foreach($maze as $y => $points) {
            $points = str_split($points);
            foreach($points as $x => $v) {
                if ($v === 'E') {
                    $exits[] = ['X' => $x + 1, 'Y' => $y + 1];
                }
            }
        }
        
        return $exits;
    }
    
    public function addMonstersToMaze($monsters, $maze) {
        foreach($monsters as $monster) {
            $maze[$monster['Y'] - 1][$monster['X'] - 1] = '@';
        }
        
        return $maze;
    }
    
    public function saveGameData($port, $timeToChange, $maze, $myExplorers, $plannedPaths, $explorerStates) {
        $file = DIR_DATA.'game-'.$port.'.json';
        $data = json_encode([
            'timeToChange' => $timeToChange,
            'maze' => $maze,
            'myExplorers' => $myExplorers,
            'plannedPaths' => $plannedPaths,
            'explorerStates' => $explorerStates
        ]);
        
        file_put_contents($file, $data);
    }
}