<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

namespace App;

class TotalGraph {
        private string  $treeType = "T";
        private string $noteRootId = "00000";  
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

        public function getNotInnerArrowsOut($sourceId) {
                $adjNotInnerOut = [];
                $adjAllOut = $this->arrowsOut[$sourceId] ?? [];
                foreach ($adjAllOut as $targetId => $arrow) {
                        if ( $this->nodes[$targetId]->groupId === null ) {
				$adjNotInnerOut[$targetId] = $arrow;
                        }
                }
                return $adjNotInnerOut;
        }
        
        public function getNotInnerArrowsIn($targetId) {
        // This is the same as using the active graph in its original definition, just
        // after the creation of the existing groups, but not after group desactivations.
                $adjNotInnerIn = [];
                $adjAllIn = $this->arrowsIn[$targetId] ?? [];
                foreach ($adjAllIn as $sourceId => $arrow) {
                        if ( $this->nodes[$sourceId]->groupId === null ) {
				$adjNotInnerIn[$sourceId] = $arrow;
                        }
                }
                return $adjNotInnerIn;
        }
        
        public function getTree(\Iterator $notesFile, ) {

                // Create the root.
                $currentId = $this->addNode($this->treeType, $this->rootNb); 
		$currentNode = $this->nodes[$currentId];
		$currentNode->attributes['parentId'] = null;
		$currentNode->attributes['startName'] = 'root';              
                
		foreach ($notesFile as $note) {
			if ( empty (trim ($note))) { continue;}
                        // Todo: Must check $note is not for a root.
                        // The logic relies on that: two cases (1) ordinary
                        // no root situation or (2) root situation after
                        // the loop. 
			$this->processNote($currentId, $currentNode,  $note);
		}
		$this->processNote($currentId, $currentNode, $this->noteRootId . ":node:endName=");
	}

        public function addGroup($label, $type, $innerNodesId, $key = null) {
                if ( count($innerNodesId) == 1)  {
                        echo "Error: attempting to create a singleton".PHP_EOL; 
                        exit();  
                }
                if (isset($key)) {
                    $groupId = $this->addNode($type, $key);
                    //echo "Added node of group $groupId using key" . PHP_EOL; 
                } else {
                    $groupId = $this->addNode($type); 
                    //echo "Added node of group $groupId." . PHP_EOL; 
                }
		$this->nodes[$groupId]->attributes['label'] = $label;
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
        
	private function processNote(&$currentId, &$currentNode, $note) {
                // This methods does not do much checks. It only fails
                // when we try to stop the root in stopNodesUpUntil...
                // If it a startName, it moves forward to noteNb from
                // whatever is the current node. Otherwise, it search
                // for the nodeId moving backard, as needed, but normally
                // not needed. If it is an endName with the correct currentId,
                // it stops the node and move backward to the parent.
                // It fails when we try to stop the root in stopNodesUpUntil...
                // The stop at the end happens on root only after the loop
                // which is fine, because there is no more note. 
          
		//echo "Note: ".trim($note).PHP_EOL;
                [$noteNb, $key,  $value] = $this->readNote($note);

		if ($key == "startName") {
                        $nodeId = $this->createTreeNode($currentId, $noteNb, $value);
                        $this->moveCurrentNodeForward($currentId, $currentNode, $nodeId); 
		} else {
                        $nodeId = self::getNodeId($this->treeType, $noteNb);
                        $this->stopNodesUpUntilMatchNoteNodeId($currentId, $currentNode, $nodeId); 
			$currentNode->attributes[$key] = $value;
			//echo "Set group:$key = $value".PHP_EOL;
			if ($key === "endName") {
                            // Todo: Must check $value !== "parent". It is reserved.
                            $this->stopNote($currentId, $currentNode, $value);
			}
		}
	}
        
        private function readNote($note) : array {
		$noteArr = explode("=", $note);
		$nodeNbAndKey = trim($noteArr[0]);
		$keyArr = explode(":", $nodeNbAndKey, 3);
		$noteNb = (int) $keyArr[0];
                // $keyArr[1] is unused 
		$key = $keyArr[2];
		$value = trim($noteArr[1]); 
                return [$noteNb, $key,  $value]; 
        }
        
        private function createTreeNode ($currentId, $noteNb, $startName) : string {

                // Set the new node (target of new arrow) 
                $nodeId = $this->addNode($this->treeType, $noteNb); 
		$this->nodes[$nodeId]->attributes['parentId'] = $currentId;
		$this->nodes[$nodeId]->attributes['startName'] = $startName;                                                
                // Set the new arrow
		$newArrow = new Arrow($currentId, $nodeId);                   
		$this->arrowsOut[$currentId][$nodeId] = $newArrow;
		$this->arrowsIn[$nodeId][$currentId] = $newArrow;
                return $nodeId; 
        }

        private function addNode($type, int|null $nodeNb = null) : string {
             
		$nodeId = self::getNodeId($type, $nodeNb);
                $this->nodes[$nodeId] = new Node($type);
		$this->nodes[$nodeId]->attributes['nodeId'] = $nodeId;
                //echo "Added node $nodeId with label $label".PHP_EOL; 
                return $nodeId; 
        }

        private static function getNodeId($prefix, int|null $nb) {
		static $n = [];
		$n[$prefix] ??= 1;
                $nb ??= $n[$prefix]++; 
                $nodeId =  $prefix . str_pad($nb, 5, '0', STR_PAD_LEFT);
		return $nodeId; 
	}

        private function moveCurrentNodeForward(&$currentId, &$currentNode, $nodeId) {
		$currentNode = $this->nodes[$nodeId];
		//echo "New currentId $nodeId with parentId $currentId.".PHP_EOL;
		$currentId = $nodeId;
        }
        
        private function stopNodesUpUntilMatchNoteNodeId (&$currentId, &$currentNode, $nodeId) {
                // Normally $currentId === $nodeId in input and the method does nothing.
                // Otherwise, it only stops notes and move up until $currentId === $nodeId
                // or exit on error when $currentNode->attributes['parentId'] === null (root). 
                // Two outcomes: exit on error or $currentId === $nodeId. 
                // In all cases, it does nothing on the final currentId node.
            
                // If $nodeId !== $rootId, it is an ordinary situation, which can
                // lead to an exit on error.  
            
                // If $nodeId == $rootId, it can only be the artificial note 
                // $this->noteRootId . ":node:endName=" after the loop. 
                // In that case, it does not exit on error, because the
                // the while loop stops with $currentId === $rootId before the
                // error can occur. 

                // The second case stops the root, but is fine, because there is no
                // note after the loop. 
                // Stopping the root in other cases might be useful for
                // debugging info, but we don't do that now. Therefore, 
                // the loop  stops with error before calling stopNote on
                // the root, i.e., when parentId is null. 
            
 		while ($currentId !== $nodeId) {
                        // No file note should have the root noteNb.  
			echo "Stopping node $currentId by its parent "; 
			$currentNode->attributes['endName'] = 'parent'; 
                        if ( $currentNode->attributes['parentId'] === null ) {
                            echo "Exiting before stopping the root while searching node $nodeId". PHP_EOL;
                            exit(); 
                        }
                        $this->stopNote($currentId, $currentNode, 'parent'); 
			echo "$currentId, which is now the new currentId.".PHP_EOL;
		}          
        }

	private function stopNote(&$currentId, &$currentNode, $endName) {
                // It only sets the label and the exclusive time and move
                // currentId and currentNode backward to their parent values.
                // StopNote is the last step before the next note, which is normally for the parent. .
                $currentNode->attributes['label'] = $currentNode->attributes['startName'] . "_". $endName;
                $this->setExclusiveTimeOfNode($currentId); 
		$currentId = $currentNode->attributes['parentId'];
		if ($currentId !== null) {
			$currentNode = $this->nodes[$currentId];
			//echo "Moving to parent $currentId after endName $endName".PHP_EOL;
		} elseif ($endName === 'parent')  {
                        echo "Error: trying to stop root with endName=parent.". PHP_EOL;
                        exit(); 
		} else {
                        // echo "Stopping the root normally"; 
                }
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