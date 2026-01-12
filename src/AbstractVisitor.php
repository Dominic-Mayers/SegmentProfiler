<?php

namespace App;

use App\TotalGraph; 

#[Exclude]
abstract class AbstractVisitor  {
    
        protected TotalGraph $totalGraph; 
        private $groupsWithNoInnerNodes; 
        
        public function __construct ( TotalGraph $totalGraph, 
                                      $groupsWithNoInnerNodes = null) {
            $this->totalGraph = $totalGraph;
            $this->groupsWithNoInnerNodes = $groupsWithNoInnerNodes; 
        }
        
        protected function groupSiblingsPerCallBack($currentId, $groupType, $groupKeyCallback) {

        	$innerLabelGroups = [];
		$adj = $this->totalGraph->adjActiveTraversalArrowsOut($currentId);
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

}