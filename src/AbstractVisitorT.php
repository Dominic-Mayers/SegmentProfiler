<?php

namespace App;

abstract class AbstractVisitorT extends AbstractVisitor {

        protected function getTreeKey ($nodeId) : int {
            if ( ! \array_key_exists('treeKey', $this->totalGraph->nodes[$nodeId]->attributes)) { 
                if ($this->totalGraph->nodes[$nodeId]->type === 'CL' ) {
                    echo "Type CL does not have a treeKey.".PHP_EOL; 
                    exit();
                } else {
                    echo "unrecognized error" . PHP_EOL;
                    exit(); 
                }
            }
            return $this->totalGraph->nodes[$nodeId]->attributes['treeKey'];
        }
    
        protected function getTreeWithEmptyKey ($nodeId) : int|string {
            if ( ! \array_key_exists('treeWithEmptyKey', $this->totalGraph->nodes[$nodeId]->attributes)) { 
                if ($this->totalGraph->nodes[$nodeId]->type === 'CL' ) {
                    echo "Type CL does not have a treeKey.".PHP_EOL; 
                    exit();
                } else {
                    echo "unrecognized error" . PHP_EOL;
                    exit(); 
                }
            }
            return $this->totalGraph->nodes[$nodeId]->attributes['treeWithEmptyKey'];
        }
    
        protected function setNewTree($currentId) {

            // It needs to be the adjacent arrows for traversal, because the
            // traversal method might not have set the treeKey attribute for
            // the others. It may not be what we expect, but I cannot think
            // of a solution.
          
            $adj = $this->totalGraph->adjActiveTraversalArrowsOut($currentId);
                        
            $treeLabel = $this->totalGraph->nodes[$currentId]->attributes["innerLabel"]; 
            //echo "set innerLabel ". $this->totalGraph->nodes[$currentId]->attributes["innerLabel"] . " of new treeLabel." . PHP_EOL;
            foreach ( $adj as $childId => $arrow) {
                $treeLabel .= "." . $this->totalGraph->nodes[$childId]->attributes["treeKey"];
                //echo "Append key ".  $this->totalGraph->nodes[$childId]->attributes["treeKey"] . PHP_EOL;
            }
            //echo "Set treeLabel of $currentId to $treeLabel" . PHP_EOL;
            
            if ( ! isset($this->totalGraph->treeLabelsTranspose[$treeLabel] ) ) { 
                $this->totalGraph->treeLabels[] = $treeLabel;
                $treeKey = array_key_last($this->totalGraph->treeLabels);
                $this->totalGraph->treeLabelsTranspose[$treeLabel] = $treeKey;
                //echo "Added new $treeKey => $treeLabel in treeLabels.". PHP_EOL; 
            } else {
                $treeKey = $this->totalGraph->treeLabelsTranspose[$treeLabel]; 
            }
            $this->totalGraph->nodes[$currentId]->attributes["treeKey"] = $treeKey;
            return [$treeKey, $treeLabel] ; 
        }

        protected function setNewTreeWithEmpty($currentId) {
            // This is extreme bceause it makes the possibly empty key of all leafs actually empty. 
            $adj = $this->totalGraph->adjActiveTraversalArrowsOut($currentId);
            
            if (empty ($adj)) {
                $treeWithEmptyLabel = "e";
                $this->totalGraph->treeWithEmptyLabels['e'] = 'e';
                $this->totalGraph->treeWithEmptyLabelsTranspose['e'] = 'e'; 
                $this->totalGraph->nodes[$currentId]->attributes["treeWithEmptyKey"] = 'e';
                return ['e', 'e'] ; 
            } else {
                $treeWithEmptyLabel = $this->totalGraph->nodes[$currentId]->attributes["innerLabel"]; 
                //echo "set innerLabel ". $this->totalGraph->nodes[$currentId]->attributes["innerLabel"] . " of new treeLabel." . PHP_EOL;
                $prevKeyIsEmpty = false; 
                foreach ( $adj as $childId => $arrow) {
                    $childKey  = $this->totalGraph->nodes[$childId]->attributes["treeWithEmptyKey"]; 
                    $toConcat = $prevKeyIsEmpty && $childKey === 'e' ? '' : ".$childKey";   
                    $treeWithEmptyLabel .= $toConcat;  
                    //echo "Append key ".  $this->totalGraph->nodes[$childId]->attributes["treeKey"] . PHP_EOL;
                }
            }
            //echo "Set treeLabel of $currentId to $treeLabel" . PHP_EOL;
            
            if ( ! isset($this->totalGraph->treeWithEmptyLabelsTranspose[$treeWithEmptyLabel] ) ) { 
                $this->totalGraph->treeWithEmptyLabels[] = $treeWithEmptyLabel;
                $treeWithEmptyKey = array_key_last($this->totalGraph->treeWithEmptyLabels);
                $this->totalGraph->treeWithEmptyLabelsTranspose[$treeWithEmptyLabel] = $treeWithEmptyKey;
                //echo "Added new $treeKey => $treeLabel in treeLabels.". PHP_EOL; 
            } else {
                $treeWithEmptyKey = $this->totalGraph->treeWithEmptyLabelsTranspose[$treeWithEmptyLabel]; 
            }
            $this->totalGraph->nodes[$currentId]->attributes["treeWithEmptyKey"] = $treeWithEmptyKey;
            return [$treeWithEmptyKey, $treeWithEmptyLabel] ; 
        }
}