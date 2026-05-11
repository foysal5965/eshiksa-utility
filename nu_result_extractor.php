<?php
// --- STEP 1: PHP SECURITY ---
require_once 'includes/functions.php'; 
check_login(); // 1. Check if user is logged in

// 2. Check permission
if (!user_can('NU_RESULT_EXTRACTOR')) {
    require_once 'includes/header.php';
    echo "<h1>Access Denied</h1><p>You do not have permission to view this tool.</p>";
   
    exit();
}

// --- STEP 2: Include the header
require_once 'includes/header.php';

// --- STEP 3: Add tool-specific styles and override content style
?>
<style>
    /* Override content wrapper */
    .content {
        min-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: var(--bg) !important;
        box-shadow: none !important;
        border: none !important;
    }

    /* --- STYLES FOR CLEAN CSV EXTRACTOR --- */
    :root { 
        --primary: #0f172a; 
        --accent: #2563eb; 
        --bg: #f1f5f9; 
        --border: #cbd5e1; 
    }
    
    .clean-csv-body {
        font-family: 'Segoe UI', system-ui, sans-serif; 
        background: var(--bg); 
        color: #334155; 
        padding: 20px; 
    }
    
    .clean-container { 
        max-width: 1200px; 
        margin: 0 auto; 
        background: white; 
        padding: 25px; 
        border-radius: 10px; 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
    }
    
    .clean-container h1 { 
        margin: 0 0 20px 0; 
        color: var(--primary); 
        font-size: 1.5rem; 
        display: flex; 
        align-items: center; 
        gap: 10px; 
        font-weight: 700;
    }
    
    .upload-zone { 
        border: 2px dashed #94a3b8; 
        border-radius: 8px; 
        padding: 30px; 
        text-align: center; 
        cursor: pointer; 
        background: #f8fafc; 
        transition: 0.2s; 
    }
    .upload-zone:hover { border-color: var(--accent); background: #eff6ff; }
    
    .btn-upload { 
        background: var(--accent); 
        color: white; 
        padding: 10px 20px; 
        border-radius: 6px; 
        border: none; 
        font-weight: 600; 
        cursor: pointer; 
        font-size: 1rem;
    }

    .stats-container {
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
        gap: 15px;
        margin-top: 20px; 
        padding: 15px; 
        background: #f8fafc; 
        border-radius: 8px; 
        border: 1px solid var(--border);
        max-height: 300px; 
        overflow-y: auto;
    }
    .stat-card { 
        background: white; 
        padding: 12px; 
        border-radius: 6px; 
        border: 1px solid #e2e8f0; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.05); 
    }
    .stat-title { 
        font-weight: 700; 
        color: var(--primary); 
        margin-bottom: 8px; 
        border-bottom: 1px solid #f1f5f9; 
        padding-bottom: 4px; 
        text-align: left; 
        font-size: 0.95rem;
    }
    .stat-row { 
        display: flex; 
        justify-content: space-between; 
        font-size: 0.85rem; 
        margin-bottom: 2px; 
    }
    .stat-badge { font-weight: 600; color: #475569; }

    .control-panel { display: none; margin-top: 25px; }
    .panel-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    @media (max-width: 900px) { .panel-grid { grid-template-columns: 1fr; } }

    .config-box { 
        border: 1px solid var(--border); 
        border-radius: 8px; 
        padding: 15px; 
        background: white; 
        text-align: left; 
    }
    .config-box h3 { 
        margin: 0 0 12px 0; 
        font-size: 1rem; 
        color: var(--primary); 
        border-bottom: 2px solid #f1f5f9; 
        padding-bottom: 8px; 
        font-weight: 600;
    }

    .scroll-check { 
        max-height: 200px; 
        overflow-y: auto; 
        display: flex; 
        flex-direction: column; 
        gap: 6px; 
    }
    label.check-item { 
        display: flex; 
        align-items: center; 
        font-size: 0.9rem; 
        cursor: pointer; 
        padding: 2px 0; 
    }
    label.check-item:hover { background: #f8fafc; }
    input[type="checkbox"] { 
        margin-right: 8px; 
        accent-color: var(--accent); 
        transform: scale(1.1); 
    }

    .action-area { 
        margin-top: 25px; 
        text-align: center; 
        border-top: 1px solid var(--border); 
        padding-top: 20px; 
    }
    .btn-download {
        background: #059669; 
        color: white; 
        padding: 14px 40px; 
        border: none; 
        border-radius: 8px;
        font-size: 1.1rem; 
        font-weight: bold; 
        cursor: pointer; 
        transition: 0.2s; 
        box-shadow: 0 4px 6px rgba(5, 150, 105, 0.2);
    }
    .btn-download:hover { background: #047857; transform: translateY(-2px); }

    #msg { margin-top: 10px; font-weight: 500; }
    /* --- END TOOL STYLES --- */
</style>

</div> <div class="clean-csv-body">
    <div class="clean-container">
        <h1>📄 Clean CSV Extractor</h1>
        
        <div class="upload-zone" onclick="document.getElementById('pdfInput').click()">
            <p>Click to select PDF files (Select multiple at once)</p>
            <button class="btn-upload">📂 Browse Files</button>
            <input type="file" id="pdfInput" multiple accept=".pdf" style="display: none" onchange="processFiles()">
            <div id="msg"></div>
        </div>

        <div id="mainInterface" class="control-panel">
            
            <h3>📈 Data Summary (Preview)</h3>
            <div id="statsBoard" class="stats-container"></div>
            <br>

            <div class="panel-grid">
                <div class="config-box">
                    <h3>1. Filter by Department</h3>
                    <div class="scroll-check">
                        <label class="check-item" style="font-weight:bold;">
                            <input type="checkbox" id="selectAllFiles" checked onchange="toggleAll('fileCheck', this)"> Select All
                        </label>
                        <div id="fileList"></div>
                    </div>
                </div>

                <div class="config-box">
                    <h3>2. Filter by Result Type</h3>
                    <div class="scroll-check">
                        <label class="check-item" style="font-weight:bold;">
                            <input type="checkbox" id="selectAllStatus" checked onchange="toggleAll('statusCheck', this)"> Select All
                        </label>
                        <div id="statusList"></div>
                    </div>
                </div>

                <div class="config-box">
                    <h3>3. Select Columns to Export</h3>
                    <div class="scroll-check" id="columnList">
                        <label class="check-item"><input type="checkbox" checked value="roll"> Roll Number</label>
                        <label class="check-item"><input type="checkbox" checked value="reg"> Registration No</label>
                        <label class="check-item"><input type="checkbox" checked value="name"> Student Name</label>
                        <label class="check-item"><input type="checkbox" checked value="resultSummary"> Result Summary</label>
                        <label class="check-item"><input type="checkbox" checked value="sourceFile"> Department (File)</label>
                        <label class="check-item"><input type="checkbox" value="resultDetails"> Full Result String</label>
                    </div>
                </div>
            </div>

            <div class="action-area">
                <button class="btn-download" onclick="downloadData()">⬇️ Download Standard CSV</button>
            </div>
        </div>
    </div>
</div>

<script>
    // --- CRITICAL FIX ---
    // Point the worker to the version loaded in header.php (v3.4.120)
    pdfjsLib.GlobalWorkerOptions.workerSrc = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js`;

    let allData = [];
    let uniqueFiles = new Set();
    let uniqueStatuses = new Set();
    const shortMap = { roll:"Roll", reg:"Reg", name:"Name", resultSummary:"Sum", sourceFile:"Dept", resultDetails:"Det" };

    async function processFiles() {
        const input = document.getElementById('pdfInput');
        if (!input.files.length) return;

        document.getElementById('mainInterface').style.display = 'none';
        document.getElementById('msg').innerText = `⏳ Processing ${input.files.length} files...`;
        allData = [];
        uniqueFiles.clear();
        uniqueStatuses.clear();

        for (let file of input.files) {
            try {
                const blocks = await parsePDF(file);
                const fileName = file.name.replace('.pdf', ''); 
                
                blocks.forEach(b => {
                    b.sourceFile = fileName;
                    allData.push(b);
                    uniqueFiles.add(fileName);
                    if(b.resultSummary) uniqueStatuses.add(b.resultSummary);
                });
            } catch (e) { console.error(e); }
        }

        document.getElementById('msg').innerText = `✅ Loaded ${allData.length} records.`;
        document.getElementById('mainInterface').style.display = 'block';
        
        renderStats();
        renderFilters();
    }

    function renderStats() {
        const board = document.getElementById('statsBoard');
        board.innerHTML = '';
        const stats = {};
        allData.forEach(d => {
            if (!stats[d.sourceFile]) stats[d.sourceFile] = {};
            const status = d.resultSummary || "Unknown";
            stats[d.sourceFile][status] = (stats[d.sourceFile][status] || 0) + 1;
        });

        for (const [file, counts] of Object.entries(stats)) {
            let detailsHtml = '';
            for (const [status, count] of Object.entries(counts)) {
                detailsHtml += `<div class="stat-row"><span>${status}</span> <span class="stat-badge">${count}</span></div>`;
            }
            const card = document.createElement('div');
            card.className = 'stat-card';
            card.innerHTML = `<div class="stat-title">${file}</div>${detailsHtml}`;
            board.appendChild(card);
        }
    }

    function renderFilters() {
        const fList = document.getElementById('fileList');
        const sList = document.getElementById('statusList');
        fList.innerHTML = '';
        sList.innerHTML = '';

        Array.from(uniqueFiles).sort().forEach(f => {
            fList.innerHTML += `<label class="check-item"><input type="checkbox" class="fileCheck" value="${f}" checked> ${f}</label>`;
        });

        Array.from(uniqueStatuses).sort().forEach(s => {
            sList.innerHTML += `<label class="check-item"><input type="checkbox" class="statusCheck" value="${s}" checked> ${s}</label>`;
        });
    }

    // Expose this function globally so the onclick attribute can find it
    window.toggleAll = function(className, mainCheck) {
        document.querySelectorAll('.' + className).forEach(cb => cb.checked = mainCheck.checked);
    }

    // --- CLEAN CSV DOWNLOAD LOGIC ---
    // Expose this globally too
    window.downloadData = function() {
        const selFiles = Array.from(document.querySelectorAll('.fileCheck:checked')).map(c => c.value);
        const selStatus = Array.from(document.querySelectorAll('.statusCheck:checked')).map(c => c.value);
        const selCols = Array.from(document.querySelectorAll('#columnList input:checked')).map(c => c.value);

        if (selFiles.length === 0 || selStatus.length === 0) return alert("Select at least one Department and Result.");
        if (selCols.length === 0) return alert("Select at least one column.");

        const exportData = allData.filter(d => 
            selFiles.includes(d.sourceFile) && 
            selStatus.includes(d.resultSummary)
        );

        if (exportData.length === 0) return alert("No students found matching your filters.");

        // Generate Rows (Standard Quoted CSV, No formulas, No headers)
        const refinedRows = exportData.map(item => {
            return selCols.map(c => {
                let val = (item[c] || '').toString();
                // Escape existing double quotes by doubling them
                val = val.replace(/"/g, '""'); 
                // Wrap value in standard quotes
                return `"${val}"`;
            }).join(',');
        });

        const csvContent = refinedRows.join('\n');

        // Filename Generation
        let fName = "MultiDept";
        if (selFiles.length === uniqueFiles.size) fName = "AllDept";
        else if (selFiles.length === 1) fName = selFiles[0].replace(/\s+/g, '_');

        let sName = "MultiStatus";
        if (selStatus.length === uniqueStatuses.size) sName = "AllRes";
        else if (selStatus.length === 1) sName = selStatus[0].replace(/\s+/g, '');

        let cName = "";
        if (selCols.length >= 5) cName = "FullData";
        else if (selCols.length === 1) cName = shortMap[selCols[0]] + "Only";
        else cName = selCols.map(c => shortMap[c]).join("-");

        const finalFileName = `${fName}_${sName}_${cName}.csv`;

        const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
        saveAs(blob, finalFileName);
    }

    async function parsePDF(file) {
        const data = await file.arrayBuffer();
        const pdf = await pdfjsLib.getDocument(data).promise;
        let fullText = '';
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const content = await page.getTextContent();
            fullText += content.items.map(item => item.str).join('\n') + '\n';
        }
        fullText = fullText.replace(/\r/g, '\n').replace(/\t|\u00A0/g, ' ').replace(/[ ]{2,}/g, ' ');
        
        const entryRegex = /(\d{7})\s+(\d{11})\s+([\s\S]*?)(?=(?:\n\d{7}\s+\d{11})|\n\s*$)/g;
        let matches = [], m;
        while ((m = entryRegex.exec(fullText)) !== null) {
            let [_, roll, reg, tail] = m;
            tail = tail.trim();
            let name = "", resultStr = "";
            let splitIdx = tail.search(/\d{6}=/);
            if (splitIdx !== -1) {
                name = tail.slice(0, splitIdx);
                resultStr = tail.slice(splitIdx);
            } else {
                let lines = tail.split('\n');
                let kwIdx = lines.findIndex(l => /Promoted|Fail|Absent|Improved/i.test(l));
                if(kwIdx !== -1) {
                    name = lines.slice(0, kwIdx).join(' ');
                    resultStr = lines.slice(kwIdx).join(' ');
                } else {
                    name = lines.slice(0,2).join(' ');
                    resultStr = lines.slice(2).join(' ');
                }
            }
            name = name.replace(/\s+/g, ' ').trim();
            resultStr = resultStr.replace(/\s+/g, ' ').trim();

            let summary = '';
            if (/\(c\)/i.test(resultStr) || /Conditional/i.test(resultStr)) summary = 'Conditional Promoted';
            else if (/Not Promoted/i.test(resultStr)) summary = 'Not Promoted';
            else if (/Promoted/i.test(resultStr)) summary = 'Promoted';
            else if (/Improved/i.test(resultStr)) summary = 'Improved';
            else if (/Fail/i.test(resultStr)) summary = 'Fail';
            else if (/Absent/i.test(resultStr)) summary = 'Absent';
            else summary = 'Other';

            matches.push({ roll, reg, name, resultDetails: resultStr, resultSummary: summary });
        }
        return matches;
    }
</script>