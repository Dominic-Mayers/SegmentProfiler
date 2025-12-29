<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Console\Input\InputInterface;
//use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'app:init-graph')]
class InitGraphCommand extends Command{
   
    public function __construct(
		private UrlGeneratorInterface $urlGenerator,
                private EntityManagerInterface $entityManager,
    ) {
        parent::__construct(); 
    }
    
    protected function configure(): void
    {
        $this->addArgument('profileName', InputArgument::REQUIRED);
    }
    
    public function __invoke(InputInterface $input): int {
        $profileName = $input->getArgument('profileName'); 
        $notesFile = new \SplFileObject(__DIR__.'/../Fixtures/'.$profileName.'.profile');
        $profiler = new \App\Profiler($this->urlGenerator, $this->entityManager,);
        $profiler->getTree($notesFile);
        $profiler->setExclusiveTime();
        $profiler->setColorCode();
        $dot = $profiler->createGraphViz($profileName); 
        file_put_contents(__DIR__.'/../../output/'.$profileName.'_tree.dot', $dot);
        echo $dot; 
        return Command::SUCCESS;
        
        //$profiler->groupDescendentsPerLabel();
        //$dot = $profiler->createDot([]);
        //file_put_contents('output/dn.dot', $dot);

        $profiler->fullGroupSiblingsPerLabel();
        file_put_contents('output/fsn1.dot', $profiler->createGraphViz());

        $profiler->groupSiblingsPerChildrenLabel();
        file_put_contents('output/scn.dot', $profiler->createGraphViz());

        $profiler->fullGroupSiblingsPerLabel();
        file_put_contents('output/fsn2.dot', $profiler->createGraphViz());

        $profiler->groupDescendentsPerLabel();
        file_put_contents('output/dn.dot', $profiler->createGraphViz());

        //$profiler->deactivateGroup('SCN00001');
        file_put_contents('output/active.dot', $profiler->createGraphViz());

        file_put_contents('output/sub.dot', $profiler->createGraphViz($profiler->getSubGraph('DN00001')));
        return Command::SUCCESS;
    }
}