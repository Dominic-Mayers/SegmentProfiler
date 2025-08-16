<?php

namespace App;

class VisitorP extends VisitorPAbstract {
    
        private array $groups;
        
	public function init() {
                $this->paths = [];
                $this->pathsTranspose = [];
		$this->groups = [];
	}

	public function afterChildren($currentId) {

            [$key, $pathLabel] = $this->setNewKeyPath($currentId);

            $this->groups[$pathLabel] ??= []; 
            $this->groups[$pathLabel][] = $currentId;
           
            //echo "Added $currentId in group $pathLabel with key $key.". PHP_EOL; 
	}

	public function finalize () {
            foreach ($this->paths as $key => $pathLabel) {
                $group = $this->groups[$pathLabel]; 
                if (count($group) > 1 ) {
                    $label = explode("\0", $pathLabel)[0]; 
                    $groupId = $this->totalGraph->addGroup($label, "P", $group, $key);
                    $this->totalGraph->createGroup($groupId); 
                    $this->totalGraph->removeInnerNodes($groupId);
                    //echo "Added group $groupId". PHP_EOL;                     
                }
            }
        }
}