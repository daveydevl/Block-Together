<?php

class Random {
	private $seed, $level;
	
	function __construct($seed, $level) {
		$this->seed = $seed;
        $this->level = $level;
	}
	
	public function next() {
		$this->seed = ($this->seed * 125) % 2796203;
        $num = $this->seed % 100;
        
        if ($num < 25)
            return 0;
        else if ($num < 50)
            return 1;
        else if ($num < 75 + $this->level * 0.7)
            return 2;
        else if ($num < 100)
            return 3;
	}
}