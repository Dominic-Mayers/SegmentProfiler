<?php

namespace App;

class VisitorOptimizedForestWe extends AbstractVisitor {

        private $heights ; 
        private $groups;
        private $contiguousCTweGroups;
        private $Size;
        private $forest;
        private $chosen; 
        private $notChosenContiguousDescendentsOf; 
        private $chosenStoppingContiguousDescendentsOf;
        private $nbLevels; 
        
        public function getHeights() {
            return $this->heights;
        }
        
        public function init() {
            $this->heights = [];
            $this->groups = [];
            $this->forest = [];
            $this->chosen = [];
            $this->notChosenContiguousDescendentsOf = [];
            $this->chosenStoppingContiguousDescendentsOf = []; 
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
            $this->nbLevels = \count($levels); 
            for ($l = 0; $l < \count($levels); $l++) {
                $this->setGroupsAndSize($levels[$l]);
                //echo "<b style=\"font-size: 1.5em;\"> Setting forest at level $l <br></b>". PHP_EOL; 
                $this->setForest($levels[$l]); 
            }
            $top = $this->chosenStoppingContiguousDescendentsOf[$this->totalGraph->rootId]; 
            $removable = 0;
            $topKey = []; 
            foreach( $top as $nodeId ) {
                $this->totalGraph->nodes[$nodeId]['attributes']['TTwe'] = 'Y'; 
                $key = $this->totalGraph->nodes[$nodeId]['attributes']['treeKeyWithEmpty'];
                $topKey[$key][] = $nodeId; 
                $treeSize = $this->Size[$key]; 
                $removable += $treeSize;
            }
            /*
            $topGroups = []; 
            foreach ($top as $nodeId) {
                $key = $this->totalGraph->nodes[$nodeId]['attributes']['treeKeyWithEmpty']; 
                $topGroups[$key][] = $nodeId;  
            }
            foreach ($top as $nodeId) {
                $key = $this->totalGraph->nodes[$nodeId]['attributes']['treeKeyWithEmpty']; 
                $topGroupSize = \count($topGroups[$key]);
                $treeSize = $this->Size[$key]; 
                echo "$nodeId is a top tree with key $key of tree size $treeSize and top group size $topGroupSize.". PHP_EOL;
            }
            */
            $nbGroups = \count($topKey);
            $nbAlias = \count($top);
            echo "There are $removable removable nodes, replaced by $nbAlias aliases toward $nbGroups top subtrees.<br>". PHP_EOL;
            // Here we create the groups. 
            ///*
            foreach ($this->contiguousCTweGroups as $nodeId => $group) {
                if (count($group) > 1 ) {
                    $innerLabel = $this->totalGraph->nodes[$nodeId]['attributes']['innerLabel'];
                    $groupRep = $this->totalGraph->nodes[$nodeId]; 
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'CTweC', $group, $groupRep);
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['CTweC']) ) {
                        $this->totalGraph->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId". PHP_EOL;
                }
            } 
            //*/
        }

        private function setGroupsAndSize ($level) {
                foreach($level as $nodeId) {
                    $key = $this->totalGraph->nodes[$nodeId]['attributes']['treeKeyWithEmpty'];
                    $this->groups[$key][] = $nodeId;   
                    if (isset($this->Size[$key])) {continue;}
                    $adj = $this->getChildrenArrowsOut($nodeId);
                    $this->Size[$key] = 1;
                    foreach ($adj as $childId => $unused) {
                            $childKey = $this->totalGraph->nodes[$childId]['attributes']['treeKeyWithEmpty'];
                            $this->Size[$key] += $this->Size[$childKey];
                    }
                }
        }
        
        private function setForest($level) {
                foreach($level as $nodeId) {
                    $key = $this->totalGraph->nodes[$nodeId]['attributes']['treeKeyWithEmpty'];
                    //echo "Placing $nodeId with key $key". PHP_EOL;
                    //echo "<b>Processing Node $nodeId.</b><br>". PHP_EOL; 
                    if (isset($this->forest[$key]) && is_array($this->forest[$key]) ||
                         ($this->Size[$key] - 1)*(\count($this->groups[$key]) - 1)  > 1 ) 
                    {
                        //echo "Added to the forest with key $key.<br>" . PHP_EOL;
                        $this->forest[$key][] = $nodeId; 
                    } else {
                        //echo "Not added to the forest.<br>" . PHP_EOL;
                        
                    }
                    $this->notChosenContiguousDescendentsOf[$nodeId] = []; 
                    $this->chosenStoppingContiguousDescendentsOf[$nodeId] = [];
                    $adj = $this->getChildrenArrowsOut($nodeId);
                    foreach ($adj as $childId => $unused) {
                        if (!empty($this->chosen[$childId])) {
                            // Even when $nodeId is not in the forest
                            $this->chosenStoppingContiguousDescendentsOf[$nodeId][] = $childId;
                            $this->chosen[] = $childId; 
                            //echo "Adding $childId to the chosen as a child of $nodeId.<br>".PHP_EOL; 
                        } else {
                            // Even when $nodeId is in the forest
                            $this->chosenStoppingContiguousDescendentsOf[$nodeId] = 
                                        array_merge($this->chosenStoppingContiguousDescendentsOf[$nodeId] , 
                                        $this->chosenStoppingContiguousDescendentsOf[$childId]);
                            //echo "Addding ". json_encode($this->chosenStoppingContiguousDescendentsOf[$childId]) .
                            //        " as first chosen descendents of $nodeId. <br>". PHP_EOL;
                            $this->notChosenContiguousDescendentsOf[$nodeId] = 
                                        array_merge([$childId], $this->notChosenContiguousDescendentsOf[$nodeId] , 
                                        $this->notChosenContiguousDescendentsOf[$childId]);
                            //echo "Addding $childId and ". json_encode($this->notChosenContiguousDescendentsOf[$childId]) .
                            //        " to non chosen contiguous descendents of $nodeId. <br>". PHP_EOL;
                        } 
                    }
                    $nodeType = ""; 
                    $this->chosen[$nodeId] = \false;
                    if (!empty($this->forest[$key]) && 
                            \count($this->notChosenContiguousDescendentsOf[$nodeId]) >= 0) {
                        // Even when $nodeId is not in the forest
                        $this->chosen[$nodeId] = \true; 
                        //echo "Adding $nodeId to the chosen.<br>".PHP_EOL;
                        $nodeType = " chosen"; 
                    }
                    //echo "There are " .
                    //            \count($this->notChosenContiguousDescendentsOf[$nodeId]) . 
                    //        " non chosen contiguous descendents of$nodeType $nodeId : " . 
                            json_encode($this->notChosenContiguousDescendentsOf[$nodeId]) . "<br>". PHP_EOL;
                    if ($this->chosen[$nodeId] || $nodeId === $this->totalGraph->rootId) {
                    //    echo "Creating the group rooted at $nodeId.<br>".PHP_EOL; 
                        $this->contiguousCTweGroups[$nodeId] = /* $nodeId === $this->totalGraph->rootId ? 
                                $this->notChosenContiguousDescendentsOf[$nodeId] : */
                                [$nodeId, ...$this->notChosenContiguousDescendentsOf[$nodeId]];
                    }
                }                
        }
        
        private function setAlias($nodeId, $key) {}
}