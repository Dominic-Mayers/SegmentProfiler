<?php

namespace App;

use App\TotalGraph;
use App\AbstractVisitor;

class Traversal {
    
        
        public function __construct(private TotalGraph $totalGraph) {
        }
        
	public function visitNodes(AbstractVisitor $visitor, $rootId = null) {
		method_exists($visitor, "init") && $visitor->init();
                $rootId ??= $this->totalGraph->rootId; 
		$toProcess = [$rootId];
		$visited = [];
		while (true) {

			if ($toProcess == []) {break;}
			$currentId = $toProcess[0];

			If (empty($visited[$currentId])) {
                                $visited[$currentId] = true;
                                $adj = $visitor->beforeChildren($currentId);
				$toProcess = [...array_keys($adj), ...$toProcess];
                                //echo "In " . get_class($visitor) . " with currentId $currentId added ". json_encode($adj) . " to toProcess." . PHP_EOL;  
			} else {
                                method_exists($visitor, "afterChildren") && $visitor->afterChildren($currentId);
                                array_shift($toProcess);
			}
		}
		method_exists($visitor, "finalize") && $ret = $visitor->finalize();
                return $ret ?? null ;
	}
        
}