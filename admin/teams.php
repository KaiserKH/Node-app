<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
// require_admin();

$admin = current_user();
$adminId = $admin['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create_team') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($name === '') {
            set_flash('error', 'Team name is required.');
            redirect('teams.php');
        }

        $stmt = db()->prepare('INSERT INTO teams (name, description, created_by) VALUES (:name, :description, :created_by)');
        $stmt->execute([
            'name' => $name,
            'description' => $description,
          'created_by' => $adminId,
        ]);

        set_flash('success', 'Team created successfully.');
        redirect('teams.php');
    }

    if ($action === 'assign_member') {
        $teamId = max(1, (int) ($_POST['team_id'] ?? 0));
        $userId = max(1, (int) ($_POST['user_id'] ?? 0));
        $roleInTeam = trim((string) ($_POST['role_in_team'] ?? 'Member'));

        $stmt = db()->prepare('INSERT INTO team_members (team_id, user_id, role_in_team, assigned_by)
                               VALUES (:team_id, :user_id, :role_in_team, :assigned_by)
                               ON DUPLICATE KEY UPDATE role_in_team = VALUES(role_in_team), assigned_by = VALUES(assigned_by), assigned_at = CURRENT_TIMESTAMP');
        $stmt->execute([
            'team_id' => $teamId,
            'user_id' => $userId,
            'role_in_team' => $roleInTeam,
          'assigned_by' => $adminId,
        ]);

        set_flash('success', 'Member assigned to team.');
        redirect('teams.php');
    }
}

$teams = db()->query('SELECT t.id, t.name, t.description, t.created_at, u.name AS creator_name FROM teams t LEFT JOIN users u ON u.id = t.created_by ORDER BY t.id DESC')->fetchAll();
$users = db()->query("SELECT id, name, role FROM users ORDER BY name ASC")->fetchAll();
$members = db()->query('SELECT tm.team_id, tm.role_in_team, u.name AS user_name, t.name AS team_name
                        FROM team_members tm
                        JOIN users u ON u.id = tm.user_id
                        JOIN teams t ON t.id = tm.team_id
                        ORDER BY tm.assigned_at DESC')->fetchAll();

$pageTitle = APP_NAME . ' - Teams';
$basePath = '../';
require __DIR__ . '/../includes/header.php';
?>

<section class="grid">
  <article class="card">
    <h2>Create Team</h2>
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="create_team">
      <label for="name">Team Name</label>
      <input id="name" name="name" required maxlength="120">

      <label for="description">Description</label>
      <textarea id="description" name="description" maxlength="1000"></textarea>

      <button type="submit">Create Team</button>
    </form>
  </article>

  <article class="card">
    <h2>Assign Member</h2>
    <form method="post">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="assign_member">

      <label for="team_id">Team</label>
      <select id="team_id" name="team_id" required>
        <?php foreach ($teams as $team): ?>
          <option value="<?= (int) $team['id'] ?>"><?= e($team['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label for="user_id">User</label>
      <select id="user_id" name="user_id" required>
        <?php foreach ($users as $u): ?>
          <option value="<?= (int) $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</option>
        <?php endforeach; ?>
      </select>

      <label for="role_in_team">Role in Team</label>
      <input id="role_in_team" name="role_in_team" value="Member" maxlength="120">

      <button type="submit">Assign</button>
    </form>
  </article>
</section>

<section class="card">
  <h2>Teams</h2>
  <?php if (!$teams): ?>
    <p>No teams created yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Created By</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($teams as $team): ?>
            <tr>
              <td><?= (int) $team['id'] ?></td>
              <td><?= e($team['name']) ?></td>
              <td><?= e($team['description']) ?></td>
              <td><?= e($team['creator_name'] ?? '-') ?></td>
              <td><?= e($team['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Team Members</h2>
  <?php if (!$members): ?>
    <p>No team assignments yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Team</th>
            <th>User</th>
            <th>Role</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($members as $member): ?>
            <tr>
              <td><?= e($member['team_name']) ?></td>
              <td><?= e($member['user_name']) ?></td>
              <td><?= e($member['role_in_team']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php';
