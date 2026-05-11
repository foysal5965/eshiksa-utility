<?php
// --- STEP 1: PHP SECURITY ---
require_once 'includes/functions.php'; 
check_login(); 

if (!user_can('IMAGE_RENAMER_TOOL')) {
    require_once 'includes/header.php';
    echo "<h1>Access Denied</h1><p>You do not have permission to view this tool.</p>";
 
    exit();
}

require_once 'includes/header.php';
?>

<style>
    /* Override content wrapper for full width */
    .content {
        background: #f3f4f6 !important;
        padding: 20px !important;
    }
    .drag-active { border-color: #3b82f6 !important; background-color: #eff6ff !important; }
    
    .renamer-loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3b82f6;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        animation: spin 1s linear infinite;
        display: inline-block;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    
    #log-area::-webkit-scrollbar { width: 8px; }
    #log-area::-webkit-scrollbar-track { background: #1f2937; }
    #log-area::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
    
    .hidden { display: none; }
</style>

<div class="max-w-5xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden" style="margin-top: 20px;">
    
    <div class="bg-gray-800 text-white p-6">
        <h1 class="text-2xl font-bold">Student Image Renamer</h1>
        <p class="text-gray-300 text-sm mt-1">Upload an Excel list and a folder of images. The system will rename the images using the Student IDs/Roll numbers from the Excel file.</p>
    </div>

    <div class="p-6 space-y-6">

        <div class="space-y-2">
            <h2 class="font-semibold text-gray-800 text-lg">1. Upload Data File (Excel/CSV)</h2>
            <div id="upload-zone" class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center transition-colors cursor-pointer hover:bg-gray-50">
                <div class="flex flex-col items-center justify-center space-y-2">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <p class="text-gray-600 text-sm font-medium">Click to upload Excel/CSV containing Roll Numbers</p>
                </div>
                <input type="file" id="file-input" class="hidden" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
            </div>
            <p id="file-name-display" class="text-sm text-green-600 font-bold hidden"></p>
        </div>

        <div id="config-section" class="hidden space-y-6 border-t pt-6">
            
            <div class="space-y-4">
                <h3 class="font-semibold text-gray-800 border-b pb-2 text-lg">2. Map Columns</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Roll Number Column (New Filename)</label>
                        <select id="col-roll" class="w-full border rounded p-2 text-sm bg-gray-50"></select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Group Column (for filtering)</label>
                        <select id="col-group" class="w-full border rounded p-2 text-sm bg-gray-50"></select>
                    </div>
                </div>
                
                <div class="flex items-center gap-4 bg-gray-50 p-3 rounded mt-2">
                    <span class="text-sm font-medium text-gray-700">Filter by Group:</span>
                    <select id="filter-group" class="border rounded p-1 text-sm bg-white shadow-sm flex-grow max-w-xs">
                        <option value="all">Process All</option>
                    </select>
                    <span class="text-xs text-gray-500" id="record-count"></span>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="font-semibold text-gray-800 border-b pb-2 text-lg">3. Upload Images to Rename</h3>
                
                <div class="bg-orange-50 p-4 rounded-lg border border-orange-100">
                    <p class="text-sm text-orange-800 mb-3 font-medium">
                        <span style="font-weight:bold;">How it works:</span> 
                        1. We sort your uploaded images alphabetically (e.g., IMG_001.jpg, IMG_002.jpg).<br>
                        2. We sort your Excel list based on the group filter.<br>
                        3. We match them in order: <strong>1st Image gets 1st Roll Number</strong>, 2nd Image gets 2nd Roll Number, etc.
                    </p>
                    
                    <div id="local-upload-zone" class="border-2 border-dashed border-orange-300 bg-white rounded-lg p-6 text-center cursor-pointer hover:bg-orange-50">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <p class="text-gray-600 text-sm font-medium">Click to select images folder (Select multiple files)</p>
                        </div>
                        <p class="text-xs text-gray-400 mt-2" id="local-count">0 files selected</p>
                        <input type="file" id="local-files" class="hidden" multiple accept="image/*">
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t">
                <button id="process-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow transition flex items-center gap-2">
                    <span>Rename & Download Zip</span>
                    <div id="btn-loader" class="renamer-loader hidden border-white border-t-transparent"></div>
                </button>
            </div>

            <div id="log-area" class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-xs md:text-sm h-48 overflow-y-auto hidden leading-relaxed">
                <div>> System Ready...</div>
            </div>
        </div>

    </div>
</div>

<script>
    let rawData = [];
    let headers = [];
    let localImages = [];

    // --- DOM Elements ---
    const dropZone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('file-input');
    const configSection = document.getElementById('config-section');
    const logArea = document.getElementById('log-area');
    
    const localDropZone = document.getElementById('local-upload-zone');
    const localInput = document.getElementById('local-files');

    // --- Excel Upload Logic ---
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-active'); });
    // --- FIX IS HERE: Removed extra symbols ---
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-active')); 
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-active');
        handleExcel(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', (e) => handleExcel(e.target.files[0]));

    // --- Local Images Upload Logic ---
    localDropZone.addEventListener('click', () => localInput.click());
    localInput.addEventListener('change', (e) => handleLocalImages(e.target.files));

    function handleLocalImages(files) {
        if (!files.length) return;
        // Convert to array and sort by name to ensure 1, 2, 3 order matches PDF extraction order usually
        localImages = Array.from(files).sort((a, b) => a.name.localeCompare(b.name, undefined, {numeric: true, sensitivity: 'base'}));
        document.getElementById('local-count').textContent = `${localImages.length} files selected. First file: ${localImages[0].name}`;
        log(`Loaded ${localImages.length} local images. Sorted by filename.`, 'text-yellow-300');
    }

    function handleExcel(file) {
        if(!file) return;
        document.getElementById('file-name-display').textContent = `Loaded: ${file.name}`;
        document.getElementById('file-name-display').classList.remove('hidden');
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            rawData = XLSX.utils.sheet_to_json(firstSheet, { defval: "" });
            
            if(rawData.length === 0) { alert("File empty"); return; }
            headers = Object.keys(rawData[0]);
            populateUI();
            configSection.classList.remove('hidden');
            log(`Excel loaded. ${rawData.length} rows found.`, 'text-white');
        };
        reader.readAsArrayBuffer(file);
    }

    function populateUI() {
        // Fill Dropdowns
        ['col-roll', 'col-group'].forEach(id => {
            const sel = document.getElementById(id);
            sel.innerHTML = '';
            headers.forEach(h => sel.add(new Option(h, h)));
        });

        // Heuristics (Smart guess)
        setSelectValue('col-roll', ['roll', 'id', 'reg']);
        setSelectValue('col-group', ['group', 'dept', 'class', 'shift']);

        updateFilterDropdown();
        document.getElementById('col-group').addEventListener('change', updateFilterDropdown);
    }

    function setSelectValue(id, keywords) {
        const sel = document.getElementById(id);
        for(let i=0; i<sel.options.length; i++) {
            if(keywords.some(k => sel.options[i].text.toLowerCase().includes(k))) {
                sel.selectedIndex = i; break;
            }
        }
    }

    function updateFilterDropdown() {
        const groupCol = document.getElementById('col-group').value;
        const filterSel = document.getElementById('filter-group');
        filterSel.innerHTML = '<option value="all">Process All</option>';
        const groups = [...new Set(rawData.map(row => row[groupCol]))].filter(g=>g).sort();
        groups.forEach(g => filterSel.add(new Option(g, g)));
        document.getElementById('record-count').textContent = `${rawData.length} records.`;
    }

    function log(msg, color='text-green-400') {
        const area = document.getElementById('log-area');
        area.classList.remove('hidden');
        const d = document.createElement('div');
        d.className = `mb-1 ${color}`;
        d.innerText = `> ${msg}`;
        area.appendChild(d);
        area.scrollTop = area.scrollHeight;
    }

    // --- PROCESS ---
    document.getElementById('process-btn').addEventListener('click', async () => {
        const btn = document.getElementById('process-btn');
        const loader = document.getElementById('btn-loader');
        
        if (localImages.length === 0) {
            alert("Please upload images first!");
            return;
        }

        btn.disabled = true; loader.classList.remove('hidden');
        
        try {
            const rollCol = document.getElementById('col-roll').value;
            const groupCol = document.getElementById('col-group').value;
            const filterVal = document.getElementById('filter-group').value;
            
            // 1. Filter Data
            let data = rawData;
            if(filterVal !== 'all') data = rawData.filter(r => r[groupCol] == filterVal);
            if(!data.length) throw new Error("No data in selected group.");

            log(`Processing ${data.length} records...`, 'text-blue-300');

            const zip = new JSZip();
            const imgFolder = zip.folder("renamed_images");
            const csvRows = [];

            if (localImages.length < data.length) {
                log(`Warning: You have ${data.length} students but only uploaded ${localImages.length} images.`, 'text-yellow-300');
            }

            // Processing Loop
            for (let i = 0; i < data.length; i++) {
                const roll = data[i][rollCol];
                
                if (i < localImages.length) {
                    const imgFile = localImages[i];
                    // Rename logic: preserve extension or force jpg? Let's preserve for safety.
                    const ext = imgFile.name.split('.').pop();
                    const newName = `${roll}.${ext}`;
                    
                    imgFolder.file(newName, imgFile); // Add file to zip with new name
                    csvRows.push(`${roll},${newName}`);
                    
                    if(i % 50 === 0) log(`Mapped: ${imgFile.name} -> ${newName}`);
                } else {
                    log(`No image found for Roll ${roll} (Ran out of files)`, 'text-red-400');
                    csvRows.push(`${roll},missing`);
                }
            }
            log(`Renamed and packaged ${Math.min(data.length, localImages.length)} images.`, 'text-white');

            // Generate Zip
            zip.file("map_log.csv", "Roll,ImageFilename\n" + csvRows.join("\n"));
            const content = await zip.generateAsync({type:"blob"});
            saveAs(content, `renamed_images_${filterVal}.zip`);
            log("Success! Download started.", 'text-green-400');

        } catch (err) {
            alert(err.message);
            log(err.message, 'text-red-500');
        } finally {
            btn.disabled = false;
            loader.classList.add('hidden');
        }
    });
</script>