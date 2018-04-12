<?php

namespace Aleron75\Redlog\Console;

use Dotenv\Dotenv;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LogCommand extends Command
{
    protected $activities = [];

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $dotenv = new Dotenv(__DIR__ . DIRECTORY_SEPARATOR . '..');
        $dotenv->load();
    }

    protected function configure()
    {
        $this
            ->setName('log')
            ->setDescription('Log entries')
            ->addArgument('date', InputArgument::REQUIRED)
            ->addArgument('hours', InputArgument::REQUIRED)
            ->addArgument('issue', InputArgument::REQUIRED)
            ->addArgument('activity', InputArgument::REQUIRED)
            ->addArgument('comment', InputArgument::OPTIONAL)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $input->getArgument('date');
        $hours = $input->getArgument('hours');
        
        // Calculate hours in case of hhmm-hhmm format
        preg_match('/(\\d\\d)(\\d\\d)-(\\d\\d)(\\d\\d)/', $hours, $matches);
        if (5 == count($matches)) {
            $timeStart = \DateTime::createFromFormat('Hi', $matches[1] . $matches[2]);
            $timeEnd = \DateTime::createFromFormat('Hi', $matches[3] . $matches[4]);
            $timeDiff = $timeEnd->diff($timeStart);
            $hours = $timeDiff->h + $timeDiff->i/60;
        }

        $issue = $input->getArgument('issue');
        $activity = $input->getArgument('activity');
        $comment = $input->getArgument('comment') ?: '';
        $data = [
            'issue_id' => $issue,
            'spent_on' => $date,
            'hours' => $hours,
            'activity_id' => $this->getActivity($activity),
            'comments' =>  htmlspecialchars($comment),
        ];

        $client = new \Redmine\Client(getenv('REDMINE_API_ENDPOINT'), getenv('REDMINE_API_TOKEN'));

        // impersonate user
        $client->setImpersonateUser(getenv('REDMINE_USER'));

        // create a time entry for jsmith
        $client->time_entry->create($data);

        // remove impersonation for further calls
        $client->setImpersonateUser(null);

    }

    private function getActivity($activity)
    {
        if (empty($this->activities)) {
            $this->initActivities();
        }
        if (!isset($this->activities[$activity])) {
            throw new \Exception('Activity ID for ' . $activity . ' not found!');
            
        }
        return $this->activities[$activity];
    }

    private function initActivities()
    {
        $activities = getenv('REDMINE_ACTIVITIES');
        if (empty($activities)) {
            throw new \Exception('The REDMINE_ACTIVITIES in .env file is empty');
        }

        $activities = explode(',', $activities);
        foreach ($activities as $kv) {
            list($k, $v) = explode(':', $kv);
            $this->activities[$k] = $v;
        }
    }
}