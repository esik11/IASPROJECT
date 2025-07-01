<?php
session_start();
include_once '../../../config/database.php';
include_once '../../../config/helpers.php';
include_once '../../../config/permissions.php';
include_once '../../../models/Notification.php';

if (!is_logged_in()) {
    header('Location: ../../auth/login.php');
    exit();
}
if (!has_permission('generate_event_reports')) {
    // Using a simple error message for non-API pages
    die('<h1>403 Forbidden</h1><p>You do not have permission to view this page.</p>');
}

$page_title = "Event Reports";
include_once '../../includes/header.php';

$report_data = [];
$show_results = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    $conn = get_db_connection();
    $sql = "SELECT e.name AS event_name, v.name AS venue_name, e.start_time, e.end_time, e.status, e.budget 
            FROM events e
            JOIN venues v ON e.venue_id = v.id
            WHERE e.start_time >= ? AND e.end_time <= ?";
    
    $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
    $types = "ss";

    if (!empty($status)) {
        $sql .= " AND e.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $report_data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
    $conn->close();

    $show_results = true;
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Event Reports</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Event Reports</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-area me-1"></i>
            Generate Report
        </div>
        <div class="card-body">
            <form id="report-form" method="POST" action="index.php">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="status">Event Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All</option>
                                <option value="Pending" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Rejected') ? 'selected' : ''; ?>>Rejected</option>
                                <option value="Completed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Generate Report</button>
            </form>
        </div>
    </div>

    <div class="card mb-4" id="report-results" style="<?php echo $show_results ? '' : 'display: block;'; ?>">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Report Results
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="report-table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Venue</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Status</th>
                            <th>Budget</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report_data)): ?>
                            <?php foreach ($report_data as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['venue_name']); ?></td>
                                    <td><?php echo htmlspecialchars(format_datetime($row['start_time'])); ?></td>
                                    <td><?php echo htmlspecialchars(format_datetime($row['end_time'])); ?></td>
                                    <td><span class="badge bg-<?php echo get_status_badge_class($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                    <td>$<?php echo htmlspecialchars(number_format($row['budget'], 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php elseif ($show_results): ?>
                            <tr>
                                <td colspan="6" class="text-center">No events found for the selected criteria.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!empty($report_data)): ?>
                <button class="btn btn-success" onclick="exportTableToCSV('event-report.csv')">Export to CSV</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportTableToCSV(filename) {
    var csv = [];
    var rows = document.querySelectorAll("#report-table tr");
    
    for (var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (var j = 0; j < cols.length; j++) 
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        
        csv.push(row.join(","));        
    }

    downloadCSV(csv.join("\n"), filename);
}

function downloadCSV(csv, filename) {
    var csvFile;
    var downloadLink;

    csvFile = new Blob([csv], {type: "text/csv"});
    downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

document.addEventListener('DOMContentLoaded', function () {
    const reportTable = new simpleDatatables.DataTable("#report-table", {
        searchable: true,
        fixedHeight: true,
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?> 