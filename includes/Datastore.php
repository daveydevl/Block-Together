<?php

require_once 'GDS.php';

define('GDS_ACCOUNT', 'block-together@appspot.gserviceaccount.com');
define('GDS_KEY_FILE', dirname(__FILE__) . '/certificate.p12');
define('POST_LIMIT', 10);

class Datastore {
    
    private $tables = [];
    
    function __construct() {
        $this->createTable('users', [
            'username' => 'String',
            
            'country'  => 'String',
            'region'   => 'String',
            'city'     => 'String',
            
            'xp'           => 'Integer',
            'energy'       => 'Integer',
            'flame'        => 'Integer',
            'last_updated' => 'Integer',
            
			'current_stage' => 'Integer',
			'seed'          => 'Integer',
            'stage'         => 'Integer',
            
            'rank_attack' => 'Integer',
            'rank_health' => 'Integer',
            'rank_flame'  => 'Integer',
            
            'api_key' => 'String',
            'ads'     => 'Boolean'
        ]);
		
		$this->createTable('server', [
			'motd' => 'String'
		]);
    }
    
    function insertRow($tableName, $data, $parent = null) {
        $table = $this->tables[$tableName];
        $entity = $table->createEntity($data);
        if (!is_null($parent))
            $entity->setAncestry($parent);
        
        $table->upsert($entity);
        
        return $entity;
    }
    
    function update($row) {
        $this->tables[$row->getKind()]->upsert($row);
    }
    
    function buildWheres($wheres) {
        $rtn = [];
        foreach ($wheres as $key => $value)
            $rtn[] = $key . ' = @' . $key;
        return implode(' AND ', $rtn);
    }
    
    function fetchRow($tableName, $wheres) {
        return $this->tables[$tableName]->fetchOne(
            'SELECT * FROM ' . $tableName . ' WHERE ' .
            $this->buildWheres($wheres),
            $wheres
        );
    }
    
    function fetchLeaders() {
        $leaders = $this->tables['users']->fetchAll(
            'SELECT * FROM users ORDER BY xp DESC LIMIT 9'
        );
        $final = [];
        
        foreach ($leaders as $leader)
            $final[] = [$leader->country, $leader->username, $leader->xp];
        
        return $final;
    }
	
	function fetchMOTD() {
		$server = $this->tables['server']->fetchOne(
			'SELECT motd FROM server'
		);
		
		return $server->motd;
	}
    
    function fetchRows($tableName, $wheres) {
        return $this->tables[$tableName]->fetchAll(
            'SELECT * FROM ' . $tableName . ' WHERE ' .
            $this->buildWheres($wheres),
            $wheres
        );
    }
    
    function fetchChildren($tableName, $parent) {
        return $this->tables[$tableName]->fetchEntityGroup($parent);
    }
    
    function fetchById($tableName, $id) {
        return $this->tables[$tableName]->fetchById($id);
    }
    
    function createTable($name, $columns) {
        $schema = new GDS\Schema($name);
        foreach ($columns as $column => $type)
            $schema->{'add' . $type}($name);
        $this->tables[$name] = new GDS\Store($schema);
    }
    
}
