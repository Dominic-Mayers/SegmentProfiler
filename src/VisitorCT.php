<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorCT extends AbstractVisitorT {
    
        #[\Override]
	public function beforeChildren($currentId) {
                $adj = parent::beforeChildren($currentId);             
                $this->groupSiblingsPerCallBack(
                        $currentId, 
                        "CT", 
                        fn($childId) : int => $this->getTreeKey($childId)  
                );
                return $adj; 
	}
}