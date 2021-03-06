<?php

namespace Kanboard\Api;

use Kanboard\Filter\TaskProjectFilter;
use Kanboard\Model\TaskModel;

/**
 * Task API controller
 *
 * @package  Kanboard\Api
 * @author   Frederic Guillot
 */
class TaskApi extends BaseApi
{
    public function searchTasks($project_id, $query)
    {
        $this->checkProjectPermission($project_id);
        return $this->taskLexer->build($query)->withFilter(new TaskProjectFilter($project_id))->toArray();
    }

    public function getTask($task_id)
    {
        $this->checkTaskPermission($task_id);
        return $this->formatTask($this->taskFinderModel->getById($task_id));
    }

    public function getTaskByReference($project_id, $reference)
    {
        $this->checkProjectPermission($project_id);
        return $this->formatTask($this->taskFinderModel->getByReference($project_id, $reference));
    }

    public function getAllTasks($project_id, $status_id = TaskModel::STATUS_OPEN)
    {
        $this->checkProjectPermission($project_id);
        return $this->formatTasks($this->taskFinderModel->getAll($project_id, $status_id));
    }

    public function getOverdueTasks()
    {
        return $this->taskFinderModel->getOverdueTasks();
    }

    public function getOverdueTasksByProject($project_id)
    {
        $this->checkProjectPermission($project_id);
        return $this->taskFinderModel->getOverdueTasksByProject($project_id);
    }

    public function openTask($task_id)
    {
        $this->checkTaskPermission($task_id);
        return $this->taskStatusModel->open($task_id);
    }

    public function closeTask($task_id)
    {
        $this->checkTaskPermission($task_id);
        return $this->taskStatusModel->close($task_id);
    }

    public function removeTask($task_id)
    {
        return $this->taskModel->remove($task_id);
    }

    public function moveTaskPosition($project_id, $task_id, $column_id, $position, $swimlane_id = 0)
    {
        $this->checkProjectPermission($project_id);
        return $this->taskPositionModel->movePosition($project_id, $task_id, $column_id, $position, $swimlane_id);
    }

    public function moveTaskToProject($task_id, $project_id, $swimlane_id = null, $column_id = null, $category_id = null, $owner_id = null)
    {
        return $this->taskDuplicationModel->moveToProject($task_id, $project_id, $swimlane_id, $column_id, $category_id, $owner_id);
    }

    public function duplicateTaskToProject($task_id, $project_id, $swimlane_id = null, $column_id = null, $category_id = null, $owner_id = null)
    {
        return $this->taskDuplicationModel->duplicateToProject($task_id, $project_id, $swimlane_id, $column_id, $category_id, $owner_id);
    }

    public function createTask($title, $project_id, $color_id = '', $column_id = 0, $owner_id = 0, $creator_id = 0,
                                $date_due = '', $description = '', $category_id = 0, $score = 0, $swimlane_id = 0, $priority = 0,
                                $recurrence_status = 0, $recurrence_trigger = 0, $recurrence_factor = 0, $recurrence_timeframe = 0,
                                $recurrence_basedate = 0, $reference = '')
    {
        $this->checkProjectPermission($project_id);

        if ($owner_id !== 0 && ! $this->projectPermissionModel->isAssignable($project_id, $owner_id)) {
            return false;
        }

        if ($this->userSession->isLogged()) {
            $creator_id = $this->userSession->getId();
        }

        $values = array(
            'title' => $title,
            'project_id' => $project_id,
            'color_id' => $color_id,
            'column_id' => $column_id,
            'owner_id' => $owner_id,
            'creator_id' => $creator_id,
            'date_due' => $date_due,
            'description' => $description,
            'category_id' => $category_id,
            'score' => $score,
            'swimlane_id' => $swimlane_id,
            'recurrence_status' => $recurrence_status,
            'recurrence_trigger' => $recurrence_trigger,
            'recurrence_factor' => $recurrence_factor,
            'recurrence_timeframe' => $recurrence_timeframe,
            'recurrence_basedate' => $recurrence_basedate,
            'reference' => $reference,
            'priority' => $priority,
        );

        list($valid, ) = $this->taskValidator->validateCreation($values);

        return $valid ? $this->taskCreationModel->create($values) : false;
    }

    public function updateTask($id, $title = null, $color_id = null, $owner_id = null,
                                $date_due = null, $description = null, $category_id = null, $score = null, $priority = null,
                                $recurrence_status = null, $recurrence_trigger = null, $recurrence_factor = null,
                                $recurrence_timeframe = null, $recurrence_basedate = null, $reference = null)
    {
        $this->checkTaskPermission($id);

        $project_id = $this->taskFinderModel->getProjectId($id);

        if ($project_id === 0) {
            return false;
        }

        if ($owner_id !== null && $owner_id != 0 && ! $this->projectPermissionModel->isAssignable($project_id, $owner_id)) {
            return false;
        }

        $values = array(
            'id' => $id,
            'title' => $title,
            'color_id' => $color_id,
            'owner_id' => $owner_id,
            'date_due' => $date_due,
            'description' => $description,
            'category_id' => $category_id,
            'score' => $score,
            'recurrence_status' => $recurrence_status,
            'recurrence_trigger' => $recurrence_trigger,
            'recurrence_factor' => $recurrence_factor,
            'recurrence_timeframe' => $recurrence_timeframe,
            'recurrence_basedate' => $recurrence_basedate,
            'reference' => $reference,
            'priority' => $priority,
        );

        foreach ($values as $key => $value) {
            if (is_null($value)) {
                unset($values[$key]);
            }
        }

        list($valid) = $this->taskValidator->validateApiModification($values);
        return $valid && $this->taskModificationModel->update($values);
    }
}
