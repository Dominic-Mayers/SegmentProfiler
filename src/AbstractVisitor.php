<?php

namespace App;

use App\TotalGraph; 

#[Exclude]
abstract class AbstractVisitor  {
    
        private $childrenArrowsOut = [];
        private $stackedMultipleIncoming = []; 
        
        public function __construct (protected  TotalGraph $totalGraph, 
                                     private $groupsWithNoInnerNodes = null) {
                $this->totalGraph = $totalGraph;
                $this->groupsWithNoInnerNodes = $groupsWithNoInnerNodes; 
        }
        
        public function beforeChildren($currentId) {
                return $this->setChildrenArrowsOut($currentId);
        }
        
        public function getChildrenArrowsOut($currentId) {
                if (isset($this->childrenArrowsOut[$currentId])) {
                    return $this->childrenArrowsOut[$currentId]; 
                } else {
                    echo "Children arrows for $currentId are not set yet."; 
                }
        }

        protected function setChildrenArrowsOut($currentId) {
        // This is intended to be called in the beforeChildren method .
                if (isset($this->childrenArrowsOut[$currentId])) {
                    echo "Children arrows for $currentId already set.";
                    exit(); 
                }
                //$this->childrenArrowsOut[$currentId] = $this->totalGraph->adjActiveArrowsOut($currentId);  
                //return $this->totalGraph->adjActiveArrowsOut($currentId); 
                $currentChildrenArrowsOut = [];
                $adjAllActiveOut = $this->totalGraph->adjActiveArrowsOut($currentId);
                foreach ($adjAllActiveOut as $targetId => $arrow) {
                        if (! $this->isStackedChild($targetId)) {
                                $this->stackedMultipleIncoming[$targetId] = true; 
                                $currentChildrenArrowsOut[$targetId] = $arrow;
                        }
                }
                $this->childrenArrowsOut[$currentId] = $currentChildrenArrowsOut;  
                return $currentChildrenArrowsOut;
        }
        
        protected function groupSiblingsPerCallBack($currentId, $groupType, $groupKeyCallback) {
        	$innerLabelGroups = [];
		$adj = $this->getChildrenArrowsOut($currentId);
		foreach ($adj as $targetId => $arrow) {
                        $adjIn = $this->totalGraph->adjActiveArrowsIn($targetId);
                        if (count($adjIn) > 1) {continue;}
                        $groupKey = $groupKeyCallback($targetId); 
			$innerLabelGroups[$groupKey][] = $targetId;
		}
		foreach ($innerLabelGroups as $groupKey => $group) {
			if (count($group) > 1) {
                                if ($groupType == 'CT' || $groupType == 'T' ) {
                                    $innerLabel = explode('.', $this->totalGraph->treeLabels[$groupKey])[0];
                                    $groupRep = $this->totalGraph->nodes[$group[0]]; 
                                } elseif ($groupType == 'CTwe' || $groupType == 'Twe') {
                                    $innerLabel = explode('.', $this->totalGraph->treeLabelsWithEmpty[$groupKey])[0];
                                    $groupRep = $this->totalGraph->nodes[$group[0]]; 
                                } else {
                                    $innerLabel = $groupKey;
                                    $groupRep = null; 
                                }
                                $this->groups[] = $groupId = $this->totalGraph->addGroup($innerLabel, $groupType, $group, $groupRep );
                                $this->totalGraph->createGroup($groupId);
                                if ( ! empty($this->groupsWithNoInnerNodes[$groupType]) ) {
                                     $this->totalGraph->removeInnerNodes($groupId);
                                }
			}
		}
        }

        private function isStackedChild($targetId) {
                // This is only valid when called in setChildrenArrowsOut, which
                // is itself called in the beforeChildren method. 
                $isStacked = $this->totalGraph->incomingActiveOrder($targetId) > 1 &&  
                        isset($this->stackedMultipleIncoming[$targetId]);
                return $isStacked; 
        }
}