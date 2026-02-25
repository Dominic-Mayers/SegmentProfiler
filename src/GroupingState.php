<?php
namespace App;

use App\BaseState; 

class GroupingState  {
    
    public function __construct(private BaseState $baseState) {
    }
    
    public function adjActiveArrowsOut($sourceId) {
    // This is the same as using the active graph in its original definition, just
    // after the creation of the existing groups, but not after group desactivations.
        $adjNotInnerOut = [];
        $adjAllOut = $this->baseState->arrowsOut[$sourceId] ?? [];
        foreach ($adjAllOut as $targetId => $arrow) {
            if ( empty($this->baseState->nodes[$targetId]['groupId']) ) {
                $adjNotInnerOut[$targetId] = $arrow;
            }
        }
        return $adjNotInnerOut;
    }
        
    public function adjActiveArrowsIn($targetId) {
    // This is the same as using the active graph in its original definition, just
    // after the creation of the existing groups, but not after group desactivations.
        $adjNotInnerIn = [];
        $adjAllIn = $this->baseState->arrowsIn[$targetId] ?? [];
        foreach ($adjAllIn as $sourceId => $arrow) {
            if ( empty($this->baseState->nodes[$sourceId]['groupId']) ) {
	        $adjNotInnerIn[$sourceId] = $arrow;
            }
        }
        return $adjNotInnerIn;
    }
    
    public function incomingActiveOrder($nodeId) : int {
       return \count($this->adjActiveArrowsIn($nodeId)); 
    }
}

