<?php

namespace Aleron75\Redlog\Console;

use Dotenv\Dotenv;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProjectCommand extends Command
{
    protected $client;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        
        $dotenv = new Dotenv(__DIR__ . DIRECTORY_SEPARATOR . '..');
        $dotenv->load();
        
        $this->client = new \Redmine\Client(getenv('REDMINE_API_ENDPOINT'), getenv('REDMINE_API_TOKEN'));
        $this->client->setImpersonateUser(getenv('REDMINE_USER'));
    }

    protected function configure()
    {
        $this
            ->setName('project')
            ->setDescription('Get project details')
            ->addArgument('id', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $projectId = $input->getArgument('id');
        $data = $this->client->project->show($projectId);
        if (empty($data) || !isset($data['project'])) {
            echo 'No data found for project ' . $projectId;
            return;
        }
        $output->writeln(print_r([
            'id' => $data['project']['id'],
            'name' => $data['project']['name'],
            'identifier' => $data['project']['identifier'],
        ], true));
    }

}