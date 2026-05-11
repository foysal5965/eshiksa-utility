<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';
check_login();

// ==========================================
//          REPORT GENERATION LOGIC
// ==========================================
$statuses = ['Pending', 'Yes', 'No', 'No PG', 'Inactive'];

// 1. KPI & At A Glance Stats
$overall_stats = array_fill_keys($statuses, 0);
$total_tracking_rows = 0;

$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM events WHERE is_archived = 0 GROUP BY status");
while ($row = $stmt->fetch()) {
    $overall_stats[$row['status']] = $row['count'];
    $total_tracking_rows += $row['count'];
}

$completion_rate = $total_tracking_rows > 0 ? round(($overall_stats['Yes'] / $total_tracking_rows) * 100, 1) : 0;
$no_pg_and_inactive = $overall_stats['No PG'] + $overall_stats['Inactive'];

// Total unique active Master Events
$total_events = $pdo->query("SELECT COUNT(*) FROM global_events WHERE is_archived = 0")->fetchColumn();

// 2. Data for Charts
$chart_status_data = [
    'labels' => $statuses,
    'data' => array_values($overall_stats)
];

// Bar Chart Data
$team_labels = [];
$team_data_yes = [];
$team_data_no = [];
$team_data_pending = [];

$stmt = $pdo->query("
    SELECT t.name as team_name, e.status, COUNT(*) as count 
    FROM events e
    JOIN institutes i ON e.institute_id = i.id
    JOIN team_members m ON i.member_id = m.id
    JOIN teams t ON m.team_id = t.id
    WHERE e.is_archived = 0
    GROUP BY t.id, e.status
");

$raw_team_data = [];
while ($row = $stmt->fetch()) {
    $team = $row['team_name'];
    if(!in_array($team, $team_labels)) $team_labels[] = $team;
    $raw_team_data[$team][$row['status']] = $row['count'];
}

foreach($team_labels as $team) {
    $team_data_yes[] = $raw_team_data[$team]['Yes'] ?? 0;
    $team_data_no[] = $raw_team_data[$team]['No'] ?? 0;
    $team_data_pending[] = $raw_team_data[$team]['Pending'] ?? 0;
}

// 3. Detailed Table Data & Critical No Data
$detailed_query = "
    SELECT e.id, e.updated_at, e.event_name, e.status, e.notes,
           i.name as college_name, c.name as course_name,
           m.name as member_name, t.name as team_name
    FROM events e
    JOIN institutes i ON e.institute_id = i.id
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN team_members m ON i.member_id = m.id
    LEFT JOIN teams t ON m.team_id = t.id
    WHERE e.is_archived = 0
    ORDER BY c.name ASC, e.event_name ASC, i.name ASC
";
$detailed_events = $pdo->query($detailed_query)->fetchAll();

// 4. Group Data
$critical_no_events = [];
$events_grouped = [];
$teams_grouped = [];
$members_grouped = [];

foreach($detailed_events as $ev) {
    $evt_name = $ev['course_name'] . ' - ' . $ev['event_name'];
    $tm_name = $ev['team_name'] ?: 'Unassigned Team';
    $mem_name = $ev['member_name'] ?: 'Unassigned Person';

    if ($ev['status'] === 'No') {
        $critical_no_events[] = $ev;
    }

    $events_grouped[$evt_name][] = $ev;
    $teams_grouped[$tm_name][] = $ev;
    $members_grouped[$mem_name][] = $ev;
}

function getGroupStats($group_data) {
    $stats = ['Total' => 0, 'Yes' => 0, 'No' => 0, 'Pending' => 0, 'No PG' => 0, 'Inactive' => 0];
    foreach($group_data as $row) {
        $stats['Total']++;
        if(isset($stats[$row['status']])) $stats[$row['status']]++;
    }
    return $stats;
}

require_once 'includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
    :root {
        --bg-main: #f4f7f6; --bg-panel: #ffffff; --text-main: #333333;
        --text-muted: #6c757d; --border-color: #e9ecef;
        --shadow: 0 4px 10px rgba(0,0,0,0.08); --header-bg: #f8f9fa; --hover-bg: #f1f3f5;
    }

    [data-theme="dark"] {
        --bg-main: #121212; --bg-panel: #1e1e1e; --text-main: #e0e0e0;
        --text-muted: #a0a0a0; --border-color: #333333;
        --shadow: 0 4px 10px rgba(0,0,0,0.4); --header-bg: #2d2d2d; --hover-bg: #383838;
    }

    body { background-color: var(--bg-main); color: var(--text-main); transition: all 0.3s ease;}

    .pdf-exporting { background: white !important; color: black !important; }
    .pdf-exporting .section-panel, .pdf-exporting .chart-panel, .pdf-exporting .kpi-card { box-shadow: none; border: 1px solid #ccc; }
    
    .crm-dashboard { max-width: 1400px; margin: 20px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 25px; }
    .dash-topbar { display: flex; justify-content: space-between; align-items: center; background: var(--bg-panel); padding: 15px 20px; border-radius: 8px; box-shadow: var(--shadow); }
    .dash-title { margin: 0; font-size: 24px; font-weight: bold; }
    
    .dash-controls { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn { padding: 8px 15px; border-radius: 6px; font-weight: bold; cursor: pointer; border: none; transition: opacity 0.2s;}
    .btn:hover { opacity: 0.9; }
    .btn-theme { background: #6c757d; color: white; }
    .btn-csv { background: #28a745; color: white; }
    .btn-pdf { background: #dc3545; color: white; }

    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; }
    .kpi-card { background: var(--bg-panel); padding: 20px; border-radius: 10px; box-shadow: var(--shadow); border-left: 5px solid #007bff;}
    .kpi-card h4 { margin: 0 0 10px 0; color: var(--text-muted); font-size: 13px; text-transform: uppercase;}
    .kpi-card .value { font-size: 36px; font-weight: bold; margin: 0; color: var(--text-main); }
    
    .border-success { border-color: #28a745; } .border-warning { border-color: #ffc107; }
    .border-danger { border-color: #dc3545; } .border-info { border-color: #17a2b8; }

    .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media (max-width: 900px) { .charts-grid { grid-template-columns: 1fr; } }
    .chart-panel { background: var(--bg-panel); padding: 20px; border-radius: 10px; box-shadow: var(--shadow); }
    .chart-title { margin:0; font-size: 18px; font-weight: bold; color: var(--text-main);}
    .chart-wrapper { position: relative; height: 300px; width: 100%; margin-top: 15px;}

    .section-panel { background: var(--bg-panel); padding: 20px; border-radius: 10px; box-shadow: var(--shadow); }
    .section-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 15px;}
    
    .accordion { border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 10px; overflow: hidden; page-break-inside: avoid; }
    .accordion-header { background: var(--header-bg); padding: 15px 20px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;}
    .accordion-title { font-size: 16px; font-weight: bold; }
    .accordion-stats { display: flex; gap: 8px; flex-wrap: wrap; }
    
    .accordion-body { padding: 0; display: none; background: var(--bg-main); border-top: 1px solid var(--border-color); }
    .accordion-body.active { display: block; }
    .toggle-icon { font-size: 12px; transition: transform 0.3s; display: inline-block; margin-right: 5px; }
    .active .toggle-icon { transform: rotate(180deg); }

    .stat-badge { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; color: #fff; white-space: nowrap;}
    .bg-total { background: #333; } .bg-pending { background: #17a2b8; } .bg-yes { background: #28a745; }
    .bg-no { background: #dc3545; } .bg-nopg { background: #fd7e14; } .bg-inactive { background: #6c757d; }

    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { background: rgba(0,0,0,0.05); padding: 10px 15px; text-align: left; }
    td { padding: 10px 15px; border-bottom: 1px solid var(--border-color); }
    
    .color-Pending { color: #17a2b8; font-weight: bold;} .color-Yes { color: #28a745; font-weight: bold;}
    .color-No { color: #dc3545; font-weight: bold;} .color-NoPG { color: #fd7e14; font-weight: bold;}
    .color-Inactive { color: #6c757d; font-weight: bold;}

    .danger-panel { border-left: 5px solid #dc3545; }
    .remark-box { background: #fff3cd; color: #856404; padding: 6px 10px; border-radius: 4px; border-left: 3px solid #ffc107; font-style: italic;}

    /* Event Separation Headers with Flexbox for Badges */
    .event-sub-header { 
        background: #e9ecef; 
        padding: 8px 15px; 
        font-size: 14px; 
        font-weight: bold; 
        color: #0056b3; 
        border-top: 1px solid var(--border-color); 
        border-bottom: 1px solid var(--border-color); 
        margin-top: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    [data-theme="dark"] .event-sub-header { background: #2a2a2a; color: #4da3ff;}
    .sub-header-stats { display: flex; gap: 6px; flex-wrap: wrap; }

    /* PDF PREVIEW MODAL STYLES */
    .preview-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9998; }
    .preview-modal { display: none; position: fixed; top: 5%; left: 5%; width: 90%; height: 90%; background: #fff; z-index: 9999; border-radius: 8px; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
    .preview-header { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #ddd; }
    .preview-frame { flex-grow: 1; border: none; width: 100%; height: 100%; background: #e9ecef; }
</style>

<div class="crm-dashboard" id="printableArea">

    <div class="dash-topbar" id="noPrintControls">
        <h1 class="dash-title">📈 CRM Reports & Analytics</h1>
        <div class="dash-controls">
            <button class="btn btn-pdf" id="btnFullPdf" onclick="exportSection('printableArea', 'Full_Dashboard.pdf', false, this)">📄 Export Full PDF</button>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card border-info">
            <h4>Active Events</h4>
            <div class="value"><?php echo $total_events; ?></div>
        </div>
        <div class="kpi-card border-success">
            <h4>Completed (Yes)</h4>
            <div class="value"><?php echo $overall_stats['Yes']; ?></div>
        </div>
        <div class="kpi-card border-success">
            <h4>Completion Rate</h4>
            <div class="value"><?php echo $completion_rate; ?>%</div>
        </div>
        <div class="kpi-card border-warning">
            <h4>Pending</h4>
            <div class="value"><?php echo $overall_stats['Pending']; ?></div>
        </div>
        <div class="kpi-card border-danger">
            <h4>Blocked (No)</h4>
            <div class="value"><?php echo $overall_stats['No']; ?></div>
        </div>
        <div class="kpi-card" style="border-color: #fd7e14;">
            <h4>No PG / Inactive</h4>
            <div class="value"><?php echo $no_pg_and_inactive; ?></div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-panel">
            <div class="section-header" style="border:none; margin:0; padding:0;">
                <h3 class="chart-title">Event Status Distribution</h3>
            </div>
            <div class="chart-wrapper">
                <canvas id="statusPieChart"></canvas>
            </div>
        </div>
        <div class="chart-panel">
            <div class="section-header" style="border:none; margin:0; padding:0;">
                <h3 class="chart-title">Team Performance Overview</h3>
            </div>
            <div class="chart-wrapper">
                <canvas id="teamBarChart"></canvas>
            </div>
        </div>
    </div>

    <div class="section-panel danger-panel" id="criticalArea">
        <div class="section-header">
            <h3 class="chart-title" style="color: #dc3545;">⚠️ Critical Action Needed: Blocked Events</h3>
            <button class="btn btn-pdf" id="btnCritPdf" onclick="exportSection('criticalArea', 'Blocked_Events_Report.pdf', false, this)">📄 Export Blocked PDF</button>
        </div>
        <?php if(count($critical_no_events) > 0): ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr><th>Course & Event</th><th>College</th><th>Responsible</th><th>Reason / Remark</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($critical_no_events as $ev): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ev['course_name']); ?></strong><br><?php echo htmlspecialchars($ev['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($ev['college_name']); ?></td>
                            <td><?php echo htmlspecialchars($ev['member_name'] ?? 'Unassigned'); ?><br><small style="color:#666;"><?php echo htmlspecialchars($ev['team_name']); ?></small></td>
                            <td>
                                <?php if(!empty($ev['notes'])): ?>
                                    <div class="remark-box"><?php echo htmlspecialchars($ev['notes']); ?></div>
                                <?php else: ?>
                                    <span style="color:#999; font-style:italic;">No remark provided.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: #28a745; font-weight: bold; text-align: center; margin: 10px 0;">🎉 Great news! No events are currently blocked.</p>
        <?php endif; ?>
    </div>

    <div class="section-panel" id="eventWiseArea">
        <div class="section-header">
            <h3 class="chart-title">📌 Event-Wise Details</h3>
            <button class="btn btn-pdf" id="btnEvtPdf" onclick="exportSection('eventWiseArea', 'Event_Wise_Blocked_Report.pdf', true, this)">📄 Export 'No' Status PDF</button>
        </div>
        <?php foreach($events_grouped as $group_name => $events): $stats = getGroupStats($events); ?>
        <div class="accordion">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <div class="accordion-title"><span class="toggle-icon">▼</span> <?php echo htmlspecialchars($group_name); ?></div>
                <div class="accordion-stats">
                    <span class="stat-badge bg-total">Total: <?php echo $stats['Total']; ?></span>
                    <?php if($stats['Yes'] > 0): ?><span class="stat-badge bg-yes">Yes: <?php echo $stats['Yes']; ?></span><?php endif; ?>
                    <?php if($stats['Pending'] > 0): ?><span class="stat-badge bg-pending">Pending: <?php echo $stats['Pending']; ?></span><?php endif; ?>
                    <?php if($stats['No'] > 0): ?><span class="stat-badge bg-no">No: <?php echo $stats['No']; ?></span><?php endif; ?>
                    <?php if($stats['No PG'] > 0): ?><span class="stat-badge bg-nopg">No PG: <?php echo $stats['No PG']; ?></span><?php endif; ?>
                    <?php if($stats['Inactive'] > 0): ?><span class="stat-badge bg-inactive">Inactive: <?php echo $stats['Inactive']; ?></span><?php endif; ?>
                </div>
            </div>
            <div class="accordion-body">
                <table>
                    <tr><th>College</th><th>Responsible</th><th>Status</th><th>Remark</th></tr>
                    <?php foreach($events as $ev): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ev['college_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($ev['member_name'] ?? '-'); ?></td>
                        <td class="color-<?php echo str_replace(' ', '', $ev['status']); ?>"><?php echo $ev['status']; ?></td>
                        <td style="font-style:italic; max-width:200px;"><?php echo htmlspecialchars($ev['notes'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="section-panel" id="teamWiseArea">
        <div class="section-header">
            <h3 class="chart-title">👥 Team-Wise Details</h3>
            <button class="btn btn-pdf" id="btnTeamPdf" onclick="exportSection('teamWiseArea', 'Team_Wise_Blocked_Report.pdf', true, this)">📄 Export 'No' Status PDF</button>
        </div>
        <?php foreach($teams_grouped as $group_name => $events): $stats = getGroupStats($events); 
            $team_events_separated = [];
            foreach($events as $ev) {
                $evt_str = $ev['course_name'] . ' - ' . $ev['event_name'];
                $team_events_separated[$evt_str][] = $ev;
            }
        ?>
        <div class="accordion">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <div class="accordion-title">
                    <span class="toggle-icon">▼</span> 
                    <span style="font-size: 18px; color: #0056b3; font-weight: 800;"><?php echo htmlspecialchars($group_name); ?></span>
                </div>
               
            </div>
            <div class="accordion-body" style="padding-bottom: 10px;">
                <?php foreach($team_events_separated as $evt_name => $te_events): 
                    // Calculate specific stats for THIS event only
                    $sub_stats = getGroupStats($te_events);
                ?>
                    <div class="event-sub-header">
                        <div>📂 <?php echo htmlspecialchars($evt_name); ?></div>
                        <div class="sub-header-stats">
                            <span class="stat-badge bg-total">Total: <?php echo $sub_stats['Total']; ?></span>
                            <?php if($sub_stats['Yes'] > 0): ?><span class="stat-badge bg-yes">Yes: <?php echo $sub_stats['Yes']; ?></span><?php endif; ?>
                            <?php if($sub_stats['Pending'] > 0): ?><span class="stat-badge bg-pending">Pending: <?php echo $sub_stats['Pending']; ?></span><?php endif; ?>
                            <?php if($sub_stats['No'] > 0): ?><span class="stat-badge bg-no">No: <?php echo $sub_stats['No']; ?></span><?php endif; ?>
                            <?php if($sub_stats['No PG'] > 0): ?><span class="stat-badge bg-nopg">No PG: <?php echo $sub_stats['No PG']; ?></span><?php endif; ?>
                            <?php if($sub_stats['Inactive'] > 0): ?><span class="stat-badge bg-inactive">Inactive: <?php echo $sub_stats['Inactive']; ?></span><?php endif; ?>
                        </div>
                    </div>
                    <table>
                        <tr><th style="width: 20%;">Member</th><th style="width: 35%;">College</th><th style="width: 15%;">Status</th><th style="width: 30%;">Remark</th></tr>
                        <?php foreach($te_events as $ev): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ev['member_name'] ?? '-'); ?></strong></td>
                            <td><?php echo htmlspecialchars($ev['college_name']); ?></td>
                            <td class="color-<?php echo str_replace(' ', '', $ev['status']); ?>"><?php echo $ev['status']; ?></td>
                            <td style="font-style:italic; max-width:200px;"><?php echo htmlspecialchars($ev['notes'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="section-panel" id="memberWiseArea">
        <div class="section-header">
            <h3 class="chart-title">👤 Individual Member Details</h3>
            <button class="btn btn-pdf" id="btnMemPdf" onclick="exportSection('memberWiseArea', 'Member_Wise_Blocked_Report.pdf', true, this)">📄 Export 'No' Status PDF</button>
        </div>
        <?php foreach($members_grouped as $group_name => $events): $stats = getGroupStats($events); 
            $member_events_separated = [];
            foreach($events as $ev) {
                $evt_str = $ev['course_name'] . ' - ' . $ev['event_name'];
                $member_events_separated[$evt_str][] = $ev;
            }
        ?>
        <div class="accordion">
            <div class="accordion-header" onclick="toggleAccordion(this)">
                <div class="accordion-title">
                    <span class="toggle-icon">▼</span> 
                    <span style="font-size: 17px; color: #17a2b8; font-weight: 800;"><?php echo htmlspecialchars($group_name); ?></span>
                </div>
              
            </div>
            <div class="accordion-body" style="padding-bottom: 10px;">
                <?php foreach($member_events_separated as $evt_name => $te_events): 
                    // Calculate specific stats for THIS event only
                    $sub_stats = getGroupStats($te_events);
                ?>
                    <div class="event-sub-header">
                        <div>📂 <?php echo htmlspecialchars($evt_name); ?></div>
                        <div class="sub-header-stats">
                            <span class="stat-badge bg-total">Total: <?php echo $sub_stats['Total']; ?></span>
                            <?php if($sub_stats['Yes'] > 0): ?><span class="stat-badge bg-yes">Yes: <?php echo $sub_stats['Yes']; ?></span><?php endif; ?>
                            <?php if($sub_stats['Pending'] > 0): ?><span class="stat-badge bg-pending">Pending: <?php echo $sub_stats['Pending']; ?></span><?php endif; ?>
                            <?php if($sub_stats['No'] > 0): ?><span class="stat-badge bg-no">No: <?php echo $sub_stats['No']; ?></span><?php endif; ?>
                            <?php if($sub_stats['No PG'] > 0): ?><span class="stat-badge bg-nopg">No PG: <?php echo $sub_stats['No PG']; ?></span><?php endif; ?>
                            <?php if($sub_stats['Inactive'] > 0): ?><span class="stat-badge bg-inactive">Inactive: <?php echo $sub_stats['Inactive']; ?></span><?php endif; ?>
                        </div>
                    </div>
                    <table>
                        <tr><th style="width: 45%;">College</th><th style="width: 15%;">Status</th><th style="width: 40%;">Remark</th></tr>
                        <?php foreach($te_events as $ev): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($ev['college_name']); ?></strong></td>
                            <td class="color-<?php echo str_replace(' ', '', $ev['status']); ?>"><?php echo $ev['status']; ?></td>
                            <td style="font-style:italic; max-width:200px;"><?php echo htmlspecialchars($ev['notes'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="preview-overlay" id="pdfPreviewOverlay"></div>
<div class="preview-modal" id="pdfPreviewModal">
    <div class="preview-header">
        <h2 style="margin:0; font-size: 20px; color: #333;">📄 PDF Document Preview</h2>
        <div>
            <button class="btn" style="background:#6c757d; color:white; margin-right: 10px;" onclick="closePdfPreview()">Close / Cancel</button>
            <button class="btn" style="background:#28a745; color:white;" onclick="downloadCurrentPdf()">📥 Download PDF</button>
        </div>
    </div>
    <iframe id="pdfPreviewFrame" class="preview-frame" src=""></iframe>
</div>

<script>
    // --- 1. ACCORDION LOGIC ---
    function toggleAccordion(headerElement) {
        const body = headerElement.nextElementSibling;
        body.classList.toggle('active');
        const icon = headerElement.querySelector('.toggle-icon');
        icon.style.transform = body.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0deg)';
    }

    // --- 2. DYNAMIC PDF PREVIEW & EXPORT LOGIC ---
    let currentPdfBlobUrl = null;
    let currentPdfFilename = '';

    function exportSection(elementId, fileName, filterNoOnly, btnElement) {
        const originalText = btnElement.innerText;
        btnElement.innerText = "⏳ Generating Preview...";
        btnElement.disabled = true;

        const element = document.getElementById(elementId);
        const accordions = element.querySelectorAll('.accordion-body');
        const allPdfBtns = document.querySelectorAll('.btn-pdf');
        
        allPdfBtns.forEach(btn => btn.style.display = 'none');
        
        let openAccordions = [];
        let hiddenElements = [];

        accordions.forEach((el, index) => {
            if(el.classList.contains('active')) openAccordions.push(index);
            el.classList.add('active');
            if(el.previousElementSibling.querySelector('.toggle-icon')) {
                el.previousElementSibling.querySelector('.toggle-icon').style.transform = 'rotate(180deg)';
            }
        });

        if(filterNoOnly) {
            // Hide individual rows not matching "No"
            const tables = element.querySelectorAll('table');
            tables.forEach(table => {
                let tableHasNo = false;
                const rows = table.querySelectorAll('tr');
                
                for(let i = 1; i < rows.length; i++) {
                    if(!rows[i].querySelector('.color-No')) {
                        hiddenElements.push(rows[i]);
                        rows[i].style.display = 'none';
                    } else {
                        tableHasNo = true; 
                    }
                }
                
                // If the entire table has no "No" statuses, hide it and its preceding sub-header
                if(!tableHasNo) {
                    hiddenElements.push(table);
                    table.style.display = 'none';
                    
                    const subHeader = table.previousElementSibling;
                    if(subHeader && subHeader.classList.contains('event-sub-header')) {
                        hiddenElements.push(subHeader);
                        subHeader.style.display = 'none';
                    }
                }
            });

            // Clean up empty accordions
            accordions.forEach(body => {
                // If all tables inside this accordion body are hidden, hide the whole accordion
                const visibleTables = Array.from(body.querySelectorAll('table')).filter(t => t.style.display !== 'none');
                if(visibleTables.length === 0) {
                    const accordionWrapper = body.closest('.accordion');
                    if(accordionWrapper) {
                        hiddenElements.push(accordionWrapper);
                        accordionWrapper.style.display = 'none';
                    }
                }
            });
        }

        const originalScroll = window.scrollY;
        window.scrollTo(0, 0);
        
        const opt = {
            margin:       [0.3, 0.3, 0.3, 0.3],
            filename:     fileName,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, scrollY: 0, backgroundColor: '#ffffff' },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
        };

        html2pdf().set(opt).from(element).outputPdf('blob').then(function(blob) {
            
            currentPdfBlobUrl = URL.createObjectURL(blob);
            currentPdfFilename = fileName;

            document.getElementById('pdfPreviewFrame').src = currentPdfBlobUrl;
            document.getElementById('pdfPreviewOverlay').style.display = 'block';
            document.getElementById('pdfPreviewModal').style.display = 'flex';

            allPdfBtns.forEach(btn => btn.style.display = 'inline-block');
            btnElement.innerText = originalText;
            btnElement.disabled = false;
            
            accordions.forEach((el, index) => {
                if(!openAccordions.includes(index)) {
                    el.classList.remove('active');
                    if(el.previousElementSibling.querySelector('.toggle-icon')) {
                        el.previousElementSibling.querySelector('.toggle-icon').style.transform = 'rotate(0deg)';
                    }
                }
            });

            // Restore all hidden elements back to view
            hiddenElements.forEach(el => el.style.display = '');
            
            window.scrollTo(0, originalScroll);
        });
    }

    function closePdfPreview() {
        document.getElementById('pdfPreviewOverlay').style.display = 'none';
        document.getElementById('pdfPreviewModal').style.display = 'none';
        document.getElementById('pdfPreviewFrame').src = '';
    }

    function downloadCurrentPdf() {
        if(currentPdfBlobUrl) {
            const a = document.createElement('a');
            a.href = currentPdfBlobUrl;
            a.download = currentPdfFilename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            closePdfPreview();
        }
    }

    // --- 3. CSV EXPORT ---
    function exportToCSV() {
        let csv = [];
        const rows = document.querySelectorAll("#eventWiseArea table tr");
        
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) {
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ");
                row.push('"' + data.replace(/"/g, '""') + '"');
            }
            csv.push(row.join(","));
        }
        
        const csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
        const downloadLink = document.createElement("a");
        downloadLink.download = "CRM_Data.csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.click();
    }

    // --- 4. THEME ---
    function toggleTheme() {
        const body = document.body;
        if(body.getAttribute('data-theme') === 'dark') {
            body.removeAttribute('data-theme');
            localStorage.setItem('crm_theme', 'light');
            updateChartColors('#333');
        } else {
            body.setAttribute('data-theme', 'dark');
            localStorage.setItem('crm_theme', 'dark');
            updateChartColors('#e0e0e0');
        }
    }
    if(localStorage.getItem('crm_theme') === 'dark') document.body.setAttribute('data-theme', 'dark');

    // --- 5. CHARTS ---
    const textColor = localStorage.getItem('crm_theme') === 'dark' ? '#e0e0e0' : '#333';
    Chart.defaults.color = textColor;

    const ctxPie = document.getElementById('statusPieChart').getContext('2d');
    const pieChart = new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($chart_status_data['labels']); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_status_data['data']); ?>,
                backgroundColor: ['#17a2b8', '#28a745', '#dc3545', '#fd7e14', '#6c757d'],
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
    });

    const ctxBar = document.getElementById('teamBarChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($team_labels); ?>,
            datasets: [
                { label: 'Yes', data: <?php echo json_encode($team_data_yes); ?>, backgroundColor: '#28a745' },
                { label: 'Pending', data: <?php echo json_encode($team_data_pending); ?>, backgroundColor: '#17a2b8' },
                { label: 'No', data: <?php echo json_encode($team_data_no); ?>, backgroundColor: '#dc3545' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { x: { stacked: true }, y: { stacked: true } }
        }
    });

    function updateChartColors(color) {
        Chart.defaults.color = color;
        pieChart.update();
        barChart.options.scales.x.ticks.color = color;
        barChart.options.scales.y.ticks.color = color;
        barChart.update();
    }
</script>

<?php require_once 'includes/footer.php'; ?>