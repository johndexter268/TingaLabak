<?php
session_start();
require __DIR__ . "../../config/db_config.php";

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $_SESSION['toast_message'] = "User not logged in.";
    $_SESSION['toast_type'] = "error";
    header("Location: login.php");
    exit();
}

// Fetch document requests and templates
try {
    $stmt = $pdo->query("SELECT request_id, firstname, lastname, document_type FROM document_requests WHERE is_archived = 0 ORDER BY lastname, firstname");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $requests = [];
    $_SESSION['toast_message'] = "Failed to load requests: " . $e->getMessage();
    $_SESSION['toast_type'] = "error";
}

try {
    $stmt = $pdo->query("SELECT template_id, document_name, file_path FROM document_templates ORDER BY document_name");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $templates = [];
    $_SESSION['toast_message'] = "Failed to load templates: " . $e->getMessage();
    $_SESSION['toast_type'] = "error";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Document - Barangay Tinga Labak</title>
    <link rel="icon" href="../imgs/BatangasCity.png" type="image/jpg">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&display=stylesheet" rel="stylesheet">
    <link rel="stylesheet" href="sidebar/styles.css">
    <link rel="stylesheet" href="css/documents.css">
    <link rel="stylesheet" href="css/createdocument.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        .preview-actions {
            margin-top: 1rem;
            display: flex;
            gap: 10px;
        }

        .preview-actions form {
            margin: 0;
        }
    </style>
</head>

<body>
    <?php include 'sidebar/sidebar.php'; ?>


    <section class="content-body">
        <div class="page-header">
            <div class="page-header-buttons">
                <a href="documents.php" class="btn btn-secondary back-btn">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Documents</span>
                </a>
                <button class="btn btn-primary choose-btn" id="chooseDocumentBtn">
                    <i class="fas fa-file-alt"></i>
                    <span>Choose Document</span>
                </button>
            </div>
        </div>

        <div class="document-preview-container" id="previewContainer" style="display: none;">
            <div class="card">
                <div class="card-header">
                    <h3>Document Preview</h3>
                </div>
                <div class="card-body">
                    <div class="request-details" id="requestDetails">
                        <table>
                            <tbody id="requestDetailsBody"></tbody>
                        </table>
                    </div>

                    <div class="annotation-controls" id="annotationControls" style="display: none;">
                        <div class="annotation-mode" id="annotationStatus">
                            <i class="fas fa-info-circle"></i> Click on the PDF to add text annotations
                        </div>

                        <div class="annotation-row">
                            <label for="annotationText">Text:</label>
                            <input type="text" id="annotationText" placeholder="Enter text to add to PDF" value="Sample Text">
                            <button id="addTextBtn" onclick="enableTextMode()">
                                <i class="fas fa-font"></i> Add Text
                            </button>
                        </div>

                        <div class="annotation-row">
                            <div class="font-control">
                                <label for="fontFamily">Font:</label>
                                <select id="fontFamily" onchange="updateSelectedTextFont()">
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Helvetica">Helvetica</option>
                                    <option value="Courier New">Courier New</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Verdana">Verdana</option>
                                    <option value="Trebuchet MS">Trebuchet MS</option>
                                    <option value="Impact">Impact</option>
                                </select>
                            </div>

                            <div class="font-control">
                                <label for="fontSize">Size:</label>
                                <input type="range" id="fontSize" min="8" max="72" value="16" onchange="updateSelectedTextSize()" oninput="updateFontSizeDisplay()">
                                <span class="font-size-display" id="fontSizeDisplay">16px</span>
                            </div>

                            <div class="font-control">
                                <label for="textColor">Color:</label>
                                <input type="color" id="textColor" value="#000000" onchange="updateSelectedTextColor()">
                            </div>
                        </div>

                        <div class="annotation-row">
                            <button id="boldBtn" onclick="toggleTextBold()">
                                <i class="fas fa-bold"></i> Bold
                            </button>
                            <button id="italicBtn" onclick="toggleTextItalic()">
                                <i class="fas fa-italic"></i> Italic
                            </button>
                            <button id="underlineBtn" onclick="toggleTextUnderline()">
                                <i class="fas fa-underline"></i> Underline
                            </button>
                            <button id="deleteTextBtn" onclick="deleteSelectedText()" class="danger">
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                        </div>

                        <div class="annotation-row">
                            <button id="clearAnnotationsBtn" onclick="clearAllAnnotations()" class="danger">
                                <i class="fas fa-eraser"></i> Clear All
                            </button>
                            <button id="undoBtn" onclick="undoLastAction()">
                                <i class="fas fa-undo"></i> Undo
                            </button>
                            <button id="redoBtn" onclick="redoLastAction()">
                                <i class="fas fa-redo"></i> Redo
                            </button>
                        </div>
                    </div>

                    <div class="pdf-container">
                        <canvas id="fabricCanvas"></canvas>
                        <embed id="pdfEmbed" type="application/pdf" style="display: none;">
                    </div>
                </div>
            </div>
            <div class="preview-actions">
                <form method="POST" id="saveDocumentForm">
                    <input type="hidden" id="save_request_id" name="request_id">
                    <input type="hidden" id="save_template_id" name="template_id">
                    <input type="hidden" id="save_file_path" name="file_path">
                    <button type="submit" name="save_document" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                </form>
                <form method="GET" action="download_file.php" id="downloadDocumentForm">
                    <input type="hidden" id="download_file_path" name="file_path">
                    <button type="submit" class="btn btn-primary" id="downloadBtn" disabled>
                        <i class="fas fa-download"></i> Download
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Choose Document Modal -->
    <div class="modal" id="chooseDocumentModal">
        <div class="modal-content modal-sm">
            <div class="modal-header">
                <h3>Choose Document</h3>
                <button class="modal-close" id="closeChooseModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="chooseDocumentForm" class="modal-form">
                <div class="form-group">
                    <label for="request_id">Document Request <span class="required">*</span></label>
                    <select id="request_id" name="request_id" required>
                        <option value="">Select Request</option>
                        <?php foreach ($requests as $request): ?>
                            <option value="<?php echo htmlspecialchars($request['request_id']); ?>" data-document-type="<?php echo htmlspecialchars($request['document_type']); ?>">
                                <?php echo htmlspecialchars($request['firstname'] . ' ' . $request['lastname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="template_id">Document Template <span class="required">*</span></label>
                    <select id="template_id" name="template_id" required>
                        <option value="">Select Document</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo htmlspecialchars($template['template_id']); ?>" data-document-name="<?php echo htmlspecialchars($template['document_name']); ?>">
                                <?php echo htmlspecialchars($template['document_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelChooseBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i>
                        Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const chooseModal = document.getElementById('chooseDocumentModal');
        const chooseBtn = document.getElementById('chooseDocumentBtn');
        const closeChooseBtn = document.getElementById('closeChooseModal');
        const cancelChooseBtn = document.getElementById('cancelChooseBtn');
        const previewContainer = document.getElementById('previewContainer');
        const requestSelect = document.getElementById('request_id');
        const templateSelect = document.getElementById('template_id');
        const fabricCanvasElement = document.getElementById('fabricCanvas');
        const pdfEmbed = document.getElementById('pdfEmbed');
        const annotationControls = document.getElementById('annotationControls');
        const downloadBtn = document.getElementById('downloadBtn');
        const downloadFilePath = document.getElementById('download_file_path');

        let pdfDocument = null;
        let fabricCanvas = null;
        let currentPage = 1;
        let pdfScale = 1.2;
        let isTextMode = false;
        let annotationHistory = [];
        let redoHistory = [];
        let originalPdfBytes = null;

        // Set PDF.js worker
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        chooseBtn.addEventListener('click', () => {
            chooseModal.style.display = 'block';
        });

        closeChooseBtn.addEventListener('click', () => {
            chooseModal.style.display = 'none';
        });

        cancelChooseBtn.addEventListener('click', () => {
            chooseModal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === chooseModal) {
                chooseModal.style.display = 'none';
            }
        });

        // Auto-select template based on document_type
        requestSelect.addEventListener('change', () => {
            const selectedOption = requestSelect.options[requestSelect.selectedIndex];
            const documentType = selectedOption ? selectedOption.getAttribute('data-document-type') : '';

            templateSelect.value = '';
            if (documentType) {
                for (let option of templateSelect.options) {
                    if (option.getAttribute('data-document-name').toLowerCase() === documentType.toLowerCase()) {
                        templateSelect.value = option.value;
                        break;
                    }
                }
            }
        });

        // Initialize PDF with Fabric.js overlay
        async function initPDFWithAnnotations(pdfUrl) {
            try {
                if (fabricCanvas) {
                    fabricCanvas.dispose();
                }

                const response = await fetch(pdfUrl);
                if (!response.ok) {
                    throw new Error(`Failed to fetch PDF: ${response.status}`);
                }
                originalPdfBytes = await response.arrayBuffer();

                pdfDocument = await pdfjsLib.getDocument({
                    data: originalPdfBytes
                }).promise;
                const page = await pdfDocument.getPage(currentPage);

                const viewport = page.getViewport({
                    scale: pdfScale
                });
                const tempCanvas = document.createElement('canvas');
                const tempContext = tempCanvas.getContext('2d');

                tempCanvas.width = viewport.width;
                tempCanvas.height = viewport.height;

                await page.render({
                    canvasContext: tempContext,
                    viewport: viewport
                }).promise;

                fabricCanvas = new fabric.Canvas('fabricCanvas', {
                    width: viewport.width,
                    height: viewport.height,
                    backgroundColor: 'white'
                });

                const pdfImageUrl = tempCanvas.toDataURL('image/png', 1.0);
                fabric.Image.fromURL(pdfImageUrl, (img) => {
                    img.set({
                        left: 0,
                        top: 0,
                        selectable: false,
                        evented: false,
                        excludeFromExport: false
                    });
                    fabricCanvas.setBackgroundImage(img, fabricCanvas.renderAll.bind(fabricCanvas));
                    saveInitialState();
                });

                fabricCanvas.on('mouse:down', (e) => {
                    if (isTextMode && !e.target) {
                        addTextAtPosition(e.pointer.x, e.pointer.y);
                    }
                });

                fabricCanvas.on('selection:created', updateFontControls);
                fabricCanvas.on('selection:updated', updateFontControls);
                fabricCanvas.on('selection:cleared', clearFontControls);

                annotationControls.style.display = 'block';
                fabricCanvasElement.style.display = 'block';
                pdfEmbed.style.display = 'none';

                updateAnnotationStatus('PDF loaded successfully. Click "Add Text" then click on the PDF to place text.');
            } catch (error) {
                console.error('PDF loading failed:', error);
                pdfEmbed.src = pdfUrl;
                pdfEmbed.style.display = 'block';
                fabricCanvasElement.style.display = 'none';
                annotationControls.style.display = 'none';
                showToast("PDF annotations not available. Using basic preview.", "error");
            }
        }

        function saveInitialState() {
            annotationHistory = [];
            redoHistory = [];
            setTimeout(() => {
                saveState();
            }, 100);
        }

        function enableTextMode() {
            const textInput = document.getElementById('annotationText');
            if (!textInput.value.trim()) {
                showToast("Please enter text to add", "error");
                return;
            }

            isTextMode = !isTextMode;
            const addTextBtn = document.getElementById('addTextBtn');

            if (isTextMode) {
                addTextBtn.classList.add('active');
                addTextBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
                updateAnnotationStatus(`Text mode enabled. Click on the PDF to place: "${textInput.value}"`);
                fabricCanvas.defaultCursor = 'crosshair';
            } else {
                addTextBtn.classList.remove('active');
                addTextBtn.innerHTML = '<i class="fas fa-font"></i> Add Text';
                updateAnnotationStatus('Text mode disabled. Click "Add Text" to enable.');
                fabricCanvas.defaultCursor = 'default';
            }
        }

        function addTextAtPosition(x, y) {
            const textInput = document.getElementById('annotationText');
            const fontFamily = document.getElementById('fontFamily').value;
            const fontSize = parseInt(document.getElementById('fontSize').value);
            const textColor = document.getElementById('textColor').value;
            const text = textInput.value.trim();

            if (!text) return;

            saveState();

            const textObj = new fabric.Text(text, {
                left: x,
                top: y,
                fontSize: fontSize,
                fontFamily: fontFamily,
                fill: textColor,
                selectable: true,
                editable: true,
                cornerStyle: 'circle',
                cornerColor: '#007bff',
                borderColor: '#007bff',
                cornerSize: 10
            });

            fabricCanvas.add(textObj);
            fabricCanvas.setActiveObject(textObj);
            fabricCanvas.renderAll();

            isTextMode = false;
            const addTextBtn = document.getElementById('addTextBtn');
            addTextBtn.classList.remove('active');
            addTextBtn.innerHTML = '<i class="fas fa-font"></i> Add Text';
            updateAnnotationStatus('Text added successfully. You can now select and edit it.');
            fabricCanvas.defaultCursor = 'default';

            updateFontControls();
        }

        function updateFontControls() {
            const activeObject = fabricCanvas.getActiveObject();
            if (activeObject && activeObject.type === 'text') {
                document.getElementById('fontFamily').value = activeObject.fontFamily || 'Arial';
                document.getElementById('fontSize').value = activeObject.fontSize || 16;
                document.getElementById('textColor').value = activeObject.fill || '#000000';
                document.getElementById('fontSizeDisplay').textContent = (activeObject.fontSize || 16) + 'px';

                document.getElementById('boldBtn').classList.toggle('active', activeObject.fontWeight === 'bold');
                document.getElementById('italicBtn').classList.toggle('active', activeObject.fontStyle === 'italic');
                document.getElementById('underlineBtn').classList.toggle('active', activeObject.underline === true);
            }
        }

        function clearFontControls() {
            document.getElementById('boldBtn').classList.remove('active');
            document.getElementById('italicBtn').classList.remove('active');
            document.getElementById('underlineBtn').classList.remove('active');
        }

        function updateFontSizeDisplay() {
            const fontSize = document.getElementById('fontSize').value;
            document.getElementById('fontSizeDisplay').textContent = fontSize + 'px';
        }

        function updateSelectedTextFont() {
            const activeObject = fabricCanvas.getActiveObject();
            if (activeObject && activeObject.type === 'text') {
                saveState();
                activeObject.set('fontFamily', document.getElementById('fontFamily').value);
                fabricCanvas.renderAll();
            }
        }

        function updateSelectedTextSize() {
            const activeObject = fabricCanvas.getActiveObject();
            if (activeObject && activeObject.type === 'text') {
                saveState();
                const fontSize = parseInt(document.getElementById('fontSize').value);
                activeObject.set('fontSize', fontSize);
                fabricCanvas.renderAll();
                updateFontSizeDisplay();
            }
        }

        function updateSelectedTextColor() {
            const activeObject = fabricCanvas.getActiveObject();
            if (activeObject && activeObject.type === 'text') {
                saveState();
                activeObject.set('fill', document.getElementById('textColor').value);
                fabricCanvas.renderAll();
            }
        }

        function toggleTextBold() {
            const activeObject = fabricCanvas.getActiveObject();
            if (activeObject && activeObject.type === 'text') {
                saveState();
                const isBold = activeObject.fontWeight === 'bold';
                activeObject.set('fontWeight', isBold ? 'normal' : 'bold');
                fabricCanvas.renderAll();
                document.getElementById('boldBtn').classList.toggle('active', !isBold);
            }
        }

        function toggleTextItalic() {
            const activeObject = fabricCanvas.getActiveObject();
            if (activeObject && activeObject.type === 'text') {
                saveState();
                const isItalic = activeObject.fontStyle === 'italic';
                activeObject.set('fontStyle', isItalic ? 'normal' : 'italic');
                fabricCanvas.renderAll();
                document.getElementById('italicBtn').classList.toggle('active', !isItalic);
            }
        }

        function toggleTextUnderline() {
            const activeObject = fabricCanvas.getActiveObject();
            if (activeObject && activeObject.type === 'text') {
                saveState();
                const isUnderlined = activeObject.underline === true;
                activeObject.set('underline', !isUnderlined);
                fabricCanvas.renderAll();
                document.getElementById('underlineBtn').classList.toggle('active', !isUnderlined);
            }
        }

        function deleteSelectedText() {
            const activeObject = fabricCanvas.getActiveObject();
            if (activeObject) {
                saveState();
                fabricCanvas.remove(activeObject);
                fabricCanvas.renderAll();
                updateAnnotationStatus('Selected text deleted.');
            } else {
                showToast('No text selected to delete', 'warning');
            }
        }

        function saveState() {
            const state = JSON.stringify(fabricCanvas.toJSON());
            annotationHistory.push(state);
            if (annotationHistory.length > 50) {
                annotationHistory.shift();
            }
            redoHistory = [];
        }

        function clearAllAnnotations() {
            if (!fabricCanvas) return;

            saveState();
            const objects = fabricCanvas.getObjects().filter(obj => obj.type !== 'image');
            objects.forEach(obj => fabricCanvas.remove(obj));
            fabricCanvas.renderAll();
            updateAnnotationStatus('All annotations cleared.');
        }

        function undoLastAction() {
            if (annotationHistory.length > 1) {
                const currentState = annotationHistory.pop();
                redoHistory.push(currentState);
                const lastState = annotationHistory[annotationHistory.length - 1];

                fabricCanvas.loadFromJSON(lastState, () => {
                    fabricCanvas.renderAll();
                    updateAnnotationStatus('Last action undone.');
                });
            } else {
                showToast("Nothing to undo", "warning");
            }
        }

        function redoLastAction() {
            if (redoHistory.length > 0) {
                const redoState = redoHistory.pop();
                annotationHistory.push(redoState);

                fabricCanvas.loadFromJSON(redoState, () => {
                    fabricCanvas.renderAll();
                    updateAnnotationStatus('Action redone.');
                });
            } else {
                showToast("Nothing to redo", "warning");
            }
        }

        function updateAnnotationStatus(message) {
            const statusElement = document.getElementById('annotationStatus');
            statusElement.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
        }

        async function generateAnnotatedPDF() {
            if (!fabricCanvas) return null;

            try {
                const textObjects = fabricCanvas.getObjects().filter(obj => obj.type === 'text');
                if (textObjects.length === 0) {
                    return document.getElementById('save_file_path').value;
                }

                // Use PDF-lib instead of jsPDF for better PDF manipulation
                // First, let's try a simpler approach using canvas export
                const canvasDataURL = fabricCanvas.toDataURL({
                    format: 'png',
                    quality: 1.0,
                    multiplier: 2 // Higher resolution
                });

                // Get canvas dimensions
                const canvasWidth = fabricCanvas.width;
                const canvasHeight = fabricCanvas.height;

                // Convert to PDF dimensions (72 DPI)
                const pdfWidth = canvasWidth * 0.75;
                const pdfHeight = canvasHeight * 0.75;

                const {
                    jsPDF
                } = window.jspdf;
                const pdf = new jsPDF({
                    orientation: pdfWidth > pdfHeight ? 'landscape' : 'portrait',
                    unit: 'pt',
                    format: [pdfWidth, pdfHeight]
                });

                // Add the entire canvas (including background PDF) as an image
                pdf.addImage(
                    canvasDataURL,
                    'PNG',
                    0,
                    0,
                    pdfWidth,
                    pdfHeight,
                    undefined,
                    'FAST'
                );

                return pdf.output('datauristring');

            } catch (error) {
                console.error('Failed to generate annotated PDF:', error);
                showToast('Failed to generate PDF: ' + error.message, 'error');
                return null;
            }
        }

        function showToast(message, type = 'info') {
            const bgColors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };

            Toastify({
                text: message,
                duration: 5000,
                gravity: "top",
                position: "right",
                backgroundColor: bgColors[type] || bgColors.info,
                stopOnFocus: true,
            }).showToast();
        }

        function formatDate(dateString, options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            if (isNaN(date)) return dateString;
            return date.toLocaleDateString('en-US', options);
        }

        document.getElementById('chooseDocumentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const requestId = document.getElementById('request_id').value;
            const templateId = document.getElementById('template_id').value;

            if (!requestId || !templateId) {
                showToast("Please select both a request and a document template", "error");
                return;
            }

            try {
                const response = await fetch(`get_request_details.php?request_id=${requestId}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const requestData = await response.json();
                if (requestData.error) {
                    throw new Error(requestData.error);
                }

                const requestDetailsBody = document.getElementById('requestDetailsBody');
                const fullName = `${requestData.firstname} ${requestData.middlename ? requestData.middlename + ' ' : ''}${requestData.lastname}`;
                const fullAddress = [
                    requestData.house_number,
                    requestData.purok_sitio_street,
                    requestData.subdivision,
                    requestData.barangay,
                    requestData.city_municipality,
                    requestData.province,
                    requestData.zip_code
                ].filter(Boolean).join(', ') || 'N/A';

                requestDetailsBody.innerHTML = `
                    <tr><th>Full Name</th><td>${fullName}</td></tr>
                    <tr><th>Gender</th><td>${requestData.gender ?? 'N/A'}</td></tr>
                     <tr><th>Date of Birth</th><td>${formatDate(requestData.dob)}</td></tr>
                    <tr><th>Contact Number</th><td>${requestData.contact ?? 'N/A'}</td></tr>
                    <tr><th>Email Address</th><td>${requestData.email ?? 'N/A'}</td></tr>
                    <tr><th>Civil Status</th><td>${requestData.civil_status ?? 'N/A'}</td></tr>
                    <tr><th>Sector</th><td>${requestData.sector ?? 'N/A'}</td></tr>
                    <tr><th>Years of Residency</th><td>${requestData.years_of_residency ?? 'N/A'}</td></tr>
                    <tr><th>Complete Address</th><td>${fullAddress}</td></tr>
                    <tr><th>Requested Document </th><td>${requestData.document_type ?? 'N/A'}</td></tr>
                    <tr><th>Purpose of Request</th><td>${requestData.purpose_of_request ?? 'N/A'}</td></tr>
                    <tr><th>Requesting for Self</th><td>${requestData.requesting_for_self ? 'Yes' : 'No'}</td></tr>
                    <tr><th>Date Requested</th><td>${formatDate(requestData.created_at, { 
        year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
    })}</td></tr>
                    <tr><th>Current Status</th><td>${requestData.status ?? 'N/A'}</td></tr>
                `;
            } catch (error) {
                showToast("Failed to load request details: " + error.message, "error");
            }

            try {
                const response = await fetch(`get_template.php?template_id=${templateId}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }

                const fileExtension = data.file_path.split('.').pop().toLowerCase();
                if (fileExtension === 'pdf') {
                    await initPDFWithAnnotations(data.file_path);
                    document.getElementById('save_file_path').value = data.file_path;
                } else if (fileExtension === 'docx') {
                    fabricCanvasElement.style.display = 'none';
                    annotationControls.style.display = 'none';
                    pdfEmbed.style.display = 'block';
                    pdfEmbed.outerHTML = `<p style="padding: 20px; text-align: center; color: #666;">Preview not available for .docx files.<br>File: ${data.document_name}</p>`;
                    document.getElementById('save_file_path').value = data.file_path;
                } else {
                    fabricCanvasElement.style.display = 'none';
                    annotationControls.style.display = 'none';
                    pdfEmbed.style.display = 'block';
                    pdfEmbed.outerHTML = `<p style="padding: 20px; text-align: center; color: #666;">Unsupported file format for preview.</p>`;
                    document.getElementById('save_file_path').value = data.file_path;
                }

                document.getElementById('save_request_id').value = requestId;
                document.getElementById('save_template_id').value = templateId;
                previewContainer.style.display = 'block';
                chooseModal.style.display = 'none';
            } catch (error) {
                showToast("Failed to load document template: " + error.message, "error");
            }
        });

        // In create_document.php, update the submit handler
        document.getElementById('saveDocumentForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;

            try {
                // Generate annotated PDF if canvas exists and has annotations
                if (fabricCanvas) {
                    const textObjects = fabricCanvas.getObjects().filter(obj => obj.type === 'text');
                    if (textObjects.length > 0) {
                        updateAnnotationStatus('Generating annotated PDF...');
                        const annotatedPdf = await generateAnnotatedPDF();
                        if (annotatedPdf && annotatedPdf !== document.getElementById('save_file_path').value) {
                            document.getElementById('save_file_path').value = annotatedPdf;
                        }
                    }
                }

                // Prepare form data
                const formData = new FormData(e.target);
                formData.append('save_document', '1'); // Explicitly add save_document

                // Log form data for debugging
                console.log('Sending form data:');
                for (let [key, value] of formData.entries()) {
                    console.log(`${key}: ${value.length > 100 ? value.substring(0, 100) + '...' : value}`);
                }

                // Send request to dedicated save endpoint
                const response = await fetch('save_document.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status} ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const responseText = await response.text();
                    console.error('Expected JSON but got:', contentType);
                    console.error('Response text:', responseText);
                    throw new Error(`Expected JSON response but got ${contentType}`);
                }

                const result = await response.json();
                if (result.success) {
                    showToast(result.message || "Document saved successfully!", "success");
                    document.getElementById('download_file_path').value = result.file_path;
                    document.getElementById('downloadBtn').disabled = false;
                    updateAnnotationStatus('Document saved successfully! You can now download it.');
                } else {
                    throw new Error(result.message || "Failed to save document");
                }
            } catch (error) {
                console.error('Save error:', error);
                showToast("Failed to save document: " + error.message, "error");
                updateAnnotationStatus('Save failed. Please try again.');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            updateFontSizeDisplay();
        });

        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'z' && !e.shiftKey) {
                e.preventDefault();
                undoLastAction();
            } else if (e.ctrlKey && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
                e.preventDefault();
                redoLastAction();
            } else if (e.key === 'Delete' || e.key === 'Backspace') {
                const activeObject = fabricCanvas?.getActiveObject();
                if (activeObject && activeObject.type === 'text') {
                    e.preventDefault();
                    deleteSelectedText();
                }
            }
        });

        <?php if (isset($_SESSION['toast_message'])): ?>
            showToast("<?php echo htmlspecialchars($_SESSION['toast_message']); ?>", "<?php echo htmlspecialchars($_SESSION['toast_type']); ?>");
            <?php
            unset($_SESSION['toast_message']);
            unset($_SESSION['toast_type']);
            ?>
        <?php endif; ?>
    </script>
</body>

</html>