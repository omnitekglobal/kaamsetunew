<?php

requireRole('super_admin', 'team_leader');
$pdo = getDb();

$managedScope = managedRoleScope($user['role']);
$allowedToCreate = allowedRolesToCreate($user['role']);
$rolesForEdit = rolesEditableBy($user['role']);

$message = '';
$error = '';

// Optional: referral_code for staff users (added by migration 006_users_referral_code.sql)
$hasUserReferralCode = false;
$hasUserLanguageCol = false;
$hasUserVillageCol = false;
$hasUserStateCol = false;
$hasUserLandmarkCol = false;
$hasUserAadhaarCol = false;
$hasUserCreatedByCol = false;
$hasUserLastLoginDateCol = false;
$hasUserLastLoginTimeCol = false;
$hasUserPincodeCol = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'referral_code'");
    $hasUserReferralCode = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'language'");
    $hasUserLanguageCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'village'");
    $hasUserVillageCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'state'");
    $hasUserStateCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'landmark'");
    $hasUserLandmarkCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'aadhaar_no'");
    $hasUserAadhaarCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_by'");
    $hasUserCreatedByCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login_date'");
    $hasUserLastLoginDateCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_login_time'");
    $hasUserLastLoginTimeCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'pincode'");
    $hasUserPincodeCol = $stmt && $stmt->rowCount() > 0;
} catch (Throwable $e) {}

// List of team leaders for assigning staff (super_admin only)
$teamLeadersForAssign = [];
if ($user['role'] === 'super_admin') {
    try {
        $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'team_leader' AND is_active = 1 ORDER BY name");
        $teamLeadersForAssign = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $e) {
        $teamLeadersForAssign = [];
    }
}

// Delete: only within scope (super can delete team_leader; team_leader can delete staff)
if (canCreateDeleteUsers($user['role']) && isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id === $user['id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($target && canManageUser($user['role'], $target, $user['id'])) {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            $message = 'User deleted.';
        } else {
            $error = 'You cannot delete that user.';
        }
    }
}

// Create / Update: role must be in allowed set
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'create';
    if ($action === 'assign_team_leader') {
        // Super admin can (re)assign a staff member to a specific team leader.
        if ($user['role'] !== 'super_admin' || !$hasUserCreatedByCol) {
            $error = 'You are not allowed to assign staff.';
        } else {
            $staffId = (int) ($_POST['staff_id'] ?? 0);
            $teamLeaderId = (int) ($_POST['team_leader_id'] ?? 0);
            if (!$staffId || !$teamLeaderId) {
                $error = 'Staff and team leader are required.';
            } else {
                // Validate roles
                $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
                $stmt->execute([$staffId]);
                $staffRow = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ? AND is_active = 1');
                $stmt->execute([$teamLeaderId]);
                $tlRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$staffRow || ($staffRow['role'] ?? '') !== 'staff') {
                    $error = 'Selected user is not a staff member.';
                } elseif (!$tlRow || ($tlRow['role'] ?? '') !== 'team_leader') {
                    $error = 'Selected team member is not a team leader.';
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET created_by = ? WHERE id = ?');
                    $stmt->execute([$teamLeaderId, $staffId]);
                    $message = 'Staff assigned to team leader successfully.';
                }
            }
        }
        // Skip the regular create/update logic for this action.
    } elseif ($action === 'change_role') {
        // Quick change role from list (admin only, within scope).
        if (!canCreateDeleteUsers($user['role'])) {
            $error = 'You are not allowed to change user roles.';
        } else {
            $targetId = (int) ($_POST['user_id'] ?? 0);
            $newRole = trim($_POST['role'] ?? '');
            if (!$targetId || $newRole === '') {
                $error = 'User and role are required.';
            } elseif (!in_array($newRole, $rolesForEdit, true)) {
                $error = 'You cannot assign that role.';
            } else {
                $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
                $stmt->execute([$targetId]);
                $target = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$target) {
                    $error = 'User not found.';
                } elseif (!canManageUser($user['role'], $target, $user['id'])) {
                    $error = 'You cannot change that user\'s role.';
                } else {
                    $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $targetId]);
                    $message = 'Role updated to ' . roleLabel($newRole) . '.';
                }
            }
        }
    } elseif ($action === 'resend_verification') {
        require_once __DIR__ . '/../../includes/whatsapp.php';
        $targetId = (int) ($_POST['user_id'] ?? 0);
        
        $stmt = $pdo->prepare('SELECT id, phone, is_verified FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$target) {
            $error = 'User not found.';
        } elseif ((int)$target['is_verified'] === 1) {
            $error = 'This user is already verified.';
        } elseif (empty($target['phone'])) {
            $error = 'User does not have a phone number to send WhatsApp.';
        } else {
            // Generate a fresh token
            $newToken   = bin2hex(random_bytes(32));
            $expiresAt  = date('Y-m-d H:i:s', time() + 86400);

            $pdo->prepare('UPDATE users SET verification_token = ?, verification_token_expires_at = ? WHERE id = ?')
                ->execute([$newToken, $expiresAt, $targetId]);
            $sent = sendWhatsAppVerification($target['phone'], $newToken);
            if ($sent) {
                $message = 'Verification link sent via WhatsApp to ' . htmlspecialchars($target['phone']) . '.';
            } else {
                $error = 'Failed to send WhatsApp message. Please check integration.';
            }
        }
    } else {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $id = (int) ($_POST['id'] ?? 0);
    $language = trim($_POST['language'] ?? '');
    $village = trim($_POST['village'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $aadhaar = trim($_POST['aadhaar_no'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');

    if ($action === 'create' && !in_array($role, $allowedToCreate, true)) $role = $allowedToCreate[0] ?? 'staff';
    if (!$name || !$phone) {
        $error = 'Name and phone are required.';
    } elseif ($action === 'create' && strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        if ($action === 'update' && $id) {
            $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existing) {
                $error = 'User not found.';
            } elseif (!canManageUser($user['role'], $existing, $user['id'])) {
                $error = 'You cannot edit that user.';
            } else {
                if (!in_array($role, $rolesForEdit, true)) $role = $existing['role'];

                // Email may be empty in UI; synthesize if needed.
                if ($email === '') {
                    $digits = preg_replace('/\D/', '', $phone);
                    if ($digits === '') {
                        $error = 'Phone number is required to generate an email.';
                    } else {
                        $email = 'user_' . $role . '_' . $digits . '@auto.kaamsetu';
                    }
                }
                if ($error) {
                    // fall through to display error
                } else {
                    $sql = 'UPDATE users SET name = ?, email = ?, phone = ?, role = ?, is_active = ?';
                    $params = [$name, $email, $phone ?: null, $role, $is_active];
                    if ($hasUserLanguageCol) {
                        $sql .= ', language = ?';
                        $params[] = $language !== '' ? $language : null;
                    }
                    if ($hasUserVillageCol) {
                        $sql .= ', village = ?';
                        $params[] = $village !== '' ? $village : null;
                    }
                    if ($hasUserStateCol) {
                        $sql .= ', state = ?';
                        $params[] = $state !== '' ? $state : null;
                    }
                    if ($hasUserLandmarkCol) {
                        $sql .= ', landmark = ?';
                        $params[] = $landmark !== '' ? $landmark : null;
                    }
                    if ($hasUserAadhaarCol) {
                        $sql .= ', aadhaar_no = ?';
                        $params[] = $aadhaar !== '' ? $aadhaar : null;
                    }
                    if ($hasUserPincodeCol) {
                        $sql .= ', pincode = ?';
                        $params[] = $pincode !== '' ? $pincode : null;
                    }
                if ($password !== '') {
                    $sql .= ', password = ?';
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }
                $params[] = $id;
                $pdo->prepare($sql . ' WHERE id = ?')->execute($params);
                $message = 'User updated.';
                }
            }
        } elseif ($action === 'create' && canCreateDeleteUsers($user['role'])) {
            // Email may be empty in UI; synthesize if needed.
            if ($email === '') {
                $digits = preg_replace('/\D/', '', $phone);
                if ($digits === '') {
                    $error = 'Phone number is required to generate an email.';
                } else {
                    $email = 'user_' . $role . '_' . $digits . '@auto.kaamsetu';
                }
            }
            if (!$error) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = 'Email already registered.';
                } else {
                    $columns = ['name', 'email', 'password', 'phone', 'role', 'is_active'];
                    $values = [$name, $email, password_hash($password, PASSWORD_DEFAULT), $phone ?: null, $role, 1];

                    if ($hasUserReferralCode) {
                        $referralCode = null;
                        if ($role === 'staff') {
                            try {
                                $referralCode = 'STF' . strtoupper(bin2hex(random_bytes(3)));
                            } catch (Throwable $e) {
                                $referralCode = 'STF' . strtoupper(substr(md5(uniqid((string) $email, true)), 0, 6));
                            }
                        }
                        $columns[] = 'referral_code';
                        $values[] = $referralCode;
                    }
                    if ($hasUserLanguageCol) {
                        $columns[] = 'language';
                        $values[] = $language !== '' ? $language : null;
                    }
                    if ($hasUserVillageCol) {
                        $columns[] = 'village';
                        $values[] = $village !== '' ? $village : null;
                    }
                    if ($hasUserStateCol) {
                        $columns[] = 'state';
                        $values[] = $state !== '' ? $state : null;
                    }
                    if ($hasUserLandmarkCol) {
                        $columns[] = 'landmark';
                        $values[] = $landmark !== '' ? $landmark : null;
                    }
                    if ($hasUserAadhaarCol) {
                        $columns[] = 'aadhaar_no';
                        $values[] = $aadhaar !== '' ? $aadhaar : null;
                    }
                    if ($hasUserPincodeCol) {
                        $columns[] = 'pincode';
                        $values[] = $pincode !== '' ? $pincode : null;
                    }
                    if ($hasUserCreatedByCol) {
                        $columns[] = 'created_by';
                        $assignTeamLeaderId = (int) ($_POST['team_leader_id'] ?? 0);
                        if ($role === 'staff' && $user['role'] === 'super_admin' && $assignTeamLeaderId > 0) {
                            $values[] = $assignTeamLeaderId;
                        } else {
                            $values[] = (int) $user['id'];
                        }
                    }

                    $placeholders = implode(',', array_fill(0, count($columns), '?'));
                    $sql = 'INSERT INTO users (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';
                    $pdo->prepare($sql)->execute($values);
                    $message = 'User created.';
                }
            }
        }
    }
    }
}

$search = trim($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$allowedRoles = userListRoleFilters($user['role']);
$filterRole = null;
if ($roleFilter !== '' && in_array($roleFilter, $allowedRoles, true)) {
    $filterRole = $roleFilter;
} elseif ($managedScope !== null) {
    $filterRole = $managedScope;
}

$perPage = 20;
$page = max(1, (int) ($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

$countSql = 'SELECT COUNT(*) FROM users WHERE 1=1';
$countParams = [];
if ($filterRole !== null) {
    $countSql .= ' AND role = ?';
    $countParams[] = $filterRole;
}
// Team leaders should only see their own staff (created_by = team leader id), if column exists.
if ($hasUserCreatedByCol && $user['role'] === 'team_leader' && $filterRole === 'staff') {
    $countSql .= ' AND created_by = ?';
    $countParams[] = (int) $user['id'];
}
if ($search !== '') {
    $countSql .= ' AND (name LIKE ? OR email LIKE ?)';
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
}
$stmt = $countParams ? $pdo->prepare($countSql) : $pdo->query($countSql);
$stmt->execute($countParams);
$totalUsers = (int) $stmt->fetchColumn();

$sql = 'SELECT id, name, email, phone, role, is_active, is_verified, created_at'
    . ($hasUserPincodeCol ? ', pincode' : '')
    . ($hasUserReferralCode ? ', referral_code' : '')
    . ($hasUserLanguageCol ? ', language' : '')
    . ($hasUserVillageCol ? ', village' : '')
    . ($hasUserStateCol ? ', state' : '')
    . ($hasUserLandmarkCol ? ', landmark' : '')
    . ($hasUserAadhaarCol ? ', aadhaar_no' : '')
    . ($hasUserCreatedByCol ? ', created_by' : '')
    . ($hasUserLastLoginDateCol ? ', last_login_date' : '')
    . ($hasUserLastLoginTimeCol ? ', last_login_time' : '')
    . ' FROM users WHERE 1=1';
$params = [];
if ($filterRole !== null) {
    $sql .= ' AND role = ?';
    $params[] = $filterRole;
}
if ($hasUserCreatedByCol && $user['role'] === 'team_leader' && $filterRole === 'staff') {
    $sql .= ' AND created_by = ?';
    $params[] = (int) $user['id'];
}
if ($search !== '') {
    $sql .= ' AND (name LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= ' ORDER BY id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
$stmt = $params ? $pdo->prepare($sql) : $pdo->query($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitleRole = $filterRole !== null ? roleLabel($filterRole) : 'All';
?>
<div class="page-header">
    <h1>Users: <?= htmlspecialchars($pageTitleRole) ?></h1>
    <?php if (canCreateDeleteUsers($user['role'])): ?>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('userModal').classList.add('open')">Add User</button>
    <?php endif; ?>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="get" class="toolbar">
    <input type="hidden" name="page" value="users">
    <?php if ($roleFilter !== ''): ?><input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter) ?>"><?php endif; ?>
    <input type="text" name="search" placeholder="Search name or email" value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-secondary">Search</button>
</form>
<?php
$paginationQueryParams = ['page' => 'users'];
if ($search !== '') $paginationQueryParams['search'] = $search;
if ($roleFilter !== '') $paginationQueryParams['role'] = $roleFilter;
?>
<?php if ($filterRole !== null): ?>
<p class="text-muted">Showing <?= htmlspecialchars($pageTitleRole) ?> only.</p>
<?php endif; ?>
<?php
// Safety: ensure all PHP blocks are properly closed for this file.
// (Closes any remaining open control structure if one was left unclosed above.)
?>
<div class="card overflow-x">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <?php if ($hasUserPincodeCol): ?><th>Pincode</th><?php endif; ?>
                <?php if ($hasUserReferralCode): ?><th>Referral Code</th><?php endif; ?>
                <?php if ($hasUserLanguageCol): ?><th>Language</th><?php endif; ?>
                <?php if ($hasUserVillageCol): ?><th>Village</th><?php endif; ?>
                <?php if ($hasUserStateCol): ?><th>State</th><?php endif; ?>
                <?php if ($hasUserLandmarkCol): ?><th>Landmark</th><?php endif; ?>
                <?php if ($hasUserAadhaarCol): ?><th>Aadhaar</th><?php endif; ?>
                <?php if ($hasUserCreatedByCol && $filterRole === 'staff'): ?><th>Created by</th><?php endif; ?>
                <?php if ($user['role'] === 'super_admin' && $hasUserLastLoginDateCol): ?><th>Last Login Date</th><?php endif; ?>
                <?php if ($user['role'] === 'super_admin' && $hasUserLastLoginTimeCol): ?><th>Last Login Time</th><?php endif; ?>
                <th>Role</th>
                <th>Status</th>
                <th>Verified</th>
                <th>Created</th>
                <?php if (canCreateDeleteUsers($user['role'])): ?><th>Actions</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                    <?php if ($hasUserPincodeCol): ?>
                        <td><?= htmlspecialchars($u['pincode'] ?? '-') ?></td>
                    <?php endif; ?>
                    <?php if ($hasUserReferralCode): ?>
                        <td><?= htmlspecialchars($u['referral_code'] ?? '-') ?></td>
                    <?php endif; ?>
                    <?php if ($hasUserLanguageCol): ?>
                        <td><?= htmlspecialchars($u['language'] ?? '-') ?></td>
                    <?php endif; ?>
                    <?php if ($hasUserVillageCol): ?>
                        <td><?= htmlspecialchars($u['village'] ?? '-') ?></td>
                    <?php endif; ?>
                    <?php if ($hasUserStateCol): ?>
                        <td><?= htmlspecialchars($u['state'] ?? '-') ?></td>
                    <?php endif; ?>
                    <?php if ($hasUserLandmarkCol): ?>
                        <td><?= htmlspecialchars($u['landmark'] ?? '-') ?></td>
                    <?php endif; ?>
                    <?php if ($hasUserAadhaarCol): ?>
                        <td><?= htmlspecialchars($u['aadhaar_no'] ?? '-') ?></td>
                    <?php endif; ?>
                    <?php if ($hasUserCreatedByCol && $filterRole === 'staff'): ?>
                        <td>
                            <?php
                            if (!empty($u['created_by'])) {
                                $cbId = (int) $u['created_by'];
                                $cb = null;
                                static $createdByCache = [];
                                if (isset($createdByCache[$cbId])) {
                                    $cb = $createdByCache[$cbId];
                                } else {
                                    $stmtCb = $pdo->prepare('SELECT name, role FROM users WHERE id = ?');
                                    $stmtCb->execute([$cbId]);
                                    $cb = $stmtCb->fetch(PDO::FETCH_ASSOC) ?: null;
                                    $createdByCache[$cbId] = $cb;
                                }
                                if ($cb) {
                                    echo htmlspecialchars($cb['name'] . ' (' . roleLabel($cb['role']) . ')');
                                } else {
                                    echo '-';
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'super_admin' && $hasUserLastLoginDateCol): ?>
                        <td><?= htmlspecialchars($u['last_login_date'] ?? '-') ?></td>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'super_admin' && $hasUserLastLoginTimeCol): ?>
                        <td><?= htmlspecialchars($u['last_login_time'] ?? '-') ?></td>
                    <?php endif; ?>
                    <td>
                        <?php if (canCreateDeleteUsers($user['role']) && canManageUser($user['role'], $u, $user['id'])): ?>
                            <form method="post" style="display:inline;" class="change-role-form">
                                <input type="hidden" name="_action" value="change_role">
                                <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                <select name="role" onchange="this.form.submit();" style="min-width:120px;">
                                    <?php foreach ($rolesForEdit as $r): ?>
                                        <option value="<?= htmlspecialchars($r) ?>" <?= ($u['role'] ?? '') === $r ? 'selected' : '' ?>><?= htmlspecialchars(roleLabel($r)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        <?php else: ?>
                            <span class="badge"><?= htmlspecialchars(roleLabel($u['role'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u['is_active'] ? 'Active' : 'Inactive' ?></td>
                    <td>
                        <?php if ((int)$u['is_verified'] === 1): ?>
                            <span class="badge" style="background:#22c55e;color:#fff;">Yes</span>
                        <?php else: ?>
                            <span class="badge" style="background:#ef4444;color:#fff;">No</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($u['created_at']) ?></td>
                    <?php if (canCreateDeleteUsers($user['role'])): ?>
                        <td>
                            <?php if ($u['role'] === 'staff'): ?>
                                <a href="?page=users&role=staff&view=<?= (int) $u['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                <?php if ($user['role'] === 'super_admin' && !empty($teamLeadersForAssign)): ?>
                                    <form method="post" style="display:inline-block; margin-left:4px;">
                                        <input type="hidden" name="_action" value="assign_team_leader">
                                        <input type="hidden" name="staff_id" value="<?= (int) $u['id'] ?>">
                                        <select name="team_leader_id" required style="min-width:120px;">
                                            <option value="">Assign TL</option>
                                            <?php foreach ($teamLeadersForAssign as $tl): ?>
                                                <option value="<?= (int) $tl['id'] ?>" <?= !empty($u['created_by']) && (int)$u['created_by'] === (int)$tl['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tl['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="?page=users&edit=<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline">Edit</a>
                            <?php if ((int) $u['id'] !== $user['id']): ?>
                                <a href="?page=users&delete=<?= (int) $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">Delete</a>
                            <?php endif; ?>
                            <?php if (!(int)$u['is_verified']): ?>
                                <form method="post" style="display:inline-block; margin-left:4px;">
                                    <input type="hidden" name="_action" value="resend_verification">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" title="Resend WhatsApp Link">Send Link</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    $paginationTotal = $totalUsers;
    $paginationPage = $page;
    $paginationPerPage = $perPage;
    require __DIR__ . '/../includes/pagination.php';
    ?>
</div>

<?php
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editUser = null;
if ($editId && canCreateDeleteUsers($user['role'])) {
    $selectSql = 'SELECT id, name, email, phone, role, is_active'
        . ($hasUserPincodeCol ? ', pincode' : '')
        . ($hasUserLanguageCol ? ', language' : '')
        . ($hasUserVillageCol ? ', village' : '')
        . ($hasUserStateCol ? ', state' : '')
        . ($hasUserLandmarkCol ? ', landmark' : '')
        . ($hasUserAadhaarCol ? ', aadhaar_no' : '')
        . ' FROM users WHERE id = ?';
    $stmt = $pdo->prepare($selectSql);
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}
$viewId = isset($_GET['view']) ? (int) $_GET['view'] : 0;
$viewUser = null;
if ($viewId && canCreateDeleteUsers($user['role'])) {
    $selectSql = 'SELECT id, name, email, phone, role, is_active, created_at'
        . ($hasUserPincodeCol ? ', pincode' : '')
        . ($hasUserLanguageCol ? ', language' : '')
        . ($hasUserVillageCol ? ', village' : '')
        . ($hasUserStateCol ? ', state' : '')
        . ($hasUserLandmarkCol ? ', landmark' : '')
        . ($hasUserAadhaarCol ? ', aadhaar_no' : '')
        . ($hasUserCreatedByCol ? ', created_by' : '')
        . ' FROM users WHERE id = ?';
    $stmt = $pdo->prepare($selectSql);
    $stmt->execute([$viewId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && canManageUser($user['role'], $row, $user['id'])) {
        $viewUser = $row;
    }
}
$modalOpen = !empty($editUser);
?>
<div id="userModal" class="modal <?= $modalOpen ? 'open' : '' ?>">
    <div class="modal-content">
        <h2><?= $editUser ? 'Edit User' : 'Add User' ?></h2>
        <form method="post">
            <input type="hidden" name="_action" value="<?= $editUser ? 'update' : 'create' ?>">
            <?php if ($editUser): ?><input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>"><?php endif; ?>
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($editUser['name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" <?= $editUser ? '' : '' ?>>
            </div>
            <div class="form-group">
                <label>Password <?= $editUser ? '(leave blank to keep)' : '*' ?></label>
                <input type="password" name="password" <?= $editUser ? '' : 'required' ?> minlength="8">
            </div>
            <div class="form-group">
                <label>Phone *</label>
                <input type="text" name="phone" required value="<?= htmlspecialchars($editUser['phone'] ?? '') ?>">
            </div>
            <?php if ($hasUserPincodeCol): ?>
            <div class="form-group">
                <label>Pincode</label>
                <input type="text" name="pincode" value="<?= htmlspecialchars($editUser['pincode'] ?? '') ?>">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label>Language</label>
                <input type="text" name="language" value="<?= htmlspecialchars($editUser['language'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Village</label>
                <input type="text" name="village" value="<?= htmlspecialchars($editUser['village'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>State</label>
                <input type="text" name="state" value="<?= htmlspecialchars($editUser['state'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Landmark</label>
                <input type="text" name="landmark" value="<?= htmlspecialchars($editUser['landmark'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Aadhaar No.</label>
                <input type="text" name="aadhaar_no" value="<?= htmlspecialchars($editUser['aadhaar_no'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="userModalRoleSelect">
                    <?php $rolesForDropdown = $editUser ? $rolesForEdit : $allowedToCreate; ?>
                    <?php foreach ($rolesForDropdown as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= ($editUser['role'] ?? $r) === $r ? 'selected' : '' ?>><?= htmlspecialchars(roleLabel($r)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($user['role'] === 'super_admin' && $hasUserCreatedByCol && !empty($teamLeadersForAssign)): ?>
            <div class="form-group" id="userModalTeamLeaderGroup" style="display:none;">
                <label>Assign Team Leader</label>
                <select name="team_leader_id" id="userModalTeamLeaderSelect">
                    <option value="">-- Select Team Leader --</option>
                    <?php foreach ($teamLeadersForAssign as $tl): ?>
                        <option value="<?= (int) $tl['id'] ?>"><?= htmlspecialchars($tl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if ($editUser): ?>
            <div class="form-group">
                <label><input type="checkbox" name="is_active" value="1" <?= ($editUser['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label>
            </div>
            <?php endif; ?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $editUser ? 'Update' : 'Create' ?></button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('userModal').classList.remove('open'); window.location.href='?page=users'">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php if ($user['role'] === 'super_admin' && $hasUserCreatedByCol && !empty($teamLeadersForAssign)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var roleSelect = document.getElementById('userModalRoleSelect');
    var tlGroup = document.getElementById('userModalTeamLeaderGroup');
    if (!roleSelect || !tlGroup) return;
    function toggleTl() {
        tlGroup.style.display = roleSelect.value === 'staff' ? '' : 'none';
    }
    toggleTl();
    roleSelect.addEventListener('change', toggleTl);
});
</script>
<?php endif; ?>
<?php if ($viewUser): ?>
<?php
// Resolve creator, team leader, and super admin chain for staff detail view
$creator = null;
$teamLeaderCreator = null;
$superAdminCreator = null;
if (!empty($viewUser['created_by'] ?? null)) {
    $cbId = (int) $viewUser['created_by'];
    $stmtCb = $pdo->prepare('SELECT id, name, role, created_by FROM users WHERE id = ?');
    $stmtCb->execute([$cbId]);
    $creator = $stmtCb->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($creator) {
        if (($creator['role'] ?? '') === 'team_leader') {
            $teamLeaderCreator = $creator;
        }
        if (($creator['role'] ?? '') === 'super_admin') {
            $superAdminCreator = $creator;
        } elseif (!empty($creator['created_by'])) {
            $saId = (int) $creator['created_by'];
            $stmtSa = $pdo->prepare('SELECT id, name, role FROM users WHERE id = ? AND role = ?');
            $stmtSa->execute([$saId, 'super_admin']);
            $superAdminCreator = $stmtSa->fetch(PDO::FETCH_ASSOC) ?: null;
        }
    }
}
?>
<div id="userDetailsModal" class="modal open">
    <div class="modal-content">
        <h2>Staff Details</h2>
        <div class="form-group">
            <label>Name</label>
            <input type="text" value="<?= htmlspecialchars($viewUser['name'] ?? '') ?>" disabled>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="text" value="<?= htmlspecialchars($viewUser['email'] ?? '') ?>" disabled>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" value="<?= htmlspecialchars($viewUser['phone'] ?? '-') ?>" disabled>
        </div>
        <?php if ($hasUserPincodeCol): ?>
        <div class="form-group">
            <label>Pincode</label>
            <input type="text" value="<?= htmlspecialchars($viewUser['pincode'] ?? '-') ?>" disabled>
        </div>
        <?php endif; ?>
        <?php if ($hasUserLanguageCol): ?>
        <div class="form-group">
            <label>Language</label>
            <input type="text" value="<?= htmlspecialchars($viewUser['language'] ?? '-') ?>" disabled>
        </div>
        <?php endif; ?>
        <?php if ($hasUserVillageCol): ?>
        <div class="form-group">
            <label>Village</label>
            <input type="text" value="<?= htmlspecialchars($viewUser['village'] ?? '-') ?>" disabled>
        </div>
        <?php endif; ?>
        <?php if ($hasUserStateCol): ?>
        <div class="form-group">
            <label>State</label>
            <input type="text" value="<?= htmlspecialchars($viewUser['state'] ?? '-') ?>" disabled>
        </div>
        <?php endif; ?>
        <?php if ($hasUserLandmarkCol): ?>
        <div class="form-group">
            <label>Landmark</label>
            <input type="text" value="<?= htmlspecialchars($viewUser['landmark'] ?? '-') ?>" disabled>
        </div>
        <?php endif; ?>
        <?php if ($hasUserAadhaarCol): ?>
        <div class="form-group">
            <label>Aadhaar No.</label>
            <input type="text" value="<?= htmlspecialchars($viewUser['aadhaar_no'] ?? '-') ?>" disabled>
        </div>
        <?php endif; ?>
        <div class="form-group">
            <label>Team Leader</label>
            <input type="text" value="<?= $teamLeaderCreator ? htmlspecialchars($teamLeaderCreator['name'] . ' (Team Leader)') : '-' ?>" disabled>
        </div>
        <div class="form-group">
            <label>Super Admin</label>
            <input type="text" value="<?= $superAdminCreator ? htmlspecialchars($superAdminCreator['name'] . ' (Super Admin)') : '-' ?>" disabled>
        </div>
        <div class="form-actions">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('userDetailsModal').classList.remove('open'); window.location.href='?page=users<?= $roleFilter ? '&role=' . urlencode($roleFilter) : '' ?>'">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>
