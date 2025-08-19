<?php

namespace App;

class VisitorSL extends AbstractVisitor {
        
	public function init() {
                $this->newNonSingletonSinceLastSet = false; 
                //echo "Starting SN".PHP_EOL."-----------".PHP_EOL;
	}

	public function beforeChildren($currentId) {
		$labelGroups = [];
                $hasNotSingleLabel = []; 
		$adjOut = $this->totalGraph->getNotInnerArrowsOut($currentId); 
                $type = "SL";
		foreach ($adjOut as $targetId => $arrow) {
                        $adjIn = $this->totalGraph->getNotInnerArrowsIn($targetId);
                        if (count($adjIn) > 1) {continue;}
			$label = $this->totalGraph->nodes[$targetId]->attributes['label'];
			$labelGroups[$label][] = $targetId;
                        if ( ! $this->hasSingleLabel($targetId) ) { $hasNotSingleLabel[$label]  = true; }
		}
		foreach ($labelGroups as $label => $group) {
                    if (count($group) > 1) {
                        $newType = isset($hasNotSingleLabel[$label])  ? $type . "X": $type; 
			$this->groups[] = $groupId = $this->totalGraph->addGroup($label, $newType, $group);
                        $this->totalGraph->createGroup($groupId); 
                        if (! isset($hasNotSingleLabel[$label])) {
                            $this->totalGraph->removeInnerNodes($groupId); 
                        }
                        //echo "Added new group $groupId".PHP_EOL; 
                    }
		}
	}
}