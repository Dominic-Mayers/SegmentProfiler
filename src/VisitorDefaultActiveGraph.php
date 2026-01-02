<?php

namespace App;

class VisitorDefaultActiveGraph extends AbstractVisitor {

        private ActiveGraph $activeGraph; 

        public function setActiveGraph($activeGraph) {
            $this->activeGraph = $activeGraph; 
        }

        public function init() {
                // This visitor resets the active graph if one was there. 
                $this->activeGraph->arrowsIn = []; 
                $this->activeGraph->arrowsOut = []; 
                $this->activeGraph->nodes = [];
        }
        
        public function beforeChildren($currentId) {
            $adjArrowsIn  = $this->totalGraph->adjActiveArrowsIn($currentId);
            $adjArrowsOut = $this->totalGraph->adjActiveArrowsOut($currentId);
            if ( ! empty ($adjArrowsIn)  || ! empty ($adjArrowsOut)) {
                $this->activeGraph->nodes[$currentId] = $this->totalGraph->nodes[$currentId];
            }
            if ( ! empty($adjArrowsIn)) {    
                $this->activeGraph->arrowsIn[$currentId] = $adjArrowsIn;
            }
            if ( ! empty($adjArrowsOut)) {    
                $this->activeGraph->arrowsOut[$currentId] = $adjArrowsOut;   
            }
        }
}