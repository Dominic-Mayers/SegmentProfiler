<?php

namespace App;

class Traversal {
    
        private Visitor $visitor;
        private TotalGraph $totalGraph;
        
        public function __construct(TotalGraph $totalGraph, Visitor $visitor) {
                $this->visitor = $visitor;
                $this->totalGraph = $totalGraph;
                $this->visitor->setTotalGraph($totalGraph); 
        }
        
	public function visitNodes() {
		method_exists($this->visitor, "init") && $this->visitor->init();
		$toProcess = [$this->totalGraph->rootId];
		$visited = [];
		while (true) {

			if ($toProcess == []) {
				break;
			}

			$currentId = end($toProcess);
			$currentNode = $this->totalGraph->nodes[$currentId];

			if ($currentNode->groupId) {
				$visited[$currentId] = true;
				array_pop($toProcess);
				continue;
			}

			If (!isset($visited[$currentId]) || !$visited[$currentId]) {
                                method_exists($this->visitor, "beforeChildren") && $this->visitor->beforeChildren($currentId);
				$visited[$currentId] = true;
				$adj = $this->totalGraph->getNotInnerArrowsOut($currentId);
				foreach ($adj as $targetId => $arrow) {
					if (
						!isset($visited[$targetId]) || !$visited[$currentId]
					) {
						$toProcess[] = $targetId;
					}
				}
			} else {
                            method_exists($this->visitor, "afterChildren") && $this->visitor->afterChildren($currentId);
                            array_pop($toProcess);
			}
		}
		method_exists($this->visitor, "finalize") && $this->visitor->finalize();
	}
        
}