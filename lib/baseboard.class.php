<?php

class Baseboard
{

  public static function loadProjects(array $config)
  {
    $projects = array();
    $availableTeammates = array();
    $workingTeammates = array();

    if(isset($config['team']))
    {
      $availableTeammates = $config['team'];
    }

    foreach($config['projects'] as $projectName => $projectProps)
    {
      $project = new project($projectName, $projectProps);
      $project->setWorkdays($config['general']['workdays']);
      $project->setHolidays($config['general']['holidays']);
      $project->setTeam($availableTeammates);

      $project->loadMilestones();
      $project->loadTodoLists('pending');

      $workingTeammates += $project->getWorkingTeammates();

      $projects [] = $project;
    }

    $availableTeammates = array_diff_key($availableTeammates, $workingTeammates);

    return array(
      'projects' => $projects,
      'availableTeammates' => $availableTeammates
    );
  }

  public static function loadHudsonFails(array $config)
  {
    $failedProjectIds = array();
    
    foreach($config['projects'] as $project)
    {
      $failedProjectIds[$project['basecamp-id']] = 0;
      if(isset($project['hudson-url']))
      {
        $requestHeader = array('Accept: application/json', 'Content-Type: application/json');
        $hudson = new hudsonAPI(new RESTConnection($project['hudson-url'], $requestHeader));
        $jobs = (isset($project['hudson-jobs']) && count($project['hudson-jobs']) > 0) ? $project['hudson-jobs'] : null;
        if($hudson->hasFailedHudsonJobs($jobs))
        {
          $failedProjectIds[$project['basecamp-id']] = 1;
        }
      }
    }
    return $failedProjectIds;
  }


}
