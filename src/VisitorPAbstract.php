<?php

namespace App;

abstract class VisitorPAbstract extends Visitor {
    
        public array $paths = []; 
        public array $pathsTranspose = []; 
        
        protected function setNewKeyPath($currentId) {

            $adjOut = $this->totalGraph->arrowsOut[$currentId] ?? [];
            $pathLabel = $this->totalGraph->nodes[$currentId]->attributes["label"]; 
            foreach ( $adjOut as $childId => $arrow) {
                $pathLabel .= "\0" . $this->totalGraph->nodes[$childId]->attributes["pathKey"];
            }
            // echo "Set pathLabel of $currentId to $pathLabel" . PHP_EOL;
            
            if ( ! isset($this->pathsTranspose[$pathLabel] ) ) { 
                $this->paths[] = $pathLabel;
                $key = array_key_last($this->paths);
                $this->totalGraph->arrayPaths[$key] = explode("\0", $pathLabel);
                $this->pathsTranspose[$pathLabel] = $key;
                //echo "Added new $key => $pathLabel in paths.". PHP_EOL; 
            } else {
                $key = $this->pathsTranspose[$pathLabel]; 
            }
            $this->totalGraph->nodes[$currentId]->attributes["pathKey"] = $key;
            return [$key, $pathLabel] ; 
        }    
}