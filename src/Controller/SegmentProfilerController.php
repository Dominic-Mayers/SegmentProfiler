<?php

namespace App\Controller;

use App\Backend;
use App\UI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SegmentProfilerController extends AbstractController {
		
	#[Route('/drawgraph/{input}/{startId}/{toUngroup}', name: 'drawgraph' )]
	public function drawGraph(Backend $backend, UI $ui, $input, $startId = null, $toUngroup = ""): Response {
                set_time_limit(600); 
		$backend->setDefaultGroups ($input);
		if ( !empty($toUngroup) ) {
			$toUngroupArr = explode("_", substr($toUngroup, 0, -1));
			foreach($toUngroupArr as $groupId) {
				$this->graphTransformation->deactivateGroup($groupId);
			}
		}
                $ui->setColorCode();
                $subGraph = $ui->getSubGraph($startId); 
                $dotString = $ui->activeGraphToDot($input, $subGraph, $toUngroup); 
                $svg = $ui->dot2svg($dotString); 
                $urlDropdown = "/js/dropdown.js";
                $urlStep = "/js/step.js";
		return new Response('<!DOCTYPE html><html><head>'.
                        '<script src='.$urlDropdown.' defer ></script>'.
                        '<script src='.$urlStep.' defer ></script>' .
                        '<script>var activeGraph ='. file_get_contents(__DIR__.'/../../input/Graphs/'.$input.'.actgraph') . '</script>' .
                    '</head><body><button type=button id=stepButton>Step in</button>'.$svg.'</body></html>'
                );
	}
}