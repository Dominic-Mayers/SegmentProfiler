<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorP extends AbstractVisitorP {
    
        private array $groups;
        
	public function afterChildren($currentId) {

            [ , $treeLabel] = $this->setNewTreeLabel($currentId);
            $this->groups[$treeLabel][] = $currentId;
           
            //echo "Added $currentId in group $treeLabel.". PHP_EOL; 
	}

	public function finalize () {
            foreach ($this->groups as $treeLabel => $group) {
                if (count($group) > 1 ) {
                    $innerLabel = explode(".", $treeLabel)[0]; 
                    $key = $this->treeLabelsTranspose[$treeLabel];
                    $groupId = $this->totalGraph->addGroup($innerLabel, "P", $group, $key);
                    $this->totalGraph->createGroup($groupId); 
                    $this->totalGraph->removeInnerNodes($groupId);
                    //echo "Added group $groupId". PHP_EOL;                     
                }
            }
        }
}