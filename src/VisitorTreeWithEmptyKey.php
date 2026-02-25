<?php

namespace App;

class VisitorTreeWithEmptyKey extends AbstractVisitorT {


        public function init() {
            $this->treePhase->treeLabelsTranspose['treeKeyWithEmpty'] = ['' => 0];
            $this->treePhase->treeLabels['treeKeyWithEmpty'] = [0 => '']; 
        }
        
        public function afterChildrenProcess($currentId) {
            $this->setNewTree($currentId, 'treeKeyWithEmpty');
        }
}