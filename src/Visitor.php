<?php

namespace App;

abstract class Visitor  {
    
        public array $groupsPhase1;
        protected TotalGraph $totalGraph; 
        
        public function setTotalGraph($totalGraph) {
            $this->totalGraph = $totalGraph; 
        }
}