<?php

namespace App;

class VisitorSCL extends AbstractVisitor {
    
	public function beforeChildren($currentId) {
            $this->groupSiblingsPerCallBack($currentId, "SCL", [$this, "getAdjacentNames"]);
	}

	protected function getAdjacentNames($nodeId) : bool | string {
		$childrenNames = [];

		$adj = $this->totalGraph->adjActiveArrowsOut($nodeId);
		foreach ($adj as $targetId => $arrow) {
			if (isset($this->totalGraph->nodes[$targetId]->groupId)) {
				continue;
			}
			$childrenNames[$this->totalGraph->nodes[$targetId]->attributes['innerLabel']] ??= 0;
			$childrenNames[$this->totalGraph->nodes[$targetId]->attributes['innerLabel']] += $arrow->calls;
		}
                if (empty($childrenNames)) {
				return false; 
	        }
		ksort ($childrenNames);
		$innerLabel = "Parents of ". implode(' & ', array_keys($childrenNames));

		return $innerLabel;
	}
}