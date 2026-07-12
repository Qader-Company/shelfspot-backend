# Reopened Tasks, Reassignment, and Worker Priority Tasks

## Objective

When an admin reopens a rejected task, they must assign it to a worker and give it a short rework window. The system must preserve every worker assignment, show the newly assigned task prominently to the selected worker, and close the task permanently when the rework window expires.

## Agreed Business Rules

1. A task can be reopened only from `rejected`.
2. Reopening requires an active, available `worker_id`.
   - The selected worker may be the original worker or a different worker.
   - The backend derives the assignment type; the request does not send a redundant `same_worker` flag.
3. A reopened task remains in `reopened` status and can be executed immediately by its assigned worker.
4. The rework deadline covers the reopening day and the following day.
   - `reopened_at`: timestamp of the admin action.
   - `reopen_deadline_at`: the start of the day after tomorrow (exclusive deadline).
5. If the selected worker does not execute the task before `reopen_deadline_at`:
   - The task becomes `failed`.
   - `failure_reason` becomes `reopen_deadline_expired`.
   - The current worker assignment is closed and `assigned_worker_id` is cleared.
   - The task cannot be reopened again.
   - No refund is made automatically. The company may use the existing company cancellation flow to request its automatic wallet refund.
6. The first worker's assignment must remain auditable after reassignment. Updating `tasks.assigned_worker_id` alone is not sufficient.
7. Notifications and WebSocket delivery are out of scope for this iteration. The mobile UI will poll/refresh the worker profile and task list.

## API Contract

### Reopen task

Update the existing admin endpoint:

```http
POST /api/v1/admin/tasks/{id}/reopen
```

Request body:

```json
{
  "worker_id": 42,
  "reason": "The submitted evidence did not satisfy the task requirements."
}
```

Validation:

- `worker_id`: required, exists, worker is active, and worker has no active task.
- `reason`: optional string, maximum 2,000 characters.
- The task must be `rejected`.

The response returns the task with the selected `assigned_worker`, the rework deadline, and assignment metadata.

## Data Model

### `tasks` additions

- `reopen_deadline_at` nullable timestamp.
- `failure_reason` nullable string/enum value.

Introduce `TaskFailureReasonEnum` initially containing:

- `reopen_deadline_expired`

Existing failure paths may remain without a reason until they are migrated deliberately.

### `task_worker_assignments` table

Create a dedicated assignment history table. Suggested columns:

| Column | Purpose |
| --- | --- |
| `task_id` | The task being worked on. |
| `worker_id` | Assigned worker. |
| `assignment_type` | `initial`, `reopened_same_worker`, `reopened_reassigned`, or `reassigned`. |
| `assigned_by` | Admin/user that made the assignment, nullable for worker self-assignment. |
| `assigned_at` | Assignment timestamp. |
| `unassigned_at` | Timestamp when this assignment stops being active. |
| `outcome` | `completed`, `rejected`, `worker_cancelled`, `reassigned`, or `reopen_deadline_expired`. |
| `reason` | Optional human-readable handoff or closure reason. |
| timestamps | Audit timestamps. |

Rules:

- Each task has at most one open assignment (`unassigned_at IS NULL`).
- `tasks.assigned_worker_id` remains the current-assignee shortcut for existing queries.
- Assignment history is the source of truth for audit and future worker-performance reporting.

## Implementation Steps

### 1. Add task lifecycle persistence

1. Add the `reopen_deadline_at` and `failure_reason` columns to `tasks`.
2. Add `TaskFailureReasonEnum` and model casts/fillable fields.
3. Create the `task_worker_assignments` migration, model, enums, relations, and indexes.
4. Add `Task::workerAssignments()` and a relation/scope for the current open assignment.

### 2. Record every worker assignment

1. Create an application service responsible for opening and closing task-worker assignments.
2. Record the initial assignment when a worker starts a pending task.
3. Close or update the assignment when the worker cancels, completes, or the company rejects the completed task.
4. Update the existing admin reassignment flow to close the old assignment and create a `reassigned` assignment for the new worker.

### 3. Replace the reopen flow

1. Extend `AdminReopenTaskRequest` with required `worker_id`.
2. Update `AdminReopenTaskUseCase` to lock the task and selected worker, validate availability, and assign the selected worker.
3. Set `status = reopened`, `reopened_at`, `reopen_deadline_at`, `reopen_reason`, and clear stale execution timing fields.
4. Reset task-service work for the new attempt.
5. Close the original assignment as rejected and open the new assignment with either `reopened_same_worker` or `reopened_reassigned`.
6. Record worker IDs, assignment type, and deadline in task status history metadata.

### 4. Expire reopened tasks

1. Add a dedicated scheduled use case/command for `reopened` tasks whose `reopen_deadline_at` has passed.
2. Lock each task before changing it.
3. Set `status = failed`, `failure_reason = reopen_deadline_expired`, and clear `assigned_worker_id`.
4. Close the open worker assignment with outcome `reopen_deadline_expired`.
5. Record a status-history event with the deadline and failure reason.
6. Do not allow the admin reopen endpoint to reopen failed tasks.

### 5. Return priority tasks in `WorkerResource`

1. Add a compact `WorkerPriorityTaskResource`; do not nest the full `TaskResource` to avoid circular payloads.
2. Add a `priority_tasks` property to `WorkerResource`, only when the relation is loaded for the authenticated worker's own profile.
3. Each priority task includes: task ID, status, assignment type, assignment time, rework deadline, location summary, and estimated duration.
4. Load this relation in the worker account/profile endpoint only. Do not expose it when `WorkerResource` is used in admin lists or nested inside a task response.

Example worker profile payload:

```json
{
  "id": 15,
  "name": "Ahmed",
  "priority_tasks": [
    {
      "id": 124,
      "status": "reopened",
      "assignment_type": "reopened_reassigned",
      "assigned_at": "2026-07-12 14:00:00",
      "reopen_deadline_at": "2026-07-14 00:00:00",
      "location_name": "Store branch"
    }
  ]
}
```

### 6. Prioritize the worker task list

1. Update `/api/v1/worker/tasks/my` to load assignment metadata.
2. Order active and newly assigned work before historical tasks:
   - `in_progress`
   - `reopened` and newly `reassigned`
   - other assigned history
3. Within priority work, order by the current assignment timestamp descending.
4. Keep cursor-pagination ordering deterministic by adding a stable ID tie-breaker.

### 7. Validation and tests

Add tests for:

1. Reopening with the same worker.
2. Reopening with a different worker and preserving the original assignment history.
3. Rejecting inactive or busy workers.
4. Correct computation of the reopening deadline.
5. Reopened task expiry: failure reason, worker unassignment, assignment closure, and no automatic refund.
6. Company cancellation of the failed rework task and its automatic refund.
7. `WorkerResource.priority_tasks` visibility only to the authenticated worker.
8. Worker `/my` list prioritization and stable pagination.
9. Prevention of reopening a `failed` task.

## Out of Scope

- Push notifications, WebSockets, and real-time event delivery.
- Automatically assigning an expired reopened task to another worker.
- Allowing a failed rework task to be reopened again.
- Automatic refunds when the reopening deadline expires.

## Definition of Done

The implementation is complete when an admin can choose an eligible worker during reopen, the selected worker sees a compact priority-task payload in their own `WorkerResource`, the original worker's contribution remains auditable, and expired rework tasks end permanently as failed for the company to decide whether to cancel and refund.
