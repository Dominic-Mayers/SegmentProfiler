<?php

namespace App;

class VisitorOptimizedForestWe extends AbstractVisitor {

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
        
        public function afterChildrenProcess($currentId) {
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
            $top = $this->forestArrows[$this->totalGraph->rootId]; 
            $removable = 0;
            $topKey = []; 
            foreach( $top as $nodeId ) {
                $this->totalGraph->nodes[$nodeId]->attributes['TKwe'] = 'Y'; 
                $key = $this->totalGraph->nodes[$nodeId]->attributes['treeKeyWithEmpty'];
                $topKey[$key][] = $nodeId; 
                $treeSize = $this->Size[$key]; 
                $removable += $treeSize;
            }
            /*
            $topGroups = []; 
            foreach ($top as $nodeId) {
                $key = $this->totalGraph->nodes[$nodeId]->attributes['treeKeyWithEmpty']; 
                $topGroups[$key][] = $nodeId;  
            }
            foreach ($top as $nodeId) {
                $key = $this->totalGraph->nodes[$nodeId]->attributes['treeKeyWithEmpty']; 
                $topGroupSize = \count($topGroups[$key]);
                $treeSize = $this->Size[$key]; 
                echo "$nodeId is a top tree with key $key of tree size $treeSize and top group size $topGroupSize.". PHP_EOL;
            }
            */
            $nbGroups = \count($topKey);
            $nbAlias = \count($top);
            echo "There are $removable removable nodes, replaced by $nbAlias aliases toward $nbGroups top subtrees.". PHP_EOL; 
        }

        private function setGroupsAndSize ($level) {
                foreach($level as $nodeId) {
                    $key = $this->totalGraph->nodes[$nodeId]->attributes['treeKeyWithEmpty'];
                    $this->groups[$key][] = $nodeId;   
                    if (isset($this->Size[$key])) {continue;}
                    $adj = $this->getChildrenArrowsOut($nodeId);
                    $this->Size[$key] = 1;
                    foreach ($adj as $childId => $unused) {
                            $childKey = $this->totalGraph->nodes[$childId]->attributes['treeKeyWithEmpty'];
                            $this->Size[$key] += $this->Size[$childKey];
                    }
                }
        }
        
        private function setForest($level) {
                foreach($level as $nodeId) {
                    $key = $this->totalGraph->nodes[$nodeId]->attributes['treeKeyWithEmpty'];
                    //echo "Placing $nodeId with key $key". PHP_EOL; 
                    if (isset($this->forest[$key]) && is_array($this->forest[$key])) {
                        //echo "Key $key already in forest" . PHP_EOL;
                        $this->forest[$key][] = $nodeId; 
                        continue; 
                    }
                    if ( ($this->Size[$key] - 1)*(\count($this->groups[$key]) - 1)  > 1 ) {
                        $this->forest[$key] = [$nodeId];
                        //echo "Added ". str_pad(\count($this->groups[$key]), 4) . "trees of size " .  
                        //      str_pad($this->Size[$key],4) . "in group with key " . str_pad($key,4) .  PHP_EOL;
                    }
                    $this->forestArrows[$nodeId] = []; 
                    $adj = $this->getChildrenArrowsOut($nodeId);
                    foreach ($adj as $childId => $unused) {
                        $childKey = $this->totalGraph->nodes[$childId]->attributes['treeKeyWithEmpty'];
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