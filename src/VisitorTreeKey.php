<?php

namespace App;

class VisitorTreeKey extends AbstractVisitorT {


        public function init() {
            $this->treePhase->treeLabelsTranspose['treeKey'] = [];
            $this->treePhase->treeLabels['treeKey'] = []; 
        }
        
        public function afterChildrenProcess($currentId) {
            $this->setNewTree($currentId, 'treeKey');
        }
}