<?php 

function createRootNode($nodeLabels, $child_range, $level, $sizePerLevel) {
    $subforest = generateForest ($nodeLabels, $child_range, $level-1, $sizePerLevel);
    return createRootOverSubforest($nodeLabels, $child_range, $level, $subforest);
}

function createRootOverSubforest($nodeLabels, $child_range, $level, $subforest) {
    static $nodeId = 0;
    if ($level < 1) {
        fwrite(STDERR, "Error: tree levels start at 1.". PHP_EOL); 
    }
    $labels = $nodeLabels;
    if (empty($labels)) { fwrite(STDERR, "Error: At the least one label is needed." . PHP_EOL); exit();  } 
    $nodeId++; 
    $node['label'] = array_shift($labels);
    if ($node['label'] === '__K__') {
        if ($level === 1) {
            // Normally, there should be no '__K__' at level 1. 
            // We just manage '__K__' as a leaf with label 'K'.
            $node['label'] = 'K';
            $node['children'] = [];
            $node['id'] = 'S' . $nodeId; 
        } else {
            $k = mt_rand(0, \count($subforest) -1);
            $node = $subforest[$k];                
        }
        return $node;
    } 
    $node['id'] = 'S' . $nodeId; 
    $childLabelsList = [];
    foreach ($labels as $label) {
        $t = mt_rand($child_range[0], $child_range[1] );
        $childLabelsList[$t][] = $label;  
    }
    $node['children'] = []; 
    foreach ($childLabelsList as $childLabels) {
        if (empty($childLabels)) {continue;}  
        $node['children'][] =  createRootOverSubforest($childLabels, $child_range, $level, $subforest); 
    }
    return $node; 
}

function generateForest ($nodeLabels, $child_range, $level, $sizePerLevel) {
    // Level n forest  = a provided level n-1 subforest + $sizePerLevel level n trees computed above that subforest. 
    // Level 0 forest  = empty array. 
    // So we need the code for a level n trees computed above a n-1 subforest. 
    // The case $n = 1 (level 1 tree and forest) must be managed separately. 
    
    if ($level === 0) {return [];}
    $forest = $subforest = generateForest ($nodeLabels, $child_range, $level-1, $sizePerLevel); 
    for ($i=0; $i < $sizePerLevel; $i++) {
        $forest[] = createRootOverSubforest($nodeLabels, $child_range, $level, $subforest); 
    }
    return $forest;     
}

function generateLabels ($rootLabel, $possibleChildLabels, $size) {
    $labels= [$rootLabel];
    for ($i=0; $i < $size; $i++) {
        $l = mt_rand(0, \count($possibleChildLabels)-1); 
        $labels[] = $possibleChildLabels[$l]; 
    }
    return $labels; 
}

function createProfile($node) {
    static $id= 0;
    static $fakeTime=0;
    $curId = $id++; 
    $curTime=$fakeTime;
    if ($curId!==0) { 
        $fakeTime += mt_rand(1,10000); 
        echo "$curId:startName={$node['label']}".PHP_EOL;
    }
    foreach ($node['children'] as $child) {
        createProfile($child); 
    }
    if ($curId!==0) { 
        $timeFct = $fakeTime-$curTime; 
        echo "$curId:timeFct=$timeFct". PHP_EOL; 
        echo "$curId:endName=none". PHP_EOL;
    }
}

$labels = generateLabels('R', ['A', 'B', 'C', '__K__'], 10);
$node = createRootNode($labels, [0,3], 3, 3); 
createProfile($node); 