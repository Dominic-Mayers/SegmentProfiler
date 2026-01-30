#!/usr/bin/env php
<?php 
/*
 * It is a recursion on $topTreeSize and $level. For a tree, $topTreeSize
 * is the number of nodes in the tree less the total size of the forest
 * subtrees without counting their root. For a forest, $level is the
 * number of times generateForest must call itself to get that forest.
 * When generateForest calls itself to generate a subforest, $level reduces
 * by one. In the base case, $level = 0, it retuns the empty forest [].
 * When it calls createRootOverSubforest, it passes the subforest (and
 * $topTreeSize, etc), not the level. The forest is constant when it calls
 * itself, but the value of $topTreeSize reduces, because it gets split
 * among $nbChildren and the root. The base case is 0 or 1. When
 * $topTreeSize is 0 it returns the empty tree. Using only 0 as base
 * case would terminate, because we remove 1 before splitting among children,
 * but the forest would never be used. Therefore, when it is 1 and the forest
 * is not empty, it picks a tree at random in the forest. When it is 1,
 * and the forest is empty it returns a leaf, but the same would happen
 * if we were to make a recursive call.   
 */
function createRootNode($labels, $topTreeSize,  $child_range, $forestLevel, $forestSizePerLevel) {
    $subforest = generateForest ($labels, $topTreeSize,  $child_range, $forestLevel, $forestSizePerLevel);
    fwrite(STDERR, "Subforest root keys: "); 
    foreach($subforest as $node) {fwrite(STDERR, $node['key'] . "|");} 
    fwrite(STDERR, PHP_EOL); 
    return createRootOverSubforest($labels, $topTreeSize, $child_range, $subforest);
}

function createRootOverSubforest($labels, $topTreeSize, $child_range, $subforest) {
    //static $nodeId = 0;
    if ( $child_range[0] === 0 ) {
        fwrite(STDERR, "Error: the no child case is determined by the code. The range must start at 1 or higher.". PHP_EOL);
        exit(); 
    }
    if ( $child_range[1] === 1 ) {
        fwrite(STDERR, "Error: more than one child must be allowed in tne range.". PHP_EOL);
        exit(); 
    }
    if (empty($labels)) {
        fwrite(STDERR, "Error: At the least one label is needed." . PHP_EOL);
        exit();
    }
    if ($topTreeSize === 0 ) {
        return null;         
    }
    if ($topTreeSize === 1 && !empty($subforest)) {
        $k = mt_rand(0, \count($subforest) -1);
        $node = $subforest[$k];
        return $node; 
    }
    if ($topTreeSize === 1 && empty($subforest)) {
        // This is not really needed. The same would happen without it. 
        $node['label'] = $labels[mt_rand(0, \count($labels)-1)];
        $node['children'] = [];
        return $node; 
    }
    $node['label'] = $labels[mt_rand(0, \count($labels)-1)];
    $node['children'] = [];
    $nbChildren = mt_rand($child_range[0],$child_range[1]); 
    $num = starAndBar($nbChildren, $topTreeSize - 1); 
    foreach ( $num as $childTopTreeSize) {
        $root = createRootOverSubforest($labels, $childTopTreeSize, $child_range, $subforest);
        if ($root === null) {continue;}
        $node['children'][] =  $root; 
    }
    return $node;
}

function generateForest ($labels, $topTreeSize,  $child_range, $level, $forestSizePerLevel) {
    // Level n forest  = a provided level n-1 subforest + $sizePerLevel level n - 1 trees computed above that subforest. 
    // Level 0 forest  = empty array. 
    // So we need the code for level n - 1 trees computed above a n-1 subforest. 
    // The case $n = 1 (level 1 tree and forest) must be managed separately. 
    
    if ($level === 0) {return [];}
    $forest = $subforest = generateForest ($labels, $topTreeSize, $child_range, $level-1, $forestSizePerLevel); 
    for ($i=0; $i < $forestSizePerLevel; $i++) {
        $treeRoot = createRootOverSubforest($labels, $topTreeSize, $child_range, $subforest);
        if ($treeRoot === null) { continue; }
        $k = \count($forest); 
        $treeRoot['key']= $k;
        $treeRoot['label'] .= "($k)";
        $forest[] = $treeRoot; 
    }
    return $forest;     
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

$labels = ['A', 'B', 'C'];
$node = createRootNode($labels, 6,  [2,4], 3, 2); 
createProfile($node); 
