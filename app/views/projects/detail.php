<?php // Project Detail View ?>
<div class="page-header">
  <div class="page-header-left">
    <div class="page-title"><?= e($project['project_name']) ?></div>
    <div class="page-subtitle">Order <?= e($project['order_number']) ?> &middot; <?= e($project['customer_name']) ?> &middot; <?= e($project['service_type']) ?></div>
  </div>
  <div class="page-header-actions">
    <a href="<?= APP_URL ?>/?page=projects" class="btn btn-secondary"><?= svgIcon('list') ?> All Projects</a>
    <a href="<?= APP_URL ?>/?page=order_detail&id=<?= $project['order_id'] ?>" class="btn btn-secondary"><?= svgIcon('eye') ?> View Order</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:22px;margin-bottom:24px">
  <div class="card">
    <div class="card-header"><div class="card-title">Project Details</div></div>
    <div class="card-body">
      <div class="form-grid form-grid-2">
        <div class="form-group"><label>Status</label><div><span class="badge <?= $project['status'] === 'Completed' ? 'badge-success' : ($project['status'] === 'In Progress' ? 'badge-primary' : ($project['status'] === 'On Hold' ? 'badge-warning' : ($project['status'] === 'Cancelled' ? 'badge-danger' : 'badge-secondary'))) ?>" style="font-size:.85rem;padding:6px 14px"><?= e($project['status']) ?></span></div></div>
        <div class="form-group"><label>Partner</label><div><?= e($project['partner_name']) ?></div></div>
        <div class="form-group"><label>Order Status</label><div><span class="badge <?= orderStatusClass($project['order_status']) ?>"><?= e($project['order_status']) ?></span></div></div>
        <div class="form-group"><label>Start Date</label><div><?= fmtDate($project['start_date']) ?></div></div>
        <div class="form-group"><label>Target Date</label><div><?= fmtDate($project['target_date']) ?></div></div>
        <div class="form-group"><label>Completion Date</label><div><?= fmtDate($project['actual_completion_date']) ?></div></div>
      </div>
      <?php if ($project['notes']): ?>
      <div class="divider"></div>
      <div class="form-group">
        <label>Notes</label>
        <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:14px;margin-top:6px;font-size:.875rem;white-space:pre-wrap"><?= e($project['notes']) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Quick Actions</div></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
      <button class="btn btn-primary w-100" data-modal-open="addTaskModal"><?= svgIcon('plus') ?> Add Task</button>
      <button class="btn btn-secondary w-100" data-modal-open="addMilestoneModal"><?= svgIcon('flag') ?> Add Milestone</button>
    </div>
  </div>
</div>

<!-- Tabs: Tasks / Milestones -->
<div class="tabs" data-group="project">
  <button class="tab-btn active" data-tab="tasks" data-tab-group="project">Tasks (<?= count($tasks) ?>)</button>
  <button class="tab-btn" data-tab="milestones" data-tab-group="project">Milestones (<?= count($milestones) ?>)</button>
</div>

<!-- Tasks -->
<div class="tab-panel active" data-tab-panel="tasks" data-tab-group="project">
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Task</th>
            <th>Assigned To</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Completed</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tasks)): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="empty-state-title">No tasks yet</div><div class="empty-state-text">Add tasks to track project progress.</div></div></td></tr>
          <?php else: ?>
          <?php foreach ($tasks as $t): ?>
          <tr>
            <td class="font-600"><?= e($t['task_name']) ?></td>
            <td class="font-sm"><?= e($t['assigned_name'] ?: '—') ?></td>
            <td class="font-sm"><?= fmtDate($t['due_date']) ?></td>
            <td>
              <span class="badge badge-<?= $t['status'] === 'Completed' ? 'success' : ($t['status'] === 'In Progress' ? 'primary' : ($t['status'] === 'Blocked' ? 'danger' : 'secondary')) ?>">
                <?= e($t['status']) ?>
              </span>
            </td>
            <td class="font-sm text-muted"><?= fmtDateTime($t['completed_at']) ?></td>
            <td class="text-right">
              <form method="POST" action="<?= APP_URL ?>/?page=projects&action=update_task" style="display:inline-flex;gap:4px">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="task_id" value="<?= $t['id'] ?>">
                <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                <select name="status" class="form-control" style="width:auto;font-size:.75rem;padding:2px 6px" onchange="this.form.submit()">
                  <option value="Pending" <?= $t['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="In Progress" <?= $t['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                  <option value="Completed" <?= $t['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                  <option value="Blocked" <?= $t['status'] === 'Blocked' ? 'selected' : '' ?>>Blocked</option>
                </select>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Milestones -->
<div class="tab-panel" data-tab-panel="milestones" data-tab-group="project">
  <div class="card">
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Milestone</th>
            <th>Target Date</th>
            <th>Actual Date</th>
            <th>Status</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($milestones)): ?>
          <tr><td colspan="5"><div class="empty-state"><div class="empty-state-title">No milestones set</div><div class="empty-state-text">Add milestones to track key dates.</div></div></td></tr>
          <?php else: ?>
          <?php foreach ($milestones as $ms): ?>
          <tr>
            <td class="font-600"><?= e($ms['milestone_name']) ?></td>
            <td class="font-sm"><?= fmtDate($ms['target_date']) ?></td>
            <td class="font-sm"><?= fmtDate($ms['actual_date']) ?></td>
            <td>
              <span class="badge badge-<?= $ms['status'] === 'Achieved' ? 'success' : ($ms['status'] === 'Missed' ? 'danger' : 'secondary') ?>">
                <?= e($ms['status']) ?>
              </span>
            </td>
            <td class="text-right">
              <form method="POST" action="<?= APP_URL ?>/?page=projects&action=update_milestone" style="display:inline-flex;gap:4px">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="milestone_id" value="<?= $ms['id'] ?>">
                <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                <select name="status" class="form-control" style="width:auto;font-size:.75rem;padding:2px 6px" onchange="this.form.submit()">
                  <option value="Pending" <?= $ms['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                  <option value="Achieved" <?= $ms['status'] === 'Achieved' ? 'selected' : '' ?>>Achieved</option>
                  <option value="Missed" <?= $ms['status'] === 'Missed' ? 'selected' : '' ?>>Missed</option>
                </select>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ============================================================
     MODALS
     ============================================================ -->

<!-- Add Task Modal -->
<div class="modal-backdrop" id="addTaskModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Task</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=projects&action=add_task">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Task Name</label>
          <input type="text" name="task_name" class="form-control" required placeholder="e.g. Site survey, Install CPE...">
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Assigned To</label>
          <select name="assigned_to" class="form-control">
            <option value="">Unassigned</option>
            <?php foreach ($assignable as $u): ?>
            <option value="<?= $u['id'] ?>"><?= e($u['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Due Date</label>
          <input type="date" name="due_date" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Add Task</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Milestone Modal -->
<div class="modal-backdrop" id="addMilestoneModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Milestone</div>
      <button class="modal-close" data-modal-close>&times;</button>
    </div>
    <form method="POST" action="<?= APP_URL ?>/?page=projects&action=add_milestone">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
      <div class="modal-body">
        <div class="form-group">
          <label>Milestone Name</label>
          <input type="text" name="milestone_name" class="form-control" required placeholder="e.g. BSA approval, NOC handover...">
        </div>
        <div class="form-group" style="margin-top:12px">
          <label>Target Date</label>
          <input type="date" name="target_date" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
        <button type="submit" class="btn btn-primary">Add Milestone</button>
      </div>
    </form>
  </div>
</div>
