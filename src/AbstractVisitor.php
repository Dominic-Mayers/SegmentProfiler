<?php

namespace App;

abstract class AbstractVisitor  {
    
        protected TotalGraph $totalGraph; 
        
        public function setTotalGraph($totalGraph) {
            $this->totalGraph = $totalGraph; 
        }
        
        protected function hasSingleLabel($nodeId) {
                return      $this->totalGraph->nodes[$nodeId]->type !== "P" || 
                            $this->totalGraph->nodes[$nodeId]->type !== "SP" || 
                            $this->totalGraph->nodes[$nodeId]->type !== "SL" || 
                            $this->totalGraph->nodes[$nodeId]->type !== "DL" ||
                            $this->totalGraph->nodes[$nodeId]->type !== "T";
        }

}