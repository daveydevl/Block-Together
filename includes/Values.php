<?php


class Values {
    
    
    public static function chara_unlock($chara) {
        return 50 * $chara - 2;
    }
	
	public static function rank_attack_unlock($rank) {
		return 23 * $rank - 25;
	}
    
    public static function rank_health_unlock($rank) {
        return 34 * $rank - 38;
    }
    
    public static function rank_flame_unlock($rank) {
        return 45 * $rank - 50;
    }
	
	public static function player_health($rank) {
		return 109 * $rank - 34;
	}
	
	public static function enemy_health($c_stage) {
		return 15 * $c_stage;
	}
    
    public static function enemy_damage($c_stage) {
        return 3 * $c_stage + 4;
    }
    
    public static function level($xp) {
		return floor(sqrt($xp));
	}
    
    public static function energy_req($c_stage) {
        return 2 * $c_stage - 1;
    }
	
	public static function max_energy($level) {
		return 2 * $level - 1;
	}
    
    public static function block_attack($amt, $rank) {
        return $rank * $amt;
    }
    
    public static function block_heal($amt, $rank) {
        return $rank * $amt * 2;
    }
    
    public static function block_flame($amt, $rank) {
        return $amt;
    }
    
    public static function block_poison($amt, $c_stage) {
        return $amt * $c_stage;
    }
    
    public static function xp_gain($c_stage) { //only server-sided
        return $c_stage * 2;
    }
    
}


