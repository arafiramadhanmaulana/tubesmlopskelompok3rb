// FILE: js/script_update.js
const UPDATE_API_URL = 'process_update.php'; 
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

document.addEventListener('DOMContentLoaded', () => {
    const updateForm = document.getElementById('updateForm');
    const updateStatus = document.getElementById('update-status');
    const updateButton = document.getElementById('updateButton');
    
    const pdfFileInput = document.getElementById('pdf_file');
    const fileNameDisplay = document.getElementById('file-name-display');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const uploadProgressBar = document.getElementById('uploadProgressBar');
    const uploadProgressText = document.getElementById('uploadProgressText');
    const progressContainer = document.getElementById('progressContainer');

    // --- FILE HANDLER (Sama dengan Upload) ---
    if (pdfFileInput && fileNameDisplay && fileUploadArea) {
        
        fileUploadArea.addEventListener('click', () => pdfFileInput.click());
        pdfFileInput.addEventListener('click', (e) => e.stopPropagation());

        pdfFileInput.addEventListener('change', () => {
            if (pdfFileInput.files.length > 0) {
                const file = pdfFileInput.files[0];
                if (file.type !== 'application/pdf') {
                    displayStatus('error', 'Hanya file PDF yang diizinkan.');
                    pdfFileInput.value = ''; // Reset input
                    return;
                }
                fileNameDisplay.textContent = `File Baru: ${file.name}`;
                fileNameDisplay.style.color = '#001a41';
                fileNameDisplay.style.fontWeight = 'bold';
                fileUploadArea.classList.add('file-selected');
            }
        });

        // Drag & Drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, (e) => {
                e.preventDefault(); e.stopPropagation();
            }, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.add('drag-over'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.remove('drag-over'), false);
        });

        fileUploadArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                pdfFileInput.files = files; // Assign file ke input
                const event = new Event('change');
                pdfFileInput.dispatchEvent(event);
            }
        }, false);
    }

    // --- FORM SUBMIT ---
    if (updateForm) {
        updateForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Validasi File Size (Hanya jika ada file baru)
            if (pdfFileInput.files.length > 0) {
                const file = pdfFileInput.files[0];
                if (file.size > MAX_FILE_SIZE) {
                    displayStatus('error', 'Ukuran file terlalu besar (Max: 10MB).');
                    return;
                }
            }

            // UI Loading
            updateButton.disabled = true;
            updateButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
            
            if (pdfFileInput.files.length > 0) {
                if(progressContainer) progressContainer.style.display = 'block';
                if(uploadProgressText) uploadProgressText.style.display = 'block';
            }

            const formData = new FormData(updateForm);
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function(event) {
                if (event.lengthComputable && pdfFileInput.files.length > 0) {
                    const percentComplete = Math.round((event.loaded / event.total) * 100);
                    if(uploadProgressBar) uploadProgressBar.style.width = percentComplete + '%';
                    if(uploadProgressText) uploadProgressText.textContent = percentComplete + '%';
                }
            });

            xhr.onload = function() {
                updateButton.disabled = false;
                updateButton.innerHTML = '<i class="fas fa-save"></i> Simpan Perubahan';

                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            displayStatus('success', result.message || 'Perubahan berhasil disimpan!');
                            setTimeout(() => { window.location.href = 'index.php'; }, 1500);
                        } else {
                            displayStatus('error', result.error || 'Gagal menyimpan perubahan.');
                        }
                    } catch (e) {
                        console.error("Respon Server Error:", xhr.responseText);
                        displayStatus('error', 'Terjadi kesalahan sistem. Cek Console (F12) untuk detail.');
                    }
                } else {
                    displayStatus('error', 'Terjadi kesalahan jaringan (Status: ' + xhr.status + ').');
                }
            };

            xhr.onerror = function() {
                updateButton.disabled = false;
                updateButton.innerHTML = '<i class="fas fa-save"></i> Simpan Perubahan';
                displayStatus('error', 'Gagal koneksi ke server.');
            };

            xhr.open('POST', UPDATE_API_URL);
            xhr.send(formData);
        });
    }

    function displayStatus(type, message) {
        if (!updateStatus) return;
        updateStatus.textContent = message;
        updateStatus.className = `status-message ${type}`;
        updateStatus.style.display = 'block';
        
        if (type === 'error') {
            setTimeout(() => { updateStatus.style.display = 'none'; }, 5000);
        }
    }
});