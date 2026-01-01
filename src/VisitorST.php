<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorST extends AbstractVisitorT {
    
	public function afterChildren($currentId) {
            
            $this->setNewTree($currentId);
            $this->groupSiblingsPerCallBack(
                        $currentId, 
                        "ST", 
                        fn($childId) => 
                            implode(".", $this->totalGraph->arrayTreeLabels[$this->totalGraph->nodes[$childId]->attributes['treeKey']])
            );                         
	}
}