<?php 

function createRootNode($labels, $topTreeSize,  $child_range, $level, $forestSizePerLevel) {
    $subforest = generateForest ($labels, $topTreeSize,  $child_range, $level-1, $forestSizePerLevel);
    fwrite(STDERR, "Subforest root keys: "); 
    foreach($subforest as $node) {fwrite(STDERR, $node['key'] . "|");} 
    fwrite(STDERR, PHP_EOL); 
    return createRootOverSubforest($labels, $topTreeSize, $child_range, $level, $subforest);
}

function createRootOverSubforest($labels, $topTreeSize, $child_range, $level, $subforest) {
    //static $nodeId = 0;
    if ($level < 1) {
        fwrite(STDERR, "Error: tree levels start at 1.". PHP_EOL); 
    }
    if (empty($labels)) { fwrite(STDERR, "Error: At the least one label is needed." . PHP_EOL); exit();  } 
    //$nodeId++; 
    $node['label'] = $labels[mt_rand(0, \count($labels)-1)];
    if ($node['label'] === '__K__') {
        if ($level === 1) {
            // Normally, there should be no '__K__' at level 1. 
            // We just manage '__K__' as a leaf with label 'K'.
            $node['label'] = 'K';
            $node['children'] = [];
            //$node['id'] = 'S' . $nodeId; 
        } else {
            $k = mt_rand(0, \count($subforest) -1);
            $node = $subforest[$k]; 
        }
        return $node;
    } 
    //$node['id'] = 'S' . $nodeId; 
    $node['children'] = [];
    $nbChildren = mt_rand($child_range[0], $child_range[1]); 
    if ($nbChildren === 0 ) {
        return $node;  
    } else {
        $num = starAndBar($nbChildren, $topTreeSize); 
        foreach ( $num as $childTopTreeSize) {
            if ($childTopTreeSize >0) {
                $node['children'][] =  createRootOverSubforest($labels, $childTopTreeSize, $child_range, $level, $subforest);
            }
        }
        return $node; 
    }
}

function generateForest ($labels, $topTreeSize,  $child_range, $level, $forestSizePerLevel) {
    // Level n forest  = a provided level n-1 subforest + $sizePerLevel level n trees computed above that subforest. 
    // Level 0 forest  = empty array. 
    // So we need the code for a level n trees computed above a n-1 subforest. 
    // The case $n = 1 (level 1 tree and forest) must be managed separately. 
    
    if ($level === 0) {return [];}
    $forest = $subforest = generateForest ($labels, $topTreeSize, $child_range, $level-1, $forestSizePerLevel); 
    for ($i=0; $i < $forestSizePerLevel; $i++) {
        $treeRoot = createRootOverSubforest($labels, $topTreeSize, $child_range, $level, $subforest);
        $k = \count($forest); 
        $treeRoot['key']= $k; 
        $forest[] = $treeRoot; 
    }
    return $forest;     
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

function starAndBar($k, $n ) {
    if ($k === 1) {return [$n];} 
    for ($i = 0; $i < $k-1; $i++ ) { $l[$i] = mt_rand(0, $n); }
    sort($l);
    array_unshift($l, 0);
    $l[$k] = $n;         
    for ($i = 0; $i < $k; $i++ ) { $num[$i] = $l[$i+1] - $l[$i]; }
    return $num; 
}

$labels = ['A', 'B', 'C', '__K__'];
$node = createRootNode($labels, 10,  [1,3], 3, 3); 
createProfile($node); 