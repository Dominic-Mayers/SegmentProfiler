<?php

namespace App\Controller;

use App\Profiler;
use App\TotalGraph; 
use Graphp\GraphViz\GraphViz; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SegmentProfilerController extends AbstractController {
	
	private GraphViz $gv;
	private \SplFileObject $notesFile;
	
	public function __construct(
                private TotalGraph $totalGraph,
                private \App\ActiveGraph $activeGraph,
        ) {
                $this->gv = new GraphViz(); 
                $this->gv->setFormat('svg');
	}
	
	#[Route('/tree/{input}')]
	public function showTree(Profiler $profiler, $input): Response {
                $this->setTree($profiler, $input);
                $profiler->createDefaultActiveGraph();
                $profiler->setColorCode();
                $profiler->createGraphViz();
                $svgHtml = $this->gv->createImageHtml($profiler->graph); 
                return new Response(
                    '<html><body>'.$svgHtml.'</body></html>'
                );
	}

	#[Route('/drawgraph/{input}/{startId}/{toUngroup}', name: 'drawgraph' )]
	public function drawGraph(Profiler $profiler, $input, $startId = null, $toUngroup = ""): Response {
                set_time_limit(600); 
		$this->setDefaultGroups ($profiler, $input);
		if ( !empty($toUngroup) ) {
			$toUngroupArr = explode("_", substr($toUngroup, 0, -1));
			foreach($toUngroupArr as $groupId) {
				$profiler->deactivateGroup($groupId);
			}
		}
		$profiler->setColorCode();
                $subGraph = $profiler->getSubGraph($startId); 
                $dotString = $profiler->activeGraphToDot($input, $subGraph, false, $toUngroup); 
                $svg = $this->dot2svg($dotString); 
                $urlDropdown = "/js/dropdown.js";
                $urlStep = "/js/step.js";
		return new Response(
                    '<!DOCTYPE html><html><head>'.
                        '<script src='.$urlDropdown.' defer ></script>'.
                        '<script src='.$urlStep.' defer ></script>' .
                    '</head><body><button type=button id=stepButton>Step in</button>'.$svg.'</body></html>'
                );
	}

        private function dot2svg ($dotString) {
            $executable = "/usr/bin/dot -Tsvg"; 
            $descriptorspec = array(
                0 => array("pipe", "r"),
                1 => array("pipe", "w"),
                2 => array("pipe", "w")
            );
            $process = proc_open(
                $executable,
                $descriptorspec,
                $pipes
            );
            fwrite($pipes[0], $dotString);
            fclose($pipes[0]);
            $svg = stream_get_contents($pipes[1]); 
            fclose($pipes[1]);
            fclose($pipes[2]);
            return $svg; 
        }

	private function setTree (Profiler $profiler, $input) {
		$this->notesFile = new \SplFileObject('../src/Fixtures/'.$input.'.profile');
        	$this->totalGraph->getTree($this->notesFile);
                $profiler->setTreeKeyWithEmpty();
                $profiler->setTreeKey();
                //xdebug_break();
        }

	private function setDefaultGroups (Profiler $profiler, $input) {
                $filenameTotal  = __DIR__ . '/../../input/Graphs/'.$input.'.totgraph'; 
                $filenameActive = __DIR__ . '/../../input/Graphs/'.$input.'.actgraph'; 
                if (false & file_exists ($filenameTotal) && file_exists ($filenameActive) ) {
                    $profiler->restoreGraphFromFile($filenameTotal,  false);
                    $profiler->restoreGraphFromFile($filenameActive, true);
                    return;
                }
                // Of course, it is pointless to modify below if the graphs are stored in files. 
                $this->setTree($profiler, $input);
                $profiler->createDefaultActiveGraph();
                $profiler->groupCTwe();
                $profiler->createDefaultActiveGraph();
                $profiler->optimizedForestWe();
                $profiler->groupTTweD();
                $profiler->createDefaultActiveGraph();
                //$profiler->groupT();
                //$profiler->groupTwe();
                //$profiler->groupCT();
                

                $profiler->saveGraphInFile($filenameTotal, false); 
                $profiler->saveGraphInFile($filenameActive, true);
        }
}