<?php
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
                // Add or update fee type
                $type_name = $_POST['type_name'];
                $amount = $_POST['amount'];
                $description = $_POST['description'];

                if ($_POST['action'] === 'add') {
                    $stmt = $conn->prepare("
                        INSERT INTO fee_types (type_name, amount, description)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$type_name, $amount, $description]);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE fee_types 
                        SET type_name = ?, amount = ?, description = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$type_name, $amount, $description, $_POST['id']]);
                }
            } elseif ($_POST['action'] === 'delete') {
                // Delete fee type
                $stmt = $conn->prepare("DELETE FROM fee_types WHERE id = ?");
                $stmt->execute([$_POST['id']]);
            }
        }
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get all fee types
$stmt = $conn->query("SELECT * FROM fee_types ORDER BY type_name");
$fee_types = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Types - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Fee Types</h2>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeTypeModal">
                            <i class="fas fa-plus"></i> Add Fee Type
                        </button>
                        <a href="index.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Fee Types Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Fee Type</th>
                                        <th>Amount</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($fee_types)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No fee types defined</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($fee_types as $type): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($type['type_name']); ?></td>
                                                <td>₹<?php echo number_format($type['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($type['description']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                            onclick="editFeeType(<?php echo htmlspecialchars(json_encode($type)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger"
                                                            onclick="deleteFeeType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['type_name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Fee Type Modal -->
    <div class="modal fade" id="addFeeTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Fee Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Fee Type Name</label>
                            <input type="text" name="type_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" class="form-control" required
                                   min="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Fee Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Fee Type Modal -->
    <div class="modal fade" id="editFeeTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Fee Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Fee Type Name</label>
                            <input type="text" name="type_name" id="edit_type_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" name="amount" id="edit_amount" class="form-control" required
                                   min="0" step="0.01">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Fee Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Fee Type Form -->
    <form id="deleteFeeTypeForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Fee Type
        function editFeeType(type) {
            document.getElementById('edit_id').value = type.id;
            document.getElementById('edit_type_name').value = type.type_name;
            document.getElementById('edit_amount').value = type.amount;
            document.getElementById('edit_description').value = type.description;
            
            new bootstrap.Modal(document.getElementById('editFeeTypeModal')).show();
        }

        // Delete Fee Type
        function deleteFeeType(id, name) {
            if (confirm(`Are you sure you want to delete the fee type "${name}"?`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteFeeTypeForm').submit();
            }
        }
    </script>
</body>
</html>
