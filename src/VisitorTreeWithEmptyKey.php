<?php

namespace App;

class VisitorTreeWithEmptyKey extends AbstractVisitorT {


        public function init() {
            $this->totalGraph->treeLabelsTranspose['treeKeyWithEmpty'] = ['' => 0];
            $this->totalGraph->treeLabels['treeKeyWithEmpty'] = [0 => '']; 
        }
        
        public function afterChildrenProcess($currentId) {
            $this->setNewTree($currentId, 'treeKeyWithEmpty');
        }
}