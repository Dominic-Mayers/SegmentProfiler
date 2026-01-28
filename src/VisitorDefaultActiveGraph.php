<?php

namespace App;

use App\TotalGraph;
use App\ActiveGraph;

#[Exclude]
class VisitorDefaultActiveGraph extends AbstractVisitor {

        private ActiveGraph $activeGraph; 
        private $totalSaved; 

        public function __construct (
                TotalGraph $totalGraph,
                ActiveGraph $activeGraph
        ) {
            parent::__construct($totalGraph);
            $this->activeGraph = $activeGraph;
        }
        
        public function init() {
                // This visitor resets the active graph if one was there. 
                $this->activeGraph->arrowsIn = []; 
                $this->activeGraph->arrowsOut = []; 
                $this->activeGraph->nodes = [];
                $this->totalGraph->totalSaved = 0; 
        }
        
        public function beforeChildrenDefinition($currentId) {
            //if ($this->totalGraph->nodes[$currentId]['attributes']['maxSaved']) {
            //    $saved = $this->totalGraph->nodes[$currentId]['attributes']['saved']; 
            //    echo $currentId . " is a maxSaved of " . $saved . PHP_EOL;
            //    $this->totalGraph->totalSaved += $saved; 
            //}
            $adjArrowsIn  = $this->totalGraph->adjActiveArrowsIn($currentId);
            $adjArrowsOut = $this->totalGraph->adjActiveArrowsOut($currentId);
            if ( ! empty ($adjArrowsIn)  || ! empty ($adjArrowsOut) || $currentId === $this->totalGraph->rootId) {
                $this->activeGraph->nodes[$currentId] = $this->totalGraph->nodes[$currentId];
            }
            if ( ! empty($adjArrowsIn)) {    
                $this->activeGraph->arrowsIn[$currentId] = $adjArrowsIn;
            }
            if ( ! empty($adjArrowsOut)) {    
                $this->activeGraph->arrowsOut[$currentId] = $adjArrowsOut;   
            }
        }
        
        public function finalize() {
            echo "The size of active graph is " . \count($this->activeGraph->nodes) . ".<br>". PHP_EOL; 
        }
}