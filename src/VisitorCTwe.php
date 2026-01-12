<?php

namespace App;

// Must be executed on the original tree, not sure why.
class VisitorCTwe extends AbstractVisitorT {
    
    
	public function beforeChildren($currentId) {         
            $this->groupSiblingsPerCallBack(
                        $currentId, 
                        "CTwe", 
                        fn($childId) : int => $this->getTreeKeyWithEmpty($childId)  
            );
	}        
}