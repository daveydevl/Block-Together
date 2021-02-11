<?php

require_once 'Methods.php';
require_once 'Client.php';
require_once 'Block.php';
require_once 'Values.php';

class Processor implements Methods {
    private $client, $user;
    private $version = 1;
    
    function __construct($datastore, $api_key = null) {
        $this->client = new Client;
        $this->datastore = $datastore;
        $this->api_key = $api_key;
        
        if (!empty($this->api_key)) {
            $this->user = $this->datastore->fetchRow('users', [
                'api_key' => $this->api_key
            ]);
            
            $convert = [
                'xp',
                'energy',
                'last_updated',
                'flame',
			    'current_stage',
			    'seed',
                'stage',
                'rank_attack',
                'rank_health',
                'rank_flame',
                'chara',
                'current_chara'
            ];
            
            if (is_null($this->user))
                $this->api_key = null;
            else foreach ($convert as $c)
                $this->user->$c = intval($this->user->$c);
        }
    }
    
    function randomHash($length) {
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
    
    function unlockRankAttack() {
        $this->unlockRank('attack');
    }
    
    function unlockRankHealth() {
        $this->unlockRank('health');
    }
    
    function unlockRankFlame() {
        $this->unlockRank('flame');
    }
    
    private function unlockRank($type) {
        $user = $this->user;
        $client = $this->client;
        
        $short = 'rank_' . $type;
        $rank = $user->$short + 1;
        
        if ($user->$short >= 10) {
            $client->alert("You've maxed out this ability!");
            return;
        }
        
        $amt_req = Values::{$short . '_unlock'}($rank);
        
        if ($user->flame < $amt_req) {
            $client->alert("You don't have enough flame!");
            return;
        }
            
        $user->flame -= $amt_req;
        $user->$short = $rank;
        
        $this->datastore->update($user);
        $this->updateInfo();
        
        $client->alertUnlock(ucfirst($type) . " rank increased to " . $rank . "!");
    }
    
    function unlockChara($num) {
        $client = $this->client;
        
        if (!is_int($num) || $num < 1 || $num > 18) {
            $client->alert("This isn't a valid character!");
            return;
        }
        
        $user = $this->user;
        
        $bools = str_pad(decbin($user->chara), 18, "0", STR_PAD_LEFT);
        
        if ($bools[$num - 1] == "1") { //already unlocked
            $client->alert("You've already unlocked this character!");
            $this->updateInfo();
            return;
        }
        
        $amt = 0;
        $len = strlen($bools);
        for ($i = 0; $i < $len; $i++)
            if ($bools[$i] == "1")
                $amt++;
        
        $amt_req = Values::chara_unlock($amt + 1);
        
        if ($user->flame < $amt_req) {
            $client->alert("You don't have enough flame!");
            return;
        }
        
        $user->flame -= $amt_req;
        $bools[$num - 1] = "1";
        $user->chara = bindec($bools);
        
        $this->datastore->update($user);
        $this->updateInfo();
        
        $client->alertUnlock("Character unlocked!");
    }
    
    private function updateInfo() {
        $user = $this->user;
        
        $this->client->updateInfo(
            $user->username,
			$user->country,
			$user->xp,
			$this->getEnergy(),
			$user->stage,
            $user->flame,
			$user->rank_attack,
            $user->rank_health,
            $user->rank_flame,
            $user->current_chara,
            $user->chara
        );
    }
    
    function newUser($api_key) {
        return $this->datastore->insertRow('users', [
            'username' => 'Click Here',
            'api_key'  => $api_key,
            
            'country'  => $_SERVER['HTTP_X_APPENGINE_COUNTRY'],
            'region'   => $_SERVER['HTTP_X_APPENGINE_REGION'],
            'city'     => $_SERVER['HTTP_X_APPENGINE_CITY'],
            
            'xp'           => 1,
            'energy'       => 1,
            'flame' => 0,
            'last_updated' => time(),
            
			'current_stage' => 0,
			'seed'    => 0,
            'stage'   => 1,
            
            'rank_attack' => 1,
            'rank_health' => 1,
            'rank_flame'  => 1,
            
            'current_chara' => 1,
            'chara'         => 131072,
            
            'ads' => true
        ]);
    }
	
	function beginStage($stage) {
        $client = $this->client;
        
        if (!is_int($stage)) {
            $client->alert("This isn't a valid level!");
			$client->changeScreen(1);
            return;
        }
        
        $energy = $this->getEnergy();
        $req = Values::energy_req($stage);
        $user = $this->user;
		
		if ($stage < 1 || $stage > $user->stage ||
            $energy < $req) {
            $client->alert("You don't have enough energy!");
			$client->changeScreen(1);
            return;
        }
            
        $seed = mt_rand(1, 1000000);
        
        $user->current_stage = $stage;
        $user->seed = $seed;
        $user->energy = intval($energy - $req);
        $user->last_updated = time();
        $this->datastore->update($user);
        
        $client->beginStage($seed);
	}
    
    private function validName($name) {
        $name = trim($name);
        $len = strlen($name);
        
        return $len >= 3 && $len <= 15 && preg_match('/[^\x00-\x7F]/', $name) == 0;
    }
    
    function changeChara($current_chara) {
        $client = $this->client;
        
        if (!is_int($current_chara) || $current_chara < 1 || $current_chara > 18) {
            $client->alert("This isn't a valid character!");
            return;
        }
        
        $user = $this->user;
        $bools = str_pad(decbin($user->chara), 18, "0", STR_PAD_LEFT);
        
        if ($bools[$current_chara - 1] == "0") {
            $client->alert("You haven't unlocked this character!");
            return;
        }
        
        $user->current_chara = $current_chara;
        $this->datastore->update($user);
        
        $this->updateInfo();
    }
    
    function changeName($name) {
        $client = $this->client;
        
        if (!$this->validName($name)) {
            $this->updateInfo();
            $client->alert("Name must be 3-15 alphanumeric!");
            return;
        }
        
        $this->user->username = $name;
        $this->datastore->update($this->user);
        
        $this->updateInfo();
        
        $client->alertUnlock("Your name has been changed!");
    }
    
    function finishStage($data) {
        $result = [0];
        
        $user   = $this->user;
        $client = $this->client;
        
        if ($user->current_stage != 0)
            $result = Block::parse($user->seed, $user->current_stage, $user->rank_attack, $user->rank_health, $user->rank_flame, $data);
        
        if ($user->ads)
            $client->showAd();
        
        $stage_unlocked = false;
        $leveled_up = false;
        $level_after = 0;
        
        switch ($result[0]) {
            case 0: //loss
                $client->showMessage("+0 xp", "+0");
                
                break;
                
            case 1: //win
                $flame_gain = $result[1] * $user->rank_flame;
                $user->flame += $flame_gain;
                $xp_gain = Values::xp_gain($user->current_stage);
                
                if ($user->xp == 1)
                    $xp_gain *= 2;
                
                $level_before = Values::level($user->xp);
                $user->xp += $xp_gain;
                $level_after = Values::level($user->xp);
                
                if ($user->stage == $user->current_stage && $user->stage < 20) {
                    $user->stage++;
                    $stage_unlocked = true;
                }
                
                if ($level_after > $level_before) { //level up!
                    $leveled_up = true;
                    $user->energy = Values::max_energy(Values::level($user->xp)); //re-fill energy
                }
                
                $user->current_stage = 0;
                $user->seed = 0;
                $this->datastore->update($user);
                
                $win = true;
                
                $client->showMessage("+" . $xp_gain . " xp", "+" . $flame_gain);
        }
        
        $this->updateInfo();
        
        if ($stage_unlocked)
            $client->alertUnlock("Stage " . $user->stage . " unlocked!");
        
        if ($leveled_up)
            $client->alertUnlock("Level " . $level_after . " reached!");
    }
    
    private function getEnergy() {
        $user = $this->user;
        
        if ($user->xp == 1)
            return 1; //always have one energy for new accounts
        
        $energy = $user->energy;
        $last_updated = $user->last_updated;
        
        $energy += floor((time() - $last_updated) / 10);
        
        $max_energy = Values::max_energy(Values::level($user->xp));
        
        if ($energy > $max_energy)
            $energy = $max_energy;
        
        return $energy;
    }
    
    function _init() {
        $client = $this->client;
        
        if (is_null($this->user)) {
            $api_key = $this->randomHash(16);
            $this->user = $this->newUser($api_key);
            $client->setApiKey($api_key);
        }
        
        $client->setMOTD($this->datastore->fetchMOTD());
        $this->updateInfo();
        $client->updateLeaders($this->datastore->fetchLeaders());
    }
    
    function execute($post) {
        $client = $this->client;
        
        if (!empty($post['v']) && $post['v'] == $this->version) {
        
            if (!empty($post['method']) && is_array($post['params'])) {
                $method = $post['method'];
                $params = array_values($post['params']);
                
                $exists = true;
                try {
                    $reflect = new ReflectionMethod('Methods', $method);
                } catch (Exception $e) {
                    $exists = false;
                }
                
                if ($exists)
                    if (substr($method, 0, 1) == '_' || isset($this->api_key))
                        if (count($params) == $reflect->getNumberOfParameters())
                            call_user_func_array([$this, $method], $params);
                        else
                            $client->error("Params miscount for `" . $method . "`");
                    else
                        $client->error("Api key missing or invalid");
                else
                    $client->error("Method does not exist `" . $method . "`");
            } else
                $client->error("Method or params missing");
        } else
            $client->error("Please update your game client!");
        
        return $client->getData();
    }
    
}
