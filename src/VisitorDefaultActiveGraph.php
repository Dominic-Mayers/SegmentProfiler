<?php

namespace App;

class VisitorDefaultActiveGraph extends Visitor {

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
            $adjArrowsIn  = $this->totalGraph->getNotInnerArrowsIn($currentId);
            $adjArrowsOut = $this->totalGraph->getNotInnerArrowsOut($currentId);
            $this->activeGraph->arrowsIn[$currentId] = $adjArrowsIn;
            $this->activeGraph->arrowsOut[$currentId] = $adjArrowsOut;
            $this->activeGraph->nodes[$currentId] = $this->totalGraph->nodes[$currentId]; 
        }
}