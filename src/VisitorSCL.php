<?php

namespace App;

class VisitorSCL extends AbstractVisitor {
    
	public function beforeChildren($currentId) {
            $this->groupSiblingsPerCallBack($currentId, "SCL", [$this, "getAdjacentNames"]);
	}

	protected function getAdjacentNames($nodeId) : bool | string {
		$childrenNames = [];

		$adj = $this->totalGraph->getNotInnerArrowsOut($nodeId);
		foreach ($adj as $targetId => $arrow) {
			if (isset($this->totalGraph->nodes[$targetId]->groupId)) {
				continue;
			}
			$childrenNames[$this->totalGraph->nodes[$targetId]->attributes['label']] ??= 0;
			$childrenNames[$this->totalGraph->nodes[$targetId]->attributes['label']] += $arrow->calls;
		}
                if (empty($childrenNames)) {
				return false; 
	        }
		ksort ($childrenNames);
		$label = "Parents of ". implode(' & ', array_keys($childrenNames));

		return $label;
	}
}