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
        // This is used to create 0, 1 or more groups for each innerLabel, by resetting after each.
        // It is a bit similar to $innerLabelGroups in SL, but more complicated.
        private array $innerLabelGroups;
        private array $hasNotSingleLabel;

        private function createGroups() {

		foreach ($this->groups as $groupId) {
                        $group = $this->totalGraph->nodes[$groupId]; 
			$this->totalGraph->createGroup($groupId);
                        if ($group->type === "DL") {
                            $this->totalGraph->removeInnerNodes($groupId); 
                        }
		}
	}
        
	public function init() {
		$this->innerLabelGroups = [];
		$this->groups = [];
                $this->hasNotSingleLabel = [];
                //echo "Starting DN".PHP_EOL."-----------".PHP_EOL;
	}

	public function beforeChildren($currentId) {
		$currentNode = $this->totalGraph->nodes[$currentId];
		$innerLabel = $currentNode->attributes['innerLabel'];
		$this->innerLabelGroups[$innerLabel][] = $currentId;
	}

	public function afterChildren($currentId) {
		$currentNode = $this->totalGraph->nodes[$currentId];
		$innerLabel = $currentNode->attributes['innerLabel'];
		if (!isset($this->innerLabelGroups[$innerLabel])) {
			return;
		}

		$firstInnerNodeId = $this->innerLabelGroups[$innerLabel][0];
		if ($firstInnerNodeId === $currentId) {
			if (count($this->innerLabelGroups[$innerLabel]) > 1) {
                                $type = isset($this->hasNotSingleLabel[$innerLabel]) ? "DLX" : "DL"; 
 				$this->groups[] = $groupId = $this->totalGraph->addGroup($innerLabel, $type, $this->innerLabelGroups[$innerLabel]); 
                        }
			unset($this->innerLabelGroups[$innerLabel]);
                        unset($this->hasNotSingleLabel[$innerLabel]);
		}
	}

        public function finalize() {
            $this->createGroups();
        }

}