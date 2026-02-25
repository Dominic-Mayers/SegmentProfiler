<?php

namespace App;

class VisitorContractionOnTwe extends AbstractVisitorContraction {

        protected $availables;
        private $forest; 
        private $forestParameter;
        private $groupsPerKey;
        private $treeSizePerKey;
        
        public function __construct(BaseState $baseState, TreePhase $treePhase, GroupState $groupState, int $chosenParameter, int $forestParameter) {
            parent::__construct($baseState, $treePhase, $groupState, $chosenParameter);
            $this->availables = [];
            $this->forest = []; 
            $this->forestParameter = $forestParameter;
            $this->groupsPerKey = []; 
            $this->treeSizePerKey = []; 
        }

        #[\Override]
        protected function setAvailables ($levelNodes) {
                foreach($levelNodes as $nodeId) {
                    $key = $this->baseState->nodes[$nodeId]['attributes']['treeKeyWithEmpty'];
                    $this->groupsPerKey[$key][] = $nodeId;   
                    if (isset($this->treeSizePerKey[$key])) {continue;}
                    $adj = $this->getChildrenArrowsOut($nodeId);
                    $this->treeSizePerKey[$key] = 1;
                    foreach ($adj as $childId => $unused) {
                            $childKey = $this->baseState->nodes[$childId]['attributes']['treeKeyWithEmpty'];
                            $this->treeSizePerKey[$key] += $this->treeSizePerKey[$childKey];
                    }
                }
                foreach($levelNodes as $nodeId) {
                    $key = $this->baseState->nodes[$nodeId]['attributes']['treeKeyWithEmpty'];
                    //echo "Placing $nodeId with key $key". PHP_EOL;
                    //echo "<b>Processing Node $nodeId.</b><br>". PHP_EOL; 
                    if (isset($this->forest[$key]) && is_array($this->forest[$key]) ||
                         ($this->treeSizePerKey[$key] - 1)*(\count($this->groupsPerKey[$key]) - 1)  > $this->forestParameter ) 
                    {
                        //echo "Added to the forest with key $key.<br>" . PHP_EOL;
                        $this->availables[$nodeId]  = (bool) $this->forest[$key][] = $nodeId ;
                    } else {$this->availables[$nodeId] = false;} //echo "Not added to the forest.<br>" . PHP_EOL;}
                }
        }        
}