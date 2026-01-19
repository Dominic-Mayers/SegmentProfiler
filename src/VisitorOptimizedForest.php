<?php

namespace App;

class VisitorOptimizedForest extends AbstractVisitor {

        private $heights ; 
        private $groups;
        private $Size;
        private $forestArrows;
        private $forestTop; 
        private $forest; 
        
        public function getHeights() {
            return $this->heights;
        }
        
        public function init() {
            $this->heights = [];
            $this->groups = [];
            $this->reducedSize = [];
            $this->forestArrows = []; 
            $this->forestTop = []; 
            $this->forest = []; 
        }
        
        public function afterChildren($currentId) {
            $this->heights[$currentId] = 0; 
            $adj = $this->getChildrenArrowsOut($currentId);
            foreach ($adj as $childId => $notused) {
                $this->heights[$currentId] = max($this->heights[$currentId], 1+ $this->heights[$childId]); 
            }
        }
                
        public function finalize() {
            $levels = []; 
            foreach($this->heights as $nodeId=> $height) {
                $levels[$height][] = $nodeId; 
            }
            for ($l = 0; $l < \count($levels); $l++) {
                $this->setGroupsAndSize($levels[$l]);
                $this->setForest($levels[$l]); 
            }
            $rootKey = $this->totalGraph->nodes[$this->totalGraph->rootId]->attributes['treeKey']; 
            $top = $this->forestArrows[$this->totalGraph->rootId]; 
            $removable = 0;
            foreach ($top as $nId) {
                $key = $this->totalGraph->nodes[$nId]->attributes['treeKey']; 
                echo "$nId with key $key is a top of size {$this->Size[$key]}". PHP_EOL;
                $removable += $this->Size[$key]; 
            }
            echo "The size of ". $this->totalGraph->rootId . " is " . 
                    $this->Size[$rootKey] . PHP_EOL; 
            echo "Total removable is $removable.". PHP_EOL; 
        }

        private function setGroupsAndSize ($level) {
                foreach($level as $nodeId) {
                    $key = $this->totalGraph->nodes[$nodeId]->attributes['treeKey'];
                    $this->groups[$key][] = $nodeId;   
                    if (isset($this->Size[$key])) {continue;}
                    $adj = $this->getChildrenArrowsOut($nodeId);
                    $this->Size[$key] = 1;
                    foreach ($adj as $childId => $unused) {
                            $childKey = $this->totalGraph->nodes[$childId]->attributes['treeKey'];
                            $this->Size[$key] += $this->Size[$childKey];
                    }
                }
        }
        
        private function setForest($level) {
                foreach($level as $nodeId) {
                    $key = $this->totalGraph->nodes[$nodeId]->attributes['treeKey'];
                    //echo "Placing $nodeId with key $key". PHP_EOL; 
                    if (isset($this->forest[$key])) {
                        //echo "Key $key already in forest" . PHP_EOL; 
                        continue; 
                    }
                    if ( ($this->Size[$key] - 1)*(\count($this->groups[$key]) - 1)  > 1 ) {
                        $this->forest[$key] = true;
                        echo "Added ". str_pad(\count($this->groups[$key]), 4) . "trees of size " .  
                              str_pad($this->Size[$key],4) . "in group with key " . str_pad($key,4) .  PHP_EOL;
                        //$this->reducedSize[$key] = 1; 
                    }
                    $this->forestArrows[$nodeId] = []; 
                    $adj = $this->getChildrenArrowsOut($nodeId);
                    foreach ($adj as $childId => $unused) {
                        $childKey = $this->totalGraph->nodes[$childId]->attributes['treeKey'];
                        if (!empty($this->forest[$childKey])) {
                            $this->forestArrows[$nodeId][] = $childId; 
                        } elseif (!empty($this->forestArrows[$childId])) {
                            $this->forestArrows[$nodeId] = 
                                        array_merge($this->forestArrows[$nodeId] , 
                                        $this->forestArrows[$childId]);
                        } else {
                            unset($this->forestArrows[$childId]); 
                        }
                    }
                    if ($nodeId !== $this->totalGraph->rootId && empty($this->forest[$nodeId]) && empty($this->forestArrows[$nodeId]) ) {
                        unset($this->forestArrows[$nodeId]); 
                    }
                    
                }
        }
        
        private function setAlias($nodeId, $key) {}
}