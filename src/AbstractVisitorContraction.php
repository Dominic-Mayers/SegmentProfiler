<?php

namespace App;

abstract class AbstractVisitorContraction extends AbstractVisitor {
        protected $availables;
        private $heights ; 
        private $chosen; 
        private $chosenParameter;
        private $notChosenContiguousOf; 
        private $chosenStoppingContiguousOf;
        private $contiguousGroups;
        
        public function __construct(
                BaseState $baseState, 
                TreePhase $treePhase,
                GroupState $groupState, 
                int $chosenParameter = 0, 
        ) {
            $this->chosenParameter = $chosenParameter; 
            parent::__construct($baseState, $treePhase, $groupState);
        }

        public function getHeights() {
            return $this->heights;
        }
        
        public function init() {
            $this->availables = []; 
            $this->heights = [];
            $this->chosen = [];
            $this->contiguousGroups = [];
            $this->notChosenContiguousOf = [];
            $this->chosenStoppingContiguousOf = []; 
        }
        
        public function afterChildrenProcess($currentId) {
            $this->heights[$currentId] = 0; 
            $adj = $this->getChildrenArrowsOut($currentId);
            foreach ($adj as $childId => $notused) {
                $this->heights[$currentId] = max($this->heights[$currentId], 1+ $this->heights[$childId]); 
            }
        }
                
        public function finalize() {
            $nodesByLevels = []; 
            foreach($this->heights as $nodeId=> $height) {
                $nodesByLevels[$height][] = $nodeId; 
            }
            for ($l = 0; $l < \count($nodesByLevels); $l++) {
                //echo "<b style=\"font-size: 1.5em;\"> Setting forest at level $l <br></b>". PHP_EOL; 
                $this->setAvailables($nodesByLevels[$l]);
                //echo "Starting contractions<br>". PHP_EOL; 
                $this->setContractionGroups($nodesByLevels[$l]); 
            }
            // Here we create the groups. 
            ///*
            foreach ($this->contiguousGroups as $nodeId => $group) {
                if (count($group) > 1 ) {
                    $innerLabel = $this->baseState->nodes[$nodeId]['attributes']['innerLabel'];
                    $groupRep = $this->baseState->nodes[$nodeId]; 
                    $groupId = $this->groupState->addGroup($innerLabel, 'CTweC', $group, $groupRep);
                    $this->groupState->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['CTweC']) ) {
                        $this->treePhase->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId.<br>". PHP_EOL;
                }
            } 
            //*/
        }

        abstract protected function setAvailables ($levelNodes);
        
        private function setContractionGroups($level) {
                foreach($level as $nodeId) {
                    $this->notChosenContiguousOf[$nodeId] = []; 
                    $this->chosenStoppingContiguousOf[$nodeId] = [];
                    $adj = $this->getChildrenArrowsOut($nodeId);
                    foreach ($adj as $childId => $unused) {
                        if ($this->chosen[$childId]) {
                            // Even when $nodeId is not in the forest
                            $this->chosenStoppingContiguousOf[$nodeId][] = $childId;
                            //echo "Adding $childId to the chosen as a child of $nodeId.<br>".PHP_EOL; 
                        } else {
                            // Even when $nodeId is in the forest
                            $this->chosenStoppingContiguousOf[$nodeId] = array_merge($this->chosenStoppingContiguousOf[$nodeId],
                                    $this->chosenStoppingContiguousOf[$childId]);
                            //echo "Addding ". json_encode($this->chosenStoppingContiguousOf[$childId]) .
                            //        " as first chosen descendents of $nodeId. <br>". PHP_EOL;
                            $this->notChosenContiguousOf[$nodeId] = array_merge([$childId], $this->notChosenContiguousOf[$nodeId],
                                    $this->notChosenContiguousOf[$childId]);
                            //echo "Addding $childId and ". json_encode($this->notChosenContiguousOf[$childId]) .
                            //        " to non chosen contiguous descendents of $nodeId. <br>". PHP_EOL;
                        }
                    }
                    $this->chosen[$nodeId] =  ($this->availables[$nodeId] && 
                            \count($this->notChosenContiguousOf[$nodeId]) >= $this->chosenParameter); 
                    if ($this->chosen[$nodeId] || $nodeId === $this->treePhase->rootId) {
                        $this->contiguousGroups[$nodeId] = [$nodeId, ...$this->notChosenContiguousOf[$nodeId]];
                        $this->treePhase->nodes[$nodeId]['attributes']['extraLabel'] = '!!';
                        //echo "There are ".count($this->notChosenContiguousOf[$nodeId]). " continguous not chosen node below <b>chosen node $nodeId.</b><br>". PHP_EOL; 
                    } // else { echo "There are ".count($this->notChosenContiguousOf[$nodeId]). " continguous not chosen node below node $nodeId.<br>". PHP_EOL;}
                }
        }        
}