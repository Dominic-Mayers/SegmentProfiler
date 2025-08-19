<?php

namespace App;

class VisitorSL extends AbstractVisitor {
        
	public function init() {
                $this->newNonSingletonSin0ceLastSet = false; 
                //echo "Starting SN".PHP_EOL."-----------".PHP_EOL;
	}

	public function beforeChildren($currentId) {
                $this->groupSiblingsPerCallBack(
                        $currentId, 
                        "SL", 
                        fn($childId) => 
                            $this->totalGraph->nodes[$childId]->attributes['label']
                ); 
        }
}