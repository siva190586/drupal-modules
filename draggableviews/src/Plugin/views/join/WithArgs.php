<?php

namespace Drupal\draggableviews\Plugin\views\join;

use Drupal\draggableviews\Plugin\views\sort\DraggableViewsSort;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a join handler with arguments.
 *
 * @ingroup views_join_handlers
 *
 * @ViewsJoin("draggableviews_with_args")
 */
class WithArgs extends JoinPluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildJoin($select_query, $table, $view_query) {
    /** @var ViewExecutable $view */
    $view = $view_query->view;
    $view_args = !empty($view_query->view->args) ? json_encode($view_query->view->args) : [];
    $view_args .= !empty($view_query->view->getExposedInput()) ? json_encode($view_query->view->getExposedInput()) : [];
    \Drupal::logger('DraggableViews')->notice('Exposed input: ' . json_encode($view_query->view->getExposedInput()));
    $context = [
      'select_query' => &$select_query,
      'table' => &$table,
      'view_query' => &$view_query,
    ];
    \Drupal::moduleHandler()->alter('draggableviews_join_withargs', $view_args, $context);
    // $view_args = json_encode($view_args);

    if (!isset($this->extra)) {
      $this->extra = [];
    }

    //exclude args if arguments aren't passed
    $includeArgs = true;
    $sort = $view->sort ?? [];
    foreach($sort as $sortClass) {
      if($sortClass instanceof DraggableViewsSort) {
        $pass = $sortClass->options['draggable_views_pass_arguments'] ?? 0;
        if(empty($pass) || $pass === '0') {
          $includeArgs = false;
        }
      }
    }

    if (is_array($this->extra)) {
      $found = FALSE;
      foreach ($this->extra as $info) {
        if (empty(array_diff(array_keys($info), ['field', 'value'])) && $info['field'] == 'args' && $info['value'] == $view_args) {
          $found = TRUE;
          break;
        }
      }

      if (!$found && $includeArgs) {
        $this->extra[] = ['field' => 'args', 'value' => $view_args];
      }
    }

    parent::buildJoin($select_query, $table, $view_query);
  }

}
