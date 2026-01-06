<?php

namespace App;

class Traversal {
    
        private AbstractVisitor $visitor;
        private TotalGraph $totalGraph;
        private $rootId; 
        
        public function __construct(TotalGraph $totalGraph, AbstractVisitor $visitor, $nodeId = null) {
                $this->visitor = $visitor;
                $this->totalGraph = $totalGraph;
                $this->visitor->setTotalGraph($totalGraph); 
                $this->rootId =  $nodeId ?? $this->totalGraph->rootId; 
        }
        
	public function visitNodes() {
		method_exists($this->visitor, "init") && $this->visitor->init();
		$toProcess = [$this->rootId];
		$visited = [];
                $this->totalGraph->isTree = true; 
		while (true) {

			if ($toProcess == []) {
				break;
			}

			$currentId = $toProcess[0];

			If (!isset($visited[$currentId]) || !$visited[$currentId]) {
                                method_exists($this->visitor, "beforeChildren") && $this->visitor->beforeChildren($currentId);
                                $visited[$currentId] = true;
                                $adj = array_keys($this->totalGraph->adjActiveArrowsOut($currentId)); 
                                $adjTraversal = array_filter($adj , fn($x) => empty($visited[$x]));
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