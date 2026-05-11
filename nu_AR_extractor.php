<?php
// --- STEP 1: PHP SECURITY ---
require_once 'includes/functions.php'; 
check_login(); // 1. Check if user is logged in

// 2. Check permission (Make sure this matches your DB key)
if (!user_can('NU_AR_EXTRACTOR_TOOL')) {
    require_once 'includes/header.php';
    echo "<h1>Access Denied</h1><p>You do not have permission to view this tool.</p>";
    require_once 'includes/footer.php';
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
        padding: 20px !important;
        margin: 0 20px 20px 20px !important;
        background: var(--nu-background-color, #f4f7fa) !important;
    }

    /* --- STYLES FOR THIS TOOL --- */
    .nu-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
    }
    .nu-container h2 {
        text-align: center;
        margin-bottom: 2rem;
        color: #007bff;
        font-weight: 700;
    }
    .nu-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        padding: 2rem;
        margin-bottom: 2rem;
    }
    /* Upload Area */
    .nu-container #upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        cursor: pointer;
        transition: background-color 0.3s ease, border-color 0.3s ease;
    }
    .nu-container #upload-area.highlight {
        border-color: #007bff;
        background-color: #f0f8ff;
    }
    .nu-container #file-input { display: none; }
    
    /* Results */
    .nu-container #results-card { display: none; }
    .nu-container .summary-stats {
        display: flex;
        justify-content: space-around;
        text-align: center;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 1.5rem;
    }
    .nu-container .stat-item h3 { margin: 0; font-size: 1.8rem; color: #007bff; }
    .nu-container .stat-item p { margin: 0; font-weight: 500; }
    
    .nu-container .nu-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        gap: 1rem;
    }
    .nu-container #search-input {
        padding: 0.75rem;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        width: 300px;
    }
    .nu-container #results-table-container {
        overflow-x: auto;
        max-height: 500px;
    }
    .nu-container table {
        width: 100%;
        border-collapse: collapse;
        white-space: nowrap;
    }
    .nu-container th, .nu-container td {
        border: 1px solid #dee2e6;
        padding: 12px 15px;
        text-align: left;
    }
    .nu-container th {
        background-color: #f8f9fa;
        position: sticky;
        top: 0;
    }
    
    /* Buttons */
    .nu-container button {
        background-color: #007bff;
        border: none;
        color: white;
        padding: 10px 18px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
    }
    .nu-container button:hover { background-color: #0056b3; }
    .nu-container .secondary-button { background-color: #6c757d; }
    .nu-container .secondary-button:hover { background-color: #5a6268; }
    .nu-container .download-button { background-color: #28a745; }
    .nu-container .download-button:hover { background-color: #218838; }
    .nu-container .small-btn { padding: 5px 10px; font-size: 0.8rem; }

    .nu-container #loader { display: none; text-align: center; padding: 2rem; font-size: 1.2rem; }
    .nu-container #error-message { color: red; text-align: center; padding: 1rem; }
    
    .nu-container .department-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }
    .nu-container .department-item div { display: flex; gap: 0.5rem; }
</style>

<div class="nu-container">
    <h2>NU Admission Result Extractor</h2>

    <div class="nu-card" id="upload-card">
        <h3>1. Upload Department PDF Files</h3>
        <p>Select one or more PDF files. The department name will be taken from the filename.</p>
        <div id="upload-area">
            <input type="file" id="file-input" accept=".pdf" multiple>
            <p><strong>Drag & drop your PDF files here</strong></p>
            <p>or</p>
            <button onclick="document.getElementById('file-input').click();">Browse Files</button>
        </div>
        <div id="loader">Processing... Please wait.</div>
        <div id="error-message"></div>
    </div>

    <div id="results-card">
        <div class="nu-card">
            <h3>2. Consolidated Results</h3>
            <div id="summary-stats" class="summary-stats"></div>
            <div class="nu-controls">
                <input type="text" id="search-input" placeholder="🔍 Search table...">
                <div>
                    <button class="secondary-button" onclick="resetApp()">🔄 Process New Files</button>
                </div>
            </div>
            <div id="results-table-container">
                <table id="results-table"></table>
            </div>
        </div>
        
        <div class="nu-card">
            <h3>3. Download by Department</h3>
            <div id="department-downloads-container"></div>
        </div>
    </div>
</div>

<script>
    // --- THIS WAS THE FIX ---
    // We updated 2.16.105 to 3.4.120 to match header.php
    pdfjsLib.GlobalWorkerOptions.workerSrc = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js`;

    let studentData = [];
    const uploadArea = document.getElementById('upload-area');
    const fileInput = document.getElementById('file-input');
    const loader = document.getElementById('loader');
    const errorMessage = document.getElementById('error-message');
    const searchInput = document.getElementById('search-input');
    const resultsTable = document.getElementById('results-table');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });
    ['dragenter', 'dragover'].forEach(e => uploadArea.addEventListener(e, () => uploadArea.classList.add('highlight')));
    ['dragleave', 'drop'].forEach(e => uploadArea.addEventListener(e, () => uploadArea.classList.remove('highlight')));

    uploadArea.addEventListener('drop', e => handleFiles(e.dataTransfer.files), false);
    fileInput.addEventListener('change', e => handleFiles(e.target.files));

    async function handleFiles(files) {
        if (files.length === 0) return;
        
        loader.style.display = 'block';
        errorMessage.textContent = '';
        studentData = [];
        let filesProcessed = 0;

        for (const file of files) {
            try {
                const departmentName = file.name.split('.').slice(0, -1).join('.') || 'Unknown';
                const arrayBuffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument(arrayBuffer).promise;
                let fullText = '';
                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const textContent = await page.getTextContent();
                    fullText += textContent.items.map(item => item.str).join(' ');
                }
                // Regex to find Admission Roll, Name, and Merit Position
                const regex = /\b(\d{7})\b\s*([A-Za-z.\s()-]+?)\s*(\d)/g;
                let match;
                while ((match = regex.exec(fullText)) !== null) {
                    studentData.push({
                        'Application ID': match[1].trim(),
                        'Applicant\'s Name': match[2].trim().replace(/\s+/g, ' '),
                        'Department': departmentName
                    });
                }
                filesProcessed++;
            } catch (err) {
                console.error('Error processing file:', file.name, err);
                errorMessage.textContent += `Failed to process ${file.name}. \n`;
            }
        }
        
        loader.style.display = 'none';
        if (studentData.length > 0) {
            displayResults(filesProcessed, studentData.length);
        } else if (!errorMessage.textContent) {
            errorMessage.textContent = 'No student data could be extracted. Please check the PDF format.';
        }
    }

    function displayResults(fileCount, studentCount) {
        document.getElementById('upload-card').style.display = 'none';
        document.getElementById('results-card').style.display = 'block';

        const summaryContainer = document.getElementById('summary-stats');
        summaryContainer.innerHTML = `
            <div class="stat-item"><h3>${fileCount}</h3><p>Files Processed</p></div>
            <div class="stat-item"><h3>${studentCount}</h3><p>Total Students Found</p></div>
        `;
        renderTable(studentData);
        renderDepartmentDownloads();
    }
    
    function renderTable(data) {
        if (!data || data.length === 0) {
            resultsTable.innerHTML = '<tbody><tr><td>No data to display.</td></tr></tbody>';
            return;
        }
        const headers = Object.keys(data[0]);
        let tableHTML = `<thead><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead><tbody>`;
        data.forEach(row => {
            tableHTML += `<tr>${headers.map(h => `<td>${row[h]}</td>`).join('')}</tr>`;
        });
        tableHTML += '</tbody>';
        resultsTable.innerHTML = tableHTML;
    }

    function renderDepartmentDownloads() {
        const uniqueDepartments = [...new Set(studentData.map(item => item.Department))].sort();
        const container = document.getElementById('department-downloads-container');
        container.innerHTML = '';

        if(uniqueDepartments.length === 0) {
            container.innerHTML = '<p>No departments found.</p>';
            return;
        }

        uniqueDepartments.forEach(dept => {
            const item = document.createElement('div');
            item.className = 'department-item';
            item.innerHTML = `
                <span>${dept}</span>
                <div>
                    <button class="small-btn download-button" onclick="downloadDepartment('${dept}', 'csv')">CSV</button>
                    <button class="small-btn download-button" onclick="downloadDepartment('${dept}', 'excel')">Excel</button>
                </div>
            `;
            container.appendChild(item);
        });
    }

    searchInput.addEventListener('input', e => {
        const searchTerm = e.target.value.toLowerCase();
        const filteredData = studentData.filter(row => 
            Object.values(row).some(value => String(value).toLowerCase().includes(searchTerm))
        );
        renderTable(filteredData);
    });

    function generateCSV(data, filename) {
        if (data.length === 0) return;
        const headers = Object.keys(data[0]);
        const csvRows = [
            headers.join(','),
            ...data.map(row => headers.map(fieldName => `"${row[fieldName]}"`).join(','))
        ];
        const blob = new Blob([csvRows.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function generateExcel(data, filename) {
        if (data.length === 0) return;
        const worksheet = XLSX.utils.json_to_sheet(data);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Students');
        XLSX.writeFile(workbook, filename);
    }

    function downloadDepartment(departmentName, format) {
        // Filter for the specific department
        const departmentStudents = studentData.filter(student => student.Department === departmentName);

        // Create a new array of objects with only the required columns
        const dataForExport = departmentStudents.map(student => {
            return {
                'Application ID': student['Application ID'],
                'Applicant\'s Name': student['Applicant\'s Name']
            };
        });

        const filename = `${departmentName}_students.${format === 'csv' ? 'csv' : 'xlsx'}`;
        
        if (format === 'csv') {
            generateCSV(dataForExport, filename);
        } else {
            generateExcel(dataForExport, filename);
        }
    }

    function resetApp() {
        studentData = [];
        fileInput.value = '';
        searchInput.value = '';
        document.getElementById('upload-card').style.display = 'block';
        document.getElementById('results-card').style.display = 'none';
        errorMessage.textContent = '';
        loader.style.display = 'none';
    }
</script>

