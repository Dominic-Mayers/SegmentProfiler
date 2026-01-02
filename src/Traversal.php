<?php

namespace App;

class Traversal {
    
        private AbstractVisitor $visitor;
        private TotalGraph $totalGraph;
        
        public function __construct(TotalGraph $totalGraph, AbstractVisitor $visitor) {
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

			If (!isset($visited[$currentId]) || !$visited[$currentId]) {
                                method_exists($this->visitor, "beforeChildren") && $this->visitor->beforeChildren($currentId);
				$visited[$currentId] = true;
				$adjTraversal = array_keys($this->totalGraph->adjActiveTraversalArrowsOut($currentId));
				$toProcess = [...$adjTraversal, ...$toProcess];
                                //echo "In " . get_class($this->visitor) . " added ". json_encode($adjFiltered) . " to toProcess." . PHP_EOL;  
			} else {
                            method_exists($this->visitor, "afterChildren") && $this->visitor->afterChildren($currentId);
                            array_shift($toProcess);
			}
		}
		method_exists($this->visitor, "finalize") && $this->visitor->finalize();
	}
        
}