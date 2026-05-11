<?php
// --- STEP 1: PHP SECURITY ---
require_once 'includes/functions.php'; 
check_login(); // 1. Check if user is logged in

// 2. Check permission
if (!user_can('HSC_ADMISSION_EXTRACTOR')) {
    require_once 'includes/header.php';
    echo "<h1>Access Denied</h1><p>You do not have permission to view this tool.</p>";
   
    exit();
}

// --- STEP 2: Include the header
require_once 'includes/header.php';

// --- STEP 3: Add tool-specific styles and override content style
?>
<style>
    /* This overrides the .content style from header.php */
    .content {
        min-height: 0 !important;
        padding: 20px !important;
        margin: 0 20px 20px 20px !important;
        background: #f4f7fa !important; /* Match tool bg */
        box-shadow: none !important;
        border: none !important;
    }

    /* --- STYLES FOR THIS TOOL --- */
    :root {
        --primary-color: #0b74de;
        --primary-hover: #0a68c7;
        --background-color: #f4f7fa;
        --card-background: #ffffff;
        --text-color: #333;
        --border-color: #dee2e6;
        --success-bg: #e6f7f1;
        --success-border: #b0e5d1;
        --error-bg: #fff1f0;
        --error-border: #ffccc7;
        --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .hsc-container { /* Renamed to avoid conflicts */
        width: 100%;
        max-width: 960px;
        margin: 0 auto;
    }
    .hsc-container h1 {
        text-align: center;
        margin-bottom: 2rem;
        font-weight: 700;
        color: var(--primary-color);
    }
    .hsc-card { /* Renamed to avoid conflicts */
        background: var(--card-background);
        border-radius: 12px;
        box-shadow: var(--shadow);
        padding: 1.5rem 2rem;
        margin-bottom: 1.5rem;
    }
    #upload-area {
        border: 2px dashed var(--border-color);
        border-radius: 8px;
        padding: 2.5rem;
        text-align: center;
        cursor: pointer;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    #upload-area.highlight {
        border-color: var(--primary-color);
        background-color: #f0f8ff;
    }
    #upload-area p { margin: 0.5rem 0; font-size: 1.1rem; }
    #file-input { display: none; }
    #results-section { display: none; }
    .results-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    .result-card {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1.5rem;
        background: var(--card-background);
        transition: box-shadow 0.3s ease;
    }
    .result-card:hover {
        box-shadow: var(--shadow);
    }
    .result-card.success { background: var(--success-bg); border-color: var(--success-border); }
    .result-card.error { background: var(--error-bg); border-color: var(--error-border); }
    .result-header {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 1rem;
    }
    .result-header span {
        font-weight: 400;
        color: #555;
        font-style: italic;
    }
    .stats {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        color: #444;
    }
    .stats strong {
        font-weight: 600;
        color: #000;
    }
    #progress-container { margin-top: 1rem; }
    #progress-bar-bg {
        height: 12px;
        background: #e9ecef;
        border-radius: 6px;
        overflow: hidden;
    }
    #progress-bar {
        height: 100%;
        width: 0%;
        background: var(--primary-color);
        border-radius: 6px;
        transition: width 0.4s ease;
    }
    #progress-text {
        text-align: center;
        margin-top: 0.5rem;
        font-size: 0.9rem;
        font-weight: 500;
    }
    .hsc-card button {
        background-color: var(--primary-color);
        border: none; color: white;
        padding: 10px 18px;
        border-radius: 6px; cursor: pointer;
        font-weight: 600; font-size: 0.9rem;
        transition: background 0.3s ease, transform 0.2s ease;
    }
    .hsc-card button:hover {
        background-color: var(--primary-hover);
        transform: translateY(-2px);
    }
    .hsc-card button.secondary { background-color: #6c757d; }
    .hsc-card button.secondary:hover { background-color: #5a6268; }
    .hsc-card button:disabled { background-color: #adb5bd; cursor: not-allowed; }
    .download-links { display: flex; flex-wrap: wrap; gap: 0.75rem; }
    .download-links a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f8f9fa;
        border: 1px solid var(--border-color);
        padding: 8px 12px;
        border-radius: 6px;
        text-decoration: none;
        color: var(--text-color);
        font-size: 0.85rem;
        font-weight: 500;
        transition: background 0.2s ease, border-color 0.2s ease;
    }
    .download-links a:hover {
        background: #e9ecef;
        border-color: #ced4da;
    }
    /* --- END OF TOOL CSS --- */
</style>

<div class="hsc-container">
    <h1>📄 PDF to Department CSV Extractor</h1>

    <section id="upload-section" class="hsc-card">
        <h3>1. Upload PDF Files</h3>
        <p>Your PDF file names should correspond to the department name (e.g., "Physics.pdf", "Political_Science.pdf").</p>
        <div id="upload-area">
            <input type="file" id="file-input" accept="application/pdf" multiple>
            <p><strong>Drag & drop your PDF files here</strong></p>
            <p>or</p>
            <button onclick="document.getElementById('file-input').click();">Browse Files</button>
        </div>
    </section>

    <section id="results-section" class="hsc-card">
        <h3>2. Processing Results</h3>
        <div id="progress-container">
            <div id="progress-bar-bg"><div id="progress-bar"></div></div>
            <div id="progress-text"></div>
        </div>
        <div style="display: flex; justify-content: flex-end; margin: 1.5rem 0; gap: 0.75rem;">
            <button id="zipBtn" disabled>📦 Download All as ZIP</button>
            <button id="resetBtn" class="secondary">🔄 Process New Files</button>
        </div>
        <div id="results-grid" class="results-grid"></div>
    </section>
</div>

<script>
    (function(){
        // --- DOM Elements ---
        const fileInput = document.getElementById('file-input');
        const uploadArea = document.getElementById('upload-area');
        const uploadSection = document.getElementById('upload-section');
        const resultsSection = document.getElementById('results-section');
        const resultsGrid = document.getElementById('results-grid');
        const progressText = document.getElementById('progress-text');
        const progressBar = document.getElementById('progress-bar');
        const zipBtn = document.getElementById('zipBtn');
        const resetBtn = document.getElementById('resetBtn');

        let generatedCsvContents = {};
        
        // --- CRITICAL FIX ---
        // Use the v3.4.120 worker, which is loaded in header.php
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

        // --- Drag and Drop Logic ---
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.add('highlight'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('highlight'), false);
        });
        uploadArea.addEventListener('drop', e => handleFiles(e.dataTransfer.files), false);
        fileInput.addEventListener('change', e => handleFiles(e.target.files));

        // --- Button Listeners ---
        resetBtn.addEventListener('click', resetApp);
        zipBtn.addEventListener('click', downloadAllAsZip);

        function resetApp() {
            uploadSection.style.display = 'block';
            resultsSection.style.display = 'none';
            resultsGrid.innerHTML = '';
            progressText.innerHTML = '';
            progressBar.style.width = '0%';
            fileInput.value = '';
            generatedCsvContents = {};
            zipBtn.disabled = true;
        }

        function handleFiles(files) {
            if (!files || files.length === 0) return;
            // Filter for PDF files only
            const pdfFiles = Array.from(files).filter(file => file.type === "application/pdf");
            if (pdfFiles.length === 0) {
                alert("No PDF files selected. Please choose files with a .pdf extension.");
                return;
            }
            processFiles(pdfFiles);
        }

        async function processFiles(files) {
            uploadSection.style.display = 'none';
            resultsSection.style.display = 'block';
            resultsGrid.innerHTML = '';
            generatedCsvContents = {};
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const progress = ((i + 1) / files.length) * 100;
                progressText.innerHTML = `Processing ${file.name} (${i + 1}/${files.length})`;
                progressBar.style.width = `${progress}%`;

                try {
                    const text = await extractTextFromPdf(file);
                    const dept = departmentFromFilename(file.name);
                    const parsed = parseTextBlocks(text);

                    // De-duplicate: remove quota rolls from the merit list
                    const quotaRolls = new Set();
                    ['freedom', 'ministry', 'org'].forEach(k => {
                        (parsed[k] || []).forEach(r => quotaRolls.add(r.roll));
                    });
                    const meritFiltered = (parsed.merit || []).filter(r => !quotaRolls.has(r.roll));

                    const fileCsvs = {};
                    function makeCsv(name, rows) {
                        if (!rows || !rows.length) return;
                        const csv = Papa.unparse(rows.map(r => ({'SSC Roll': r.roll, 'Student Name': r.name})));
                        const fname = `${sanitize(dept)}_${name}.csv`;
                        fileCsvs[fname] = csv;
                        generatedCsvContents[fname] = csv;
                    }
                    
                    makeCsv('merit', meritFiltered);
                    makeCsv('freedom_fighter', parsed.freedom || []);
                    makeCsv('education_ministry', parsed.ministry || []);
                    makeCsv('education_org', parsed.org || []);
                    
                    renderResultCard(file, dept, meritFiltered, parsed, fileCsvs);

                } catch(e) {
                    renderErrorCard(file, e.message);
                    console.error(`Error processing ${file.name}:`, e);
                }
            }
            progressText.innerHTML = `Finished processing ${files.length} files.`;
            zipBtn.disabled = Object.keys(generatedCsvContents).length === 0;
        }

        function renderResultCard(file, dept, merit, parsedData, csvs) {
            const card = document.createElement('div');
            card.className = 'result-card success';
            let downloadLinksHTML = '';
            for (const fname of Object.keys(csvs)) {
                const blob = new Blob([csvs[fname]], {type:"text/csv"});
                const url = URL.createObjectURL(blob);
                downloadLinksHTML += `<a href="${url}" download="${fname}">📄 ${fname}</a>`;
            }

            card.innerHTML = `
                <div class="result-header">${file.name} <span>(Dept: ${dept})</span></div>
                <div class="stats">
                    <span>Merit: <strong>${merit.length}</strong></span>
                    <span>Freedom Fighter: <strong>${parsedData.freedom.length || 0}</strong></span>
                    <span>Ministry Quota: <strong>${parsedData.ministry.length || 0}</strong></span>
                    <span>Org Quota: <strong>${parsedData.org.length || 0}</strong></span>
                </div>
                <div class="download-links">${downloadLinksHTML}</div>
            `;
            resultsGrid.appendChild(card);
        }

        function renderErrorCard(file, errorMsg) {
            const card = document.createElement('div');
            card.className = 'result-card error';
            card.innerHTML = `<div class="result-header">⚠️ Error processing ${file.name}</div><p>${errorMsg}</p>`;
            resultsGrid.appendChild(card);
        }

        async function downloadAllAsZip() {
            const zip = new JSZip();
            for (const fname of Object.keys(generatedCsvContents)) {
                zip.file(fname, generatedCsvContents[fname]);
            }
            const blob = await zip.generateAsync({type:'blob'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'all_department_csvs.zip';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }

        // --- Helper & Parsing Functions ---
        async function extractTextFromPdf(file) {
            const buf = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({data: buf}).promise;
            let fullText = '';
            for (let p = 1; p <= pdf.numPages; p++) {
                const page = await pdf.getPage(p);
                const content = await page.getTextContent();
                fullText += content.items.map(i => i.str).join(' ') + "\n";
            }
            return fullText;
        }

        function departmentFromFilename(fn) {
            return fn.replace(/\.[^.]+$/, '').replace(/[_\-]+/g, ' ').trim();
        }

        function sanitize(s) {
            return s.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/_+/g, '_');
        }

        function parseTextBlocks(text) {
            const raw = text.replace(/\u00A0/g, ' ').replace(/\s+/g, ' ').trim();
            const chunks = raw.split(/(?=\b\d{5,9}\b)/g).map(s => s.trim()).filter(Boolean);
            const res = {merit: [], freedom: [], ministry: [], org: []};
            let current = 'merit';

            for (let chunk of chunks) {
                const low = chunk.toLowerCase();
                if (low.includes('freedom fighter')) { current = 'freedom'; continue; }
                if (low.includes('education (ministry')) { current = 'ministry'; continue; }
                if (low.includes('education (org')) { current = 'org'; continue; }
                if (low.startsWith('merit')) { current = 'merit'; continue; }

                const match = chunk.match(/^(\d{5,9})\s+[A-Za-z\s.]+\s+\d{4}\s+(.+?)(?=\s+\d{5,9}|\s*$)/);
                if (match) {
                    res[current].push({roll: match[1], name: match[2].trim()});
                }
            }
            return res;
        }

    })();
</script>

