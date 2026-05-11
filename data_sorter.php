<?php
// --- STEP 1: PHP SECURITY ---
require_once 'includes/functions.php'; 
check_login(); // 1. Check if user is logged in

// 2. Check permission (Make sure this matches your DB key)
if (!user_can('DATA_SORTER_TOOL')) {
    require_once 'includes/header.php';
    echo "<h1>Access Denied</h1><p>You do not have permission to view this tool.</p>";
    exit();
}

// --- STEP 2: Include the header
require_once 'includes/header.php';

// --- STEP 3: Override default content style
?>
<style>
    /* This overrides the .content style from header.php */
    .content {
        min-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: none !important;
        box-shadow: none !important;
        border: none !important;
    }

    /* --- STYLES FOR THIS TOOL (Migrated from your HTML) --- */
    .data-sorter-body {
        font-family: 'Inter', sans-serif;
        background-color: #f3f4f6;
    }

    .file-drop-area {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        padding: 2.5rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }

    .file-drop-area.dragover {
        border-color: #2563eb;
        background-color: #eff6ff;
    }

    .loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Style for checkbox lists */
    .filter-list,
    #column-selector-list {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.5rem;
        max-height: 150px;
        overflow-y: auto;
        background-color: #fff;
        padding: 0.75rem;
        border-radius: 0.375rem;
        border: 1px solid #d1d5db;
    }

    @media (min-width: 640px) {
        .filter-list,
        #column-selector-list {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .filter-group h4 {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #111827;
    }

    .toggle-all {
        color: #2563eb;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        float: right;
    }

    /* --- STYLES FROM PDF EXTRACTOR --- */
    .log {
        margin-top: 25px;
        width: 100%;
        font-size: 14px;
        color: #222;
    }

    .log-entry {
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #e5e7eb;
    }

    .preview-table {
        overflow-x: auto;
        max-height: 250px;
        overflow-y: auto;
        border: 1px solid #eee;
        margin-top: 10px;
    }

    .preview-table table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .preview-table th,
    .preview-table td {
        border: 1px solid #ddd;
        padding: 5px;
        text-align: left;
        white-space: nowrap;
    }

    .preview-table th {
        background: #f4f4f4;
        position: sticky;
        top: 0;
    }
    /* --- END OF PDF STYLES --- */
</style>

</div> 

<div class="data-sorter-body">
    <div class="container mx-auto p-4 md:p-8 max-w-5xl">

        <main class="bg-white p-6 md:p-8 rounded-xl shadow-md">

            <div id="pdf-processor-section">
                <header class="text-center mb-6">
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900">PDF & CSV Sorter</h1>
                    <p class="mt-2 text-md text-gray-600">Start by processing PDFs or upload existing CSVs below.</p>
                </header>

                <div class="p-6 bg-gray-50 rounded-lg border">
                    <h2 class="text-xl font-semibold text-gray-800 text-center">Step 1: Process PDFs (Optional)</h2>
                    <p class="text-sm text-gray-500 text-center mb-4">Data will be automatically loaded into the filters below.</p>
                    <div class="flex flex-col items-center">
                        <input type="file" id="pdfInput" multiple accept=".pdf" class="block w-full max-w-lg text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:bg-blue-600 file:text-white file:px-4 file:py-2 file:border-none file:mr-4" />
                        <button id="processBtn" class="mt-4 px-6 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 font-medium">Process PDFs & Load Data</button>
                    </div>
                    <div class="log" id="log"></div>
                </div>
            </div>
            <div id="upload-section">
                <div class="flex items-center justify-center my-6">
                    <span class="flex-grow bg-gray-200 h-px"></span>
                    <span class="px-4 font-semibold text-gray-500">OR</span>
                    <span class="flex-grow bg-gray-200 h-px"></span>
                </div>

                <h2 class="text-xl font-semibold text-gray-800 text-center mb-4">Step 1: Upload CSVs</h2>
                <div id="file-drop-area" class="file-drop-area">
                    <input type="file" id="csv-file-input" class="hidden" accept=".csv" multiple>
                    <div class="flex flex-col items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                            </path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500"><span class="font-semibold text-blue-600">Click to
                                upload</span> or drag and drop</p>
                        <p class="text-xs text-gray-500">Multiple CSV files are supported</p>
                    </div>
                </div>
                <div id="file-name-display" class="mt-4 text-center text-sm text-gray-700"></div>
            </div>
            
            <div id="fee-matcher-section" class="mt-12 border-t-4 border-gray-200 pt-8">
                <h2 class="text-3xl font-bold text-gray-900 text-center mb-2">Step 3: Fee Status Matcher</h2>
                <p class="text-gray-600 text-center mb-6">Matches File 1 (System) against File 2 (Fee List).</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-6 bg-blue-50 rounded-lg border border-blue-200">
                        <h3 class="font-semibold text-blue-900 mb-2">File 1: System Output</h3>
                        <p class="text-xs text-blue-700 mb-4">Headers: <b>REG. No</b>, Student Name, Can. Type, Department</p>
                        <input type="file" id="system-file-input" accept=".csv, .xlsx, .xls" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200" />
                    </div>

                    <div class="p-6 bg-green-50 rounded-lg border border-green-200">
                        <h3 class="font-semibold text-green-900 mb-2">File 2: Fee List</h3>
                        <p class="text-xs text-green-700 mb-4">Headers: <b>Reg No</b>, Student Name, Department, Fee Name</p>
                        <input type="file" id="fee-file-input" accept=".csv, .xlsx, .xls" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-100 file:text-green-700 hover:file:bg-green-200" />
                    </div>
                </div>

                <div class="text-center mt-6">
                    <button id="merge-btn" class="px-8 py-3 text-white bg-gray-800 rounded-lg hover:bg-black font-semibold shadow-lg transition transform hover:scale-105">
                        Match & Merge Files
                    </button>
                    <div id="merge-error" class="text-red-600 mt-2 text-sm font-medium hidden"></div>
                </div>

                <div id="merge-result-container" class="hidden mt-8 bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
                    
                    <div class="flex justify-between items-end mb-4 border-b pb-2">
                        <h3 class="text-xl font-bold text-gray-800">Filter & Download</h3>
                        <button id="merge-reset-btn" class="text-sm text-blue-600 font-medium hover:underline">Clear Filters</button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <h4 class="font-semibold text-sm text-gray-700 mb-2">
                                Department
                                <span class="merge-toggle-all text-blue-600 text-xs cursor-pointer float-right" data-target="merge-filter-dept">Select All</span>
                            </h4>
                            <div id="merge-filter-dept" class="filter-list h-48 overflow-y-auto border p-2 rounded bg-gray-50"></div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-sm text-gray-700 mb-2">
                                Fee Status
                                <span class="merge-toggle-all text-blue-600 text-xs cursor-pointer float-right" data-target="merge-filter-fee">Select All</span>
                            </h4>
                            <div id="merge-filter-fee" class="filter-list h-48 overflow-y-auto border p-2 rounded bg-gray-50"></div>
                        </div>

                        <div>
                            <h4 class="font-semibold text-sm text-gray-700 mb-2">
                                Student Type
                                <span class="merge-toggle-all text-blue-600 text-xs cursor-pointer float-right" data-target="merge-filter-type">Select All</span>
                            </h4>
                            <div id="merge-filter-type" class="filter-list h-48 overflow-y-auto border p-2 rounded bg-gray-50"></div>
                        </div>
                    </div>

                    <div class="mb-6 bg-gray-50 p-4 rounded border">
                        <h4 class="font-semibold text-sm text-gray-800 mb-2">Select Columns to Download:</h4>
                        <div id="merge-column-list" class="grid grid-cols-2 md:grid-cols-4 gap-2"></div>
                    </div>

                    <div class="mb-6 p-4 bg-indigo-50 rounded border border-indigo-100">
                        <h4 class="font-semibold text-sm text-gray-800 mb-2">Download Mode</h4>
                        <div class="flex items-center">
                            <input type="checkbox" id="merge-combine-mode" class="h-4 w-4 text-indigo-600 rounded focus:ring-indigo-500" checked>
                            <label for="merge-combine-mode" class="ml-2 text-sm text-gray-900 font-medium">Generate a single combined file</label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1 ml-6">Uncheck this to download separate files for each Department.</p>
                    </div>

                    <div class="bg-gray-100 p-4 rounded-lg">
                        <div id="merge-count" class="font-medium text-gray-700 mb-4">Records found: 0</div>
                        
                        <button id="download-merged-btn" class="w-full sm:w-auto px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium hidden">
                            Download Combined Result
                        </button>

                        <div id="merge-download-list" class="space-y-3 hidden"></div>
                    </div>
                </div>
            </div>
            
            <div id="status-section" class="text-center my-6 hidden">
                <div class="flex items-center justify-center space-x-2">
                    <div id="loader" class="loader"></div>
                    <span id="status-message" class="font-medium text-gray-700">Processing file...</span>
                </div>
            </div>
            
            <div id="data-processing-section" class="hidden">
                <hr class="my-8">
                <h2 class="text-2xl font-semibold text-gray-800 text-center mb-6">Step 2: Filter & Download Data</h2>
                
                <div id="multi-filter-section" class="mt-6 p-4 bg-gray-50 rounded-lg border">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">1. Select Filters</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="filter-group">
                            <h4>Sessions <span class="toggle-all" data-target="session-filter-list">Select All</span></h4>
                            <div id="session-filter-list" class="filter-list"></div>
                        </div>
                        <div class="filter-group">
                            <h4>Departments <span class="toggle-all" data-target="dept-filter-list">Select All</span></h4>
                            <div id="dept-filter-list" class="filter-list"></div>
                        </div>
                        <div class="filter-group">
                            <h4>Candidate Types <span class="toggle-all" data-target="type-filter-list">Select All</span></h4>
                            <div id="type-filter-list" class="filter-list"></div>
                        </div>
                    </div>
                    <button id="reset-general-filters-btn"
                        class="mt-4 w-full sm:w-auto px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 font-medium">Clear
                        All Filters</button>
                </div>

                <div id="irregular-filter-section" class="mt-4 p-4 bg-gray-50 rounded-lg border">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">2. Filter Irregular Students</h3>
                    <div class="flex flex-col sm:flex-row items-center gap-3">
                        <div class="flex-grow w-full sm:w-auto">
                            <label for="irregular-threshold" class="sr-only">Subject Count Threshold</label>
                            <input type="number" id="irregular-threshold" placeholder="e.g., 5"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex-shrink-0 w-full sm:w-auto">
                            <label for="irregular-operator" class="sr-only">Filter Operator</label>
                            <select id="irregular-operator"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="gt">Greater Than (>)</option>
                                <option value="lt">Less Than or Equal To (≤)</option>
                            </select>
                        </div>
                        <button id="filter-btn"
                            class="w-full sm:w-auto px-4 py-2 text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 font-medium">Apply
                            Filter</button>
                        <button id="reset-irr-filter-btn"
                            class="w-full sm:w-auto px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400 font-medium">Reset</button>
                    </div>
                </div>
                <div id="column-selector-section" class="mt-4 p-4 bg-gray-50 rounded-lg border">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-lg font-semibold text-gray-800">3. Select Columns to Download</h3>
                        <button id="toggle-all-cols" class="px-3 py-1 text-sm text-blue-600 font-medium hover:text-blue-800">Select
                            All</button>
                    </div>
                    <div id="column-selector-list" class="max-h-48 overflow-y-auto">
                    </div>
                </div>

                <div id="mode-selector-section" class="mt-4 p-4 bg-gray-50 rounded-lg border">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">4. Download Mode</h3>
                    <div class="flex items-center">
                        <input type="checkbox" id="combine-mode"
                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" checked>
                        <label for="combine-mode" class="ml-2 block text-sm text-gray-900">Generate a single combined file</label>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Uncheck this to get separate files for each group (e.g., by
                        department, type, etc.)</p>
                </div>

                <div id="results-section" class="hidden mt-6">
                    <h2 id="results-title" class="text-xl font-semibold mb-4 border-b pb-2">5. Download Your File(s)</h2>
                    <div id="results-list" class="space-y-3">
                    </div>
                </div>
                <div id="no-results" class="hidden text-center py-8">
                    <p class="text-gray-500">No data found for the current filter selection.</p>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // --- CRITICAL FIX ---
    // Point the worker to the version loaded in header.php (v3.4.120)
    pdfjsLib.GlobalWorkerOptions.workerSrc = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js`;

    // --- GLOBAL DATA VARS ---
    let fileHeader = [];
    let originalData = [];
    let irregularThreshold = null;
    let irregularOperator = 'gt'; 

    // --- PDF HELPER FUNCTIONS (FROM SYSTEM 1) ---
    const round10 = n => Math.round(n);

    function groupByRows(items, yThreshold = 5) {
        items.sort((a, b) => b.y - a.y || a.x - b.x);
        const rows = [];
        for (const it of items) {
            let placed = false;
            for (const r of rows) {
                if (Math.abs(r.y - it.y) <= yThreshold) { r.items.push(it); placed = true; break; }
            }
            if (!placed) rows.push({ y: it.y, items: [it] });
        }
        rows.forEach(r => r.items.sort((a, b) => a.x - b.x));
        rows.sort((a, b) => b.y - a.y);
        return rows;
    }

    const inferColumnBoundaries = r => {
        const b = r.items.map(it => it.x);
        b.push(b[b.length - 1] + 1000);
        return b;
    };

    const assignColumn = (x, b) => {
        for (let i = 0; i < b.length - 1; i++) { if (x >= b[i] - 1 && x < b[i + 1] - 1) return i; }
        return b.length - 2;
    };

    const csvSafe = (v, num = false) => {
        if (!v && v !== 0) return '';
        const s = String(v).replace(/\s+/g, ' ').trim();
        if (num) return `="${s}"`;
        if (/[,"]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
        return s;
    };

    function createPreviewTable(data) {
        if (!data || data.length === 0) return null;
        const table = document.createElement('table');
        const thead = document.createElement('thead');
        const headerRow = document.createElement('tr');
        const headers = data[0]; // Header row
        headers.forEach(headerText => {
            const th = document.createElement('th');
            th.textContent = headerText;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);
        const tbody = document.createElement('tbody');
        const rows = data.slice(1); // Data rows
        rows.forEach(rowData => {
            const tr = document.createElement('tr');
            rowData.forEach(cellData => {
                const td = document.createElement('td');
                td.textContent = cellData;
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        table.appendChild(tbody);
        return table;
    }

    // --- DOM Elements (CSV SORTER - SYSTEM 2) ---
    const fileDropArea = document.getElementById('file-drop-area');
    const csvFileInput = document.getElementById('csv-file-input');
    const fileNameDisplay = document.getElementById('file-name-display');
    const statusSection = document.getElementById('status-section');
    const loader = document.getElementById('loader');
    const statusMessage = document.getElementById('status-message');
    const resultsSection = document.getElementById('results-section');
    const resultsTitle = document.getElementById('results-title');
    const resultsList = document.getElementById('results-list');
    const noResults = document.getElementById('no-results');

    const dataProcessingSection = document.getElementById('data-processing-section'); // Wrapper for all filters
    const multiFilterSection = document.getElementById('multi-filter-section');
    const sessionFilterList = document.getElementById('session-filter-list');
    const deptFilterList = document.getElementById('dept-filter-list');
    const typeFilterList = document.getElementById('type-filter-list');
    const resetGeneralFiltersBtn = document.getElementById('reset-general-filters-btn');
    const toggleAllButtons = document.querySelectorAll('.toggle-all');

    const irregularFilterSection = document.getElementById('irregular-filter-section');
    const irregularThresholdInput = document.getElementById('irregular-threshold');
    const irregularOperatorSelect = document.getElementById('irregular-operator'); // <-- NEW
    const filterBtn = document.getElementById('filter-btn');
    const resetIrrFilterBtn = document.getElementById('reset-irr-filter-btn');

    const columnSelectorSection = document.getElementById('column-selector-section');
    const columnSelectorList = document.getElementById('column-selector-list');
    const toggleAllColsBtn = document.getElementById('toggle-all-cols');

    const modeSelectorSection = document.getElementById('mode-selector-section');
    const combineModeCheckbox = document.getElementById('combine-mode');

    // --- DOM Elements (PDF EXTRACTOR - SYSTEM 1) ---
    const pdfInputEl = document.getElementById('pdfInput');
    const pdfLogEl = document.getElementById('log');
    const pdfProcessBtnEl = document.getElementById('processBtn');
    const uploadSection = document.getElementById('upload-section');


    // --- EVENT LISTENERS (CSV SORTER - SYSTEM 2) ---
    fileDropArea.addEventListener('click', () => csvFileInput.click());
    csvFileInput.addEventListener('change', (e) => handleFileSelect(e.target.files));

    multiFilterSection.addEventListener('change', filterAndDisplayResults);
    resetGeneralFiltersBtn.addEventListener('click', resetGeneralFilters);
    toggleAllButtons.forEach(btn => btn.addEventListener('click', handleToggleAllCheckboxes));

    filterBtn.addEventListener('click', applyIrregularFilter);
    resetIrrFilterBtn.addEventListener('click', resetIrregularFilter);

    combineModeCheckbox.addEventListener('change', filterAndDisplayResults);
    toggleAllColsBtn.addEventListener('click', toggleAllColumns);
    columnSelectorList.addEventListener('change', () => {
        // Re-run the download button creation if in combined mode
        if (combineModeCheckbox.checked) {
            filterAndDisplayResults();
        }
    });

    // Drag and Drop (CSV Sorter)
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, preventDefaults, false);
    });
    ['dragenter', 'dragover'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, () => fileDropArea.classList.add('dragover'), false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        fileDropArea.addEventListener(eventName, () => fileDropArea.classList.remove('dragover'), false);
    });
    fileDropArea.addEventListener('drop', handleDrop, false);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFileSelect(files);
    }

    /**
     * Main function to handle CSV file selection and processing.
     */
    async function handleFileSelect(files) {
        if (files.length === 0) return;
        const csvFiles = Array.from(files).filter(file => file.type === 'text/csv' || file.name.endsWith('.csv'));
        if (csvFiles.length === 0) {
            alert('Please upload at least one valid CSV file.');
            return;
        }

        fileNameDisplay.textContent = `Selected file(s): ${csvFiles.map(f => f.name).join(', ')}`;
        statusMessage.textContent = `Processing ${csvFiles.length} file(s)...`;
        [statusSection].forEach(el => el.classList.remove('hidden'));
        [resultsSection, noResults, dataProcessingSection].forEach(el => el.classList.add('hidden'));
        resultsList.innerHTML = '';

        const readFileAsText = (file) => new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(reader.error);
            reader.readAsText(file);
        });

        try {
            let combinedData = [];
            let localFileHeader = [];

            for (const file of csvFiles) {
                const csvText = await readFileAsText(file);
                const lines = csvText.trim().split(/\r?\n/);
                if (lines.length < 2) continue;

                const fileName = file.name;
                const match = fileName.match(/\d+/);
                const deptFromFile = match ? match[0] : fileName.replace(/\.csv$/i, '');

                const splitRegex = /,(?=(?:(?:[^"]*"){2})*[^"]*$)/;
                const currentHeader = lines[0].split(splitRegex).map(h => h.replace(/"/g, '').trim());

                if (localFileHeader.length === 0) localFileHeader = currentHeader;

                const dataRows = lines.slice(1).map(line => {
                    const values = line.split(splitRegex);
                    const rowObject = {};
                    localFileHeader.forEach((header, hIndex) => {
                        rowObject[header] = values[hIndex] ? values[hIndex].replace(/"/g, '').trim() : '';
                    });
                    rowObject.departmentFromFile = deptFromFile;
                    return rowObject;
                });
                combinedData = combinedData.concat(dataRows);
            }

            // Pass data to global vars
            originalData = combinedData;
            fileHeader = localFileHeader;

            // --- Trigger Filter UI ---
            loadDataIntoFilters();
            statusMessage.textContent = `${originalData.length} CSV rows loaded.`;

        } catch (error) {
            console.error("Error processing files:", error);
            statusMessage.textContent = 'Error processing files. Please check console.';
            loader.classList.add('hidden');
        }
    }

    // --- EVENT LISTENER (PDF EXTRACTOR - SYSTEM 1) ---
    pdfProcessBtnEl.addEventListener('click', async () => {
        if (!pdfInputEl.files.length) { alert('Please select PDF files first.'); return; }

        pdfLogEl.innerHTML = ''; // Clear log
        const pStart = document.createElement('p');
        pStart.textContent = 'Processing...';
        pdfLogEl.appendChild(pStart);

        let combinedPdfData = []; // NEW: To store data for filters
        let pdfFileHeader = [];   // NEW: To store header for filters
        let pdfFileCount = 0;

        for (const file of pdfInputEl.files) {
            pdfFileCount++;
            const pFile = document.createElement('p');
            pFile.textContent = `Reading: ${file.name}`;
            pdfLogEl.appendChild(pFile);

            const buf = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: buf }).promise;

            const csvHeader = ['REG. No', 'Session', 'Student Name', 'Father Name', 'Mobile No', 'Reg. Type', 'Can. Type', 'Course Code(s)'];
            if (pdfFileHeader.length === 0) pdfFileHeader = csvHeader; // Set global header

            const previewData = [csvHeader]; // For this file's preview table
            
            for (let p = 1; p <= pdf.numPages; p++) {
                const page = await pdf.getPage(p);
                const c = await page.getTextContent();
                const page_items = [];
                for (const it of c.items) {
                    const tr = it.transform || [1, 0, 0, 1, 0, 0];
                    const x = round10(tr[4]), y = round10(tr[5]);
                    const str = (it.str || '').trim();
                    if (str) page_items.push({ str, x, y, page: p });
                }
                if (!page_items.length) {
                    const pSkip = document.createElement('p');
                    pSkip.textContent = `ℹ️ Skipping empty page ${p}`;
                    pdfLogEl.appendChild(pSkip);
                    continue;
                }
                const rows = groupByRows(page_items, 5);
                let hi = rows.findIndex(r => r.items.map(i => i.str.toLowerCase()).join(' ').includes('student') && r.items.map(i => i.str.toLowerCase()).join(' ').includes('reg'));
                if (hi === -1) hi = rows.findIndex(r => r.items.map(i => i.str.toLowerCase()).join(' ').includes('reg no'));
                if (hi === -1) {
                    const pErr = document.createElement('p');
                    pErr.textContent = `⚠️ No header found on page ${p}, skipping page.`;
                    pErr.style.color = 'orange';
                    pdfLogEl.appendChild(pErr);
                    continue;
                }
                const header = rows[hi];
                const b = inferColumnBoundaries(header);
                const headerItems = header.items.map(i => i.str.toLowerCase());
                const colCount = headerItems.length;
                let pageLayout = 'unknown';
                if (colCount === 9) pageLayout = '9col';
                else if (colCount === 8) pageLayout = '8col';
                else if (colCount === 10) pageLayout = '10col';
                else if (colCount === 7) {
                    const col4 = headerItems[4] || '';
                    const col5 = headerItems[5] || '';
                    if (col4.includes('reg type') && col4.includes('mobile no')) pageLayout = '7col-soil';
                    else if (col5.includes('reg type') && col5.includes('can type')) pageLayout = '7col-english';
                    else pageLayout = '8col';
                } else if (colCount === 6) pageLayout = '6col';

                for (let r = hi + 1; r < rows.length; r++) {
                    const row = rows[r];
                    const cols = Array(b.length - 1).fill('').map(() => []);
                    for (const it of row.items) { cols[assignColumn(it.x, b)].push(it.str); }
                    const t = cols.map(c => c.join(' ').trim());
                    let regNo, session, student, father, mobile, regType, canType, course;
                    switch (pageLayout) {
                        case '9col': [, regNo, session, student, father, mobile, regType, canType, course] = t; break;
                        case '8col': [regNo, session, student, father, mobile, regType, canType, course] = t; break;
                        case '7col-soil':
                            [, regNo, session, student, mobile, canType, course] = t;
                            father = student;
                            const mobileParts = (mobile || '').trim().split(/\s+/);
                            if (/^(REG|IRR|IMP|PVT)$/i.test(mobileParts[0])) {
                                regType = mobileParts.shift();
                                mobile = mobileParts.join(' ');
                            } else {
                                regType = "REG"; mobile = mobileParts.join(' ');
                            } break;
                        case '7col-english': [, regNo, session, student, mobile, regType, course] = t; father = student; canType = regType; break;
                        case '6col': [, regNo, student, mobile, regType, course] = t; session = regNo; father = student; canType = course; break;
                        default: [regNo, session, student, father, mobile, regType, canType, course] = t;
                    }
                    if (regNo) { const m = regNo.match(/\d{11}/); regNo = m ? m[0] : ''; }
                    if ((!session || !session.trim() || session.length < 4) && student) {
                        const sm = student.match(/\b(20\d{2}-\d{2}|20\d{2})\b/);
                        if (sm) { session = sm[1]; student = student.replace(sm[1], '').trim(); }
                    }
                    if ((!session || !session.trim() || session.length < 4) && (t[1] || '')) {
                        const sm = (t[1] || '').match(/\b(20\d{2}-\d{2}|20\d{2})\b/); if (sm) session = sm[1];
                    }
                    if ((!father || father.length < 2 || father === student) && student) {
                        const studentNameRegex = /^(MD\.?|MD|MST\.?|MST|MOSTT\.?|MOSTT|MOSA\.?|MOSA)$/i;
                        const parts = student.split(' ');
                        let split = null;
                        const prefixIndices = [];
                        for (let i = 0; i < parts.length; i++) { if (studentNameRegex.test(parts[i])) { prefixIndices.push(i); } }
                        if (prefixIndices.length >= 2) { const splitIndex = prefixIndices[1]; split = [parts.slice(0, splitIndex).join(' '), parts.slice(splitIndex).join(' ')]; }
                        else if (/\s{2,}/.test(student)) { split = student.split(/\s{2,}/); }
                        else if (parts.length >= 4) { const cut = Math.max(2, parts.length - 2); split = [parts.slice(0, cut).join(' '), parts.slice(cut).join(' ')]; }
                        if (split && split.length >= 2) {
                            let part1 = split[0].trim(), part2 = split[1].trim();
                            const part1Matches = studentNameRegex.test(part1.split(' ')[0] || ''), part2Matches = studentNameRegex.test(part2.split(' ')[0] || '');
                            father = part1; student = part2;
                            if (!part1Matches && part2Matches) { student = part1; father = part2; }
                        }
                    }
                    if (regType && (!canType || !canType.trim() || canType === regType)) {
                        const parts = regType.trim().split(/\s+/);
                        if (parts.length === 2 && /^(REG|IRR|IMP|PVT)$/i.test(parts[0]) && /^(REG|IRR|IMP|PVT)$/i.test(parts[1])) { regType = parts[0]; canType = parts[1]; }
                    }
                    if ((!regType || !regType.trim()) && canType && /^(REG|IRR|IMP|PVT)$/i.test(canType)) { regType = canType; canType = ''; }
                    if ((!canType || !canType.trim()) && course) {
                        const parts = course.trim().split(/\s+/);
                        const firstWord = (parts[0] || '').toUpperCase(), lastWord = (parts[parts.length - 1] || '').toUpperCase();
                        if (firstWord === 'REG' || firstWord === 'IRR' || firstWord === 'IMP' || firstWord === 'PVT') { canType = parts.shift(); course = parts.join(' ').trim(); }
                        else if (lastWord === 'REG' || lastWord === 'IRR' || lastWord === 'IMP' || firstWord === 'PVT') { canType = parts.pop(); course = parts.join(' ').trim(); }
                    }

                    // --- NEW: AUTO-CORRECTION LOGIC (V3) ---
                    let rawRegType = (regType || '').trim();
                    let rawCanType = (canType || '').trim();
                    let rawCourse = (course || '').trim();
                    const regTypeAsNum = rawRegType.replace(/[^\d]/g, '');
                    const canTypeAsType = rawCanType.toUpperCase();
                    const courseParts = rawCourse.split(/\s+/);
                    const courseFirstWord = (courseParts[0] || '').toUpperCase();
                    const isCanTypeARegType = canTypeAsType === 'REG' || canTypeAsType === 'IRR' || canTypeAsType === 'IMP' || canTypeAsType === 'PVT';
                    const isCourseMixed = courseFirstWord === 'REG' || courseFirstWord === 'IRR' || courseFirstWord === 'IMP' || courseFirstWord === 'PVT';
                    const isRegTypeAMobile = regTypeAsNum.length === 11 && /^(01\d{9})$/.test(regTypeAsNum);
                    const isRegTypeARegNo = regTypeAsNum.length === 11 && /^(1|2)\d{10}$/.test(regTypeAsNum) && !isRegTypeAMobile;

                    if (isCanTypeARegType && isCourseMixed) {
                        if (isRegTypeAMobile) {
                            mobile = regTypeAsNum;
                            regType = canTypeAsType;
                            canType = courseFirstWord;
                            courseParts.shift();
                            course = courseParts.join(' ');
                        } else if (isRegTypeARegNo) {
                            regNo = regTypeAsNum;
                            mobile = '';
                            regType = canTypeAsType;
                            canType = courseFirstWord;
                            courseParts.shift();
                            course = courseParts.join(' ');
                        }
                    }
                    // --- END OF AUTO-CORRECTION LOGIC (V3) ---

                    const regClean = (regNo || '').trim(), studentClean = (student || '').trim(), fatherClean = (father || '').trim(), mobClean = (mobile || '').replace(/[^\d]/g, ''), sessionClean = (session || '').trim(), regTypeClean = (regType || '').trim(), canTypeClean = (canType || '').trim(), courseClean = (course || '').trim();
                    if (!regClean) continue;
                    const finalRowData = [regClean, sessionClean, studentClean, fatherClean, mobClean, regTypeClean, canTypeClean, courseClean];
                    
                    previewData.push(finalRowData); // For preview table
                    
                    // NEW: Add to global data as an object
                    const rowObject = {};
                    pdfFileHeader.forEach((header, index) => {
                        rowObject[header] = finalRowData[index];
                    });
                    // Add departmentFromFile, using the PDF filename
                    const match = file.name.replace(/\.pdf$/i, '').match(/\d+/);
                    rowObject.departmentFromFile = match ? match[0] : file.name.replace(/\.pdf$/i, '');
                    
                    combinedPdfData.push(rowObject);
                }
            }

            // --- NO LONGER DOWNLOADS, just shows preview ---
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            const pSuccess = document.createElement('p');
            pSuccess.textContent = `✅ ${file.name} processed (${previewData.length - 1} rows)`;
            pSuccess.style.fontWeight = 'bold'; pSuccess.style.color = 'green';
            entry.appendChild(pSuccess);
            if (previewData.length > 1) {
                const tableContainer = document.createElement('div');
                tableContainer.className = 'preview-table';
                tableContainer.appendChild(createPreviewTable(previewData));
                entry.appendChild(tableContainer);
            } else {
                const pNoData = document.createElement('p');
                pNoData.textContent = 'No data rows were extracted for preview.';
                entry.appendChild(pNoData);
            }
            pdfLogEl.appendChild(entry);
        }

        const pDone = document.createElement('p');
        pDone.textContent = 'All files processed.';
        pdfLogEl.appendChild(pDone);
        
        // --- NEW: INTEGRATION STEP ---
        // Pass data to global vars
        originalData = combinedPdfData;
        fileHeader = pdfFileHeader;
        
        // Trigger Filter UI
        loadDataIntoFilters();
        [statusSection].forEach(el => el.classList.remove('hidden'));
        statusMessage.textContent = `Processed ${pdfFileCount} PDFs, ${originalData.length} rows loaded.`;
        uploadSection.classList.add('hidden'); // Hide CSV uploader
    });

    
    /**
     * --- NEW: Centralized function to populate filters and show UI ---
     */
    function loadDataIntoFilters() {
        if (originalData.length > 0) {
            populateFilterCheckboxes(originalData);
            populateColumnSelector(fileHeader);
            
            // Show all filter sections
            [dataProcessingSection].forEach(el => el.classList.remove('hidden'));
            
            filterAndDisplayResults(); // Run initial filter
        } else {
             [statusSection].forEach(el => el.classList.remove('hidden'));
            statusMessage.textContent = 'No data was loaded.';
        }
        // Hide status message/loader after a moment
        setTimeout(() => {
            if (originalData.length > 0) {
               [statusSection].forEach(el => el.classList.add('hidden'));
            }
        }, 2000);
    }

    /**
     * Populates filter checkbox lists
     */
    function populateFilterCheckboxes(data) {
        const sessions = new Set();
        const depts = new Set();
        const types = new Set();

        data.forEach(row => {
            if (row['Session']) sessions.add(row['Session']);
            if (row.departmentFromFile) depts.add(row.departmentFromFile);
            if (row['Can. Type']) types.add(row['Can. Type']);
        });

        const populateList = (listElement, items) => {
            listElement.innerHTML = ''; // Clear previous
            Array.from(items).sort().forEach(item => {
                listElement.appendChild(createCheckboxItem(item, item));
            });
        };

        populateList(sessionFilterList, sessions);
        populateList(deptFilterList, depts);
        populateList(typeFilterList, types);
    }

    /**
     * Helper to create a single checkbox item
     */
    function createCheckboxItem(value, labelText) {
        const div = document.createElement('div');
        div.className = 'flex items-center';

        const input = document.createElement('input');
        input.type = 'checkbox';
        // Create a safe ID by replacing spaces with dashes
        const safeId = String(value).replace(/[^a-zA-Z0-9]/g, '-'); 
        input.id = `cb-${safeId}`;
        input.value = value;
        input.className = 'h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500';

        const label = document.createElement('label');
        label.htmlFor = input.id;
        label.textContent = labelText;
        label.className = 'ml-2 block text-sm text-gray-900 truncate';

        div.appendChild(input);
        div.appendChild(label);
        return div;
    }

    /**
     * Populates column selector with checkboxes.
     */
    function populateColumnSelector(headers) {
        const list = document.getElementById('column-selector-list');
        if (!list) return;
        
        list.innerHTML = '';
        
        // 1. Manually add "Department" (derived from filename)
        if (typeof createCheckboxItem === 'function') {
            list.appendChild(createCheckboxItem('Department', 'Department'));

            // 2. Add the rest of the headers
            if (Array.isArray(headers)) {
                headers.forEach(header => {
                    if (header && header !== 'Department') { 
                        list.appendChild(createCheckboxItem(header, header));
                    }
                });
            }
        } else {
            console.error("Helper function createCheckboxItem is missing");
            return;
        }

        // 3. Auto-check defaults
        const regNo = document.getElementById('cb-REG.-No');
        const stdName = document.getElementById('cb-Student-Name');
        const deptCb = document.getElementById('cb-Department');
        
        if (regNo) regNo.checked = true;
        if (stdName) stdName.checked = true;
        if (deptCb) deptCb.checked = true; 
    }

    /**
     * Handles "Select All" for any checkbox list
     */
    function handleToggleAllCheckboxes(e) {
        const targetListId = e.target.dataset.target;
        const listElement = document.getElementById(targetListId);
        if (!listElement) return;

        const checkboxes = listElement.querySelectorAll('input[type="checkbox"]');
        const isSelectAll = e.target.textContent === 'Select All';
        checkboxes.forEach(cb => cb.checked = isSelectAll);
        e.target.textContent = isSelectAll ? 'Deselect All' : 'Select All';

        filterAndDisplayResults(); // Re-run filter after toggling
    }

    /**
     * Toggles all column checkboxes.
     */
    function toggleAllColumns() {
        const checkboxes = columnSelectorList.querySelectorAll('input[type="checkbox"]');
        const isSelectAll = toggleAllColsBtn.textContent === 'Select All';
        checkboxes.forEach(cb => cb.checked = isSelectAll);
        toggleAllColsBtn.textContent = isSelectAll ? 'Deselect All' : 'Select All';
    }

    // --- FILTERING AND DISPLAY LOGIC ---

    function applyIrregularFilter() {
        const thresholdValue = parseInt(irregularThresholdInput.value, 10);
        if (isNaN(thresholdValue) || thresholdValue < 0) {
            alert('Please enter a valid, non-negative number for the subject count.');
            irregularThreshold = null; 
            irregularOperator = 'gt'; 
            filterAndDisplayResults(); 
            return;
        }
        irregularThreshold = thresholdValue;
        irregularOperator = irregularOperatorSelect.value; 
        filterAndDisplayResults();
    }

    function resetIrregularFilter() {
        irregularThresholdInput.value = '';
        irregularOperatorSelect.value = 'gt'; 
        irregularThreshold = null;
        irregularOperator = 'gt'; 
        filterAndDisplayResults();
    }

    function resetGeneralFilters() {
        [sessionFilterList, deptFilterList, typeFilterList].forEach(list => {
            list.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        });
        toggleAllButtons.forEach(btn => btn.textContent = 'Select All');
        filterAndDisplayResults();
    }

    function getCheckedValues(listElement) {
        const values = [];
        listElement.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            values.push(cb.value);
        });
        return values;
    }

    function filterAndDisplayResults() {
        if (originalData.length === 0) return;

        resultsList.innerHTML = '';
        [noResults, resultsSection].forEach(el => el.classList.add('hidden'));
        
        // 1. Get all filter selections
        const selectedSessions = getCheckedValues(sessionFilterList);
        const selectedDepts = getCheckedValues(deptFilterList);
        const selectedTypes = getCheckedValues(typeFilterList);

        // 2. Filter by general filters first
        let filteredData = originalData.filter(row => {
            const sessionMatch = selectedSessions.length === 0 || selectedSessions.includes(row['Session']);
            const deptMatch = selectedDepts.length === 0 || selectedDepts.includes(row.departmentFromFile);
            const typeMatch = selectedTypes.length === 0 || selectedTypes.includes(row['Can. Type']);
            return sessionMatch && deptMatch && typeMatch;
        });

        // 3. Process Irregular filter
        const courseKey = 'Course Code(s)';
        let finalData = filteredData.filter(row => {
            // If no filter is active, keep all rows
            if (irregularThreshold === null) {
                return true; 
            }

            const canType = row['Can. Type'];
            // Keep all non-IRR students (or those without a type)
            if (!canType || canType.trim().toUpperCase() !== 'IRR') {
                return true;
            }
            
            // It IS an IRR student, and the filter IS active. Apply the filter.
            const subjectCount = (row[courseKey] || '').split(',').filter(s => s.trim() !== '').length;
            
            if (irregularOperator === 'gt') {
                return subjectCount > irregularThreshold;
            } else { // 'lt'
                return subjectCount <= irregularThreshold;
            }
        }).map(row => {
            // Now, we map the *remaining* rows to set 'Processed Can. Type' for grouping
            let processedType = row['Can. Type'];
            // If filter is active and this is an IRR student (which it must be to get here)
            if (irregularThreshold !== null && row['Can. Type'] && row['Can. Type'].trim().toUpperCase() === 'IRR') {
                 const opText = irregularOperator === 'gt' ? '>' : '≤';
                 processedType = `IRR (${opText} ${irregularThreshold} subjects)`;
            }
            return { ...row, 'Processed Can. Type': processedType };
        });

        // 4. Decide which mode to display
        if (finalData.length === 0) {
            noResults.classList.remove('hidden');
            return;
        }

        resultsSection.classList.remove('hidden');

        if (combineModeCheckbox.checked) {
            // --- COMBINED MODE ---
            resultsTitle.textContent = '5. Download Your Combined File';
            createCombinedDownloadCard(finalData);
        } else {
            // --- GROUPED MODE ---
            resultsTitle.textContent = '5. Download Your Grouped Files';

            const groups = {};
            finalData.forEach(row => {
                const session = row['Session'] || 'NoSession';
                const dept = row.departmentFromFile || 'NoDept';
                const canType = row['Processed Can. Type'] || 'NoType';

                const key = `${session}|${dept}|${canType}`;
                if (!groups[key]) {
                    groups[key] = { session, dept, canType, data: [] };
                }
                groups[key].data.push(row);
            });

            const sortedGroupKeys = Object.keys(groups).sort();
            if (sortedGroupKeys.length > 0) {
                sortedGroupKeys.forEach(key => createGroupListItem(groups[key]));
            } else {
                noResults.classList.remove('hidden');
            }
        }
    }

    function createCombinedDownloadCard(dataToDownload) {
        resultsList.innerHTML = ''; // Clear any previous card
        const count = dataToDownload.length;

        const li = document.createElement('div');
        li.className = 'flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-gray-50 rounded-lg border gap-3';

        const textContentDiv = document.createElement('div');
        textContentDiv.className = 'flex-grow';
        textContentDiv.innerHTML = `
            <div><span class="font-semibold text-gray-800">Combined File</span></div>
            <div class="text-sm text-gray-500">${count} total student(s) found.</div>
        `;

        if (irregularThreshold !== null) {
            const irrNote = document.createElement('div');
            irrNote.className = "text-sm text-indigo-600 font-medium mt-1";
            const opText = irregularOperator === 'gt' ? '>' : '≤';
            irrNote.textContent = `Irregular filter active: Showing IRR with ${opText} ${irregularThreshold} subjects.`;
            textContentDiv.appendChild(irrNote);
        }

        const button = createDownloadButton();
        button.onclick = () => handleDownloadClick(dataToDownload, 'combined_data.csv');

        li.appendChild(textContentDiv);
        li.appendChild(button);
        resultsList.appendChild(li);
    }

    function createGroupListItem(groupInfo) {
        const { session, dept, canType, data } = groupInfo;
        const count = data.length;

        const li = document.createElement('div');
        li.className = 'flex flex-col sm:flex-row items-start sm:items-center justify-between p-3 bg-gray-50 rounded-lg border gap-3';

        const textContentDiv = document.createElement('div');
        textContentDiv.className = 'flex-grow';
        textContentDiv.innerHTML = `
            <div>
                <span class="font-semibold text-gray-800">Session:</span> ${session} | 
                <span class="font-semibold text-gray-800">Dept:</span> ${dept} | 
                <span class="font-semibold text-gray-800">Type:</span> ${canType.replace(/</g, "&lt;").replace(/>/g, "&gt;")}
            </div>
            <div class="text-sm text-gray-500">${count} student(s)</div>
        `;

        const button = createDownloadButton();
        const safeFilename = canType.replace(/[^a-zA-Z0-9_]/g, '');
        const filename = `data_${session}_${dept}_${safeFilename}.csv`;
        button.onclick = () => handleDownloadClick(data, filename);

        li.appendChild(textContentDiv);
        li.appendChild(button);
        resultsList.appendChild(li);
    }

    function createDownloadButton() {
        const button = document.createElement('button');
        button.className = `px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors flex-shrink-0`;
        button.textContent = 'Download';
        return button;
    }

    function handleDownloadClick(dataToDownload, filename) {
        const selectedHeaders = [];
        const checkboxes = columnSelectorList.querySelectorAll('input[type="checkbox"]:checked');
        checkboxes.forEach(cb => selectedHeaders.push(cb.value));

        if (selectedHeaders.length === 0) {
            alert('Please select at least one column from section 3 to download.');
            return;
        }
        
        // Ensure "Can. Type" is selected if grouping by it, or just add it
        if (!selectedHeaders.includes('Can. Type') && fileHeader.includes('Can. Type')) {
            const canTypeCheckbox = document.getElementById('cb-Can.-Type');
            if(canTypeCheckbox) {
                canTypeCheckbox.checked = true;
                selectedHeaders.push('Can. Type');
            }
        }

        downloadCSV(dataToDownload, filename, selectedHeaders);
    }

    function downloadCSV(data, filename, headersToDownload) {
        if (!headersToDownload || headersToDownload.length === 0) {
            alert("Could not generate CSV: No columns selected.");
            return;
        }

        const csvRows = [headersToDownload.join(',')]; // Start with the header row
        
        data.forEach(row => {
            const values = headersToDownload.map(header => {
                let value = '';

                // --- NEW LOGIC: Handle Special Columns ---
                if (header === 'Department') {
                    value = row['departmentFromFile'] || row['Department'] || '';
                } else if (header === 'Can. Type') {
                    value = row['Processed Can. Type'] || row['Can. Type'] || '';
                } else {
                    value = row[header] || '';
                }
                // ----------------------------------------

                if (/[",\n\r]/.test(value)) {
                    value = `"${value.replace(/"/g, '""')}"`;
                }
                return value;
            });
            csvRows.push(values.join(','));
        });

        const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    // --- FEE MERGER SYSTEM LOGIC (Final Version with Toggle Fix) ---

    const systemFileInput = document.getElementById('system-file-input');
    const feeFileInput = document.getElementById('fee-file-input');
    const mergeBtn = document.getElementById('merge-btn');
    const mergeError = document.getElementById('merge-error');
    const mergeResultContainer = document.getElementById('merge-result-container');
    
    // UI Elements
    const mergeFilterType = document.getElementById('merge-filter-type');
    const mergeFilterDept = document.getElementById('merge-filter-dept');
    const mergeFilterFee = document.getElementById('merge-filter-fee');
    const mergeColumnList = document.getElementById('merge-column-list');
    const mergeCountDisplay = document.getElementById('merge-count');
    const mergeCombineMode = document.getElementById('merge-combine-mode');
    const downloadMergedBtn = document.getElementById('download-merged-btn'); 
    const mergeDownloadList = document.getElementById('merge-download-list');
    const mergeResetBtn = document.getElementById('merge-reset-btn');

    let mergedGlobalData = []; 

    // --- Event Listeners ---
    mergeBtn.addEventListener('click', handleMergeProcess);
    mergeResetBtn.addEventListener('click', resetMergeFilters);

    [mergeFilterType, mergeFilterDept, mergeFilterFee, mergeCombineMode].forEach(el => {
        el.addEventListener('change', updateMergedUI);
    });

    downloadMergedBtn.addEventListener('click', () => {
        const data = getFilteredMergedData();
        downloadCustomCSV(data, 'Fee_Report_Combined.csv');
    });

    // Toggle All Logic (Select All / Deselect All)
    document.querySelectorAll('.merge-toggle-all').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetId = e.target.dataset.target;
            const list = document.getElementById(targetId);
            if (!list) return;

            const checkboxes = list.querySelectorAll('input[type="checkbox"]');
            // Determine state: If current text is Select All, we check all. If Deselect, we uncheck.
            const shouldCheck = e.target.textContent === 'Select All';
            
            checkboxes.forEach(cb => cb.checked = shouldCheck);
            
            // Toggle text
            e.target.textContent = shouldCheck ? 'Deselect All' : 'Select All';
            
            updateMergedUI();
        });
    });

    // --- Main Process ---
    async function handleMergeProcess() {
        mergeError.classList.add('hidden');
        mergeResultContainer.classList.add('hidden');

        if (!systemFileInput.files.length || !feeFileInput.files.length) {
            showMergeError("Please select both files.");
            return;
        }

        try {
            // 1. Parse Files
            const systemData = await parseFile(systemFileInput.files[0]);
            const feeData = await parseFile(feeFileInput.files[0]);

            if (systemData.length === 0) { showMergeError("File 1 is empty."); return; }
            if (feeData.length === 0) { showMergeError("File 2 is empty."); return; }

            // 2. Identify Headers
            const sysHeaders = Object.keys(systemData[0]);
            const feeHeaders = Object.keys(feeData[0]);

            const sysRegKey = sysHeaders.find(h => /reg\.?\s*no/i.test(h));
            const sysNameKey = sysHeaders.find(h => /student\s*name/i.test(h));
            const sysTypeKey = sysHeaders.find(h => /can\.?\s*type/i.test(h));
            const sysDeptKey = sysHeaders.find(h => /department|dept/i.test(h)); 

            const feeRegKey = feeHeaders.find(h => /reg\.?\s*no/i.test(h));
            const feeDeptKey = feeHeaders.find(h => /department|dept/i.test(h));
            const feeNameKey = feeHeaders.find(h => /fee\s*name/i.test(h));

            if (!sysRegKey) { showMergeError("File 1 Error: Missing 'REG. No' column."); return; }
            if (!feeRegKey) { showMergeError("File 2 Error: Missing 'Reg No' column."); return; }

            // 3. Index File 2
            const feeMap = new Map();
            feeData.forEach(row => {
                const cleanReg = String(row[feeRegKey] || '').replace(/[^\d]/g, '');
                if(cleanReg) {
                    feeMap.set(cleanReg, {
                        dept: row[feeDeptKey], 
                        fee: row[feeNameKey] || 'Unspecified'
                    });
                }
            });

            // --- NEW STEP: Build Department Name Mapping ---
            const deptMapping = {};
            
            if (sysDeptKey) {
                systemData.forEach(sysRow => {
                    const cleanReg = String(sysRow[sysRegKey] || '').replace(/[^\d]/g, '');
                    const match = feeMap.get(cleanReg);
                    const sysDeptVal = sysRow[sysDeptKey];

                    // If student exists in BOTH files, map the File 1 name to the File 2 name
                    if (match && match.dept && sysDeptVal && match.dept !== sysDeptVal) {
                        deptMapping[sysDeptVal] = match.dept;
                    }
                });
            }
            // -----------------------------------------------

            

            mergedGlobalData = systemData.map(sysRow => {
                const cleanReg = String(sysRow[sysRegKey] || '').replace(/[^\d]/g, '');
                const match = feeMap.get(cleanReg);
                
                let sysDeptVal = (sysDeptKey && sysRow[sysDeptKey]) ? sysRow[sysDeptKey] : null;

                // Apply the Auto-Correction Mapping
                if (sysDeptVal && deptMapping[sysDeptVal]) {
                    sysDeptVal = deptMapping[sysDeptVal];
                }

                let finalDept = 'Unknown Dept';
                let feeStatus = '';

                if (match) {
                    // Found
                    finalDept = match.dept || sysDeptVal || 'Unknown Dept';
                    feeStatus = match.fee;
                } else {
                    // Not Found -> Use Corrected File 1 Dept
                    finalDept = sysDeptVal || 'Unknown Dept';
                    feeStatus = 'Not Found in Fee List';
                }

                return {
                    'REG. No': sysRow[sysRegKey],
                    'Student Name': sysRow[sysNameKey],
                    'Can. Type': sysRow[sysTypeKey],
                    'Department': finalDept,
                    'Fee Name': feeStatus
                };
            });

            // 5. Setup Output UI
            populateMergeFilters(mergedGlobalData);
            populateMergeColumnSelector();
            mergeResultContainer.classList.remove('hidden');
            
            updateMergedUI();

        } catch (err) {
            console.error(err);
            showMergeError("Error parsing files. Check console.");
        }
    }

    function resetMergeFilters() {
        [mergeFilterType, mergeFilterDept, mergeFilterFee].forEach(list => {
            list.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
        });
        // Reset Select All text
        document.querySelectorAll('.merge-toggle-all').forEach(el => el.textContent = 'Deselect All');
        updateMergedUI();
    }

    function updateMergedUI() {
        const data = getFilteredMergedData();
        mergeCountDisplay.textContent = `Records selected: ${data.length}`;

        if (mergeCombineMode.checked) {
            downloadMergedBtn.classList.remove('hidden');
            mergeDownloadList.classList.add('hidden');
        } else {
            downloadMergedBtn.classList.add('hidden');
            mergeDownloadList.classList.remove('hidden');
            renderGroupedDownloadList(data);
        }
    }

    function renderGroupedDownloadList(data) {
        mergeDownloadList.innerHTML = '';
        
        if(data.length === 0) {
            mergeDownloadList.innerHTML = '<p class="text-gray-500 italic">No data matches filters.</p>';
            return;
        }

        const groups = {};
        data.forEach(row => {
            const dept = row['Department'] || 'Unknown';
            if (!groups[dept]) groups[dept] = [];
            groups[dept].push(row);
        });

        Object.keys(groups).sort().forEach(deptName => {
            const groupData = groups[deptName];
            
            const div = document.createElement('div');
            div.className = 'flex flex-col sm:flex-row items-center justify-between p-3 bg-white rounded border border-gray-200 shadow-sm';
            
            div.innerHTML = `
                <div class="mb-2 sm:mb-0">
                    <span class="font-bold text-gray-800">${deptName}</span>
                    <span class="text-sm text-gray-500 ml-2">(${groupData.length} students)</span>
                </div>
            `;

            const btn = document.createElement('button');
            btn.className = "px-4 py-1 text-sm bg-gray-800 text-white rounded hover:bg-black transition";
            btn.textContent = "Download CSV";
            
            const safeName = deptName.replace(/[^a-zA-Z0-9]/g, '_');
            btn.onclick = () => downloadCustomCSV(groupData, `Fee_Report_${safeName}.csv`);

            div.appendChild(btn);
            mergeDownloadList.appendChild(div);
        });
    }

    function getFilteredMergedData() {
        const getChecked = (el) => Array.from(el.querySelectorAll('input:checked')).map(cb => cb.value);
        
        const selTypes = getChecked(mergeFilterType);
        const selDepts = getChecked(mergeFilterDept);
        const selFees = getChecked(mergeFilterFee);

        return mergedGlobalData.filter(row => {
            const typeVal = row['Can. Type'] || row['Can. Type'] || 'Unknown Type';
            const deptVal = row['Department'] || 'Unknown Dept';
            const feeVal = row['Fee Name'] || 'Unknown Status';

            const typeMatch = selTypes.includes(typeVal);
            const deptMatch = selDepts.includes(deptVal);
            const feeMatch = selFees.includes(feeVal);

            return typeMatch && deptMatch && feeMatch;
        });
    }

    function downloadCustomCSV(data, filename) {
        if (data.length === 0) { alert("No data."); return; }

        const selectedColumns = [];
        mergeColumnList.querySelectorAll('input:checked').forEach(cb => {
            selectedColumns.push(cb.value);
        });

        if (selectedColumns.length === 0) { alert("Select at least one column."); return; }

        const csvRows = [selectedColumns.join(',')];
        
        data.forEach(row => {
            const values = selectedColumns.map(col => {
                let val = row[col] || '';
                if (/[",\n]/.test(val)) val = `"${val.replace(/"/g, '""')}"`;
                return val;
            });
            csvRows.push(values.join(','));
        });

        const blob = new Blob([csvRows.join('\n')], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function parseFile(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const data = e.target.result;
                let workbook;
                try { workbook = XLSX.read(data, { type: 'binary' }); } catch (err) {}
                if (workbook) {
                    const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                    resolve(XLSX.utils.sheet_to_json(worksheet, { defval: "" }));
                } else { resolve([]); }
            };
            reader.onerror = (err) => reject(err);
            reader.readAsBinaryString(file);
        });
    }

    function showMergeError(msg) {
        mergeError.textContent = msg;
        mergeError.classList.remove('hidden');
    }

    function populateMergeFilters(data) {
        const types = new Set();
        const depts = new Set();
        const fees = new Set();

        data.forEach(row => {
            const typeVal = row['Can. Type'] || row['Can. Type'] || 'Unknown Type';
            const deptVal = row['Department'] || 'Unknown Dept';
            const feeVal = row['Fee Name'] || 'Unknown Status';

            if (typeVal) types.add(typeVal);
            if (deptVal) depts.add(deptVal);
            if (feeVal) fees.add(feeVal);
        });

        fillCheckboxList(mergeFilterType, types);
        fillCheckboxList(mergeFilterDept, depts);
        fillCheckboxList(mergeFilterFee, fees);
    }

    function populateMergeColumnSelector() {
        mergeColumnList.innerHTML = '';
        ['REG. No', 'Student Name', 'Can. Type', 'Department', 'Fee Name'].forEach(col => {
            const div = document.createElement('div');
            div.className = 'flex items-center';
            div.innerHTML = `
                <input type="checkbox" id="mcol-${col.replace(/\s/g, '')}" value="${col}" checked class="h-4 w-4 text-indigo-600 rounded">
                <label for="mcol-${col.replace(/\s/g, '')}" class="ml-2 text-sm text-gray-900">${col}</label>
            `;
            mergeColumnList.appendChild(div);
        });
    }

    function fillCheckboxList(element, valuesSet) {
        element.innerHTML = '';
        Array.from(valuesSet).sort().forEach(val => {
            const div = document.createElement('div');
            div.className = 'flex items-center mb-1';
            div.innerHTML = `
                <input type="checkbox" value="${val}" checked class="h-4 w-4 text-blue-600 rounded">
                <label class="ml-2 text-sm text-gray-700 truncate" title="${val}">${val}</label>
            `;
            element.appendChild(div);
        });
    }
</script>

</div> 
<?php require_once 'includes/footer.php'; ?>