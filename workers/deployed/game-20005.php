<?php

// setup php configuration
set_time_limit(0);
error_reporting(E_ALL ^ E_DEPRECATED);

// include libs
include_once 'inc/logger.php';
include_once 'inc/sio.php';
include_once 'inc/astar.php';
include_once 'inc/responseFormatter.php';
include_once 'inc/dl24Server.php';
include_once 'inc/dl24Game.php';
include_once 'inc/dl24Path.php';
include_once 'inc/dl24Strategy.php';

// defined
define("DIR_DATA", __DIR__.'/../../data/');

// configuration
$server = 'dl24-lite.fp.lan';
$port = isset($argv[1]) ? (int)$argv[1] : 20005;
$login = 'team21';
$pass = 'oorkeosajp';

try {
    // setup objects
    $logger = new Logger();
    $responseFormatter = new ResponseFormatter();

    $sio = new SIO($logger);
    $sio->connect($server, $port);

    $dl24Server = new DL24Server($sio, $responseFormatter);
    $dl24Path = new DL24Path($logger);
    $dl24Strategy = new DL24Strategy($dl24Server, $dl24Path, $logger);
    $dl24Game = new DL24Game($dl24Server, $dl24Path);

    // common commands
    $dl24Server->login($login);
    $dl24Server->pass($pass);

    // game while
    while(true) {
        // describe world etc.
        $timeToChange = $dl24Server->timeToChange();
        $maze = $dl24Server->maze();
        
        if (!$maze) {
            continue;
        }
        
        $treasures = $dl24Server->treasures();
        $myExplorers = $dl24Server->myExplorers();
        $monsters = $dl24Server->monsters();
        $maze = $dl24Game->addMonstersToMaze($monsters, $maze);
        $exits = $dl24Game->exits($maze);
        
        // game strategy
        $dl24Strategy->clearPlannedPaths();
        foreach($myExplorers as $explorer) {
            $dl24Strategy->execute($timeToChange, $maze, $explorer, $treasures, $exits, $monsters);
        }
        
        // draw data for preview
        $dl24Game->saveGameData($port, $timeToChange, $maze, $myExplorers, $dl24Strategy->getPlannedPaths(), $dl24Strategy->getStates());
        
        // wait
        $dl24Server->wait();
    }
} catch(Exception $e) {
    echo "\n### EXCEPTION\n";
    echo "    ".$e->getMessage()."\n";
    echo "    Plik: ".$e->getFile()."\n";
    echo "    Linia: ".$e->getLine();
    exit;
}

/*

function heap_float(&$heap, &$values, $i, $index) {
    for (; $i; $i = $j) {
        $j = ($i + $i%2)/2 - 1;
        if ($values[$heap[$j]] < $values[$index])
            break;
        $heap[$i] = $heap[$j];
    }
    $heap[$i] = $index;
}

function heap_push(&$heap, &$values, $index) {
    heap_float($heap, $values, count($heap), $index);
}

function heap_raise(&$heap, &$values, $index) {
    heap_float($heap, $values, array_search($index, $heap), $index);
}

function heap_pop(&$heap, &$values) {
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


// A-star algorithm:
//   $start, $target - node indexes
//   $neighbors($i)     - map of neighbor index => step cost
//   $heuristic($i, $j) - minimum cost between $i and $j

function a_star($start, $target, $neighbors, $heuristic) {
    $open_heap = array($start); // binary min-heap of indexes with values in $f
    $open      = array($start => TRUE); // set of indexes
    $closed    = array();               // set of indexes

    $g[$start] = 0;
    $h[$start] = $heuristic($start, $target);
    $f[$start] = $g[$start] + $h[$start];

    while ($open) {
        $i = heap_pop($open_heap, $f);
        unset($open[$i]);
        $closed[$i] = TRUE;

        if ($i == $target) {
            $path = array();
            for (; $i != $start; $i = $from[$i])
                $path[] = $i;
            return array_reverse($path);
        }

        foreach ($neighbors($i) as $j => $step)
            if (!array_key_exists($j, $closed))
                if (!array_key_exists($j, $open) || $g[$i] + $step < $g[$j]) {
                    $g[$j] = $g[$i] + $step;
                    $h[$j] = $heuristic($j, $target);
                    $f[$j] = $g[$j] + $h[$j];
                    $from[$j] = $i;

                    if (!array_key_exists($j, $open)) {
                        $open[$j] = TRUE;
                        heap_push($open_heap, $f, $j);
                    } else
                        heap_raise($open_heap, $f, $j);
                }
    }

    return FALSE;
}

function node($x, $y) {
    global $width;
    return $y * $width + $x;
}

function neighbors($i) {
    global $map, $width, $height;
    list ($x, $y) = coord($i);
    $neighbors = array();
    if ($x-1 >= 0      && $map[$y][$x-1]['value'] != '#' && $map[$y][$x-1]['value'] != '@') $neighbors[node($x-1, $y)] = 1;
    if ($x+1 < $width  && $map[$y][$x+1]['value'] != '#' && $map[$y][$x+1]['value'] != '@') $neighbors[node($x+1, $y)] = 1;
    if ($y-1 >= 0      && $map[$y-1][$x]['value'] != '#' && $map[$y-1][$x]['value'] != '@') $neighbors[node($x, $y-1)] = 1;
    if ($y+1 < $height && $map[$y+1][$x]['value'] != '#' && $map[$y+1][$x]['value'] != '@') $neighbors[node($x, $y+1)] = 1;
    return $neighbors;
}

function heuristic($i, $j) {
    list ($i_x, $i_y) = coord($i);
    list ($j_x, $j_y) = coord($j);
    return abs($i_x - $j_x) + abs($i_y - $j_y);
}


function coord($i) {
    global $width;
    $x = $i % $width;
    $y = ($i - $x) / $width;
    return array($x, $y);
}

function ruchy($x, $y, $wyjscie) {
    global $WORLD, $R, $KOSZT;
    
    //echo "Ide z x = ".$x." y = ".$y."\n";
    //echo "Ide do x = ".$wyjscie['x']." y = ".$wyjscie['y']."\n";
    
    $start  = node($x, $y);
    $target = node($wyjscie['x'], $wyjscie['y']);

    $path = a_star($start, $target, 'neighbors', 'heuristic');
    $sciezka = [];
    
    if ($path) {
        foreach ($path as $i) {
            list ($x, $y) = coord($i);
            $sciezka[] = [$y, $x];
        }
    }
    
    return $sciezka;
}

function najblizsze_wyjscie($s) {
    global $WYJSCIE;
    
    $c_ile_ruchow = 999999;
    $c_xy = [];

    foreach ($WYJSCIE as $wyjscie_nr) {
        $jakie_ruchy = ruchy($s['x'], $s['y'], $wyjscie_nr);
        
        if (count($jakie_ruchy) < $c_ile_ruchow) {
            $c_ile_ruchow = count($jakie_ruchy);
            $c_xy = $wyjscie_nr;
        }
    }
     
    return $c_xy;
}

function najblizszy_skarb($s) {
    global $SKARBY;
    
    $c_ile_ruchow = 999999;
    $c_xy = [];

    foreach ($SKARBY as $skarb_nr) {
        if ($s['x'] != $skarb_nr['x'] && $s['y'] != $skarb_nr['y']) {
            $jakie_ruchy = ruchy($s['x'], $s['y'], $skarb_nr);
            
            if (count($jakie_ruchy) < $c_ile_ruchow) {
                $c_ile_ruchow = count($jakie_ruchy);
                $c_xy = $skarb_nr;
            }
        }
    }
     
    return $c_xy;
}

function najlepszy_skarb($s) {
    global $socket;
    
    echo "\n";
    fputs($socket, "TREASURES\n");
    echo '## Send TREASURES'."\n";
    fgets($socket);
    $ilosc_skarbow = fgets($socket);	
    echo "Skarbow w labiryncie ".$ilosc_skarbow."\n";
    
    $najlepszy = [
        'value' => 0
    ];
    
    for($i = 1; $i <= $ilosc_skarbow; $i++) {
        echo $skarb = fgets($socket);	
        $skarb = explode(" ", $skarb);
        
        if ((int)$skarb[2] > $najlepszy['value']) {
            $najlepszy = [
                'x' => $skarb[0],
                'y' => $skarb[1],
                'value' => $skarb[2]
            ];
        }
    }
    
    return $najlepszy;
}

function losuj_wyjscie() {
    global $WYJSCIE;
    
    $k = array_keys($WYJSCIE);
    $a = count($k) - 1;
    return $WYJSCIE[$k[rand(0, $a)]];
}

function losuj_skarb() {
    global $SKARBY;
    
    $k = array_keys($SKARBY);
    $a = count($k) - 1;
    return $SKARBY[$k[rand(0, $a)]];
}

function droga_do_przebycia($gracz) {
    $s1 = najblizszy_skarb($gracz);
    $w = najblizsze_wyjscie($s1);
    
    $ss1 = ruchy($gracz['x'], $gracz['y'], $s1);
    $ww = ruchy($s1['x'], $s1['y'], $w);
        
    return array_merge($ss1, $ww);
}

function uciekaj_do_wyjscia($gracz) {
    $w = najblizsze_wyjscie($gracz);
    return ruchy($gracz['x'], $gracz['y'], $w);
}

function oblicz_ruch($start_x, $start_y, $end_x, $end_y) {
    $xr = 0;
    $yr = 0;
    
    if ($start_x != $end_x) {
        if ($start_x > $end_x) {
            $xr = -1;
        } else {
            $xr = 1;
        }
    }
    
    if ($start_y != $end_y) {
        if ($start_y > $end_y) {
            $yr = -1;
        } else {
            $yr = 1;
        }
    }
    
    return [$xr, $yr];
}

function czy_czasem_zaraz_sie_niewypierdoli() {
    global $socket;
    
    fputs($socket, "TIME_TO_CHANGE\n");
    fgets($socket);
    $TIME = fgets($socket);
    
    $t = explode(" ", $TIME);
    
    if (count($t) == 2) {
        $zmiana_struktury_za = $t[0];
        $zawalenie_labiryntu_za = $t[1];
        
        if ($zawalenie_labiryntu_za < 2) {
            //echo "Zawalenie labiryntu\n";
            exit("\nZawalenie labiryntu");
        }
        
        if ($zmiana_struktury_za < 2) {
            echo "\nZmiana struktury czekam 3 sekundy\n";
            sleep(3);
            return true;
        }
    }
    
    return false;
}

function SETUP_TREASURES() {
    global $socket, $WORLD;
	
    echo "\n";
    fputs($socket, "TREASURES\n");
    fgets($socket);
    $N = fgets($socket);
    flush();
    
    for($p = 1; $p <= $N; $p++) {
        $data = fgets($socket);
        $treasure = split(" ", removeNL($data));
        flush();
        
        $x = $treasure[0];
        $y = $treasure[1];
        
        $SKARBY[$x.'-'.$y] = [
            'x' => $x,
            'y' => $y
        ];
    }
}

function WYKONAJ_KROK($KROK) {
    global $zaraz_wypierdoli, $port, $ruchy, $R, $MOJE, $SKARBY, $NIEISC, $WYJSCIE, $socket;
    
    $wykonano_pomyslnie = true;
    $gracz = $MOJE[$KROK['gracz_index']];
    
    if (isset($WYJSCIE[$gracz['x'].'-'.$gracz['y']]) && $gracz['v'] > 0) {
        echo "Gracz ".$gracz["id"]." w wyjsciu ze skarbem!!! :-)\n";
        continue;
    }
    
    echo "\nWykonuje ruch dla gracza ".$gracz["id"]."\n";
    
    $aktualna_pozycja_x = $gracz['x'];
    $aktualna_pozycja_y = $gracz['y'];
    $polozono_skarb = true;
    
    $xy_ruch = oblicz_ruch($aktualna_pozycja_x, $aktualna_pozycja_y, $KROK['x'], $KROK['y']);
    $xr = $xy_ruch[0];
    $yr = $xy_ruch[1];
    
    $aktualna_pozycja_x = $KROK['x'];
    $aktualna_pozycja_y = $KROK['y'];

    $niewyslano = true;
    while($niewyslano) {
        fputs($socket, "MOVE ".$gracz["id"]." $xr $yr\n");
        $tmp = fgets($socket);	
        flush();
        
        if (strpos($tmp, "FAILED") !== false) {
            if (strpos($tmp, "FAILED 113") !== false || strpos($tmp, "FAILED 101") !== false ) {
                echo "- wyjebalo : $tmp";
                break;
            }
            
            echo "- czekam ze zmiana pozycji : $tmp";
            flush();
            sleep(1);
        } else {
            $niewyslano = false;
            echo "- zmienilem pozycje $xr $yr\n";
            flush();
            //sleep(1);
        }
    }
   
    if (isset($SKARBY[$aktualna_pozycja_x.'-'.$aktualna_pozycja_y])) { 
        $niewyslano = true;
        while($niewyslano) {
            $wartosc_skarbu = (int)$gracz['c'];
            fputs($socket, "TAKE_TREASURE ".$gracz["id"]." $wartosc_skarbu\n");
            $tmp = fgets($socket);	
            flush();
        
            if (strpos($tmp, "FAILED 104") !== false) {
                $niewyslano = false;
                $wykonano_pomyslnie = false;
                echo "- nie mozna pobrac skarbu : $tmp";
                SETUP_TREASURES();
                flush();
            } else if (strpos($tmp, "FAILED") !== false) {
                echo "- musimy poczekac z pobraniem skarbu : $tmp";
                flush();
                sleep(1);
            } else {
                $niewyslano = false;
                echo "- pobralem skarb\n";
                $polozono_skarb = true;
                flush();
                unset($SKARBY[$aktualna_pozycja_x.'-'.$aktualna_pozycja_y]);
                //usleep(500);
            }
        }
    }
    
    $MOJE[$KROK['gracz_index']]['x'] = $aktualna_pozycja_x;
    $MOJE[$KROK['gracz_index']]['y'] = $aktualna_pozycja_y;
    serialize_app();
    
    return $wykonano_pomyslnie;
}

function USTAW_KROKI() {
    global $MOJE;
    
    $KROKI = [];
    
    // przygotuj informacje po ktorych posortujemy graczy
    $wg_ilosci_ruchow_dla_gracza = [];
    foreach($MOJE as $gracz_index => $gracz) {
        $ruchy = ($gracz['v'] > 0)
            ? uciekaj_do_wyjscia($gracz)
            : droga_do_przebycia($gracz);
        
        if (!isset($WYJSCIE[$gracz['x'].'-'.$gracz['y']])) {
            if ($gracz['v'] > 0 && count($ruchy)) {
                $wg_ilosci_ruchow_dla_gracza = [[
                    'index_gracza' => $gracz_index,
                    'ilosc_ruchow' => count($ruchy),
                    'ruchy' => $ruchy
                ]];
                
                echo "Kierujemy gracza ".$gracz['id']." do wyjscia bo ma skarb\n";
                
                break;
            }
        }
        
        if (count($ruchy)) {
            $wg_ilosci_ruchow_dla_gracza[] = [
                'index_gracza' => $gracz_index,
                'ilosc_ruchow' => count($ruchy),
                'ruchy' => $ruchy
            ];
        }
    }
    
    // sortuj
    usort($wg_ilosci_ruchow_dla_gracza, function($a, $b) {
       return $a['ilosc_ruchow']  - $b['ilosc_ruchow'];
    });
    
    // najszybsze dwa
    for($i = 0; $i <= 0; $i++) {
        $gracz_index = $wg_ilosci_ruchow_dla_gracza[$i]['index_gracza'];
        $ruchy = $wg_ilosci_ruchow_dla_gracza[$i]['ruchy'];

        foreach($ruchy as $ruch) {
            $KROKI[] = ['gracz_index' => $gracz_index, 'x' => $ruch[1], 'y' => $ruch[0]];
        }
    }
    
    return $KROKI;
}

LOGIN();
PASS();

TIME_TO_CHANGE();
MAZE();
MONSTERS();
SETUP_EXPLORERS();
    
if ($WORLD) {
    $KROKI = [];
    
    while(true) {
        if (czy_czasem_zaraz_sie_niewypierdoli()) {
            MAZE();
            MONSTERS(); 
            SETUP_EXPLORERS();
        }

        if (!$KROKI) {
            DISPLAY_EXPLORERS();
            SETUP_EXPLORERS();
            $ruchy = $KROKI = USTAW_KROKI();
            serialize_app();
        }
        
        
        $index = array_keys($KROKI)[0];
        $KROK = $KROKI[$index];
        unset($KROKI[$index]);
        
        if (!WYKONAJ_KROK($KROK)) {
            $KROKI = [];
        }
    }
}
*/