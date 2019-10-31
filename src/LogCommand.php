<?php

namespace Aleron75\Redlog\Console;

use Dotenv\Dotenv;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class LogCommand extends Command
{
    protected $client = null;
    protected $config = [];
    protected $projects = null;

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $dotenv = new Dotenv(__DIR__ . DIRECTORY_SEPARATOR . '..');
        $dotenv->load();

        $this->client = new \Redmine\Client(getenv('REDMINE_API_ENDPOINT'), getenv('REDMINE_API_TOKEN'));
        $this->client->setImpersonateUser(getenv('REDMINE_USER'));

        $this->config = Yaml::parseFile(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.yml');
    }

    protected function configure(): void
    {
        $this
            ->setName('log')
            ->setDescription('Log entries')
            ->addArgument('date', InputArgument::REQUIRED)
            ->addArgument('hours', InputArgument::REQUIRED)
            ->addArgument('issue', InputArgument::REQUIRED)
            ->addArgument('activity', InputArgument::REQUIRED)
            ->addArgument('comment', InputArgument::OPTIONAL)
            ->addOption(
                'dryrun', 
                'd', 
                InputOption::VALUE_NONE, 
                'Print command but doesn\'t perform any call.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $input->getArgument('date');
        $hours = $input->getArgument('hours');
        
        // Calculate hours in case of hhmm-hhmm format
        preg_match('/(\\d?\\d)(\\d\\d)-(\\d?\\d)(\\d\\d)/', $hours, $matches);
        if (5 == count($matches)) {
            $timeStart = \DateTime::createFromFormat('Hi', $matches[1] . $matches[2]);
            $timeEnd = \DateTime::createFromFormat('Hi', $matches[3] . $matches[4]);
            $timeDiff = $timeEnd->diff($timeStart);
            $hours = round($timeDiff->h + $timeDiff->i/60, 2);
        }

        $issue = $input->getArgument('issue');
        
        $projectId = $this->getProjectIdByIssue($issue);
        if ($projectId === 0) {
            throw new \Exception("No Project found for issue '{$issue}'");
        }
        $projectIdentifier = $this->getProjectIdentifierById($projectId);
        if ($projectIdentifier === null) {
            throw new \Exception("No Identifier found for Project '{$projectId}'");
        }

        $activity = $input->getArgument('activity');

        // verify the activity is assigned to the project 
        $projectActivities = $this->getActivitiesByProjectIdentifier($projectIdentifier);    
        if (!count($projectActivities)) {
            $projectActivities = $this->config['project_activities'][$projectIdentifier] ?? []; 
        }
        if (!in_array($activity, $projectActivities)) {
            $message = "Activity '{$activity}' not allowed in Project '{$projectIdentifier}' (id: {$projectId})";
            $message .= PHP_EOL . $this->getProjectActivitiesUrl($projectIdentifier);
            throw new \Exception($message);
        }
        $activityId = $this->getActivity($activity);

        $comment = $input->getArgument('comment') ?: '';
        $data = [
            'issue_id' => $issue,
            'spent_on' => $date,
            'hours' => $hours,
            'activity_id' => $activityId,
            'comments' =>  htmlspecialchars($comment),
        ];

        if ($input->getOption('dryrun')) {
            $output->writeln("redlog log {$date} {$hours} {$issue} {$activity} \"{$comment}\"");
            exit;
        }

        $this->client->time_entry->create($data);
    }

    private function getActivity(string $activity): int
    {
        if (!isset($this->config['activities'][$activity])) {
            throw new \Exception("Activity ID for '{$activity}' not found");
        }
        return (int)$this->config['activities'][$activity];
    }

    private function getProjectIdByIssue(string $issue): int
    {
        $issueDetails = $this->client->issue->show($issue);
        return (int)$issueDetails['issue']['project']['id'] ?? 0;
    }

    private function getProjectIdByIdentifier(string $identifier): int
    {
        if ($this->projects === null) {
            $this->projects = [];
            $projectsData = $this->client->project->all(['limit' => PHP_INT_MAX]);
            foreach ($projectsData['projects'] ?? [] as $project) {
                $this->projects[$project['identifier']] = $project['id'];    
            }
        }
        return (int)$this->projects[$identifier] ?? 0;
    }

    private function getProjectIdentifierById(int $id): ?string
    {
        if ($this->projects === null) {
            $this->projects = [];
            $projectsData = $this->client->project->all(['limit' => PHP_INT_MAX]);
            foreach ($projectsData['projects'] ?? [] as $project) {
                $this->projects[$project['id']] = $project['identifier'];    
            }
        }
        return (string)$this->projects[$id] ?? null;
    }

    private function getProjectActivitiesUrl(string $identifier): string
    {
        return sprintf(
            "%s/projects/%s/settings/activities",
            getenv('REDMINE_API_ENDPOINT'),
            $identifier
        );
    }

    private function getActivitiesByProjectIdentifier(string $identifier): array
    {
        $content = $this->getPageContent($this->getProjectActivitiesUrl($identifier));

        if ($content === '') {
            return [];
        }    

        $pattern = '/checked="checked" id="enumerations_(\d+)_active"/';
        preg_match_all($pattern, $content, $matches);
        if (!isset($matches[1])) {
            return [];
        }

        $activitiesById = $matches[1];
        $activities = $this->config['activities'];

        $activitiesByProjectIdentifier = array_keys(array_filter(
            $activities, 
            function ($item) use ($activitiesById) {
                return in_array($item, $activitiesById);
            }
        ));
        return $activitiesByProjectIdentifier;
    }

    private function getPageContent(string $url): string
    {
        $session = getenv('REDMINE_SESSION');
        if (!$session) {
            return '';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: _redmine_session=" . $session]);
        $content = curl_exec($ch);
        curl_close($ch);

        return $content;
    }
}