<?php

class Baseboard
{
  public static function compute(array $config)
  {
    $projects = array();
    $availableTeammates = array();
    
    if(isset($config['team']))
    {
      $availableTeammates = $config['team'];
    }

    foreach($config['projects'] as $projectName => $project)
    {
      $basecampAPI = new basecampAPI(new curlConnexion($project['basecamp-url'], 'xml', $project['basecamp-token'], 'X'));
      
      $tmpTodolists = $basecampAPI->get('projects/#{project_id}/todo_lists.xml', $project['basecamp-id']);
      if(is_null($tmpTodolists))
      {
        continue;
      }
  
      $milestones = array();
      $openedBugsCount = 0;
      
      foreach($tmpTodolists as $tmpTodolist)
      {
        if(substr(strtolower($tmpTodolist['name']), 0, 3) == 'bug' && $tmpTodolist['complete'] == 'false')
        {
          $openedBugsCount += $tmpTodolist['uncompleted-count'];
        }
        
        if(is_array($tmpTodolist['milestone-id']))
        {
          continue; // pas de milestone reliée
        }
  
        $milestoneId = $tmpTodolist['milestone-id'];
    
        // Ajout de la milestone si premier passage
        if(!array_key_exists($milestoneId, $milestones))
        {
          $tmpMilestone = $basecampAPI->get('projects/#{project_id}/calendar_entries/#{id}.xml', $project['basecamp-id'], $milestoneId);
      
          if(is_null($tmpMilestone) || is_array($tmpMilestone['start-at']) || is_array($tmpMilestone['deadline']))
          {
            continue;
          }
          
          if($tmpMilestone['completed'] == 'true' || strtotime($tmpMilestone['start-at']) > strtotime('now'))
          {
            continue;
          }
  
          $milestones[$milestoneId] = array(
            'id' => $tmpMilestone['id'],
            'name' => self::customStrip($tmpMilestone['title'], 40),
            'todoLists' => array(),
            'completed' => $tmpMilestone['completed'],
            'deadline' => $tmpMilestone['deadline'],
            'startAt' => $tmpMilestone['start-at'],
            'totalCotation' => 0,
            'completedCotation' => 0,
            'percentCotation' => 0,
            'totalBug' => 0,
            'completedBug' => 0,
            'openedBug' => 0,
            'outdated' => false,
            'lateCssClass' => '',
            'teammates' => array()
          );
        }
    
        // Mise à jour des infos de la todolist
        $milestones[$milestoneId]['todoLists'][] = array(
          'id' => $tmpTodolist['id'],
          'name' => $tmpTodolist['name'],
          'complete' => ($tmpTodolist['complete'] == 'true')
        );
    
        $todoItems = $basecampAPI->get('todo_lists/#{todo_list_id}/todo_items.xml', $tmpTodolist['id']);
        
        if(is_null($todoItems))
        {
          continue;
        }
    
        foreach($todoItems as $todoItem)
        {
          $cotation = 0;
          $isBug = false;
          $isCompleted = ($todoItem['completed'] == 'true');
  
          // Récupération de la cotation
          preg_match('/ ((\d+)?\.*(\d+)*)$/', $todoItem['content'], $matches);
          if(count($matches) > 1)
          {
            $cotation = $matches[1];
          }
      
          // Récupération du type (bug ?)
          preg_match('/^bug/i', $todoItem['content'], $matches);
          $isBug = (count($matches) > 0);
      
          if($isBug)
          {
            $milestones[$milestoneId]['totalBug']++;
            $milestones[$milestoneId]['completedBug'] += ($isCompleted) ? 1 : 0;
            $openedBugsCount += ($isCompleted) ? 0 : 1;
          }
          else
          {
            $milestones[$milestoneId]['totalCotation'] += $cotation;
            $milestones[$milestoneId]['completedCotation'] += ($isCompleted) ? $cotation : 0;
          }
          
          if(!$isCompleted)
          {
            if(isset($todoItem['responsible-party-id']) && isset($config['team'][$todoItem['responsible-party-id']]))
            {
              if(!isset($milestones[$milestoneId]['teammates'][$todoItem['responsible-party-id']]))
              {
                $milestones[$milestoneId]['teammates'][$todoItem['responsible-party-id']] = $config['team'][$todoItem['responsible-party-id']];
              }
              
              if(isset($availableTeammates[$todoItem['responsible-party-id']]))
              {
                unset($availableTeammates[$todoItem['responsible-party-id']]);
              }
            }
          }
        }
    
        if($milestones[$milestoneId]['totalCotation'] > 0)
        {
          $milestones[$milestoneId]['percentCotation'] = round($milestones[$milestoneId]['completedCotation'] / $milestones[$milestoneId]['totalCotation'] * 100);
        }
        $milestones[$milestoneId]['openedBug'] = $milestones[$milestoneId]['totalBug'] - $milestones[$milestoneId]['completedBug'];
        $milestones[$milestoneId]['outdated'] = (strtotime(date('c')) > strtotime($milestones[$milestoneId]['deadline'].' 23:59:59'));
    
        if($milestones[$milestoneId]['percentCotation'] == 100)
        {
          $milestones[$milestoneId]['lateCssClass'] = 'done';
        }
        else
        {
          $timeDiff = self::getDiffTimestamp($milestones[$milestoneId]['startAt'], $milestones[$milestoneId]['deadline'], $config);
          if($timeDiff > 0)
          {
            $lastDay = new DateTime('-1 day');
            $theoricalCompletedCotation = self::getDiffTimestamp($milestones[$milestoneId]['startAt'], $lastDay->format('Y-m-d'), $config) * $milestones[$milestoneId]['totalCotation'] / $timeDiff;
            $cotationGap = $milestones[$milestoneId]['completedCotation'] - $theoricalCompletedCotation;
            $byDay = $milestones[$milestoneId]['totalCotation'] / $timeDiff;
            if($cotationGap <= 0 - $byDay)
            {
              $milestones[$milestoneId]['lateCssClass'] = 'late';
            }
            else if($cotationGap > $byDay)
            {
              $milestones[$milestoneId]['lateCssClass'] = 'early';
            }
          }
        }
      }
      
      $projects [] = array(
        'name' => $projectName,
        'id' => $project['basecamp-id'],
        'milestones' => $milestones,
        'openedBugsCount' => $openedBugsCount
      );
    }
    
    return array(
      'projects' => $projects,
      'availableTeammates' => $availableTeammates
    );
  }
  
  public static function getDiffTimestamp($start, $end, $config = null)
  {
    $startDate = new DateTime(substr($start, 0, 10));
    $endDate = new DateTime(substr($end, 0, 10).' + 1 day');
    $interval = DateInterval::createFromDateString('1 day');
    
    $total = 0;
    
    $days = new DatePeriod($startDate, $interval, $endDate);
    foreach ( $days as $day ) {
      // Week End
      if(isset($config['general']['workdays']) && !in_array($day->format('w'), $config['general']['workdays']))
      {
          continue;
      }
      
      // Holidays
      if(isset($config['general']['holidays']) && in_array($day->format('d/m/y'), $config['general']['holidays']))
      {
        continue;
      }
      
      $total++;
    }
    
    return $total;
  }
  
  public static function computeHudsonFails(array $config)
  {
    $failedProjectIds = array();
    
    foreach($config['projects'] as $project)
    {
      $failedProjectIds[$project['basecamp-id']] = 0;
      if(isset($project['hudson-url']))
      {
        $hudson = new hudsonAPI(new curlConnexion($project['hudson-url']));
        $jobs = (isset($project['hudson-jobs']) && count($project['hudson-jobs']) > 0) ? $project['hudson-jobs'] : null;
        if($hudson->hasFailedHudsonJobs($jobs))
        {
          $failedProjectIds[$project['basecamp-id']] = 1;
        }
      }
    }
    return $failedProjectIds;
  }

  public static function customStrip($str, $length = null)
  {
    $retour = ucfirst(preg_replace('/^.*:[ ]+/i', '', trim($str)));
    
    if(!is_null($length) && is_int($length) && $length < strlen($retour))
    {
      $retour = substr($retour, 0, $length - 3) . "...";
    }
    
    return $retour;
  }
}
