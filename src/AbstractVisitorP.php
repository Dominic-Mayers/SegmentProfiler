<?php

namespace App;

abstract class AbstractVisitorP extends AbstractVisitor {
    
        public array $paths = []; 
        public array $pathsTranspose = []; 
        
        protected function setNewPath($currentId) {

            $adjOut = $this->totalGraph->arrowsOut[$currentId] ?? [];
            $path = $this->totalGraph->nodes[$currentId]->attributes["label"]; 
            foreach ( $adjOut as $childId => $arrow) {
                $path .= "." . $this->totalGraph->nodes[$childId]->attributes["pathKey"];
            }
            // echo "Set pathLabel of $currentId to $pathLabel" . PHP_EOL;
            
            if ( ! isset($this->pathsTranspose[$path] ) ) { 
                $this->paths[] = $path;
                $key = array_key_last($this->paths);
                $arrayPath =  explode(".", $path);
                $this->totalGraph->arrayPaths[$key] = $arrayPath;
                $this->pathsTranspose[$path] = $key;
                //echo "Added new $key => $pathLabel in paths.". PHP_EOL; 
            } else {
                $key = $this->pathsTranspose[$path]; 
            }
            $this->totalGraph->nodes[$currentId]->attributes["pathKey"] = $key;
            return [$key, $path] ; 
        }    
}