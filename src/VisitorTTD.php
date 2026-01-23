<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorTTD extends AbstractVisitorT {
    
        private array $groups;
        private $currentGroupKey;
        
        public function init() {
            $this->groups = []; 
            $this->currentGroupKey = null; 
        }
        
	public function beforeChildrenDefinition($currentId) {
                if (!empty($this->totalGraph->nodes[$currentId]->attributes['TK']) && $this->currentGroupKey === null) {
                        $this->currentGroupKey = $currentId; 
                        $this->groups[$this->currentGroupKey] = [];
                }
                if ( $this->currentGroupKey !== null) {
                    $this->groups[$this->currentGroupKey][] = $currentId;
                }
	}
        
	public function afterChildrenProcess($currentId) {
            if ( isset($this->currentGroupKey) && $this->currentGroupKey === $currentId  ) {
                $this->currentGroupKey = null; 
            }
	}

	public function finalize () {
            foreach( $this->groups as $group) {
                if (count($group) > 1 ) {
                    $treeKey = $this->totalGraph->nodes[$group[0]]->attributes['treeKey'];
                    $treeLabel = $this->totalGraph->treeLabels['treeKey'][$treeKey];
                    $innerLabel = explode('.', $treeLabel)[0]; 
                    $groupRep = $this->totalGraph->nodes[$group[0]]; 
                                              
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'TTD', $group, $groupRep);
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['TTD']) ) {
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