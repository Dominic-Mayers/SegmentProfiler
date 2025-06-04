<?php
namespace App;

class Node {
	public array $attributes = [];
	public string|null  $groupId = null;
	public array $innerNodesId = []; 
}