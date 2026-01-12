<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorTwe extends AbstractVisitorT {
    
        private array $groups;

        public function init() {
            if (isset ($this->groups)) {
                echo "Error: VisitorTwe is not reentrant";
                exit(); 
            }
            $this->groups = [];
        }
        
	public function afterChildren($currentId) {

            $treeKeyWithEmpty = $this->getTreeKeyWithEmpty($currentId); 
            $treeLabelWithEmpty = $this->totalGraph->treeLabelsWithEmpty[$treeKeyWithEmpty]; 
            $this->groups[$treeLabelWithEmpty][] = $currentId;
           
            //echo "Added $currentId in group $treeLabel.". PHP_EOL; 
	}

	public function finalize () {
            foreach ($this->groups as $treeLabelWithEmpty => $group) {
                if (count($group) > 1 ) {
                    $innerLabel = explode(".", $treeLabelWithEmpty)[0]; 
                    $treeKeyWithEmpty = $this->totalGraph->treeLabelsTransposeWithEmpty[$treeLabelWithEmpty];
                    $groupRep = $this->totalGraph->nodes[$group[0]]; 
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'Twe', $group, $groupRep);
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['Twe']) ) {
                        $this->totalGraph->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId". PHP_EOL;                     
                }
            }
            //$this->totalGraph->removeNode('Twe1');
            unset($this->groups); 
        }
}