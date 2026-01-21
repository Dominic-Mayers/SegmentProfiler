<?php

namespace App;

abstract class AbstractVisitorT extends AbstractVisitor {

        protected function getTreeKey ($nodeId, $keyType) : int {
            if ( ! \array_key_exists($keyType, $this->totalGraph->nodes[$nodeId]->attributes)) { 
                if ($this->totalGraph->nodes[$nodeId]->type === 'CL' ) {
                    echo "Type CL does not have a $keyType.".PHP_EOL; 
                    exit();
                } else {
                    echo "unrecognized error" . PHP_EOL;
                    exit(); 
                }
            }
            return $this->totalGraph->nodes[$nodeId]->attributes[$keyType];
        }
        
        private function setNewTree_($currentId, $treeKeyType) {
            
            //$this->totalGraph->treeLabelsTranspose[$treeKeyType] = [];
            $adj = $this->getChildrenArrowsOut($currentId);            
            $treeLabel = $this->totalGraph->nodes[$currentId]->attributes["innerLabel"]; 
            //echo "set innerLabel ". $this->totalGraph->nodes[$currentId]->attributes["innerLabel"] . " of new treeLabel." . PHP_EOL;
            foreach ( $adj as $childId => $notused) {
                $treeLabel .= "." . $this->totalGraph->nodes[$childId]->attributes[$treeKeyType];
                //echo "Append key ".  $this->totalGraph->nodes[$childId]->attributes[$treeKeyType] . PHP_EOL;
            }
            //echo "Set treeLabel of $currentId to $treeLabel" . PHP_EOL;
            
            if ( ! isset($this->totalGraph->treeLabelsTranspose[$treeKeyType][$treeLabel] ) ) {
                $this->totalGraph->treeLabels[$treeKeyType][] = $treeLabel;
                $treeKey = \array_key_last($this->totalGraph->treeLabels[$treeKeyType]); 
                $this->totalGraph->treeLabelsTranspose[$treeKeyType][$treeLabel] = $treeKey;
                //echo "Added new $treeKey => $treeLabel in treeLabels.". PHP_EOL; 
            } else {
                $treeKey = $this->totalGraph->treeLabelsTranspose[$treeKeyType][$treeLabel]; 
            }
            $this->totalGraph->nodes[$currentId]->attributes[$treeKeyType] = $treeKey;
            return [$treeKey, $treeLabel] ; 
        }

        protected function setNewTree($currentId, $treeKeyType = 'treeKey') {
            $adj = $this->getChildrenArrowsOut($currentId);
            if ($adj === [] && $treeKeyType === 'treeKeyWithEmpty') {
                $this->totalGraph->nodes[$currentId]->attributes['treeKeyWithEmpty'] = 0;
                return [0, ''] ;             
            } 
            return $this->setNewTree_($currentId, $treeKeyType);
        }
}