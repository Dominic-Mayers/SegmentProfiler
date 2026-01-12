<?php

namespace App;

class VisitorSetMaxSavedFalse extends AbstractVisitor {
        
	public function beforeChildren($currentId) {
                if ($this->totalGraph->nodes[$currentId]->attributes['maxSaved'] === true) {
                    $this->totalGraph->nodes[$currentId]->attributes['maxSaved'] = false; 
                    $currentNode = $this->totalGraph->nodes[$currentId]; 
                    $this->totalGraph->savedGroups[$currentNode->attributes['treeKeyWithEmpty']]--; 
                }
        }
}