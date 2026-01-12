<?php

namespace App;

use App\TotalGraph;

class Traversal {
    
        
        public function __construct(private TotalGraph $totalGraph) {
        }
        
	public function visitNodes( $visitor, $rootId = null) {
		method_exists($visitor, "init") && $visitor->init();
                $rootId ??= $this->totalGraph->rootId; 
		$toProcess = [$rootId];
		$visited = [];
		while (true) {

			if ($toProcess == []) {
				break;
			}

			$currentId = $toProcess[0];

			If (empty($visited[$currentId])) {
                                method_exists($visitor, "beforeChildren") && $visitor->beforeChildren($currentId);
                                $visited[$currentId] = true;
                                $adj = array_keys($this->totalGraph->adjActiveArrowsOut($currentId)); 
                                $adjTraversal = array_filter($adj , fn($x) => empty($visited[$x]));
				$toProcess = [...$adjTraversal, ...$toProcess];
                                //echo "In " . get_class($visitor) . " added ". json_encode($adjFiltered) . " to toProcess." . PHP_EOL;  
			} else {
                                method_exists($visitor, "afterChildren") && $visitor->afterChildren($currentId);
                                array_shift($toProcess);
			}
		}
		method_exists($visitor, "finalize") && $visitor->finalize();
	}
        
}