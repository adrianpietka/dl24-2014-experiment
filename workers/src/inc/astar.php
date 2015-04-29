<?php

// A-star algorithm:
//   $start, $target - node indexes
//   $neighbors($i)     - map of neighbor index => step cost
//   $heuristic($i, $j) - minimum cost between $i and $j

class AStar {
    private $map = [];
    private $width = 0;
    private $height = 0;
    
    public function __construct($map) {
        $this->map = $map;
        $this->width = strlen($map[0]);
        $this->height = count($map);
    }
    
    public function heap_float(&$heap, &$values, $i, $index) {
        for (; $i; $i = $j) {
            $j = ($i + $i%2)/2 - 1;
            if ($values[$heap[$j]] < $values[$index])
                break;
            $heap[$i] = $heap[$j];
        }
        $heap[$i] = $index;
    }

    public function heap_push(&$heap, &$values, $index) {
        $this->heap_float($heap, $values, count($heap), $index);
    }

    public function heap_raise(&$heap, &$values, $index) {
        $this->heap_float($heap, $values, array_search($index, $heap), $index);
    }

    public function heap_pop(&$heap, &$values) {
        $front = $heap[0];
        $index = array_pop($heap);
        $n = count($heap);
        if ($n) {
            for ($i = 0;; $i = $j) {
                $j = $i*2 + 1;
                if ($j >= $n)
                    break;
                if ($j+1 < $n && $values[$heap[$j+1]] < $values[$heap[$j]])
                    ++$j;
                if ($values[$index] < $values[$heap[$j]])
                    break;
                $heap[$i] = $heap[$j];
            }
            $heap[$i] = $index;
        }
        return $front;
    }

    public function findPath($start, $target) {
        $open_heap = array($start); // binary min-heap of indexes with values in $f
        $open      = array($start => TRUE); // set of indexes
        $closed    = array();               // set of indexes

        $g[$start] = 0;
        $h[$start] = $this->heuristic($start, $target);
        $f[$start] = $g[$start] + $h[$start];

        while ($open) {
            $i = $this->heap_pop($open_heap, $f);
            unset($open[$i]);
            $closed[$i] = TRUE;

            if ($i == $target) {
                $path = array();
                for (; $i != $start; $i = $from[$i])
                    $path[] = $i;
                return array_reverse($path);
            }

            foreach ($this->neighbors($i) as $j => $step)
                if (!array_key_exists($j, $closed))
                    if (!array_key_exists($j, $open) || $g[$i] + $step < $g[$j]) {
                        $g[$j] = $g[$i] + $step;
                        $h[$j] = $this->heuristic($j, $target);
                        $f[$j] = $g[$j] + $h[$j];
                        $from[$j] = $i;

                        if (!array_key_exists($j, $open)) {
                            $open[$j] = TRUE;
                            $this->heap_push($open_heap, $f, $j);
                        } else {
                            $this->heap_raise($open_heap, $f, $j);
                        }
                    }
        }

        return FALSE;
    }
    
    public function coord($i) {
        $x = $i % $this->width;
        $y = ($i - $x) / $this->width;
        
        return array($x, $y);
    }
    
    public function node($x, $y) {
        return $y * $this->width + $x;
    }
    
    // implementation per game
    
    public function neighbors($i) {
        $neighbors = array();
        list ($x, $y) = $this->coord($i);
        
        $monster = '@';
        $available = '#';
        
        if ($x-1 >= 0      && $this->map[$y][$x-1] != $available && $this->map[$y][$x-1] != $monster) $neighbors[$this->node($x-1, $y)] = 1;
        if ($x+1 < $this->width  && $this->map[$y][$x+1] != $available && $this->map[$y][$x+1] != $monster) $neighbors[$this->node($x+1, $y)] = 1;
        if ($y-1 >= 0      && $this->map[$y-1][$x] != $available && $this->map[$y-1][$x] != $monster) $neighbors[$this->node($x, $y-1)] = 1;
        if ($y+1 < $this->height && $this->map[$y+1][$x] != $available && $this->map[$y+1][$x] != $monster) $neighbors[$this->node($x, $y+1)] = 1;
        
        return $neighbors;
    }

    public function heuristic($i, $j) {
        list ($i_x, $i_y) = $this->coord($i);
        list ($j_x, $j_y) = $this->coord($j);
        
        return abs($i_x - $j_x) + abs($i_y - $j_y);
    }    
}