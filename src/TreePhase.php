<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

namespace App;

use App\BaseState; 

class TreePhase {
        public string   $treeType = "S"; // S for segment. 
        public string   $rootId; // Needed in Traversal to initiate toProcess
        // For each type of treeKey, gives the treeLabel for each treeKey. 
        // Set in AbstractVisitorT::setNewTree.
        public array    $treeLabels; 
        public array    $treeLabelsTranspose; 

        private int     $rootNb = 0; // The notes start at 1. 
        
        public function __construct(private BaseState $baseState ) {
            $this->rootId = BaseState::getNodeId($this->treeType, $this->rootNb);
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
                        $nodeId = BaseState::getNodeId($this->treeType, $noteNb);
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
                $nodeId = $this->baseState->addNode($this->treeType, $noteNb); 
		$this->baseState->nodes[$nodeId]['attributes']['parentId'] = $currentId;
		$this->baseState->nodes[$nodeId]['attributes']['startName'] = $startName;
                // Set the new incoming arrow toward the node, even when the node is the root
                // The one toward the root will not be included in the active graph. It will
                // not be visited in traversal, only seen if we look for that incoming arrow.
                $newArrow = $this->baseState->createArrow($currentId, $nodeId);                   
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