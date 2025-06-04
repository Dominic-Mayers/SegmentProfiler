<?php
namespace App;

class Arrow {
	public string $sourceId;
	public string   $targetId;
	public int|null $calls; 
	
	public function __construct (string $sourceId, string $targetId, $calls = 1) {
		$this->sourceId = $sourceId;
		$this->targetId = $targetId;
		$this->calls    = $calls; 
	}
}
