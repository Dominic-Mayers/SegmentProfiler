<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorSP extends AbstractVisitorP {
    
	public function afterChildren($currentId) {
            
            $this->setNewTreeLabel($currentId);
            $this->groupSiblingsPerCallBack(
                        $currentId, 
                        "SP", 
                        fn($childId) => 
                            implode(".", $this->totalGraph->arrayTreeLabels[$this->totalGraph->nodes[$childId]->attributes['treeKey']])
            );                         
	}
}