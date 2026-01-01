<?php

namespace App;

abstract class AbstractVisitor  {
    
        protected TotalGraph $totalGraph; 
        
        
        public function __construct ( private $groupsWithNoInnerNodes = null) {           
        }
        
        public function setTotalGraph($totalGraph) {
            $this->totalGraph = $totalGraph; 
        }
        
        
        protected function groupSiblingsPerCallBack($currentId, $groupType, $innerLabelCallback) {

        	$innerLabelGroups = [];
		$adj = $this->totalGraph->getNotInnerArrowsOut($currentId);
		foreach ($adj as $targetId => $arrow) {
                        $adjIn = $this->totalGraph->getNotInnerArrowsIn($targetId);
                        if (count($adjIn) > 1) {continue;}
                        $innerLabel = $innerLabelCallback($targetId); 
                        if (!$innerLabel) { continue;}
			$innerLabelGroups[$innerLabel][] = $targetId;
		}
		foreach ($innerLabelGroups as $innerLabel => $group) {
 
			if (count($group) > 1) {
                                $newGroupType = isset($hasNotSingleLabel[$innerLabel])   ? $groupType . "X": $groupType;
                                $this->groups[] = $groupId = $this->totalGraph->addGroup($innerLabel, $newGroupType, $group);
                                $this->totalGraph->createGroup($groupId);
                                if ( ! empty($this->groupsWithNoInnerNodes[$groupType]) ) {
                                     $this->totalGraph->removeInnerNodes($groupId);
                                }
			}
		}
        }

}