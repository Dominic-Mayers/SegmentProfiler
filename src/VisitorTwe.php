<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorTwe extends AbstractVisitorT {
    
        private array $groups;

        public function __construct ( private $groupsWithNoInnerNodes = null) {            
        }
        
	public function afterChildren($currentId) {

            $treeWithEmptyKey = $this->getTreeWithEmptyKey($currentId); 
            $treeWithEmptyLabel = $this->totalGraph->treeWithEmptyLabels[$treeWithEmptyKey]; 
            $this->groups[$treeWithEmptyLabel][] = $currentId;
           
            //echo "Added $currentId in group $treeLabel.". PHP_EOL; 
	}

	public function finalize () {
            foreach ($this->groups as $treeWithEmptyLabel => $group) {
                if (count($group) > 1 ) {
                    $innerLabel = explode(".", $treeWithEmptyLabel)[0]; 
                    $treeWithEmptyKey = $this->totalGraph->treeWithEmptyLabelsTranspose[$treeWithEmptyLabel];
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'T', $group, $treeWithEmptyKey);
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['Twe']) ) {
                        $this->totalGraph->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId". PHP_EOL;                     
                }
            }
        }
}