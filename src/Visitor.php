<?php

namespace App;

abstract class Visitor  {
    
        public array $groupsPhase1;
        protected TotalGraph $totalGraph; 
        
        public function setTotalGraph($totalGraph) {
            $this->totalGraph = $totalGraph; 
        }

	private static function getGroupId($prefix) {
		static $n = [];
		$n[$prefix] ??= 1;
		return $prefix . str_pad($n[$prefix]++, 5, '0', STR_PAD_LEFT);
	}

        protected function addGroup($group, $type, $label) {
                if ( count($group) == 1)  {
                        // Singleton are represented by their inner node
                        $groupId = $group[0];
                        if ( $this->totalGraph->nodes[$groupId]->groupId !== null) {
                            echo "Warning: resetting groupId of innernode of singleton $groupId"; 
                            $this->totalGraph->nodes[$groupId]->groupId = null; 
                        }
                        //echo "Taking $groupId as a singleton".PHP_EOL; 
                        return $groupId;  
                }
		$groupId = self::getGroupId($type);
		$this->totalGraph->nodes[$groupId] = new Node($type);
		$this->totalGraph->nodes[$groupId]->attributes['nodeId'] = $groupId;
		$this->totalGraph->nodes[$groupId]->attributes['label'] = $label;

		$this->totalGraph->nodes[$groupId]->attributes['timeFct'] = 0;
		$this->totalGraph->nodes[$groupId]->attributes['timeExclusive'] = 0; 
		foreach ($group as $innernodeId) {
			$this->totalGraph->nodes[$innernodeId]->groupId = $groupId;
			$this->totalGraph->nodes[$groupId]->innerNodesId[] = $innernodeId;
			$this->totalGraph->nodes[$groupId]->attributes['timeFct']       += $this->totalGraph->nodes[$innernodeId]->attributes['timeFct'];
			$this->totalGraph->nodes[$groupId]->attributes['timeExclusive'] += $this->totalGraph->nodes[$innernodeId]->attributes['timeExclusive'];
                }
                //echo "Added group $groupId with label $label".PHP_EOL; 
		return $groupId;
	}
        	
        protected function createGroups() {

		foreach ($this->groupsPhase1 as $groupId) {
                        if (count($this->totalGraph->nodes[$groupId]->innerNodesId) === 1  ) {continue;} 
			$this->createGroup($groupId);
		}
	}
        
        protected function createGroup($groupId) {

		$innerNodesId = $this->totalGraph->nodes[$groupId]->innerNodesId;
		foreach ($innerNodesId as $nodeId) {
			$arrowsOut = $this->totalGraph->arrowsOut[$nodeId] ?? [];
			foreach ($arrowsOut as $targetId => $arrowOut) {
				$this->totalGraph->arrowsOut[$groupId][$targetId] ??= new Arrow($groupId, $targetId, 0);
				$this->totalGraph->arrowsOut[$groupId][$targetId]->calls += $arrowOut->calls;
                                //echo "Adding {$arrowOut->calls} to arrow from $groupId to $targetId because of $nodeId".PHP_EOL; 
				$this->totalGraph->arrowsIn[$targetId][$groupId] = $this->totalGraph->arrowsOut[$groupId][$targetId];
			}
			$arrowsIn = $this->totalGraph->arrowsIn[$nodeId] ?? [];
			foreach ($arrowsIn as $sourceId => $arrowIn) {
				$this->totalGraph->arrowsOut[$sourceId][$groupId] ??= new Arrow($sourceId, $groupId, 0);
				$this->totalGraph->arrowsOut[$sourceId][$groupId]->calls += $arrowIn->calls;
                                //echo "Adding {$arrowIn->calls} to arrow from $sourceId to $groupId because of $nodeId".PHP_EOL; 
				$this->totalGraph->arrowsIn[$groupId][$sourceId] = $this->totalGraph->arrowsOut[$sourceId][$groupId];
			}
		}
	}

        protected function removeInnerNodes($groupId) {
            
                $group = $this->totalGraph->nodes[$groupId]; 
                $innerNodesId = $group->innerNodesId??[]; 
                foreach ($innerNodesId as $nodeId) {
                        $this->removeInnerNodes($nodeId);
                        $this->removeNode($nodeId); 
                }
                $group->innerNodesId = [];
        }
        
        protected function removeNode($nodeId) {
		if (isset($this->totalGraph->arrowsIn[$nodeId])) {
			foreach ($this->totalGraph->arrowsIn[$nodeId] as $sourceId => $arrow) {
                                unset($this->totalGraph->arrowsOut[$sourceId][$nodeId]);
                                unset($this->totalGraph->arrowsIn[$nodeId][$sourceId]);
			}
			if (empty($this->totalGraph->arrowsOut[$sourceId])) {
				unset($this->totalGraph->arrowsOut[$sourceId]);
			}
   			if (empty($this->totalGraph->arrowsIn[$nodeId])) {
				unset($this->totalGraph->arrowsIn[$nodeId]);
			}
		}
		if (isset($this->totalGraph->arrowsOut[$nodeId])) {
			foreach ($this->totalGraph->arrowsOut[$nodeId] as $targetId => $arrow) {
				unset($this->totalGraph->arrowsOut[$nodeId][$targetId]);
                                unset($this->totalGraph->arrowsIn[$targetId][$nodeId]);                                    
			}
			if (empty($this->totalGraph->arrowsOut[$nodeId])) {
				unset($this->totalGraph->arrowsOut[$nodeId]);
			}
			if (empty($this->totalGraph->arrowsIn[$targetId])) {
				unset($this->totalGraph->arrowsIn[$targetId]);
                        }
		}
		unset($this->totalGraph->nodes[$nodeId]);
        }        
}