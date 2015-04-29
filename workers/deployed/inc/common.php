<?php

function saveGameData($port, $timeToChange, $maze, $myExplorers, $plannedPaths, $explorerStates) {
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