<?php

namespace App;

class VisitorP extends AbstractVisitorP {
    
        private array $groups;
        
	public function afterChildren($currentId) {

            [ , $path] = $this->setNewPath($currentId);
            $this->groups[$path][] = $currentId;
           
            //echo "Added $currentId in group $path.". PHP_EOL; 
	}

	public function finalize () {
            foreach ($this->groups as $path => $group) {
                if (count($group) > 1 ) {
                    $label = explode(".", $path)[0]; 
                    $key = $this->pathsTranspose[$path];
                    $groupId = $this->totalGraph->addGroup($label, "P", $group, $key);
                    $this->totalGraph->createGroup($groupId); 
                    $this->totalGraph->removeInnerNodes($groupId);
                    //echo "Added group $groupId". PHP_EOL;                     
                }
            }
        }
}