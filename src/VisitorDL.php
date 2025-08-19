<?php

namespace App;

class VisitorDL extends AbstractVisitor {
        // The methods beforeChildren and afterChildren only create the group
        // using addGroup: This includes setting the innerNodesId and groupId
        // of these inner nodes. It does not create any arrow.
        // This process is done in finalize

        // This is the array of groupId that have been identified and remains
        // to be created with addGroup (without the arrows).  Not sure why
        // I do not create (fully) the group as soon as identified. 
        private array $groups;
        // This is used to create 0, 1 or more groups for each label, by resetting after each.
        // It is a bit similar to $labelGroups in SL, but more complicated.
        private array $labelGroups;
        private array $hasNotSingleLabel;

        private function createGroups() {

		foreach ($this->groups as $groupId) {
                        $group = $this->totalGraph->nodes[$groupId]; 
                        if (count($group->innerNodesId) === 1  ) {continue;} 
			$this->totalGraph->createGroup($groupId);
                        if ($group->type === "DL") {
                            $this->totalGraph->removeInnerNodes($groupId); 
                        }
		}
	}
        
	public function init() {
		$this->labelGroups = [];
		$this->groups = [];
                $this->hasNotSingleLabel = [];
                //echo "Starting DN".PHP_EOL."-----------".PHP_EOL;
	}

	public function beforeChildren($currentId) {
		$grps0 =& $this->labelGroups;
		$currentNode = $this->totalGraph->nodes[$currentId];
		$label = $currentNode->attributes['label'];
		$grps0[$label][] = $currentId;
                if ( ! $this->hasSingleLabel($currentId) )
                {
                    $this->hasNotSingleLabel[$label] = true;     
                }
	}

	public function afterChildren($currentId) {
		$grps0 = & $this->labelGroups;
		$grps1 = & $this->groups;
		$currentNode = $this->totalGraph->nodes[$currentId];
		$label = $currentNode->attributes['label'];
		if (!isset($grps0[$label])) {
			return;
		}

		$firstInnerNodeId = $grps0[$label][0];
		if ($firstInnerNodeId === $currentId) {
			if (isset($grps0[$label][1])) {
                                $type = isset($this->hasNotSingleLabel[$label]) ? "DLX" : "DL"; 
 				$grps1[] = $groupId = $this->totalGraph->addGroup($label, $type, $grps0[$label]); 
                                $a = 1;
                        }
			unset($grps0[$label]);
                        unset($this->hasNotSingleLabel[$label]);
		}
	}

        public function finalize() {
            $this->createGroups();
        }

}