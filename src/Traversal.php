<?php

namespace App;

use App\BaseState; 
use App\TotalGraph;
use App\AbstractVisitor;

class Traversal {
    
        
        public function __construct(private BaseState $baseState, private TotalGraph $totalGraph) {
        }
        
	public function visitNodes(AbstractVisitor $visitor, $rootId = null) {
                $visitor->exitIfUsed(); 
		method_exists($visitor, "init") && $visitor->init();
                // Must manage the case where the root is in a group
                $rootId ??= $this->findActiveContainingGroup($this->totalGraph->rootId);
		$toProcess = [$rootId];
		$visited = [];
		while (true) {

			if ($toProcess == []) {break;}
			$currentId = $toProcess[0];

			If (empty($visited[$currentId])) {
                                $visited[$currentId] = true;
                                $adj = $visitor->beforeChildrenProcess($currentId);
				$toProcess = [...array_keys($adj), ...$toProcess];
                                //echo "In " . get_class($visitor) . " with currentId $currentId added ". json_encode($adj) . " to toProcess." . PHP_EOL;  
			} else {
                                method_exists($visitor, "afterChildrenProcess") && $visitor->afterChildrenProcess($currentId);
                                array_shift($toProcess);
			}
		}
		method_exists($visitor, "finalize") && $ret = $visitor->finalize();
                return $ret ?? null ;
	}
        
        private function findActiveContainingGroup($nodeId) {
            while (($groupId = $this->baseState->nodes[$nodeId]['groupId'] ?? false) && null !== ($nodeId = $groupId));            
            return isset($this->baseState->nodes[$nodeId]) ?  $nodeId: false; 
        }
}