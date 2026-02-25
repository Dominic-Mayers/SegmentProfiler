<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

namespace App;

use App\BaseState; 

class TotalGraph {
        public string   $treeType = "S"; // S for segment. 
        public string   $rootId; // Needed in Traversal to initiate toProcess
        // For each type of treeKey, gives the treeLabel for each treeKey. 
        // Set in AbstractVisitorT::setNewTree.
        public array    $treeLabels; 
        public array    $treeLabelsTranspose; 

        private int     $rootNb = 0; // The notes start at 1. 
        
        public function __construct(private BaseState $baseState ) {
            $this->rootId = $this->getNodeId($this->treeType, $this->rootNb);
        }
                        
        public function getTree(\Iterator $notesFile, ) {

                // Create the root.
                $currentId = $this->createTreeNode("", $this->rootNb, 'root'); 
                
		foreach ($notesFile as $note) {
			if ( empty (trim ($note))) { continue;}
			$currentId = $this->processNote($currentId,  $note);
		}
		$this->processNote($currentId, $this->rootNb . ":endName=root");
	}

	private function processNote($currentId, $note) {
		//echo "Note: ".trim($note).PHP_EOL;
                [$noteNb, $key,  $value] = $this->readNote($note);

		if ($key == "startName") {
                        $currentId = $this->createTreeNode($currentId, $noteNb, $value); 
                        return $currentId; 
		} else {
                        $nodeId = self::getNodeId($this->treeType, $noteNb);
                        $this->stopNodeIfNodeIdDoesNotMatch($currentId, $nodeId); 
                        if ($key === 'timeFct') {  $key = 'timeInclusive' ;}  
			$this->baseState->nodes[$currentId]['attributes'][$key] = $value;
			//echo "Set group:$key = $value".PHP_EOL;
			if ($key === "endName") {
                            // Todo: Must check $value !== "parent". It is reserved.
                            $currentId = $this->stopNote($currentId);
			}
                        return $currentId; 
		}
	}
        
        private function createTreeNode ($currentId, $noteNb, $startName) : string {

                // Set the new node (target of new arrow) 
                $nodeId = $this->addNode($this->treeType, $noteNb); 
		$this->baseState->nodes[$nodeId]['attributes']['parentId'] = $currentId;
		$this->baseState->nodes[$nodeId]['attributes']['startName'] = $startName;
                // Set the new incoming arrow toward the node, even when the node is the root
                // The one toward the root will not be included in the active graph. It will
                // not be visited in traversal, only seen if we look for that incoming arrow.
                $newArrow = $this->createArrow($currentId, $nodeId);                   
                $this->baseState->arrowsOut[$currentId][$nodeId] = $newArrow;
                $this->baseState->arrowsIn[$nodeId][$currentId] = $newArrow;
                return $nodeId; 
        }
        
        private function stopNodeIfNodeIdDoesNotMatch ($currentId, $nodeId) {
                // Four cases :
                // $currentId === $nodeId === Id of root. Typical at the end.  Nothihg is done.       
                // $currentId === $nodeId !== Id of root. The typical situation. Nothing is done. 
                // $currentId !== $nodeId === Id of root. 
                //      $nodeId can only be the artificial note after the loop. 
                //      In that case, it does not exit on error.
                // $currentId !== $nodeId !== Id of root. 
                //      It leads to an exit on error when currentId === Id of root. 
                                       
 		if ($currentId !== $nodeId) {
                        // No file note should have the root noteNb. 
			echo "While managing new  node $nodeId, stopping current node $currentId by its parent  "; 
                        if ( $currentNode['attributes']['parentId'] === null ) {
                            echo "but exiting because the current node is  the root". PHP_EOL;
                            exit(); 
                        }
                        // The following mimic what is done when key = endName in process Note. 
			$currentNode['attributes']['endName'] = 'parent'; 
                        $currentId = $this->stopNote($currentId); 
			echo "$currentId, which is now the new currentId.".PHP_EOL;
		}          
                return $currentId; 
        }

	private function stopNote($currentId) {
                // It only sets the innerLabel and the exclusive time and move
                // currentId  backward to its parent values.
                $currentNode = & $this->baseState->nodes[$currentId]; 
                $currentNode['attributes']['innerLabel'] = $currentNode['attributes']['startName'] . "_". 
                                                    $currentNode['attributes']['endName'];
                $this->setTimeFlowOfNode($currentId); 
		$newCurrentId = $currentNode['attributes']['parentId'];
		if ($newCurrentId === null && $currentNode['attributes']['endName'] === 'parent')  {
                  // Normally, this condition never occurs because, it only occurs wnen
                  // we pass by stopNodeIfNodeIdDoesNotMatch and we exit in that method.   
                        echo "Error: trying to stop root with endName=parent.". PHP_EOL;
                        exit(); 
		} 
                return $newCurrentId; 
        }
        
        private function readNote($note) : array {
		$noteArr = explode("=", $note);
		$nodeNbAndKey = trim($noteArr[0]);
		$keyArr = explode(":", $nodeNbAndKey, 3);
		$noteNb = (int) $keyArr[0];
		$key = $keyArr[1];
		$value = trim($noteArr[1]); 
                return [$noteNb, $key,  $value]; 
        }
        
        private function addNode($type, int|null $nodeNb = null) : string {
             
		$nodeId = self::getNodeId($type, $nodeNb);
                $this->baseState->nodes[$nodeId]['type'] = $type; 
		$this->baseState->nodes[$nodeId]['attributes']['nodeId'] = $nodeId;
                //echo "Added node $nodeId".PHP_EOL; 
                return $nodeId; 
        }

        private static function getNodeId($prefix, int|null $nb) {
		static $n = [];
		$n[$prefix] ??= 1; // We could start at 0. No conflicr with root. It's not used for tree nodes.  
                $nb ??= $n[$prefix]++; 
                $nodeId =  $prefix . $nb;
		return $nodeId; 
	}
        
        public function addGroup($innerLabel, $type, $innerNodesId, $rootRep = null) {
                if ( count($innerNodesId) == 1)  {
                        echo "Error: attempting to create a singleton".PHP_EOL; 
                        exit();  
                }
                $groupId = $this->addNode($type); 
                //echo "Adding group $groupId." . PHP_EOL; 
                
                if ( isset($rootRep) ) {
                        $this->baseState->nodes[$groupId]['attributes']['treeKey'] = $rootRep['attributes']['treeKey'];
                        $this->baseState->nodes[$groupId]['attributes']['treeKeyWithEmpty'] = $rootRep['attributes']['treeKeyWithEmpty'];
                }
		$this->baseState->nodes[$groupId]['attributes']['innerLabel'] = $innerLabel;
		$this->baseState->nodes[$groupId]['attributes']['timeExclusive'] = 0; 
		foreach ($innerNodesId as $innerNodeId) {
			$this->baseState->nodes[$innerNodeId]['groupId'] = $groupId;
			$this->baseState->nodes[$groupId]['innerNodesId'][] = $innerNodeId;
			$this->baseState->nodes[$groupId]['attributes']['timeExclusive'] += 
                                $this->baseState->nodes[$innerNodeId]['attributes']['timeExclusive'];
                        //echo "Set groupId of $innerNodeId to $groupId and time attributes of that group.". PHP_EOL; 
                }
                //echo "Added group $groupId.". PHP_EOL; 
		return $groupId;
	}

        public function createGroup($groupId) {

		$innerNodesId = $this->baseState->nodes[$groupId]['innerNodesId'];
                $this->baseState->nodes[$groupId]['attributes']['timeInclusive'] = 0;
                $groupTimeInclusive = & $this->baseState->nodes[$groupId]['attributes']['timeInclusive']; 
		foreach ($innerNodesId as $nodeId) {
			$arrowsOut = $this->baseState->arrowsOut[$nodeId] ?? [];
			foreach ($arrowsOut as $targetId => $arrowOut) {
				$this->baseState->arrowsOut[$groupId][$targetId] ??= $this->createArrow($groupId, $targetId, 0);
				$this->baseState->arrowsOut[$groupId][$targetId]['calls'] += $arrowOut['calls'];
                                //echo "Adding {$arrowOut['calls']} outgoing arrow from added $groupId" . ; 
                                //" to $targetId because of its inner node $nodeId<br>".PHP_EOL;
				$this->baseState->arrowsIn[$targetId][$groupId] = $this->baseState->arrowsOut[$groupId][$targetId];
			}
			$arrowsIn = $this->baseState->arrowsIn[$nodeId] ?? [];
			foreach ($arrowsIn as $sourceId => $arrowIn) {
				$this->baseState->arrowsOut[$sourceId][$groupId] ??= $this->createArrow($sourceId, $groupId, 0);
				$this->baseState->arrowsOut[$sourceId][$groupId]['calls'] += $arrowIn['calls'];
                                //echo "Adding {$arrowIn['calls']} incoming arrow from $sourceId 
                                //to added $groupId because of its inner node $nodeId<br>".PHP_EOL;
                                $timeInc = $arrowIn['timeInclusive'];
                                $this->baseState->arrowsOut[$sourceId][$groupId]['timeInclusive'] += $timeInc;
                                if (!in_array($sourceId, $innerNodesId)) {
                                    // Here we have true incoming arrow from $sourceId toward $groupId.
                                    $groupTimeInclusive += $timeInc;
                                }
				$this->baseState->arrowsIn[$groupId][$sourceId] = $this->baseState->arrowsOut[$sourceId][$groupId];
			}
		}
	}

        public function removeInnerNodes($groupId) {
                
                if (empty($this->baseState->nodes[$groupId]) ) { return ; }
                $group = $this->baseState->nodes[$groupId]; 
                $innerNodesId = $group['innerNodesId']??[]; 
                foreach ($innerNodesId as $nodeId) {
                        $this->removeNode($nodeId); 
                        //echo "Removed node $nodeId.". PHP_EOL; 
                }
                $group['innerNodesId'] = [];
        }

        public function removeNode($nodeId) {
		if (isset($this->baseState->arrowsIn[$nodeId])) {
			foreach ($this->baseState->arrowsIn[$nodeId] as $sourceId => $arrow) {
                                unset($this->baseState->arrowsOut[$sourceId][$nodeId]);
                                unset($this->baseState->arrowsIn[$nodeId][$sourceId]);
			}
			if (empty($this->baseState->arrowsOut[$sourceId])) {
				unset($this->baseState->arrowsOut[$sourceId]);
			}
   			if (empty($this->baseState->arrowsIn[$nodeId])) {
				unset($this->baseState->arrowsIn[$nodeId]);
			}
		}
		if (isset($this->baseState->arrowsOut[$nodeId])) {
			foreach ($this->baseState->arrowsOut[$nodeId] as $targetId => $arrow) {
				unset($this->baseState->arrowsOut[$nodeId][$targetId]);
                                unset($this->baseState->arrowsIn[$targetId][$nodeId]);                                    
			}
			if (empty($this->baseState->arrowsOut[$nodeId])) {
				unset($this->baseState->arrowsOut[$nodeId]);
			}
			if (empty($this->baseState->arrowsIn[$targetId])) {
				unset($this->baseState->arrowsIn[$targetId]);
                        }
		}
                $this->removeInnerNodes($nodeId);
		unset($this->baseState->nodes[$nodeId]);
        }
        
        private function createArrow(string $sourceId, string $targetId, $calls = 1) {
		$arrow['sourceId']      = $sourceId;
		$arrow['targetId']      = $targetId;
		$arrow['calls']         = $calls; 
                $arrow['timeInclusive'] = 0; 
                return $arrow; 
	}

        private function setTimeFlowOfNode($currentId) {
	    // To be executed on the tree only.
	    $totalTimeChildren = 0;
            $node = &$this->baseState->nodes[$currentId]; 
	    $adj = $this->baseState->arrowsOut[$currentId] ?? []; 
	    foreach ( $adj as $targetId => $arrow ) {
	        $totalTimeChildren += $this->baseState->nodes[$targetId]['attributes']['timeInclusive'];
	    }
	    if ( isset( $node['attributes']['timeInclusive'] ) ) {
	        $timeExclusive = $node['attributes']['timeInclusive'] - $totalTimeChildren;
		$node['attributes']['timeExclusive'] = $timeExclusive;
	    } else {
		// Normally, this should only happen for the root.
		$node['attributes']['timeInclusive'] = $totalTimeChildren;
		$node['attributes']['timeExclusive'] = 0; 
	    }
            $parentId = $node['attributes']['parentId'];  
            $this->baseState->arrowsIn[$currentId][$parentId]['timeInclusive']  = $node['attributes']['timeInclusive']; 
            $this->baseState->arrowsOut[$parentId][$currentId]['timeInclusive'] = $node['attributes']['timeInclusive']; 
	}
}