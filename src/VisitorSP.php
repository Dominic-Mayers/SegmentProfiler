<?php

namespace App;

class VisitorSP extends AbstractVisitorP {
    
	public function afterChildren($currentId) {
            
            $this->setNewPath($currentId);
            $this->groupSiblingsPerCallBack(
                        $currentId, 
                        "SP", 
                        fn($childId) => 
                            implode(".", $this->totalGraph->arrayPaths[$this->totalGraph->nodes[$childId]->attributes['pathKey']])
            );                         
	}
}