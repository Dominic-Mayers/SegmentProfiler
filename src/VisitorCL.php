<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorCL extends AbstractVisitorT {
    
	public function beforeChildrenDefinition( $currentId) {
                $this->groupSiblingsPerCallBack(
                        $currentId, 
                        "CL", 
                        fn($childId) : string => $this->baseState->nodes[$childId]['attributes']['innerLabel']  
                );
	}
}