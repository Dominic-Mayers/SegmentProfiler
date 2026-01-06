<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorCT extends AbstractVisitorT {
    
    
	public function beforeChildren($currentId) {
            
             
            $this->groupSiblingsPerCallBack(
                        $currentId, 
                        "CT", 
                        fn($childId) : int => $this->getTreeKey($childId)  
            );
	}
}