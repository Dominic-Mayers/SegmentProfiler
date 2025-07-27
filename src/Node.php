<?php
namespace App;

class Node {
        public string $type; 
	public array $attributes = [];
	public string|null  $groupId = null;
	public array $innerNodesId = [];
        
        public function __construct($type = "T") {
            $this->type = $type; 
        }
}