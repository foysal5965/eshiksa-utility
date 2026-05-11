<?php
// --- STEP 1: PHP SECURITY ---
require_once 'includes/functions.php'; 
check_login(); // 1. Check if user is logged in

// 2. Check permission
if (!user_can('HSC_DATA_EXTRACTOR')) {
    require_once 'includes/header.php';
    echo "<h1>Access Denied</h1><p>You do not have permission to view this tool.</p>";
    require_once 'includes/footer.php';
    exit();
}

// --- STEP 2: Include the header
require_once 'includes/header.php';

// --- STEP 3: Add tool-specific styles and override content style
?>
 <style>
        

        .container {
            max-width: 1400px; /* Wider container for table */
            margin: 0 auto;
            background: var(--card-bg);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        h2 { 
            text-align: center; 
            margin-bottom: 25px; 
            color: var(--text-main);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Input Area Styling */
        textarea { 
            width: 100%; 
            height: 250px; 
            padding: 15px; 
            border: 2px solid var(--border-color); 
            border-radius: 12px; 
            font-size: 14px; 
            font-family: monospace;
            resize: vertical; 
            background: #fff; 
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        /* Controls Section */
        .controls-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            margin-top: 20px;
            align-items: start;
        }

        .format-selector {
            background-color: var(--table-head-bg);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .format-selector h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: var(--text-main);
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .radio-label:hover {
            background: rgba(37, 99, 235, 0.05);
        }

        input[type="radio"] {
            accent-color: var(--primary-color);
            transform: scale(1.2);
        }

        /* Action Buttons */
        .action-area {
            display: flex;
            flex-direction: column;
            gap: 10px;
            justify-content: center;
            height: 100%;
        }

        button { 
            padding: 14px 28px; 
            font-size: 16px; 
            font-weight: 600;
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover { 
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(37, 99, 235, 0.3);
        }

        /* Status Bar */
        #status-bar {
            margin-top: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 500;
            display: none;
        }
        .status-success { background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Table Styling */
        #preview { 
            margin-top: 30px; 
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden; /* Contains the scrollbar corners */
            background: white;
            position: relative;
            max-height: 600px;
            display: flex;
            flex-direction: column;
        }

        .table-scroll-wrapper {
            overflow: auto;
            width: 100%;
            height: 100%;
        }

        table { 
            width: max-content; /* Allows table to expand horizontally */
            border-collapse: separate; 
            border-spacing: 0;
            min-width: 100%;
        }

        th, td { 
            padding: 12px 16px;
            text-align: left; 
            font-size: 13px;
            border-bottom: 1px solid var(--border-color);
            border-right: 1px solid var(--border-color);
            white-space: nowrap; /* Prevents wrapping */
        }

        /* Sticky Headers */
        thead {
            position: sticky;
            top: 0;
            z-index: 20;
        }

        th { 
            background-color: var(--table-head-bg); 
            color: var(--text-main);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        /* Zebra Striping & Hover */
        tbody tr:nth-child(even) { background-color: var(--table-stripe); }
        tbody tr:hover { background-color: var(--table-hover) !important; transition: background 0.1s; }

        /* Sticky Columns (Serial & Name) */
        th:nth-child(1), td:nth-child(1) {
            position: sticky;
            left: 0;
            z-index: 10;
            border-right: 2px solid var(--border-color);
        }
        
        /* Specific coloring for sticky columns to cover scrolling content */
        td:nth-child(1) { background-color: #fff; }
        tbody tr:nth-child(even) td:nth-child(1) { background-color: var(--table-stripe); }
        tbody tr:hover td:nth-child(1) { background-color: var(--table-hover); }
        
        th:nth-child(1) { z-index: 30; } /* Higher z-index for corner header */

        /* Name Column Sticky (Optional - uncomment if desired, but can be tricky with widths) 
           currently only locking Serial for safety */

        /* Custom Scrollbar */
        .table-scroll-wrapper::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        .table-scroll-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .table-scroll-wrapper::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 5px;
        }
        .table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Empty State */
        .empty-cell { color: #cbd5e1; font-style: italic; }

        @media (max-width: 768px) {
            .controls-grid { grid-template-columns: 1fr; }
            .container { padding: 15px; }
        }
    </style>

<div class="container">
    <h2>
        <span>🎓</span> Universal Data Extractor V7
    </h2>
    
    <textarea id="inputText" placeholder="Paste your raw PDF text data here..."></textarea>
    
    <div class="controls-grid">
        <div class="format-selector">
            <h3>⚙️ Select Parsing Logic:</h3>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="dataFormat" value="regNoAtStart" checked>
                    <span><strong>Format A:</strong> Reg No. at start of record</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="dataFormat" value="regNoAtEnd">
                    <span><strong>Format B:</strong> Reg No. at end of record</span>
                </label>
                <label class="radio-label">
                    <input type="radio" name="dataFormat" value="jashore">
                    <span><strong>Format C:</strong> Sequential serials (1, 2, 3...), Reg No at the end, multi line</span>
                </label>
            </div>
            
        </div>

        <div class="action-area">
            <button class="btn-primary" style="background-color: #10b981;" onclick="convertData()">
                <span>🚀 Convert Data</span>
            </button>
            <button class="btn-primary" style="background-color: #10b981;" onclick="downloadExcelWrapper()">
                <span>💾 Download Excel</span>
            </button>
        </div>
    </div>

    <div id="status-bar"></div>
    <div id="preview"></div>
</div>


<script>
// Global variable to store processed data for download
let globalProcessedData = [];

/* ---------------------- YOUR CUSTOM NAME MERGING LOGIC ---------------------- */
function processNameLines(nameLines) {
    let candidate = "", father = "", mother = "";
    let pointer = 0;

    // Safety check
    if (!nameLines || nameLines.length === 0) return ["", "", ""];

    if (nameLines.length === 3) {
        candidate = nameLines[0];
        father = nameLines[1];
        mother = nameLines[2];
        return [candidate, father, mother];
    }

    if (
        nameLines.length > 3 &&
        nameLines[0].length >= 11 &&
        nameLines[1].length <= 10
    ) {
        candidate = nameLines[0] + " " + nameLines[1];
        pointer = 2;
    } else {
        candidate = nameLines[0] || "";
        pointer = 1;
    }

    const remaining = nameLines.slice(pointer);

    if (remaining.length === 4) {
        father = remaining[0] + " " + remaining[1];
        mother = remaining[2] + " " + remaining[3];
    } else if (remaining.length === 3) {
        if (remaining[1].length >= 11) {
            father = remaining[0];
            mother = remaining[1] + " " + remaining[2];
        } else {
            father = remaining[0] + " " + remaining[1];
            mother = remaining[2];
        }
    } else if (remaining.length === 2) {
        father = remaining[0];
        mother = remaining[1];
    } else if (remaining.length === 1) {
        father = remaining[0];
        mother = "";
    } else {
        father = "";
        mother = "";
    }

    return [candidate, father, mother];
}

/* ---------------------- Main Controller ---------------------- */
function convertData() {
    const raw = document.getElementById("inputText").value.trim();
    if (!raw) {
        showStatus("Please paste some data first!", "error");
        return;
    }

    const lines = raw.split('\n').map(l => l.trim()).filter(Boolean);

    // Headers
    const data = [
        ["Serial", "Candidate Name", "Father's Name", "Mother's Name", "Gender", "Religion", "Group", "Class Roll",
         "Sub 1", "Sub 2", "Sub 3", "Sub 4", "Sub 5", "Sub 6", "Sub 7", "Sub 8", "Sub 9", "Sub 10", "Sub 11", "Sub 12", "Sub 13"]
    ];

    const selectedFormat = document.querySelector('input[name="dataFormat"]:checked').value;

    try {
        if (selectedFormat === 'regNoAtStart') {
            parseFormatWithRegNoAtStart(lines, data);
        } else if (selectedFormat === 'regNoAtEnd') {
            parseFormatWithRegNoAtEnd(lines, data);
        } else if (selectedFormat === 'jashore') {
            parseJashoreFormat(lines, data);
        }
        
        globalProcessedData = data;
        displayPreview(data);
        
        const count = data.length - 1;
        if(count > 0) {
            showStatus(`✅ Successfully extracted ${count} student records. Review below.`, "success");
        } else {
            showStatus("⚠️ No records matched the selected format. Try a different format.", "error");
        }

    } catch (e) {
        console.error(e);
        showStatus("❌ An error occurred during parsing.", "error");
    }
}

function downloadExcelWrapper() {
    if(!globalProcessedData || globalProcessedData.length <= 1) {
        showStatus("⚠️ No data to download. Please convert first.", "error");
        return;
    }
    downloadExcel(globalProcessedData);
}

function showStatus(msg, type) {
    const el = document.getElementById("status-bar");
    el.style.display = "block";
    el.className = type === "success" ? "status-success" : "status-error";
    el.innerText = msg;
}

/* ---------------------- Format A Parser ---------------------- */
function parseFormatWithRegNoAtStart(lines, data) {
    let startIndex = -1;
    for (let i = 0; i < lines.length - 1; i++) {
        if (/^\d{1,5}$/.test(lines[i]) && /^\d{10}$/.test(lines[i + 1])) {
            startIndex = i;
            break;
        }
    }

    if (startIndex === -1) return;

    let i = startIndex;
    const isStartOfRecord = (idx) => idx < lines.length -1 && /^\d{1,5}$/.test(lines[idx]) && /^\d{10}$/.test(lines[idx + 1]);
    
    const isMetadata = (line) => {
        const clean = line.trim();
        const up = clean.toUpperCase().replace(/\s/g, '');
        return ["MALE", "FEMALE", "ISLAM", "HINDUISM", "CHRISTIANITY", "BUDDHISM", "SCIENCE", "HUMANITIES", "BUSINESSSTUDIES", "BUSINESS"].includes(up) 
                || /^[A-Z]$/.test(clean);
    };

    while (i < lines.length) {
        if (isStartOfRecord(i)) {
            const serial = lines[i];
            i += 2; 

            const nameBuffer = [];
            while (i < lines.length && !isStartOfRecord(i) && !isMetadata(lines[i]) && !/^\d/.test(lines[i])) {
                nameBuffer.push(lines[i]);
                i++;
            }

            const [candidate, father, mother] = processNameLines(nameBuffer);

            let gender = "", religion = "", group = "", classRoll = "";
            let subjectString = "";
            
            const remainingRecordLines = [];
            while (i < lines.length && !isStartOfRecord(i)) {
                remainingRecordLines.push(lines[i]);
                i++;
            }
            
            const groupIndex = remainingRecordLines.findIndex(l => ["SCIENCE", "HUMANITIES", "BUSINESSSTUDIES",'BUSINESS'].includes(l.toUpperCase().replace(/\s/g, '')));
            if (groupIndex !== -1) {
                group = remainingRecordLines[groupIndex];
                if (groupIndex > 0 && /^\d{1,5}$/.test(remainingRecordLines[groupIndex - 1])) {
                    classRoll = remainingRecordLines[groupIndex - 1];
                }
            }
            
            for (const line of remainingRecordLines) {
                const upper = line.toUpperCase();
                if (!gender && (upper === "MALE" || upper === "FEMALE")) gender = line;
                else if (!religion && ["ISLAM", "HINDUISM", "CHRISTIANITY", "BUDDHISM"].includes(upper)) religion = line;
                if (/\d/.test(line)) subjectString += " " + line;
            }
            
            if (classRoll) subjectString = subjectString.replace(new RegExp(`\\b${classRoll}\\b`), '');
            
            const fixedSubjectString = subjectString.replace(/(\d{3})(?=\d)/g, "$1 ");
            const subjectCodes = (fixedSubjectString.match(/\d{3}/g) || []).map(Number);
            subjectCodes.length = 13;

            data.push([serial, candidate, father, mother, gender, religion, group, classRoll, ...subjectCodes]);
        } else {
            i++;
        }
    }
}

/* ---------------------- Format B Parser ---------------------- */
function parseFormatWithRegNoAtEnd(lines, data) {
    let i = 0;
    
    const isMetadata = (line) => {
        const clean = line.trim();
        const up = clean.toUpperCase().replace(/\s/g, '');
        return ["MALE", "FEMALE", "ISLAM", "HINDUISM", "CHRISTIANITY", "BUDDHISM", "SCIENCE", "HUMANITIES", "BUSINESSSTUDIES", "BUSINESS"].includes(up)
                || /^[A-Z]$/.test(clean); 
    };

    while (i < lines.length) {
        let endOfRecordIndex = -1;
        if (/^\d{1,4}$/.test(lines[i])) {
            for (let j = i + 5; j < Math.min(i + 30, lines.length); j++) {
                if (lines[j] && /^\d{10}$/.test(lines[j])) {
                    endOfRecordIndex = j;
                    break;
                }
            }
        }

        if (endOfRecordIndex !== -1) {
            const recordLines = lines.slice(i, endOfRecordIndex + 1);
            const serial = recordLines[0];

            const nameBuffer = [];
            let k = 1;
            while (k < recordLines.length) {
                const line = recordLines[k];
                if (isMetadata(line) || (/^\d{4,6}$/.test(line) && !line.startsWith("202")) || k === recordLines.length - 1) {
                    break;
                }
                nameBuffer.push(line);
                k++;
            }

            const [candidateName, fatherName, motherName] = processNameLines(nameBuffer);

            let gender = "", religion = "", group = "", classRoll = "";
            let subjectString = "";
            const subjectCodes = [];

            for (; k < recordLines.length; k++) {
                const cleanLine = recordLines[k].trim();
                const upperLine = cleanLine.toUpperCase();
                
                if (upperLine === "MALE" || upperLine === "FEMALE") gender = cleanLine;
                else if (["ISLAM", "HINDUISM", "CHRISTIANITY", "BUDDHISM"].includes(upperLine)) religion = cleanLine;
                else if (["SCIENCE", "HUMANITIES", "BUSINESSSTUDIES", "BUSINESS"].includes(upperLine.replace(/\s/g, ''))) group = cleanLine;
                else if (!classRoll && /^\d{1,6}$/.test(cleanLine) && !cleanLine.startsWith("202")) classRoll = cleanLine;
                else if (/\d{3}/.test(cleanLine)) {
                    if (cleanLine.startsWith("202")) continue;
                    if (/^\d+$/.test(cleanLine) && cleanLine.length !== 3) continue;
                    subjectString += " " + cleanLine;
                }
            }

            const fixedSubjectString = subjectString.replace(/(\d{3})(?=\d)/g, "$1 ");
            const codes = fixedSubjectString.match(/\d{3}/g) || [];
            codes.forEach(code => subjectCodes.push(code));
            subjectCodes.length = 13;
            
            data.push([serial, candidateName, fatherName, motherName, gender, religion, group, classRoll, ...subjectCodes]);
            i = endOfRecordIndex + 1;
        } else {
            i++;
        }
    }
}

/* ---------------------- Format C (Jashore) Parser ---------------------- */
function parseJashoreFormat(lines, data) {
    let i = 0;
    while (i < lines.length) {
        if (/^\d+$/.test(lines[i])) {
            const recordStartIndex = i;
            let recordEndIndex = -1;
            for (let j = recordStartIndex + 1; j < lines.length; j++) {
                if (lines[j].trim() === '-') {
                    recordEndIndex = j;
                    break;
                }
            }

            if (recordEndIndex !== -1) {
                const recordLines = lines.slice(recordStartIndex, recordEndIndex + 1);
                const serial = recordLines[0];
                
                // --- FIX FOR MD. ABDUL MALEK ISSUE ---
                // We use strict equality check on trimmed uppercase string
                // This prevents "MALEK" from triggering the gender detection
                const detailsLineIndex = recordLines.findIndex(l => {
                    const clean = l.trim().toUpperCase();
                    return clean === "MALE" || clean === "FEMALE";
                });
                
                if (detailsLineIndex > 1) {
                    const nameLines = recordLines.slice(1, detailsLineIndex - 2); 
                    
                    const [candidateName, fatherName, motherName] = processNameLines(nameLines);

                    const rawClassRollLine = recordLines[detailsLineIndex - 1]; 
                    const classRoll = (rawClassRollLine.match(/\d+/) || [""])[0];
                    const gender = recordLines[detailsLineIndex];
                    const group = recordLines[detailsLineIndex + 1];

                    const subjectLines = recordLines.slice(detailsLineIndex + 2, recordLines.length - 1);
                    const subjectString = subjectLines.join(" ");
                    const subjectCodes = (subjectString.match(/\d{3}/g) || []).map(Number);
                    subjectCodes.length = 13;

                    data.push([serial, candidateName.trim(), fatherName.trim(), motherName.trim(), gender, "", group, classRoll, ...subjectCodes]);
                }
                i = recordEndIndex + 1;
            } else {
                i++;
            }
        } else {
            i++;
        }
    }
}

/* ---------------------- Preview & Excel ---------------------- */
function displayPreview(data) {
    const preview = document.getElementById("preview");
    
    // Clear previous
    preview.innerHTML = "";

    if (data.length <= 1) {
        preview.innerHTML = "<div style='padding:20px; text-align:center;'><h3>No student data found.</h3></div>";
        return;
    }

    const wrapper = document.createElement("div");
    wrapper.className = "table-scroll-wrapper";

    const table = document.createElement("table");
    const thead = document.createElement("thead");
    const tbody = document.createElement("tbody");

    // Header
    thead.innerHTML = `<tr>${data[0].map(h => `<th>${h}</th>`).join('')}</tr>`;
    
    // Body
    for (let r = 1; r < data.length; r++) {
        const row = document.createElement("tr");
        row.innerHTML = data[r].map(c => {
            if(c === undefined || c === null || c === "") {
                return `<td class="empty-cell">-</td>`;
            }
            return `<td>${c}</td>`;
        }).join('');
        tbody.appendChild(row);
    }

    table.appendChild(thead);
    table.appendChild(tbody);
    wrapper.appendChild(table);
    preview.appendChild(wrapper);
}

function downloadExcel(data) {
    const ws = XLSX.utils.aoa_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Candidates");
    XLSX.writeFile(wb, "parsed_candidate_data.xlsx");
}
</script>