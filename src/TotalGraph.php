<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

namespace App;

class TotalGraph {
        private string  $treeType = "T";
        private string $noteRootId = "0";  
        private int  $rootNb; // = (int) $noteRootId 
        
        public string $rootId; 
        public array   $nodes = [];
        public array   $arrowsOut = []; 
        private array   $arrowsIn = [];
        
        //Set in P or SP, but use arrays here instead of strings.
        public array $arrayPaths = []; 

        public function __construct() {
            $this->rootNb = (int) $this->noteRootId; 
            $this->rootId = $this->getNodeId($this->treeType, $this->rootNb);             
        }

        public function isGroup($nodeId) {
            return $this->nodes[$nodeId]->isGroup(); 
        }

        public function getNotInnerArrowsOut($nodeId) {
        // This is the same as using the active graph in its original definition, just
        // after the creation of the existing groups, but not after group desactivations.
                $adjNotInnerOut = [];
                $adjAllOut = $this->arrowsOut[$nodeId] ?? [];
                foreach ($adjAllOut as $targetId => $arrow) {
                        if ( $this->nodes[$targetId]->groupId === null ) {
				$adjNotInnerOut[$targetId] = $arrow;
                        }
                }
                return $adjNotInnerOut;
        }
        
        public function getNotInnerArrowsIn($nodeId) {
        // This is the same as using the active graph in its original definition, just
        // after the creation of the existing groups, but not after group desactivations.
                $adjNotInnerIn = [];
                $adjAllIn = $this->arrowsIn[$nodeId] ?? [];
                foreach ($adjAllIn as $targetId => $arrow) {
                        if ( $this->nodes[$targetId]->groupId === null ) {
				$adjNotInnerIn[$targetId] = $arrow;
                        }
                }
                return $adjNotInnerIn;
        }
        
        public function getTree(\Iterator $notesFile, ) {
                // Create the root.
                $currentId = $this->addNode("root", $this->treeType, $this->rootNb); 
		$currentNode = $this->nodes[$currentId];                
		$currentNode->attributes['parentId'] = null;
		$currentNode->attributes['startName'] = 'root';
                
                
		foreach ($notesFile as $note) {
			if ( empty (trim ($note))) { continue;}

			$res = $this->processNote($currentId, $currentNode,  $note);
                        
			if ($res === "StopRoot") {
				break;
			}
		}
		$this->processNote($currentId, $currentNode, $this->noteRootId . ":node:endName=none");
	}

        public function addGroup($label, $type, $innerNodesId, $key = null) {
                if ( count($innerNodesId) == 1)  {
                        echo "Error: attempting to create a singleton".PHP_EOL; 
                        exit();  
                }
                if (isset($key)) {
                    $groupId = $this->addNode($label, $type, $key);
                    //echo "Added node of group $groupId using key" . PHP_EOL; 
                } else {
                    $groupId = $this->addNode($label, $type); 
                    //echo "Added node of group $groupId." . PHP_EOL; 
                }
		$this->nodes[$groupId]->attributes['timeFct'] = 0;
		$this->nodes[$groupId]->attributes['timeExclusive'] = 0; 
		foreach ($innerNodesId as $innerNodeId) {
			$this->nodes[$innerNodeId]->groupId = $groupId;
			$this->nodes[$groupId]->innerNodesId[] = $innerNodeId;
			$this->nodes[$groupId]->attributes['timeFct']       += $this->nodes[$innerNodeId]->attributes['timeFct'];
			$this->nodes[$groupId]->attributes['timeExclusive'] += $this->nodes[$innerNodeId]->attributes['timeExclusive'];
                }
                //echo "Set attributes groupId of inner nodes and innerNodesId for node $groupId". PHP_EOL; 
		return $groupId;
	}

        public function createGroup($groupId) {

		$innerNodesId = $this->nodes[$groupId]->innerNodesId;
		foreach ($innerNodesId as $nodeId) {
			$arrowsOut = $this->arrowsOut[$nodeId] ?? [];
			foreach ($arrowsOut as $targetId => $arrowOut) {
				$this->arrowsOut[$groupId][$targetId] ??= new Arrow($groupId, $targetId, 0);
				$this->arrowsOut[$groupId][$targetId]->calls += $arrowOut->calls;
                                //echo "Adding {$arrowOut->calls} to arrow from $groupId to $targetId because of $nodeId".PHP_EOL; 
				$this->arrowsIn[$targetId][$groupId] = $this->arrowsOut[$groupId][$targetId];
			}
			$arrowsIn = $this->arrowsIn[$nodeId] ?? [];
			foreach ($arrowsIn as $sourceId => $arrowIn) {
				$this->arrowsOut[$sourceId][$groupId] ??= new Arrow($sourceId, $groupId, 0);
				$this->arrowsOut[$sourceId][$groupId]->calls += $arrowIn->calls;
                                //echo "Adding {$arrowIn->calls} to arrow from $sourceId to $groupId because of $nodeId".PHP_EOL; 
				$this->arrowsIn[$groupId][$sourceId] = $this->arrowsOut[$sourceId][$groupId];
			}
		}
	}

        public function removeInnerNodes($groupId) {
            
                $group = $this->nodes[$groupId]; 
                $innerNodesId = $group->innerNodesId??[]; 
                foreach ($innerNodesId as $nodeId) {
                        $this->removeInnerNodes($nodeId);
                        $this->removeNode($nodeId); 
                        //echo "Removed node $nodeId.". PHP_EOL; 
                }
                $group->innerNodesId = [];
        }

        public function removeNode($nodeId) {
		if (isset($this->arrowsIn[$nodeId])) {
			foreach ($this->arrowsIn[$nodeId] as $sourceId => $arrow) {
                                unset($this->arrowsOut[$sourceId][$nodeId]);
                                unset($this->arrowsIn[$nodeId][$sourceId]);
			}
			if (empty($this->arrowsOut[$sourceId])) {
				unset($this->arrowsOut[$sourceId]);
			}
   			if (empty($this->arrowsIn[$nodeId])) {
				unset($this->arrowsIn[$nodeId]);
			}
		}
		if (isset($this->arrowsOut[$nodeId])) {
			foreach ($this->arrowsOut[$nodeId] as $targetId => $arrow) {
				unset($this->arrowsOut[$nodeId][$targetId]);
                                unset($this->arrowsIn[$targetId][$nodeId]);                                    
			}
			if (empty($this->arrowsOut[$nodeId])) {
				unset($this->arrowsOut[$nodeId]);
			}
			if (empty($this->arrowsIn[$targetId])) {
				unset($this->arrowsIn[$targetId]);
                        }
		}
		unset($this->nodes[$nodeId]);
        }

        private static function getNodeId($prefix, int|null $nodeNb) {
		static $n = [];
		$n[$prefix] ??= 1;
                $nodeNb ??= $n[$prefix]++; 
                $nodeId =  $prefix . str_pad($nodeNb, 5, '0', STR_PAD_LEFT);
		return $nodeId; 
	}
        
	private function processNote(&$currentId, &$currentNode, $note) {
		//echo "Note: ".trim($note).PHP_EOL;
		$noteArr = explode("=", $note);
		$nodeNbAndKey = trim($noteArr[0]);
		$keyArr = explode(":", $nodeNbAndKey, 3);
		$nodeNb = (int) $keyArr[0];
                // $keyArr[1] is unused 
		$key = $keyArr[2];
		$value = trim($noteArr[1]);

		if ($key == "startName") {
                        // Set the new arrow
                        $nodeId = $this->addNode($value, $this->treeType, $nodeNb); 
			$newArrow = new Arrow($currentId, $nodeId);                   
			$this->arrowsOut[$currentId][$nodeId] = $newArrow;
			$this->arrowsIn[$nodeId][$currentId] = $newArrow;
                        
                        // Set the new currentNode !
			$currentNode = $this->nodes[$nodeId];
			$currentNode->attributes['parentId'] = $currentId;
			$currentNode->attributes['startName'] = $value;                                                
			//echo "New currentId $nodeId with parentId $currentId.".PHP_EOL;
			$currentId = $nodeId;
			$this->nodes[$currentId] = $currentNode;
		} else {
                        $nodeId = self::getNodeId($this->treeType, $nodeNb); 
			while ($currentId !== $nodeId) {
				if ($currentNode->attributes['parentId'] === null) {
					// Stopped by parent up to root, 
					echo "Error: Could not find segment to stop $nodeId." . PHP_EOL;
					exit();
				}
				echo "Stopping node $currentId by its parent "; 
				$currentNode->attributes['stoppedByParent'] = true;
				$currentNode->attributes['endName'] = 'none';
                                                                
				$currentId = $currentNode->attributes['parentId'];
				$currentNode = $this->nodes[$currentId];
				echo "$currentId, which is now the new currentId. <br>".PHP_EOL;
			}
			$currentNode->attributes[$key] = $value;
			//echo "Set group:$key = $value".PHP_EOL;
			if ($key === "endName") {
				$newvalue = $currentNode->attributes['label'] .= "_$value";
                                $this->setExclusiveTimeOfNode($currentId); 
				$currentId = $currentNode->attributes['parentId'];
				if (!empty($currentId)) {
					$currentNode = $this->nodes[$currentId];
					//echo "Moving to parent $currentId after endName".PHP_EOL;
				} else {
					//echo "Stopping the root.".PHP_EOL;
					return "StopRoot";
				}
			}
		}
	}

        private function addNode($label, $type, int|null $nodeNb = null) : string {
             
		$nodeId = self::getNodeId($type, $nodeNb);
                $this->nodes[$nodeId] = new Node($type);
		$this->nodes[$nodeId]->attributes['nodeId'] = $nodeId;
		$this->nodes[$nodeId]->attributes['label'] = $label;
                //echo "Added node $nodeId with label $label".PHP_EOL; 
                return $nodeId; 
        }

        private function setExclusiveTimeOfNode($currentId) {
	    // To be executed on the tree only.
	    $totalTimeChildren = 0;
            $node = $this->nodes[$currentId]; 
	    $adj = $this->arrowsOut[$currentId] ?? []; 
	    foreach ( $adj as $targetId => $arrow ) {
	        $totalTimeChildren += $this->nodes[$targetId]->attributes['timeFct'];
	    }
	    if ( isset( $node->attributes['timeFct'] ) ) {
	        $timeExclusive = $node->attributes['timeFct'] - $totalTimeChildren;
		$node->attributes[ 'timeExclusive' ] = $timeExclusive;
	    } else {
		// Normally, this should only happen for the root.
		$node->attributes['timeFct'] = $totalTimeChildren;
		$node->attributes[ 'timeExclusive' ] = 0; 
	    }
	}
}