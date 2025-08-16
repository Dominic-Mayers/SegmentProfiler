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

			$currentId = $toProcess[0];

			if ($this->totalGraph->isGroup($currentId)) {
				$visited[$currentId] = true;
				array_shift($toProcess);
				continue;
			}

			If (!isset($visited[$currentId]) || !$visited[$currentId]) {
                                method_exists($this->visitor, "beforeChildren") && $this->visitor->beforeChildren($currentId);
				$visited[$currentId] = true;
				$adj = array_keys($this->totalGraph->getNotInnerArrowsOut($currentId));
                                $adjFiltered = array_filter($adj, fn($k) => !isset($visited[$k]) || !$visited[$k], 0);
				$toProcess = [...$adjFiltered, ...$toProcess];
			} else {
                            method_exists($this->visitor, "afterChildren") && $this->visitor->afterChildren($currentId);
                            array_shift($toProcess);
			}
		}
		method_exists($this->visitor, "finalize") && $this->visitor->finalize();
	}
        
}