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
            $message = "No Project found for issue '{$issue}'";
            $output->writeln($message);
            exit(1);
        }
        $projectIdentifier = $this->getProjectIdentifierById($projectId);
        if ($projectIdentifier === null) {
            $message = "No Identifier found for Project '{$projectId}'";
            $output->writeln($message);
            exit(1);
        }

        $activity = $input->getArgument('activity');
        $activityName = $this->config['activities'][$activity] ?? null;
        if ($activityName === null) {
            $message = "Unknown activity '{$activity}'";
            $output->writeln($message);
            exit(1);
        }

        // verify the activity is assigned to the project 
        $projectActivities = $this->getActivitiesByProjectIdentifier($projectIdentifier);    

        if (!array_key_exists($activityName, $projectActivities)) {
            $output->writeln("Activity '{$activityName}' ({$activity}) not allowed in Project '{$projectIdentifier}' (id: {$projectId})");
            $output->writeln($this->getProjectActivitiesUrl($projectIdentifier));
            $output->writeln("The list of allowed activities is:");
            $aliases = array_flip($this->config['activities']);
            foreach (array_keys($projectActivities) as $i => $label) {
                #$output->writeln(($i + 1) . ") {$label}");
                $output->writeln($aliases[$label] . "\t" . $label);
            }
            exit(1);
        }
        $activityId = $projectActivities[$activityName];

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

    /**
     * Return array of allowd activities by project in the form:
     * [
     *     ['Test'] => 20,
     *     ['Event'] => 41,
     *     ...
     *     ['Sales/Pre-sales'] => 440,
     * ]
     *
     */
    private function getActivitiesByProjectIdentifier(string $projectIdentifier): array
    {
        $content = $this->getPageContent($this->getProjectActivitiesUrl($projectIdentifier));

        if ($content === '') {
            return [];
        }    

        $begin = strpos($content, '<form accept-charset="UTF-8" action="/projects/' . $projectIdentifier . '/enumerations"');
        $end = strpos($content, '</form>', $begin);
        $content = substr($content, $begin, $end - $begin);

        $begin = strpos($content, '</thead>') + 8;
        $end = strpos($content, '</table>', $begin);
        $content = substr($content, $begin, $end - $begin);

        $rows = explode('<tr', $content);
        foreach ($rows as $row) {
          preg_match_all("|(.*)\s+<\/td>\s+<td|", $row, $matches);
          $name = $matches[1][0] ?? '';
          if (empty($name)) {
            continue;
          }
          preg_match_all("|\"checked\" id=\"enumerations_(\d+)_active\"|", $row, $matches);
          $id = $matches[1][0] ?? 0;
          if (!$id) {
            continue;
          }
          $activities[$id] = trim($name);
        }

        return array_flip($activities ?? []);
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