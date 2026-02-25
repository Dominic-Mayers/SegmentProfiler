<?php

namespace App;

use App\TreePhase;
use App\ActiveGraph;

#[Exclude]
class VisitorDefaultActiveGraph extends AbstractVisitor {

        private ActiveGraph $activeGraph; 

        public function __construct (
                BaseState $baseState,
                TreePhase $treePhase,
                GroupState $groupState, 
                ActiveGraph $activeGraph
        ) {
            parent::__construct($baseState, $treePhase, $groupState);
            $this->activeGraph = $activeGraph;
        }
        
        public function init() {
                // This visitor resets the active graph if one was there. 
                $this->activeGraph->arrowsIn = []; 
                $this->activeGraph->arrowsOut = []; 
                $this->activeGraph->nodes = [];
        }
        
        public function beforeChildrenDefinition($currentId) {
            $node = $this->baseState->nodes[$currentId]; 
            unset($node['innerNodesId']); 
            $this->activeGraph->nodes[$currentId] = $node;
            $adjArrowsOut = $this->groupState->adjActiveArrowsOut($currentId);
            if ( ! empty($adjArrowsOut)) {
                unset($adjArrowsOut['timeInclusive']); 
                $this->activeGraph->arrowsOut[$currentId] = $adjArrowsOut;   
            }
        }
        
        public function finalize() {
            //echo "The size of active graph is " . \count($this->activeGraph->nodes) . ".<br>". PHP_EOL; 
        }
}