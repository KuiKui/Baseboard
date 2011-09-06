<?php

class Baseboard
{
  public static function compute(array $config)
  {
    $projects = array();
    $availableTeammates = array();
    
    if(key_exists('team', $config))
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
            'cotationGap' => 0,
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
            if(key_exists('responsible-party-id', $todoItem) && key_exists($todoItem['responsible-party-id'], $config['team']))
            {
              $milestones[$milestoneId]['teammates'][] = $config['team'][$todoItem['responsible-party-id']];
              if(key_exists($todoItem['responsible-party-id'], $availableTeammates))
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
    
        $timeDiff = self::getDiffTimestamp($milestones[$milestoneId]['startAt'], $milestones[$milestoneId]['deadline'], $config);
        if($timeDiff > 0)
        {
          $lastDay = new DateTime('-1 day');
          $theoricalCompletedCotation = self::getDiffTimestamp($milestones[$milestoneId]['startAt'], $lastDay->format('Y-m-d'), $config) * $milestones[$milestoneId]['totalCotation'] / $timeDiff;
          $milestones[$milestoneId]['cotationGap'] = $milestones[$milestoneId]['completedCotation'] - $theoricalCompletedCotation;
          $byDay = $milestones[$milestoneId]['totalCotation'] / $timeDiff;
          if($milestones[$milestoneId]['cotationGap'] <= 0 - $byDay)
          {
            $milestones[$milestoneId]['lateCssClass'] = 'late';
          }
          else if($milestones[$milestoneId]['cotationGap'] > $byDay)
          {
            $milestones[$milestoneId]['lateCssClass'] = 'early';
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
      if(!is_null($config) && key_exists('general', $config) && key_exists('workdays', $config['general']))
      {
        if(!in_array($day->format('w'), $config['general']['workdays']))
        {
          continue;
        }
      }
      
      // Holidays
      if(!is_null($config) && key_exists('general', $config) && key_exists('holidays', $config['general']))
      {
        if(in_array($day->format('d/m/y'), $config['general']['holidays']))
        {
          continue;
        }
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
      if(key_exists('hudson-url', $project))
      {
        $hudson = new hudsonAPI(new curlConnexion($project['hudson-url']));
        $jobs = (key_exists('hudson-jobs', $project) && count($project['hudson-jobs']) > 0) ? $project['hudson-jobs'] : null;
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
  
  public static function computeCompanyTwitts(array $config)
  {
    $twitts = array();
    
    if(!key_exists('twitter', $config) || !key_exists('company', $config['twitter']) || !key_exists('enable', $config['twitter']['company']) || $config['twitter']['company']['enable'] != 'true')
    {
      return $twitts;
    }
    
    if(!key_exists('twitter', $config) || !key_exists('search', $config['twitter']['company']) || !key_exists('count', $config['twitter']['company']))
    {
      return $twitts;
    }
    
    $twitterAPI = new twitterAPI(new curlConnexion('http://search.twitter.com/'));
    $tmpTwitts = $twitterAPI->get(sprintf('search.json?q=%s&result_type=recent&count=%s', urlencode($config['twitter']['company']['search']), $config['twitter']['company']['count']));
    
    if(key_exists('error', $tmpTwitts) || count($tmpTwitts) == 0)
    {
      return $twitts;
    }
    
    foreach($tmpTwitts['results'] as $tmpTwitt)
    {
      $twitts[] = array(
        'timestamp' => strtotime($tmpTwitt['created_at']),
        'user'  => $tmpTwitt['from_user'],
        'text'  => $tmpTwitt['text'],
        'image' => $tmpTwitt['profile_image_url']
      );
    }
    
    return $twitts;
  }

  public static function computeTeamTwitts(array $config)
  {
    $twitts = array();

    if(!key_exists('twitter', $config) || !key_exists('team', $config['twitter']) || !key_exists('enable', $config['twitter']['team']) || $config['twitter']['team']['enable'] != 'true')
    {
      return $twitts;
    }
    
    if(!key_exists('team', $config) || count($config['team']) == 0)
    {
      return $twitts;
    }

    $twitterAPI = new twitterAPI(new curlConnexion('http://api.twitter.com/1/'));
    
    foreach($config['team'] as $member)
    {
      if(!key_exists('twitter-display-name', $member))
      {
        continue;
      }
      
      $tmpTwitts = $twitterAPI->get(sprintf('statuses/user_timeline.json?include_entities=false&include_rts=false&screen_name=%s&count=5', $member['twitter-display-name']));
      
      if(key_exists('error', $tmpTwitts) || count($tmpTwitts) == 0)
      {
        continue;
      }

      foreach($tmpTwitts as $tmpTwitt)
      {
        $twitts[] = array(
          'timestamp' => strtotime($tmpTwitt['created_at']),
          'user'  => $tmpTwitt['user']['screen_name'],
          'text'  => $tmpTwitt['text'],
          'image' => $tmpTwitt['user']['profile_image_url']
        );
      }
    }

    if(count($twitts) > 1)
    {
      usort($twitts, function ($a, $b) {
        return ($a['timestamp'] < $b['timestamp']);
      });
    }
    
    if(key_exists('working-time-only', $config['twitter']['team']) && $config['twitter']['team']['working-time-only'] == 'true')
    {
      $validTwitts = array();
      foreach($twitts as $twitt)
      {
        if(
          date('w', $twitt['timestamp']) == 0 || date('w', $twitt['timestamp']) == 6
          ||
          date('G', $twitt['timestamp']) < 9 || date('G', $twitt['timestamp']) >= 18
          )
        {
          continue;
        }
        $validTwitts[] = $twitt;
      }
      $twitts = $validTwitts;
    }
    
    return $twitts;
  }
  
}
