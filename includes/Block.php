<?php

require_once 'Random.php';
require_once 'Values.php';

class Block {
    private $x, $y, $point, $tmp_y;
    private static $random, $pooled, $indexed, $required, $consumed, $similar;
    
    /*
        First number indicates state
            - 0 -> Loss, second number is error code
            - 1 -> Win, second number is flame
        
        Error codes
            - 1 -> death by self poison
            - 2 -> death by enemy damage
            - 3 -> loss by unexpected EOF
            - 4 -> loss by extra data
            
        error 3/4 might be the result of a replay attack
        
        Block codes
        
            - 0 -> attack
            - 1 -> health
            - 2 -> poison
            - 3 -> flame
    */
    
    public static function parse($seed, $c_stage, $rank_attack, $rank_health, $rank_flame, $data) {
        if (!is_array($data))
            return false;
        
        foreach ($data as $point)
            if (!is_int($point) || $point < 1 || $point > 36)
                return false;
            
        self::$random = new Random($seed, $c_stage);
        self::$pooled = [];
        self::$indexed = [[], [], [], [], [], []];
        
        $player_hp = Values::player_health($rank_health);
        $enemy_hp = Values::enemy_health($c_stage);
        $flame = 0;
        
        for ($x = 0; $x < 6; $x++)
            for ($y = 0; $y < 6; $y++)
                new Block($x, $y);
        
        $counter = 0;
        $len     = count($data);
        
        foreach ($data as $point) { // <------------------------------------------
            $counter++;                                                       // ^
                                                                              // ^
            $coord = self::pointToCoord($point);                              // ^
            $action = self::$indexed[$coord[0]][$coord[1]]->press();          // ^
                                                                              // ^
            switch ($action[0]) {                                             // ^
                case -1: //one or two blocks = nothing                        // ^
                    continue 2; // ----------------------------------------------^
                    
                case 0: //attack
                    $enemy_hp -= Values::block_attack($action[1], $rank_attack);
                    
                    if ($enemy_hp <= 0) {
                        if ($counter != $len)
                            return [0, 4]; //loss by extra-data
                        
                        return [1, $flame]; //win by enemy death
                    }
                    break;
                    
                case 1: //health
                    $player_hp += Values::block_heal($action[1], $rank_health);
                    break;
                    
                case 2: //poison
                    $player_hp -= Values::block_poison($action[1], $c_stage);
                    
                    if ($player_hp <= 0)
                        return [0, 1]; //death by self-poison
                     
                    break;
                    
                case 3: //flame
                    $flame += Values::block_flame($action[1], $rank_flame);
            }
            
            if ($action[0] != 2 && $action[0] != 1) { //if you pick poison or heal, the enemy will not attack
                $player_hp -= Values::enemy_damage($c_stage);
                
                if ($player_hp <= 0)
                    return [0, 2]; //death by enemy damage
            }
        }
        
        return [0, 3]; //unexpected EOF
    }
    
    function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
        
        $this->tmp_y = $y;
        
        $this->point = self::$random->next();
        
        self::$pooled[] = $this;
        self::$indexed[$x][$y] = $this;
    }
    
    private static function pointToCoord($point) {
        $point--;
        
        return [$point % 6, floor($point / 6)];
    }
    
    private static function reindex() {
        foreach (self::$pooled as $block) {
            $block->y = $block->tmp_y;
            self::$indexed[$block->x][$block->y] = $block;
        }
    }
    
    private static function isEQ($x, $y, $point) {
        return $x >= 0 and $y >= 0 and $x < 6 and $y < 6 and 
               self::$indexed[$x][$y]->point == $point;
    }
    
    private function findSimilar() {
        for ($cx = -1; $cx <= 1; $cx++)
			for ($cy = -1; $cy <= 1; $cy++)
				if (abs($cx + $cy) == 1) {
                    
					$dx = $this->x + $cx;
					$dy = $this->y + $cy;
					
					if (self::isEQ($dx, $dy, $this->point)) {
						$test = self::$indexed[$dx][$dy];
						
						if (!in_array($test, self::$similar)) {
							self::$similar[] = $test;
							$test->findSimilar();
						}
					}
				}
    }
    
    private function press() {
		self::$similar = [$this];
        self::$required = [0, 0, 0, 0, 0, 0];
		
        $this->findSimilar();
        
        $size = count(self::$similar);
        
        for ($i = 0; $i < $size; $i++) {
            $block = self::$similar[$i];
			$block->moveDown();
            unset(self::$pooled[array_search($block, self::$pooled)]);
		}
        
		for ($x = 0; $x < 6; $x++)
            for ($y = 0; $y < self::$required[$x]; $y++)
                new Block($x, $y);
            
        self::reindex();
        
        if ($size > 2)
			return [$this->point, $size];
        else
            return [-1];
    }
    
    private function moveDown() {
        if ($this->y == 0)
            self::$required[$this->x]++;
        else
            self::$indexed[$this->x][$this->y - 1]->moveDown();
        
		$this->tmp_y++;
    }

}

