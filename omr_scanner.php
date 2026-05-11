<?php
// --- STEP 1: PHP SECURITY ---
require_once 'includes/functions.php'; 
check_login(); 

if (!user_can('OMR_SCANNER_TOOL')) {
    require_once 'includes/header.php';
    echo "<h1>Access Denied</h1><p>You do not have permission to view this tool.</p>";
    require_once 'includes/footer.php';
    exit();
}

require_once 'includes/header.php';
?>

<style>
    .content {
        min-height: 0 !important;
        padding: 20px !important;
        margin: 0 20px 20px 20px !important;
        background: #f4f7fa !important;
    }
    .omr-container {
        max-width: 1200px; margin: 0 auto;
        background: #fff; padding: 20px;
        border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .omr-header {
        text-align: center; margin-bottom: 20px;
        color: #333; font-weight: 700;
    }
    
    /* Workspace Layout */
    .workspace {
        display: flex; gap: 20px; align-items: flex-start;
    }
    
    /* Left Side: Display Area */
    .canvas-wrapper {
        position: relative;
        border: 2px solid #333;
        background: #222;
        overflow: hidden;
        width: 100%;
        min-height: 500px;
        flex-grow: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* The Image Canvas (Hidden initially) */
    #omrCanvas { 
        display: block; 
        max-width: 100%; 
    }

    /* The Live Video Feed (Hidden initially) */
    #cameraFeed {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: none; /* Hidden by default */
    }

    /* The Resizable Grid Overlay */
    #gridOverlay {
        position: absolute;
        top: 50px; left: 50px;
        width: 200px; height: 400px;
        border: 2px solid red;
        background: rgba(255, 0, 0, 0.1);
        resize: both;
        overflow: hidden;
        cursor: grab;
        z-index: 10;
        display: none; /* Hidden until image is loaded/captured */
    }
    #gridOverlay:active { cursor: grabbing; }

    .grid-cell {
        box-sizing: border-box;
        border: 1px solid rgba(255, 0, 0, 0.5);
        float: left;
    }

    /* Right Side: Controls */
    .settings-panel {
        width: 320px;
        flex-shrink: 0;
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    /* Controls */
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 5px; }
    .form-group input { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    
    .btn-row { display: flex; gap: 5px; margin-bottom: 10px; }
    
    .result-box {
        margin-top: 20px;
        max-height: 300px;
        overflow-y: auto;
    }
    table { width: 100%; border-collapse: collapse; font-size: 13px; background: white; }
    th, td { border: 1px solid #ddd; padding: 6px; text-align: center; }
    th { background: #eee; position: sticky; top: 0; }
    
    .high-conf { background-color: #d4edda; }
    
    @media (max-width: 900px) {
        .workspace { flex-direction: column; }
        .settings-panel { width: 100%; }
    }
</style>

<div class="omr-container">
    <h2 class="omr-header">📷 OMR Camera Scanner</h2>
    
    <div class="workspace">
        
        <div class="canvas-wrapper" id="canvasWrapper">
            <video id="cameraFeed" autoplay playsinline></video>
            <canvas id="omrCanvas"></canvas>
            <div id="gridOverlay"></div>
            <div id="placeholderText" style="color:white;">Select Input Source to Begin</div>
        </div>

        <div class="settings-panel">
            
            <div style="background: #e9ecef; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <label style="font-weight:bold;">1. Input Source</label>
                <div class="btn-row">
                    <button onclick="startCamera()" class="btn" style="flex:1; background:#17a2b8;">📷 Camera</button>
                    <button onclick="document.getElementById('imageInput').click()" class="btn" style="flex:1; background:#6c757d;">📂 Upload</button>
                </div>
                <input type="file" id="imageInput" accept="image/*" style="display:none;">
                
                <button id="captureBtn" onclick="captureImage()" class="btn" style="width:100%; background:#dc3545; display:none; margin-top:5px;">
                    ◉ Capture Photo
                </button>
            </div>

            <h3>2. Grid Configuration</h3>
            <div class="form-group">
                <label>Total Questions (Rows)</label>
                <input type="number" id="numRows" value="20" min="1">
            </div>

            <div class="form-group">
                <label>Options per Question (Cols)</label>
                <input type="number" id="numCols" value="4" min="1">
            </div>

            <div class="form-group">
                <label>Sensitivity (Darkness)</label>
                <input type="range" id="threshold" min="50" max="200" value="130">
            </div>

            <button onclick="updateGridLines()" class="btn" style="width:100%; background:#6c757d; margin-bottom:10px;">Update Grid</button>
            
            <h3>3. Action</h3>
            <p style="font-size:12px; color:#666; margin-bottom:10px;">
                Align the <b>Red Box</b> exactly over the bubbles.
            </p>
            <button onclick="scanOMR()" class="btn" style="width:100%; background:#007bff;">🚀 Scan & Process</button>
            <button onclick="downloadExcel()" class="btn" style="width:100%; background:#28a745; margin-top:10px;" id="dlBtn" disabled>📥 Download Excel</button>

            <div class="result-box">
                <table id="resultTable">
                    <thead><tr><th>Q#</th><th>Marked</th></tr></thead>
                    <tbody><tr><td colspan="2">No scan yet</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    const canvas = document.getElementById('omrCanvas');
    const ctx = canvas.getContext('2d');
    const video = document.getElementById('cameraFeed');
    const overlay = document.getElementById('gridOverlay');
    const captureBtn = document.getElementById('captureBtn');
    const placeholder = document.getElementById('placeholderText');
    
    let stream = null;
    let scanResults = [];

    // --- CAMERA LOGIC ---
    async function startCamera() {
        try {
            // Stop previous stream if any
            stopCamera();
            
            // Request camera (prefer rear camera on mobile)
            stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: "environment",
                    width: { ideal: 1920 },
                    height: { ideal: 1080 } 
                } 
            });
            
            video.srcObject = stream;
            
            // UI Updates
            video.style.display = 'block';
            canvas.style.display = 'none';
            overlay.style.display = 'none'; // Hide grid while framing
            placeholder.style.display = 'none';
            captureBtn.style.display = 'block';
            
        } catch (err) {
            alert("Error accessing camera: " + err.message + "\n(Note: Camera requires HTTPS or localhost)");
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        video.style.display = 'none';
        captureBtn.style.display = 'none';
    }

    function captureImage() {
        // Set canvas size to match video resolution
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Draw current video frame to canvas
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Switch UI back to Canvas mode
        stopCamera();
        canvas.style.display = 'block';
        overlay.style.display = 'block'; // Show grid again
        
        updateGridLines(); // Re-initialize grid
    }

    // --- FILE UPLOAD LOGIC ---
    document.getElementById('imageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if(!file) return;
        
        stopCamera(); // Ensure camera is off
        
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                
                canvas.style.display = 'block';
                overlay.style.display = 'block';
                placeholder.style.display = 'none';
                updateGridLines();
            }
            img.src = event.target.result;
        }
        reader.readAsDataURL(file);
    });

    // --- GRID & SCAN LOGIC (Same as before) ---
    let isDragging = false;
    let startX, startY, initialLeft, initialTop;

    overlay.addEventListener('mousedown', function(e) {
        if (e.offsetX > overlay.offsetWidth - 20 && e.offsetY > overlay.offsetHeight - 20) return;
        isDragging = true;
        startX = e.clientX;
        startY = e.clientY;
        initialLeft = overlay.offsetLeft;
        initialTop = overlay.offsetTop;
        overlay.style.cursor = 'grabbing';
    });

    window.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        overlay.style.left = (initialLeft + (e.clientX - startX)) + 'px';
        overlay.style.top = (initialTop + (e.clientY - startY)) + 'px';
    });

    window.addEventListener('mouseup', () => { isDragging = false; overlay.style.cursor = 'grab'; });

    function updateGridLines() {
        const rows = parseInt(document.getElementById('numRows').value);
        const cols = parseInt(document.getElementById('numCols').value);
        overlay.innerHTML = ''; 
        
        const cellWidth = 100 / cols;
        const cellHeight = 100 / rows;

        for(let r=0; r<rows; r++) {
            for(let c=0; c<cols; c++) {
                const cell = document.createElement('div');
                cell.className = 'grid-cell';
                cell.style.width = cellWidth + '%';
                cell.style.height = cellHeight + '%';
                overlay.appendChild(cell);
            }
        }
    }

    function scanOMR() {
        if (canvas.style.display === 'none') { alert("Please capture or upload an image first."); return; }

        const rows = parseInt(document.getElementById('numRows').value);
        const cols = parseInt(document.getElementById('numCols').value);
        const sensitivity = parseInt(document.getElementById('threshold').value);
        
        const rect = overlay.getBoundingClientRect();
        const canvasRect = canvas.getBoundingClientRect();
        
        const scaleX = canvas.width / canvasRect.width;
        const scaleY = canvas.height / canvasRect.height;

        const gridX = (rect.left - canvasRect.left) * scaleX;
        const gridY = (rect.top - canvasRect.top) * scaleY;
        const gridW = rect.width * scaleX;
        const gridH = rect.height * scaleY;

        const cellW = gridW / cols;
        const cellH = gridH / rows;

        const alphabet = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        scanResults = [];
        let tableHtml = "";

        for(let r=0; r<rows; r++) {
            let bestOption = -1;
            let darkestValue = 255; 

            for(let c=0; c<cols; c++) {
                const sampleX = gridX + (c * cellW) + (cellW * 0.35); // Take center 30%
                const sampleY = gridY + (r * cellH) + (cellH * 0.35);
                const sampleW = cellW * 0.3;
                const sampleH = cellH * 0.3;

                const imageData = ctx.getImageData(sampleX, sampleY, sampleW, sampleH);
                const data = imageData.data;
                let totalBrightness = 0;
                let count = 0;

                for(let i=0; i<data.length; i+=4) {
                    const avg = (data[i] + data[i+1] + data[i+2]) / 3;
                    totalBrightness += avg;
                    count++;
                }
                
                const avgCellBrightness = totalBrightness / count;

                if (avgCellBrightness < darkestValue) {
                    darkestValue = avgCellBrightness;
                    if (avgCellBrightness < sensitivity) {
                        bestOption = c;
                    }
                }
            }

            const qNum = r + 1;
            const marked = (bestOption > -1) ? alphabet[bestOption] : '-';
            const cssClass = (bestOption > -1) ? 'high-conf' : '';
            
            scanResults.push([qNum, marked]);
            tableHtml += `<tr class="${cssClass}"><td>${qNum}</td><td>${marked}</td></tr>`;
        }

        document.querySelector('#resultTable tbody').innerHTML = tableHtml;
        document.getElementById('dlBtn').disabled = false;
    }

    function downloadExcel() {
        if(!scanResults.length) return;
        const data = [['Question No', 'Marked Answer']];
        scanResults.forEach(row => data.push(row));
        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "OMR_Result");
        XLSX.writeFile(wb, "omr_scan_result.xlsx");
    }
</script>

<?php require_once 'includes/footer.php'; ?>