<?php
require_once ('vendor/autoload.php');

//include('src/Profiler.php');
$notesFile = new SplFileObject($argv[1]);
$profiler = new App\Profiler();
$profiler->getTree($notesFile);
$profiler->setExclusiveTime();
$profiler->setColorCode();
file_put_contents('output/tree.dot', $profiler->createGraphViz());

//$profiler->groupDescendentsPerName();
//$dot = $profiler->createDot([]);
//file_put_contents('output/dn.dot', $dot);

$profiler->fullGroupSiblingsPerName();
file_put_contents('output/fsn1.dot', $profiler->createGraphViz());

$profiler->groupSiblingsPerChildrenName();
file_put_contents('output/scn.dot', $profiler->createGraphViz());

$profiler->fullGroupSiblingsPerName();
file_put_contents('output/fsn2.dot', $profiler->createGraphViz());

$profiler->groupDescendentsPerName();
file_put_contents('output/dn.dot', $profiler->createGraphViz());

//$profiler->deactivateGroup('SCN00001');
file_put_contents('output/active.dot', $profiler->createGraphViz());

file_put_contents('output/sub.dot', $profiler->createGraphViz($profiler->getSubGraph('DN00001')));
pass();

function pass() {};
