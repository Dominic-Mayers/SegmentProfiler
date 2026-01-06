<?php

namespace App;

// Must be executed on the original tree, not sure why.    
class VisitorCTDwe extends AbstractVisitorT {
    
        private array $groups;
        private ?string $currentGroupKey;

        public function __construct ( private $groupsWithNoInnerNodes = null) {            
        }
        
        public function init() {
            $this->groups = []; 
            $this->currentGroupKey = null; 
        }
        
	public function beforeChildren($currentId) {
            if ($this->totalGraph->nodes[$currentId]->type === 'Twe' || 
                $this->totalGraph->nodes[$currentId]->type === 'CTwe') {
                if ($this->currentGroupKey === null) {
                    $this->groups[$currentId] = [];
                    $this->currentGroupKey = $currentId; 
                }
                $this->groups[$this->currentGroupKey][] = $currentId;
            }
	}
        
	public function afterChildren($currentId) {
            if ( isset($this->currentGroupKey) && $this->currentGroupKey === $currentId  ) {
                $this->currentGroupKey = null; 
            }
	}

	public function finalize () {
            foreach( $this->groups as $group) {
                if (count($group) > 1 ) {
                    $treeWithEmptyKey = $this->totalGraph->nodes[$group[0]]->attributes['treeWithEmptyKey'];
                    $treeWithEmptyLabel = $this->totalGraph->treeLabels[$treeWithEmptyKey];
                    $innerLabel = explode('.', $treeWithEmptyLabel)[0]; 
                    $groupId = $this->totalGraph->addGroup($innerLabel, 'CTDwe', $group, $treeWithEmptyKey);
                    //echo "Added group $groupId with inner nodes ". json_encode($group) . PHP_EOL ;
                    $this->totalGraph->createGroup($groupId);
                    if ( ! empty($this->groupsWithNoInnerNodes['CTDwe']) ) {
                        // Very unlikely, but to cover all cases ...
                        $this->totalGraph->removeInnerNodes($groupId);
                    }
                    //echo "Added group $groupId". PHP_EOL;                     
                }
            }
        }
}