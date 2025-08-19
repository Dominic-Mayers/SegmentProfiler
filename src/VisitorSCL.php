<?php

namespace App;

class VisitorSCL extends AbstractVisitor {
    
	public function init() {
		$this->groups = [];
                //echo "Starting SCN".PHP_EOL."-----------".PHP_EOL;
	}

	public function beforeChildren($currentId) {
		$groups = []; 
		$adj = $this->totalGraph->getNotInnerArrowsOut($currentId);
		foreach ($adj as $targetId => $arrow) {
                        $adjIn = $this->totalGraph->getNotInnerArrowsIn($targetId);
                        if (count($adjIn) > 1) {continue;}
			$adjacentNames = $this->getAdjacentNames($targetId);
			if (empty($adjacentNames)) {
				continue;
			}
			$label = "Parents of ". implode(' & ', array_keys($adjacentNames));
			$groups[$label][] = $targetId;
		}
		foreach ($groups as $label => $group) {
			if (count($group) > 1) {
                                $this->groups[] = $groupId = $this->totalGraph->addGroup($label, "SCN", $group);
                                $this->totalGraph->createGroup($groupId); 
                                $a = 0; 
			}
		}
	}

	private function getAdjacentNames($nodeId) {
		$childrenNames = [];

		$adj = $this->totalGraph->getNotInnerArrowsOut($nodeId);
		foreach ($adj as $targetId => $arrow) {
			if (isset($this->totalGraph->nodes[$targetId]->groupId)) {
				continue;
			}
			$childrenNames[$this->totalGraph->nodes[$targetId]->attributes['label']] ??= 0;
			$childrenNames[$this->totalGraph->nodes[$targetId]->attributes['label']] += $arrow->calls;
		}
		ksort ($childrenNames);
		return $childrenNames;
	}
}