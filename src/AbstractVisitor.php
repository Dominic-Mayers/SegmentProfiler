<?php

namespace App;

abstract class AbstractVisitor  {
    
        protected TotalGraph $totalGraph; 
        
        public function setTotalGraph($totalGraph) {
            $this->totalGraph = $totalGraph; 
        }
        
        protected function hasSingleLabel($type) {
                return      $type === "T"; // ||
                            //$type === "P" || 
                            //$type === "SP" || 
                            //$type === "SL" || 
                            //$type === "DL";
        }
        
        protected function groupSiblingsPerCallBack($currentId, $groupType, $labelCallback) {

        	$labelGroups = [];
                $hasNotSingleLabel = [];
		$adj = $this->totalGraph->getNotInnerArrowsOut($currentId);
		foreach ($adj as $targetId => $arrow) {
                        $adjIn = $this->totalGraph->getNotInnerArrowsIn($targetId);
                        if (count($adjIn) > 1) {continue;}
                        $label = $labelCallback($targetId); 
                        if (!$label && $label !== 0) { continue;}
			$labelGroups[$label][] = $targetId;
                        $type = $this->totalGraph->nodes[$targetId]->type; 
                        if ( ! $this->hasSingleLabel($type) ) { $hasNotSingleLabel[$label]  = true; }
		}
		foreach ($labelGroups as $label => $group) {
 
			if (count($group) > 1) {
                                $newGroupType = isset($hasNotSingleLabel[$label])   ? $groupType . "X": $groupType;
                                $this->groups[] = $groupId = $this->totalGraph->addGroup($label, $newGroupType, $group);
                                $this->totalGraph->createGroup($groupId);
                                if ( ! isset($hasNotSingleLabel[$label]) && $this->hasSingleLabel($groupType) ) {
                                     $this->totalGraph->removeInnerNodes($groupId);
                                }
			}
		}
        }

}