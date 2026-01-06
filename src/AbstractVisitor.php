<?php

namespace App;

abstract class AbstractVisitor  {
    
        protected TotalGraph $totalGraph; 
        
        
        public function __construct ( private $groupsWithNoInnerNodes = null) {           
        }
        
        public function setTotalGraph($totalGraph) {
            $this->totalGraph = $totalGraph; 
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
                                    $treeKey = $groupKey;
                                    $innerLabel = explode('.', $this->totalGraph->treeLabels[$treeKey])[0];
                                } elseif ($groupType == 'CTwe' || $groupType == 'Twe') {
                                    $treeKey = $groupKey;
                                    $innerLabel = explode('.', $this->totalGraph->treeLabels[$treeKey])[0];
                                } else {
                                    $treeKey = null;
                                    $innerLabel = $groupKey;
                                }
                                $this->groups[] = $groupId = $this->totalGraph->addGroup($innerLabel, $groupType, $group, $treeKey);
                                $this->totalGraph->createGroup($groupId);
                                if ( ! empty($this->groupsWithNoInnerNodes[$groupType]) ) {
                                     $this->totalGraph->removeInnerNodes($groupId);
                                }
			}
		}
        }

}