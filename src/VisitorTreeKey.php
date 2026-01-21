<?php

namespace App;

class VisitorTreeKey extends AbstractVisitorT {


        public function init() {
            $this->totalGraph->treeLabelsTranspose['treeKey'] = [];
            $this->totalGraph->treeLabels['treeKey'] = []; 
        }
        
        public function afterChildrenProcess($currentId) {
            $this->setNewTree($currentId, 'treeKey');
        }
}