<?php

namespace App;

abstract class AbstractVisitorT extends AbstractVisitor {
    
        public array $treeLabels = []; 
        public array $treeLabelsTranspose = []; 
        
        protected function setNewTree($currentId) {

            $adjOut = $this->totalGraph->getNotInnerArrowsOut($currentId);
            
            $treeLabel = $this->totalGraph->nodes[$currentId]->attributes["innerLabel"]; 
            //echo "set innerLabel ". $this->totalGraph->nodes[$currentId]->attributes["innerLabel"] . " of new treeLabel." . PHP_EOL;
            foreach ( $adjOut as $childId => $arrow) {
                $treeLabel .= "." . $this->totalGraph->nodes[$childId]->attributes["treeKey"];
                //echo "Append key ".  $this->totalGraph->nodes[$childId]->attributes["treeKey"] . PHP_EOL;
            }
            //echo "Set treeLabel of $currentId to $treeLabel" . PHP_EOL;
            
            if ( ! isset($this->treeLabelsTranspose[$treeLabel] ) ) { 
                $this->treeLabels[] = $treeLabel;
                $key = array_key_last($this->treeLabels);
                $arrayTreeLabel =  explode(".", $treeLabel);
                $this->totalGraph->arrayTreeLabels[$key] = $arrayTreeLabel;
                $this->treeLabelsTranspose[$treeLabel] = $key;
                //echo "Added new $key => $treeLabel in treeLabels.". PHP_EOL; 
            } else {
                $key = $this->treeLabelsTranspose[$treeLabel]; 
            }
            $this->totalGraph->nodes[$currentId]->attributes["treeKey"] = $key;
            return [$key, $treeLabel] ; 
        }    
}