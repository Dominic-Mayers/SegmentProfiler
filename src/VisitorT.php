<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorT extends AbstractVisitorT {
    
        private array $groups;

        public function init() {
            if (isset ($this->groups)) {
                echo "Error: VisitorT is not reentrant";
                exit(); 
            }
            $this->groups = [];
        }

	public function afterChildrenProcess($currentId) {

            $treeKey = $this->getTreeKey($currentId, 'treeKey'); 
            $treeLabel = $this->totalGraph->treeLabels[$treeKey]; 
            $this->groups[$treeLabel][] = $currentId;
           
            //echo "Added $currentId in group $treeLabel.". PHP_EOL; 
	}

	public function finalize () {
            foreach ($this->groups as $treeLabel => $group) {
                if (count($group) > 1 ) {
                    $innerLabel = explode(".", $treeLabel)[0]; 
                    $groupRep = $this->totalGraph->nodes[$group[0]]; 
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'T', $group, $groupRep);
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['T']) ) {
                        $this->totalGraph->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId". PHP_EOL;                     
                }
            }
            unset($this->groups); 
        }
}