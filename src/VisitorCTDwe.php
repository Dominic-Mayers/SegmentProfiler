<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorCTDwe extends AbstractVisitorT {
    
        private array $groups;
        private bool|string $currentGroupKey;
        
        public function init() {
            if ( isset($this->groups) || isset($this->currentGroupKey) ) {
                echo "Error: VisitorCTDwe is not reentrant";
                exit();                 
            }
            $this->groups = []; 
            $this->currentGroupKey = false; 
        }
        
	public function beforeChildrenDefinition($currentId) {
                if ($this->totalGraph->nodes[$currentId]->type === 'Twe' || 
                    $this->totalGraph->nodes[$currentId]->type === 'CTwe') {
                    if ($this->currentGroupKey === false) {
                        $this->groups[$currentId] = [];
                        $this->currentGroupKey = $currentId; 
                    }
                    $this->groups[$this->currentGroupKey][] = $currentId;
                }
	}
        
	public function afterChildrenProcess($currentId) {
            if ( isset($this->currentGroupKey) && $this->currentGroupKey === $currentId  ) {
                $this->currentGroupKey = false; 
            }
	}

	public function finalize () {
            foreach( $this->groups as $group) {
                if (count($group) > 1 ) {
                    $treeKeyWithEmpty = $this->totalGraph->nodes[$group[0]]->attributes['treeKeyWithEmpty'];
                    $treeLabelWithEmpty = $this->totalGraph->treeLabels[$treeKeyWithEmpty];
                    $innerLabel = explode('.', $treeWithEmptyLabel)[0]; 
                    $groupRep = $this->totalGraph->nodes[$group[0]]; 
                          
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'CTDwe', $group, $groupRep);
                    //echo "Added group $groupId with inner nodes ". json_encode($group) . PHP_EOL ;
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['CTDwe']) ) {
                        // Very unlikely, but to cover all cases ...
                        $this->totalGraph->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId". PHP_EOL; 
                }
            }
            unset($this->groups);
            unset($this->currentGroupKey); 
        }
}