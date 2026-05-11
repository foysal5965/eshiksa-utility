<?php
// --- STEP 1: PHP SECURITY ---
require_once 'includes/functions.php'; 
check_login(); // 1. Check if user is logged in

// 2. Check permission
if (!user_can('REG_MATCHER_TOOL')) {
    require_once 'includes/header.php';
    echo "<h1>Access Denied</h1><p>You do not have permission to view this tool.</p>";
    
    exit();
}

// --- STEP 2: Include the header
require_once 'includes/header.php';

// --- STEP 3: Override default content style & Add Tool Styles
?>
<style>
    /* Override content wrapper */
    .content {
        min-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #f8fafc !important; /* Match tool bg */
        box-shadow: none !important;
        border: none !important;
    }

    /* --- STYLES FOR MATCHER V7 --- */
    :root {
      --primary: #2563eb;
      --success: #16a34a;
      --warning: #d97706;
      --danger: #dc2626;
      --bg: #f8fafc;
      --card: #ffffff;
      --text: #1e293b;
      --border: #e2e8f0;
    }

    .matcher-body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      color: var(--text);
      padding: 40px 20px;
      line-height: 1.5;
    }

    .matcher-container { max-width: 1100px; margin: 0 auto; }
    
    .matcher-card {
      background: var(--card);
      border-radius: 8px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      padding: 30px;
      margin-bottom: 25px;
      border: 1px solid var(--border);
    }

    .matcher-card h2 { margin-top: 0; color: #0f172a; text-align: center; font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; }
    .matcher-card p.subtitle { text-align: center; color: #64748b; margin-top: 0; margin-bottom: 25px; }

    /* Upload Section */
    .upload-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 25px;
      margin-bottom: 25px;
    }
    @media (max-width: 768px) { .upload-grid { grid-template-columns: 1fr; } }

    .file-input-wrapper {
      border: 2px dashed #cbd5e1;
      border-radius: 12px;
      padding: 30px;
      text-align: center;
      position: relative;
      transition: all 0.2s;
      background: #fff;
    }
    .file-input-wrapper:hover { border-color: var(--primary); background: #eff6ff; }
    .file-input-wrapper input {
      position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer;
    }
    .file-icon { font-size: 32px; margin-bottom: 10px; display: block; }
    .file-label { display: block; font-weight: 600; color: #334155; }
    .file-status { margin-top: 10px; font-weight: bold; font-size: 0.9em; min-height: 20px; color: var(--primary); }

    /* Mapping Section */
    .mapping-section {
      display: none; 
      margin-top: 25px;
      background: #f8fafc;
      padding: 25px;
      border-radius: 12px;
      border: 1px solid #cbd5e1;
    }
    
    .mapping-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
    }
    
    .map-col h4 { 
      margin-bottom: 20px; 
      color: var(--primary); 
      border-bottom: 2px solid #e2e8f0; 
      padding-bottom: 10px;
      font-size: 1.1rem; 
    }
    
    .field-row {
      display: grid;
      grid-template-columns: 120px 1fr;
      align-items: center;
      margin-bottom: 15px;
    }
    .field-row label { font-size: 14px; font-weight: 600; color: #475569; }
    .field-row select {
      width: 100%;
      padding: 10px;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      background: white;
      font-size: 14px;
    }

    /* Buttons */
    .action-btn {
      background: var(--primary);
      color: white;
      border: none;
      padding: 14px 24px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      width: 100%;
      margin-top: 25px;
      transition: background 0.2s;
      box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2);
    }
    .action-btn:hover { background: #1d4ed8; transform: translateY(-1px); }

    .btn-group {
      display: flex;
      gap: 15px;
      margin-top: 20px;
      flex-wrap: wrap;
    }

    .download-btn {
      flex: 1;
      border: none;
      padding: 15px 15px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 4px;
      color: white;
      font-size: 14px;
      transition: transform 0.2s;
      min-width: 120px;
    }
    .download-btn:hover { transform: translateY(-2px); }
    .download-btn small { font-weight: 400; opacity: 0.9; font-size: 12px; }
    
    .btn-success { background: var(--success); }
    .btn-success:hover { background: #15803d; }
    .btn-warning { background: var(--warning); }
    .btn-warning:hover { background: #b45309; }
    .btn-danger { background: var(--danger); }
    .btn-danger:hover { background: #b91c1c; }

    /* Table */
    .table-container { overflow-x: auto; margin-top: 20px; border-radius: 8px; border: 1px solid var(--border); }
    .matcher-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .matcher-table th { background: #f1f5f9; font-weight: 600; color: #475569; padding: 12px 16px; border-bottom: 2px solid var(--border); text-align: left;}
    .matcher-table td { padding: 12px 16px; border-bottom: 1px solid var(--border); color: #334155; }
    .matcher-table tr:last-child td { border-bottom: none; }
    .matcher-table tr:hover { background-color: #f8fafc; }

    /* Filter Controls */
    .filter-area {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 25px;
      margin-bottom: 20px;
    }
    @media (max-width: 768px) { .filter-area { grid-template-columns: 1fr; } }

    .filter-box {
      background: #f8fafc;
      padding: 15px;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
    }
    .filter-header {
      font-weight: 600;
      margin-bottom: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: #334155;
    }
    .filter-actions {
      font-size: 0.85em;
      color: var(--primary);
      cursor: pointer;
      text-decoration: underline;
    }
    .checkbox-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      max-height: 200px;
      overflow-y: auto;
    }
    .checkbox-item { 
        display: flex; align-items: center; gap: 8px; 
        font-size: 13px; cursor: pointer; 
        background: white; padding: 6px 10px; 
        border-radius: 6px; border: 1px solid #cbd5e1; 
        transition: border-color 0.2s;
    }
    .checkbox-item:hover { border-color: var(--primary); }
    
    .summary-text { font-size: 1.1rem; margin: 10px 0; }
    /* --- END TOOL STYLES --- */
</style>

</div>

<div class="matcher-body">
    <div class="matcher-container">
      
      <div class="matcher-card">
        <h2> Student Matcher</h2>
        <p class="subtitle">Ambiguous Duplicate Safety System</p>
        
        <div class="upload-grid">
          <div class="file-input-wrapper">
            <input type="file" id="file1" accept=".xlsx, .xls, .csv">
            <span class="file-icon">📂</span>
            <span class="file-label">Upload File 1</span>
            <small style="color: #64748b; display:block; margin-top:4px;">(Contains Roll No)</small>
            <div id="file1Name" class="file-status"></div>
          </div>

          <div class="file-input-wrapper">
            <input type="file" id="file2" accept=".xlsx, .xls, .csv">
            <span class="file-icon">📂</span>
            <span class="file-label">Upload File 2</span>
            <small style="color: #64748b; display:block; margin-top:4px;">(Contains Reg No & Subjects)</small>
            <div id="file2Name" class="file-status"></div>
          </div>
        </div>

        <div id="mappingSection" class="mapping-section">
          <div style="text-align: center; margin-bottom: 20px; background: #e0f2fe; color: #0369a1; padding: 12px; border-radius: 6px; font-size: 0.95rem;">
            ℹ️ <strong>Safety Tip:</strong> If File 1 has duplicate names, we will move them to the "Ambiguous" list unless you select a Father Name column to distinguish them.
          </div>

          <div class="mapping-grid">
            <div class="map-col">
              <h4>File 1 Columns</h4>
              <div class="field-row">
                <label>Roll No:</label>
                <select id="mapF1Roll"></select>
              </div>
              <div class="field-row">
                <label>Name:</label>
                <select id="mapF1Name"></select>
              </div>
              <div class="field-row">
                <label>Father (Opt):</label>
                <select id="mapF1Father"></select>
              </div>
              <div class="field-row">
                <label>Department:</label>
                <select id="mapF1Dept"></select>
              </div>
            </div>

            <div class="map-col">
              <h4>File 2 Columns</h4>
              <div class="field-row">
                <label>Reg No:</label>
                <select id="mapF2Reg"></select>
              </div>
              <div class="field-row">
                <label>Name:</label>
                <select id="mapF2Name"></select>
              </div>
              <div class="field-row">
                <label>Father Name:</label>
                <select id="mapF2Father"></select>
              </div>
              <div class="field-row">
                <label>Department:</label>
                <select id="mapF2Dept"></select>
              </div>
              <div class="field-row">
                <label>Subjects:</label>
                <select id="mapF2Sub"></select>
              </div>
            </div>
          </div>
          <button id="processBtn" class="action-btn">✅ Confirm Mapping & Process</button>
        </div>
      </div>

      <div id="resultSection" class="matcher-card" style="display:none;">
        <h3>📥 Export & Results</h3>
        
        <div class="filter-area">
          <div class="filter-box">
            <div class="filter-header">
              Filter by Department
              <span class="filter-actions" onclick="toggleAll('dept', true)">Select All</span>
            </div>
            <div id="deptCheckboxGrid" class="checkbox-grid"></div>
          </div>
          
          <div class="filter-box">
            <div class="filter-header">
              Include Columns
              <span class="filter-actions" onclick="toggleAll('col', true)">Select All</span>
            </div>
            <div id="colCheckboxGrid" class="checkbox-grid">
              <label class="checkbox-item"><input type="checkbox" class="col-check" value="roll" checked> Roll</label>
              <label class="checkbox-item"><input type="checkbox" class="col-check" value="reg" checked> Reg</label>
              <label class="checkbox-item"><input type="checkbox" class="col-check" value="name" checked> Name</label>
              <label class="checkbox-item"><input type="checkbox" class="col-check" value="father" checked> Father</label>
              <label class="checkbox-item"><input type="checkbox" class="col-check" value="dept" checked> Dept</label>
              <label class="checkbox-item"><input type="checkbox" class="col-check" value="subjects" checked> Subjects</label>
            </div>
          </div>
        </div>

        <div class="btn-group">
          <button id="downloadMatchedBtn" class="download-btn btn-success">
            📥 Matched <small>(Clean Data)</small>
          </button>
          <button id="downloadAmbiguousBtn" class="download-btn btn-danger">
            ⚠️ Ambiguous <small>(Check Manually)</small>
          </button>
          <button id="downloadUnmatchedBtn" class="download-btn btn-warning">
            📥 Unmatched <small>(Not Found)</small>
          </button>
        </div>

        <div style="margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
          <h4>Summary</h4>
          <p class="summary-text">✅ <strong>Matched:</strong> <span id="matchCount">0</span> <small>(Unique Matches)</small></p>
          <p class="summary-text" style="color:var(--danger)">⚠️ <strong>Ambiguous:</strong> <span id="ambiguousCount">0</span> <small>(Name exists in File 1 multiple times. Check "Ambiguous" file)</small></p>
          <p class="summary-text" style="color:var(--warning)">❌ <strong>Unmatched:</strong> <span id="unmatchedCount">0</span> <small>(Not found in File 1)</small></p>
          
          <h4 style="margin-top: 30px;">Preview (First 5 Matches)</h4>
          <div id="previewTable" class="table-container"></div>
        </div>
      </div>

    </div>
</div>

<script>
  let rawFile1 = null, rawFile2 = null;
  let headers1 = [], headers2 = [];
  let file1Rows = [], file2Rows = [];
  
  // Data Buckets
  let matchedData = [];
  let unmatchedData = [];
  let ambiguousData = []; // New bucket for unsafe duplicates

  const file1Input = document.getElementById('file1');
  const file2Input = document.getElementById('file2');
  const mappingSection = document.getElementById('mappingSection');
  
  // File Readers
  file1Input.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if(file) {
      document.getElementById('file1Name').textContent = "✅ " + file.name;
      const data = await parseExcel(file);
      headers1 = data[0] || [];
      file1Rows = data;
      checkBothFiles();
    }
  });

  file2Input.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if(file) {
      document.getElementById('file2Name').textContent = "✅ " + file.name;
      const data = await parseExcel(file);
      headers2 = data[0] || [];
      file2Rows = data;
      checkBothFiles();
    }
  });

  function parseExcel(file) {
    return new Promise((resolve) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        const data = new Uint8Array(e.target.result);
        const wb = XLSX.read(data, {type: 'array'});
        const sheet = wb.Sheets[wb.SheetNames[0]];
        const json = XLSX.utils.sheet_to_json(sheet, {header: 1});
        resolve(json);
      };
      reader.readAsArrayBuffer(file);
    });
  }

  function checkBothFiles() {
    if(file1Rows.length > 0 && file2Rows.length > 0) {
      populateDropdowns();
      mappingSection.style.display = 'block';
    }
  }

  // --- MAPPING LOGIC ---
  function populateDropdowns() {
    const createOpts = (headers, selectId, guessKeywords, isOptional = false) => {
      const select = document.getElementById(selectId);
      select.innerHTML = '';
      let bestMatchIndex = isOptional ? -1 : 0; 
      
      if (isOptional) {
        const opt = document.createElement('option');
        opt.value = -1;
        opt.textContent = "-- Not Present --";
        select.appendChild(opt);
      }

      headers.forEach((h, i) => {
        const opt = document.createElement('option');
        opt.value = i;
        opt.textContent = `[Col ${String.fromCharCode(65+i)}] ${h}`;
        select.appendChild(opt);
        
        if (h) {
          const cleanH = h.toString().toLowerCase().replace(/[^a-z]/g, '');
          if(guessKeywords.some(k => cleanH.includes(k))) {
            bestMatchIndex = i;
          }
        }
      });
      select.value = bestMatchIndex;
    };

    // File 1 Guesses
    createOpts(headers1, 'mapF1Roll', ['roll']);
    createOpts(headers1, 'mapF1Name', ['name', 'student']);
    createOpts(headers1, 'mapF1Father', ['father'], true);
    createOpts(headers1, 'mapF1Dept', ['dept', 'department']);

    // File 2 Guesses
    createOpts(headers2, 'mapF2Reg', ['reg', 'registration']);
    createOpts(headers2, 'mapF2Name', ['name', 'student']);
    createOpts(headers2, 'mapF2Father', ['father']);
    createOpts(headers2, 'mapF2Dept', ['dept', 'department']);
    createOpts(headers2, 'mapF2Sub', ['course', 'code', 'subject']);
  }

  // --- PROCESSING LOGIC ---
  document.getElementById('processBtn').addEventListener('click', () => {
    const idx1 = {
      roll: parseInt(document.getElementById('mapF1Roll').value),
      name: parseInt(document.getElementById('mapF1Name').value),
      father: parseInt(document.getElementById('mapF1Father').value),
      dept: parseInt(document.getElementById('mapF1Dept').value)
    };
    
    const idx2 = {
      reg: parseInt(document.getElementById('mapF2Reg').value),
      name: parseInt(document.getElementById('mapF2Name').value),
      father: parseInt(document.getElementById('mapF2Father').value),
      dept: parseInt(document.getElementById('mapF2Dept').value),
      sub: parseInt(document.getElementById('mapF2Sub').value)
    };

    processData(idx1, idx2);
  });

  function cleanStr(s) {
    return s ? s.toString().toLowerCase().replace(/[^a-z0-9]/g, '') : '';
  }

  function processData(i1, i2) {
    matchedData = [];
    unmatchedData = [];
    ambiguousData = [];
    const depts = new Set();
    
    // Use an Array to store records because keys might not be unique in File 1 (Duplicate Names)
    const f1Map = {}; 

    const useFatherMatch = (i1.father !== -1);

    // 1. Index File 1 (Supporting Duplicates)
    file1Rows.forEach((row, idx) => {
      if(idx === 0) return;
      if(!row[i1.name]) return;

      const namePart = cleanStr(row[i1.name]);
      let key = namePart;

      if (useFatherMatch) {
        const fatherPart = cleanStr(row[i1.father] || '');
        key = namePart + "|" + fatherPart;
      }

      const deptVal = row[i1.dept] ? row[i1.dept].toString().trim() : '';
      
      // Init array if key not exists
      if(!f1Map[key]) f1Map[key] = [];

      f1Map[key].push({
        roll: row[i1.roll],
        name: row[i1.name],
        father: useFatherMatch ? row[i1.father] : '',
        dept: deptVal
      });
      
      if(deptVal) depts.add(deptVal);
    });

    // 2. Match with File 2
    file2Rows.forEach((row, idx) => {
      if(idx === 0) return;
      if(!row[i2.name]) return;

      const namePart = cleanStr(row[i2.name]);
      let key = namePart;

      if (useFatherMatch) {
         const fatherPart = cleanStr(row[i2.father] || '');
         key = namePart + "|" + fatherPart;
      }
      
      const dept2 = row[i2.dept] ? row[i2.dept].toString().trim() : 'Unknown';
      if(dept2) depts.add(dept2); 

      const potentialMatches = f1Map[key];

      if(potentialMatches && potentialMatches.length > 0) {
        
        if (potentialMatches.length === 1) {
          // --- EXACT UNIQUE MATCH ---
          const f1 = potentialMatches[0];
          const finalDept = f1.dept || dept2;

          matchedData.push({
            roll: f1.roll,
            reg: row[i2.reg],
            name: f1.name, 
            father: row[i2.father], 
            dept: finalDept,
            subjects: row[i2.sub]
          });

        } else {
          // --- AMBIGUOUS MATCH (Duplicate Names found) ---
          // We found 2+ people in File 1 with this name. We don't know which one is this Reg No.
          const possibleRolls = potentialMatches.map(m => m.roll).join(", ");
          
          ambiguousData.push({
            roll: "AMBIGUOUS", 
            possibleRolls: possibleRolls, // Special field for ambiguous export
            reg: row[i2.reg],
            name: row[i2.name],
            father: row[i2.father],
            dept: dept2,
            subjects: row[i2.sub]
          });
        }

      } else {
        // --- UNMATCHED ---
        unmatchedData.push({
          roll: "N/A", 
          reg: row[i2.reg],
          name: row[i2.name],
          father: row[i2.father],
          dept: dept2,
          subjects: row[i2.sub]
        });
      }
    });

    // 3. UI Updates
    document.getElementById('resultSection').style.display = 'block';
    document.getElementById('matchCount').textContent = matchedData.length;
    document.getElementById('unmatchedCount').textContent = unmatchedData.length;
    document.getElementById('ambiguousCount').textContent = ambiguousData.length;
    
    // Department Checkboxes
    const deptGrid = document.getElementById('deptCheckboxGrid');
    deptGrid.innerHTML = '';
    Array.from(depts).sort().forEach(d => {
      if(!d) return;
      const label = document.createElement('label');
      label.className = 'checkbox-item';
      label.innerHTML = `<input type="checkbox" class="dept-check" value="${d}" checked> ${d}`;
      deptGrid.appendChild(label);
    });

    renderPreview();
    document.getElementById('resultSection').scrollIntoView({behavior: 'smooth'});
  }

  function renderPreview() {
    const div = document.getElementById('previewTable');
    if(matchedData.length === 0) {
      if(ambiguousData.length > 0) {
        div.innerHTML = '<div style="padding:20px; text-align:center; color:#d97706;">No unique matches found, but found Ambiguous duplicates! Check the Ambiguous list.</div>';
      } else {
        div.innerHTML = '<div style="padding:20px; text-align:center; color:#ef4444;">No matches found.</div>';
      }
      return;
    }
    
    let html = `<table class="matcher-table"><tr><th>Roll</th><th>Reg</th><th>Name</th><th>Father Name</th><th>Dept</th></tr>`;
    matchedData.slice(0, 5).forEach(r => {
      html += `<tr>
        <td>${r.roll||''}</td>
        <td>${r.reg||''}</td>
        <td>${r.name||''}</td>
        <td>${r.father||''}</td>
        <td>${r.dept||''}</td>
      </tr>`;
    });
    html += '</table>';
    div.innerHTML = html;
  }

  // --- DOWNLOAD LOGIC ---
  function getSelectedFilters() {
    const deptChecks = document.querySelectorAll('.dept-check:checked');
    const selectedDepts = Array.from(deptChecks).map(c => c.value);
    
    const colChecks = document.querySelectorAll('.col-check:checked');
    const selectedCols = Array.from(colChecks).map(c => c.value);

    return { selectedDepts, selectedCols };
  }

  function triggerDownload(data, filenamePrefix, isAmbiguous = false) {
    if(data.length === 0) return alert("No data to download!");

    const { selectedDepts, selectedCols } = getSelectedFilters();
    if(selectedDepts.length === 0) return alert("Please select at least one department.");
    
    // Map standard columns
    const headerMap = {
      roll:'Roll No', 
      reg:'Reg No', 
      name:'Student Name', 
      father:'Father Name', 
      dept:'Department', 
      subjects:'Subjects'
    };
    
    // Build CSV Header
    let csvCols = [...selectedCols];
    
    // If printing Ambiguous list, add "Possible Rolls" column automatically
    if(isAmbiguous) {
      csvCols.unshift("possibleRolls"); // Add to front
      headerMap["possibleRolls"] = "Possible Rolls (Ambiguous)";
    }

    let csv = csvCols.map(c => `"${headerMap[c] || c}"`).join(',') + '\n';
    
    data.forEach(r => {
      if(!selectedDepts.includes(r.dept) && r.dept !== "Unknown") return;
      
      const line = csvCols.map(c => {
        let val = (r[c] || '').toString();
        val = val.replace(/"/g, '""');
        if(val.search(/("|,|\n)/g) >= 0) val = `"${val}"`;
        return val;
      });
      csv += line.join(',') + '\n';
    });

    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    const time = new Date().toISOString().slice(0,19).replace(/:/g,"-");
    a.download = `${filenamePrefix}_${time}.csv`;
    a.click();
  }

  document.getElementById('downloadMatchedBtn').addEventListener('click', () => {
    triggerDownload(matchedData, "Matched_Students");
  });

  document.getElementById('downloadUnmatchedBtn').addEventListener('click', () => {
    triggerDownload(unmatchedData, "Unmatched_Students");
  });

  document.getElementById('downloadAmbiguousBtn').addEventListener('click', () => {
    triggerDownload(ambiguousData, "Ambiguous_Students", true);
  });

  window.toggleAll = function(type, forceSelect) {
    const selector = type === 'dept' ? '.dept-check' : '.col-check';
    const checkboxes = document.querySelectorAll(selector);
    const allChecked = Array.from(checkboxes).every(c => c.checked);
    checkboxes.forEach(c => c.checked = !allChecked);
  };

</script>