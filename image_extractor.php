<?php
// --- STEP 1: PHP SECURITY ---
require_once 'includes/functions.php'; 
check_login(); 

if (!user_can('IMAGE_EXTRACTOR_TOOL')) {
    require_once 'includes/header.php';
    echo "<h1>Access Denied</h1><p>You do not have permission to view this tool.</p>";
    require_once 'includes/footer.php';
    exit();
}

require_once 'includes/header.php';
?>

<style>
    /* Override content wrapper */
    .content {
        min-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: #f3f4f6 !important;
        box-shadow: none !important;
        border: none !important;
    }

    /* --- TOOL STYLES --- */
    :root { --primary: #4f46e5; --secondary: #db2777; --bg: #f3f4f6; --text: #1f2937; }
    
    .extractor-container { max-width: 1100px; margin: 20px auto; padding: 0 20px; font-family: 'Inter', sans-serif; color: var(--text); }
    
    .ex-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 2rem; }
    .ex-title { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.25rem; margin-bottom: 1rem; color: #374151; }

    .upload-area { 
        border: 2px dashed #cbd5e1; padding: 2rem; text-align: center; 
        border-radius: 8px; cursor: pointer; background: #f9fafb; transition: 0.2s;
        display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
    }
    .upload-area:hover { border-color: var(--primary); background: #eef2ff; }
    
    .btn-tool { 
        background: var(--primary); color: white; padding: 0.8rem 1.5rem; 
        border: none; border-radius: 6px; cursor: pointer; font-weight: 600; 
        display: inline-flex; align-items: center; gap: 8px; font-size: 14px;
    }
    .btn-tool:disabled { background: #9ca3af; cursor: not-allowed; }
    .btn-reset { background: #ef4444; color: white; padding: 4px 10px; font-size: 12px; margin-left: auto; border:none; border-radius: 4px; cursor: pointer; }

    table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
    th, td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    th { background: #f9fafb; font-size: 0.85rem; text-transform: uppercase; color: #6b7280; }

    /* Cropper Styles */
    .cropper-wrapper { display: none; margin-top: 20px; text-align: center; }
    .cropper-container { position: relative; display: inline-block; border: 4px solid #333; line-height: 0; user-select: none; }
    .selection-box { position: absolute; border: 2px solid; pointer-events: none; }
    
    .box-id { border-color: var(--primary); background-color: rgba(79, 70, 229, 0.2); }
    .box-photo { border-color: var(--secondary); background-color: rgba(219, 39, 119, 0.2); }
    .box-simple { border-color: #ef4444; background-color: rgba(239, 68, 68, 0.3); }

    .box-label { 
        position: absolute; top: -18px; left: 0; color: white; 
        padding: 1px 5px; font-size: 10px; font-weight: bold; border-radius: 3px; white-space: nowrap;
    }
    
    .toolbar { display: flex; gap: 10px; justify-content: center; margin-bottom: 15px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 8px; flex-wrap: wrap;}
    .tool-btn { padding: 8px 15px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer; background: #fff; font-weight: 600; display: flex; align-items: center; gap:6px; font-size: 13px;}
    .tool-btn.active { background: #eff6ff; border-color: var(--primary); color: var(--primary); }
    
    /* SVG Icon Style */
    .icon { width: 24px; height: 24px; fill: currentColor; }
    .icon-lg { width: 48px; height: 48px; color: var(--primary); }
    .icon-lg-pink { width: 48px; height: 48px; color: var(--secondary); }
</style>

</div>

<div class="extractor-container">
    <h1 style="text-align:center; margin-bottom:2rem; font-size: 2rem; font-weight: 800; color: #111827;">PDF Image & Data Extractor System</h1>

    <div class="ex-title">
        <svg class="icon" viewBox="0 0 256 256"><path d="M213.66,82.34l-56-56A8,8,0,0,0,152,24H56A16,16,0,0,0,40,40V216a16,16,0,0,0,16,16H200a16,16,0,0,0,16-16V88A8,8,0,0,0,213.66,82.34ZM160,51.31,188.69,80H160ZM200,216H56V40h88V88a8,8,0,0,0,8,8h48V216Zm-32-80a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,136Zm0,32a8,8,0,0,1-8,8H96a8,8,0,0,1,0-16h64A8,8,0,0,1,168,168Z"></path></svg>
        1. Excel Data Extractor
    </div>
    <div class="ex-card">
        <label class="upload-area">
            <input type="file" id="excelInput" multiple accept=".pdf" style="display:none">
            <svg class="icon-lg" viewBox="0 0 256 256"><path d="M237.66,106.35l-80-80a8,8,0,0,0-11.32,0l-80,80A8,8,0,0,0,72,120h32v88a8,8,0,0,0,8,8h32a8,8,0,0,0,8-8V120h32a8,8,0,0,0,5.66-13.65ZM136,192H120V112a8,8,0,0,0-8-8H83.31L128,59.31,172.69,104H144a8,8,0,0,0-8,8Z" opacity="0.2"></path><path d="M240,136v64a16,16,0,0,1-16,16H32a16,16,0,0,1-16-16V136a16,16,0,0,1,16-16H80a8,8,0,0,1,0,16H32v64H224V136H176a8,8,0,0,1,0-16h48A16,16,0,0,1,240,136ZM85.66,77.66,120,43.31V128a8,8,0,0,0,16,0V43.31l34.34,34.35a8,8,0,0,0,11.32-11.32l-48-48a8,8,0,0,0-11.32,0l-48,48A8,8,0,0,0,85.66,77.66Z"></path></svg>
            <div>Upload PDFs (Multiple Files)</div>
        </label>
        <div style="margin-top:1rem; display:flex; justify-content:space-between; align-items:center;">
            <span id="excelStatus" style="color:#6b7280; font-size:0.9rem;">Waiting...</span>
            <button id="excelBtn" class="btn-tool" onclick="exportToExcel()" disabled>Download Excel</button>
        </div>
        <div style="max-height:200px; overflow-y:auto; margin-top:1rem; border:1px solid #e5e7eb; border-radius:6px;">
            <table id="excelTable"><thead><tr><th>Class</th><th>Name</th><th>Father</th><th>Mother</th><th>Mobile</th></tr></thead><tbody></tbody></table>
        </div>
    </div>

    <div class="ex-title">
        <svg class="icon" viewBox="0 0 256 256"><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,16V158.75l-26.07-26.06a16,16,0,0,0-22.63,0l-20,20-44-44a16,16,0,0,0-22.62,0L40,149.37V56ZM40,172l52-52,80,80H40Zm176,28H194.63l-36-36,20-20L216,181.38V200ZM96,112a12,12,0,1,1,12-12A12,12,0,0,1,96,112Z"></path></svg>
        2. Batch Photo (Multiple Files)
    </div>
    <div class="ex-card">
        <label class="upload-area">
            <input type="file" id="imgInput" multiple accept=".pdf" style="display:none">
            <svg class="icon-lg" viewBox="0 0 256 256"><path d="M224,115.55V208a16,16,0,0,1-16,16H48a16,16,0,0,1-16-16V115.55a16,16,0,0,1,8.44-14.1l80-45.71a16,16,0,0,1,15.12,0l80,45.71A16,16,0,0,1,224,115.55Z" opacity="0.2"></path><path d="M208,80H48A16,16,0,0,0,32,96V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V96A16,16,0,0,0,208,80ZM48,96H208v26.67L128,168.34,48,122.67Zm0,112V141.1l76,43.43a8,8,0,0,0,8,0l76-43.43V208Z"></path><path d="M208,32H48A16,16,0,0,0,32,48V80a8,8,0,0,0,16,0V48H208V80a8,8,0,0,0,16,0V48A16,16,0,0,0,208,32Z"></path></svg>
            <div>Upload PDFs (Multiple Files)</div>
        </label>

        <div id="cropperWrapper" class="cropper-wrapper">
            <p style="margin-bottom:10px; font-weight:600; color:#dc2626;">Draw a box around the photo:</p>
            <div class="cropper-container" id="cropContainer">
                <canvas id="pdfCanvas"></canvas>
                <div id="selectionBox" class="selection-box box-simple" style="display:none"></div>
            </div>
        </div>

        <div style="margin-top:1rem; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; padding-top:1rem;">
            <span id="imgStatus" style="color:#6b7280; font-size:0.9rem;">Waiting...</span>
            <button id="imgBtn" class="btn-tool" onclick="processImages()" disabled>Extract Images</button>
        </div>
    </div>

    <div class="ex-title">
        <svg class="icon" viewBox="0 0 256 256"><path d="M224,48V208a16,16,0,0,1-16,16H48a16,16,0,0,1-16-16V48A16,16,0,0,1,48,32H208A16,16,0,0,1,224,48Zm-96,88V48H48v88Zm80,0V48H144v88ZM128,152H48v56H128Zm80,0H144v56h64Z"></path></svg>
        3. Multi-Selection Grid (One File)
    </div>
    <div class="ex-card">
        <label class="upload-area">
            <input type="file" id="multiInput" accept=".pdf" style="display:none">
            <svg class="icon-lg-pink" viewBox="0 0 256 256"><path d="M200,32H168a8,8,0,0,0,0,16h32v32a8,8,0,0,0,16,0V48A16,16,0,0,0,200,32Z"></path><path d="M216,168a8,8,0,0,0-8,8v32H176a8,8,0,0,0,0,16h40a16,16,0,0,0,16-16V176A8,8,0,0,0,216,168Z"></path><path d="M80,208H48V176a8,8,0,0,0-16,0v40a16,16,0,0,0,16,16H80a8,8,0,0,0,0-16Z"></path><path d="M48,80V48H80a8,8,0,0,0,0-16H40A16,16,0,0,0,24,48V80a8,8,0,0,0,16,0Z"></path><path d="M128,88a40,40,0,1,0,40,40A40,40,0,0,0,128,88Zm0,64a24,24,0,1,1,24-24A24,24,0,0,1,128,152Z"></path></svg>
            <div>Upload 1 Large PDF</div>
        </label>

        <div id="multiWrapper" class="cropper-wrapper">
            <div class="toolbar">
                <button class="tool-btn active" id="btnId" onclick="setMode('id')">
                    <svg class="icon" style="width:16px; height:16px;" viewBox="0 0 256 256"><path d="M208,56H144V184h24a8,8,0,0,1,0,16H88a8,8,0,0,1,0-16h24V56H48a8,8,0,0,1,0-16H208a8,8,0,0,1,0,16Z"></path></svg>
                    1. Select IDs (Blue)
                </button>
                <button class="tool-btn" id="btnPhoto" onclick="setMode('photo')">
                    <svg class="icon" style="width:16px; height:16px;" viewBox="0 0 256 256"><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,16V158.75l-26.07-26.06a16,16,0,0,0-22.63,0l-20,20-44-44a16,16,0,0,0-22.62,0L40,149.37V56ZM40,172l52-52,80,80H40Zm176,28H194.63l-36-36,20-20L216,181.38V200ZM96,112a12,12,0,1,1,12-12A12,12,0,0,1,96,112Z"></path></svg>
                    2. Select Photos (Pink)
                </button>
                <div style="width:1px; height:20px; background:#ddd; margin:0 10px;"></div>
                <span id="countDisplay" style="font-size:0.8rem; font-weight:bold; color:#555;">IDs: 0 | Photos: 0</span>
                <button class="btn-reset" onclick="resetMulti()">Reset</button>
            </div>
            
            <div class="cropper-container" id="multiContainer">
                <canvas id="multiCanvas"></canvas>
            </div>
        </div>

        <div style="margin-top:1rem; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; padding-top:1rem;">
            <span id="multiStatus" style="color:#6b7280; font-size:0.9rem;">Waiting...</span>
            <button id="multiBtn" class="btn-tool" style="background:var(--secondary)" onclick="processMultiPage()" disabled>Extract All Pages</button>
        </div>
    </div>

</div>

<script>
    // --- CRITICAL FIX: MATCH VERSION WITH HEADER ---
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';

    // ================= CONFIGURATION =================
    const PREVIEW_SCALE = 1.5; 
    const DOWNLOAD_SCALE = 4.0;
    // =================================================

    // 1. EXCEL DATA
    let excelRows = [];
    document.getElementById('excelInput').addEventListener('change', async (e) => {
        const files = Array.from(e.target.files); 
        if(!files.length) return;
        const tbody = document.querySelector('#excelTable tbody');
        tbody.innerHTML = ''; excelRows = [];
        document.getElementById('excelStatus').innerText = `Processing ${files.length} files...`;
        for(let i=0; i<files.length; i++) {
            try {
                const text = await getPDFText(files[i]);
                const d = parseData(text, files[i].name);
                excelRows.push(d);
                tbody.insertAdjacentHTML('beforeend', `<tr><td>${d.classVal}</td><td><b>${d.studentName}</b></td><td>${d.fatherName}</td><td>${d.motherName}</td><td>${d.mobile}</td></tr>`);
            } catch(e) { console.error(e); alert("Error parsing PDF. Check console."); }
        }
        document.getElementById('excelStatus').innerText = `Done! ${excelRows.length} records.`;
        document.getElementById('excelBtn').disabled = false;
    });

    // 2. BATCH PHOTO (Multiple Files)
    let imgFiles = [];
    let cropRect = null;
    let s2Selecting = false, s2StartX, s2StartY;
    const canvas2 = document.getElementById('pdfCanvas');
    const box2 = document.getElementById('selectionBox');

    document.getElementById('imgInput').addEventListener('change', async (e) => {
        imgFiles = Array.from(e.target.files);
        if(!imgFiles.length) return;
        document.getElementById('cropperWrapper').style.display = 'block';
        document.getElementById('imgStatus').innerText = "Rendering preview...";
        try {
            const pdf = await pdfjsLib.getDocument(await imgFiles[0].arrayBuffer()).promise;
            const page = await pdf.getPage(1);
            const vp = page.getViewport({ scale: PREVIEW_SCALE });
            canvas2.width = vp.width; canvas2.height = vp.height;
            await page.render({ canvasContext: canvas2.getContext('2d'), viewport: vp }).promise;
            document.getElementById('imgStatus').innerText = "Draw a box around the photo.";
        } catch(err) {
            alert("Error rendering PDF: " + err.message);
            console.error(err);
        }
    });

    const cont2 = document.getElementById('cropContainer');
    cont2.addEventListener('mousedown', e => {
        s2Selecting = true;
        const r = canvas2.getBoundingClientRect();
        s2StartX = e.clientX - r.left; s2StartY = e.clientY - r.top;
        box2.style.display = 'block';
        box2.style.left = s2StartX+'px'; box2.style.top = s2StartY+'px';
        box2.style.width = '0px'; box2.style.height = '0px';
    });
    cont2.addEventListener('mousemove', e => {
        if(!s2Selecting) return;
        const r = canvas2.getBoundingClientRect();
        const curX = e.clientX - r.left; const curY = e.clientY - r.top;
        box2.style.width = Math.abs(curX - s2StartX)+'px';
        box2.style.height = Math.abs(curY - s2StartY)+'px';
        box2.style.left = (curX < s2StartX ? curX : s2StartX)+'px';
        box2.style.top = (curY < s2StartY ? curY : s2StartY)+'px';
    });
    cont2.addEventListener('mouseup', () => {
        s2Selecting = false;
        cropRect = { x: parseFloat(box2.style.left), y: parseFloat(box2.style.top), w: parseFloat(box2.style.width), h: parseFloat(box2.style.height) };
        if(cropRect.w > 10) document.getElementById('imgBtn').disabled = false;
    });

    async function processImages() {
        const zip = new JSZip();
        const status = document.getElementById('imgStatus');
        const ratio = DOWNLOAD_SCALE / PREVIEW_SCALE;
        const fW = cropRect.w * ratio; const fH = cropRect.h * ratio;
        const fX = cropRect.x * ratio; const fY = cropRect.y * ratio;
        const tmpCanvas = document.createElement('canvas');
        const ctx = tmpCanvas.getContext('2d');
        tmpCanvas.width = fW; tmpCanvas.height = fH;

        for(let i=0; i<imgFiles.length; i++) {
            status.innerText = `Extracting ${i+1}/${imgFiles.length}...`;
            try {
                const pdf = await pdfjsLib.getDocument(await imgFiles[i].arrayBuffer()).promise;
                const page = await pdf.getPage(1);
                const vp = page.getViewport({ scale: DOWNLOAD_SCALE });
                const renderCanvas = document.createElement('canvas');
                renderCanvas.width = vp.width; renderCanvas.height = vp.height;
                await page.render({ canvasContext: renderCanvas.getContext('2d'), viewport: vp }).promise;
                ctx.drawImage(renderCanvas, fX, fY, fW, fH, 0, 0, fW, fH);
                const blob = await new Promise(r => tmpCanvas.toBlob(r, 'image/png'));
                const seq = String(i + 1).padStart(3, '0');
                zip.file(`${seq}_${imgFiles[i].name.replace('.pdf','.png')}`, blob);
                renderCanvas.width=1; 
            } catch(e) { console.error(e); }
        }
        status.innerText = "Zipping...";
        saveAs(await zip.generateAsync({type:"blob"}), "Batch_Photos_HQ.zip");
        status.innerText = "Done!";
    }

    // 3. MULTI-SELECTION (One File - All Pages)
    let multiDoc = null;
    let multiMode = 'id';
    let idRects = [];
    let photoRects = [];
    let s3Selecting = false, s3StartX, s3StartY;
    let tempBox = null;
    const canvas3 = document.getElementById('multiCanvas');
    const cont3 = document.getElementById('multiContainer');

    document.getElementById('multiInput').addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if(!file) return;
        document.getElementById('multiWrapper').style.display = 'block';
        document.getElementById('multiStatus').innerText = "Loading PDF...";
        try {
            multiDoc = await pdfjsLib.getDocument(await file.arrayBuffer()).promise;
            const page = await multiDoc.getPage(1);
            const vp = page.getViewport({ scale: PREVIEW_SCALE }); 
            canvas3.width = vp.width; canvas3.height = vp.height;
            await page.render({ canvasContext: canvas3.getContext('2d'), viewport: vp }).promise;
            document.getElementById('multiStatus').innerText = `Loaded ${multiDoc.numPages} pages.`;
            resetMulti();
        } catch(err) {
            alert("Error loading PDF: " + err.message);
            console.error(err);
        }
    });

    function setMode(m) {
        multiMode = m;
        document.getElementById('btnId').classList.toggle('active', m === 'id');
        document.getElementById('btnPhoto').classList.toggle('active', m === 'photo');
    }

    function resetMulti() {
        idRects = []; photoRects = [];
        const boxes = cont3.querySelectorAll('.selection-box');
        boxes.forEach(b => b.remove());
        updateCount();
        document.getElementById('multiBtn').disabled = true;
    }

    function updateCount() {
        document.getElementById('countDisplay').innerText = `IDs: ${idRects.length} | Photos: ${photoRects.length}`;
        const ready = idRects.length > 0 && idRects.length === photoRects.length;
        document.getElementById('multiBtn').disabled = !ready;
    }

    cont3.addEventListener('mousedown', e => {
        s3Selecting = true;
        const r = canvas3.getBoundingClientRect();
        s3StartX = e.clientX - r.left; s3StartY = e.clientY - r.top;
        tempBox = document.createElement('div');
        tempBox.className = `selection-box ${multiMode === 'id' ? 'box-id' : 'box-photo'}`;
        tempBox.style.left = s3StartX+'px'; tempBox.style.top = s3StartY+'px';
        tempBox.style.width = '0px'; tempBox.style.height = '0px';
        cont3.appendChild(tempBox);
    });

    cont3.addEventListener('mousemove', e => {
        if(!s3Selecting || !tempBox) return;
        const r = canvas3.getBoundingClientRect();
        const curX = e.clientX - r.left; const curY = e.clientY - r.top;
        const w = Math.abs(curX - s3StartX); const h = Math.abs(curY - s3StartY);
        const l = curX < s3StartX ? curX : s3StartX;
        const t = curY < s3StartY ? curY : s3StartY;
        tempBox.style.width = w+'px'; tempBox.style.height = h+'px';
        tempBox.style.left = l+'px'; tempBox.style.top = t+'px';
    });

    cont3.addEventListener('mouseup', () => {
        if(!s3Selecting || !tempBox) return;
        s3Selecting = false;
        const rect = {
            x: parseFloat(tempBox.style.left), y: parseFloat(tempBox.style.top),
            w: parseFloat(tempBox.style.width), h: parseFloat(tempBox.style.height)
        };
        if(rect.w > 10) {
            const count = (multiMode === 'id' ? idRects.length : photoRects.length) + 1;
            const label = document.createElement('div');
            label.className = 'box-label';
            label.style.background = multiMode === 'id' ? 'var(--primary)' : 'var(--secondary)';
            label.innerText = (multiMode === 'id' ? 'ID ' : 'Photo ') + count;
            tempBox.appendChild(label);
            if(multiMode === 'id') idRects.push(rect);
            else photoRects.push(rect);
            updateCount();
        } else {
            tempBox.remove();
        }
        tempBox = null;
    });

    async function processMultiPage() {
        const zip = new JSZip();
        const status = document.getElementById('multiStatus');
        document.getElementById('multiBtn').disabled = true;

        if(idRects.length !== photoRects.length) {
            alert("Error: The number of IDs and Photos must match!");
            document.getElementById('multiBtn').disabled = false;
            return;
        }

        const ratio = DOWNLOAD_SCALE / PREVIEW_SCALE;
        const existingNames = new Set();

        for(let i=1; i <= multiDoc.numPages; i++) {
            status.innerText = `Processing Page ${i} / ${multiDoc.numPages}...`;
            try {
                const page = await multiDoc.getPage(i);
                
                // 1. Get Text (ID)
                const vp = page.getViewport({ scale: PREVIEW_SCALE }); 
                const txt = await page.getTextContent();
                
                // 2. Render HQ Image
                const hqVp = page.getViewport({ scale: DOWNLOAD_SCALE });
                const renderC = document.createElement('canvas');
                renderC.width = hqVp.width; renderC.height = hqVp.height;
                await page.render({ canvasContext: renderC.getContext('2d'), viewport: hqVp }).promise;

                // 3. Loop pairs on this page
                for(let k=0; k < idRects.length; k++) {
                    const idR = idRects[k];
                    const photoR = photoRects[k];

                    // Extract ID Text
                    let pageID = "";
                    txt.items.forEach(item => {
                        const tx = pdfjsLib.Util.transform(vp.transform, item.transform);
                        const x = tx[4]; const y = tx[5] - item.height;
                        if(x >= idR.x && x <= idR.x+idR.w && y >= idR.y && y <= idR.y+idR.h) {
                            pageID += item.str + " ";
                        }
                    });
                    
                    // Clean Filename
                    pageID = pageID.trim().replace(/[\/\\:*?"<>|]/g, "_");
                    if(!pageID || pageID.length < 2) pageID = `Page${i}_Item${k+1}`;

                    // PREVENT OVERWRITE
                    if(existingNames.has(pageID)) {
                        pageID = `${pageID}_Page${i}_${k+1}`;
                    }
                    existingNames.add(pageID);

                    // Extract Photo
                    const fW = photoR.w * ratio; const fH = photoR.h * ratio;
                    const fX = photoR.x * ratio; const fY = photoR.y * ratio;
                    const cutC = document.createElement('canvas');
                    cutC.width = fW; cutC.height = fH;
                    cutC.getContext('2d').drawImage(renderC, fX, fY, fW, fH, 0, 0, fW, fH);

                    const blob = await new Promise(r => cutC.toBlob(r, 'image/png'));
                    zip.file(`${pageID}.png`, blob);
                    cutC.width=1; 
                }
                renderC.width=1; 

            } catch(e) { console.error(e); }
        }
        status.innerText = "Zipping...";
        saveAs(await zip.generateAsync({type:"blob"}), "Grid_Extraction.zip");
        status.innerText = "Done!";
        document.getElementById('multiBtn').disabled = false;
    }

    // SHARED HELPERS
    async function getPDFText(file) {
        const pdf = await pdfjsLib.getDocument(await file.arrayBuffer()).promise;
        const page = await pdf.getPage(1);
        const t = await page.getTextContent();
        return t.items.map(i=>i.str).join(" ");
    }
    function parseData(text, fn) {
        const mobile = text.match(/01\d{9}/);
        const cls = text.match(/\b(One|Two|Three|Four|Five|Six|Seven|Eight|Nine|Ten)\b/i);
        const name = text.match(/([a-zA-Z\s\.]+?)\s+(?:জন্ম|Birth|20\d{10})/);
        const nids = [...text.matchAll(/([a-zA-Z\s\.]+?)\s+NID/g)];
        return {
            fileName: fn,
            mobile: mobile ? mobile[0] : "Not Found",
            classVal: cls ? cls[0] : "Not Found",
            studentName: name ? clean(name[1]) : "Not Found",
            fatherName: nids[0] ? clean(nids[0][1]) : "Not Found",
            motherName: nids[1] ? clean(nids[1][1]) : "Not Found"
        };
    }
    function clean(s){ return s.replace(/[\(\):]|English|Name/gi,"").trim(); }
    function exportToExcel(){
        const ws = XLSX.utils.json_to_sheet(excelRows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Data");
        XLSX.writeFile(wb, "Student_Data.xlsx");
    }
</script>
