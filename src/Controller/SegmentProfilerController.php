<?php

namespace App\Controller;

use App\Backend;
use App\UI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SegmentProfilerController extends AbstractController {
    
        #[Route('/show/{input}/{startId}/{toUngroup}', name: 'show')]
        public function show(Backend $backend, UI $ui, $input, $startId = null, $toUngroup = "") : Response {
                $svg = $this->computeSvg ($backend, $ui, $input, $toUngroup, $startId);   
                return $this->render('graph/show-test.html.twig', [
                    'svg' => $svg,
                ]);
        }
		        
        private function computeSvg (Backend $backend, UI $ui, $input, $toUngroup, $startId) {
                set_time_limit(600); 
		$backend->setDefaultGroups ($input);
		if ( !empty($toUngroup) ) {
			$toUngroupArr = explode("_", substr($toUngroup, 0, -1));
			foreach($toUngroupArr as $groupId) {
				$this->graphTransformationAPI->deactivateGroup($groupId);
			}
		}
                $ui->setColorCode();
                $subGraph = $ui->getSubGraph($startId); 
                $dotString = $ui->activeGraphToDot($input, $subGraph); 
                $svg = $ui->dot2svg($dotString);
                return $svg; 
        }
}