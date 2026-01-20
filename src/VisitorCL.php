<?php

namespace App;

class VisitorCL extends AbstractVisitor {

	public function beforeChildrenDefinition($currentId) {
                $this->groupSiblingsPerCallBack(
                        $currentId, 
                        "CL", 
                        fn($childId) => 
                            $this->totalGraph->nodes[$childId]->attributes['innerLabel']
                ); 
        }
}