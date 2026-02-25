<?php

namespace App;

abstract class AbstractVisitorT extends AbstractVisitor {

        protected function getTreeKey ($nodeId, $keyType) : int {
            if ( !\array_key_exists( $keyType, $this->baseState->nodes[$nodeId]['attributes'])) { 
                if ($this->baseState->nodes[$nodeId]['type'] === 'CL' ) {
                    echo "Type CL does not have a $keyType.".PHP_EOL; 
                    exit();
                } else {
                    echo "unrecognized error" . PHP_EOL;
                    exit(); 
                }
            }
            return $this->baseState->nodes[$nodeId]['attributes'][$keyType];
        }
        
        private function setNewTree_($currentId, $treeKeyType) {
            
            //$this->treePhase->treeLabelsTranspose[$treeKeyType] = [];
            $adj = $this->getChildrenArrowsOut($currentId);            
            $treeLabel = $this->baseState->nodes[$currentId]['attributes']['innerLabel']; 
            //echo "set innerLabel ". $this->baseState->nodes[$currentId]['attributes']['innerLabel']] . " of new treeLabel." . PHP_EOL;
            foreach ( $adj as $childId => $notused) {
                $treeLabel .= "." . $this->baseState->nodes[$childId]['attributes'][$treeKeyType];
                //echo "Append key ".  $this->baseState->nodes[$childId]['attributes'][$treeKeyType] . PHP_EOL;
            }
            //echo "Set treeLabel of $currentId to $treeLabel" . PHP_EOL;
            
            if ( ! isset($this->treePhase->treeLabelsTranspose[$treeKeyType][$treeLabel] ) ) {
                $this->treePhase->treeLabels[$treeKeyType][] = $treeLabel;
                $treeKey = \array_key_last($this->treePhase->treeLabels[$treeKeyType]); 
                $this->treePhase->treeLabelsTranspose[$treeKeyType][$treeLabel] = $treeKey;
                //echo "Added new $treeKey => $treeLabel in treeLabels.". PHP_EOL; 
            } else {
                $treeKey = $this->treePhase->treeLabelsTranspose[$treeKeyType][$treeLabel]; 
            }
            $this->baseState->nodes[$currentId]['attributes'][$treeKeyType] = $treeKey;
            return [$treeKey, $treeLabel] ; 
        }

        protected function setNewTree($currentId, $treeKeyType = 'treeKey') {
            $adj = $this->getChildrenArrowsOut($currentId);
            if ($adj === [] && $treeKeyType === 'treeKeyWithEmpty') {
                $this->baseState->nodes[$currentId]['attributes']['treeKeyWithEmpty'] = 0;
                return [0, ''] ;             
            } 
            return $this->setNewTree_($currentId, $treeKeyType);
        }
}