const UPLOAD_API_URL = 'process_upload.php';
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

document.addEventListener('DOMContentLoaded', () => {
    const uploadForm = document.getElementById('uploadForm');
    const fileNameDisplay = document.getElementById('file-name-display');
    const pdfFileInput = document.getElementById('pdf_file');
    const uploadStatus = document.getElementById('upload-status');
    const fileUploadArea = document.getElementById('fileUploadArea');
    const submitButton = document.getElementById('submitButton');
    const resetButton = uploadForm ? uploadForm.querySelector('.btn-reset') : null;
    const uploadProgressBar = document.getElementById('uploadProgressBar');
    const uploadProgressText = document.getElementById('uploadProgressText');
    const progressContainer = document.getElementById('progressContainer');
    
    // --- 1. FILE HANDLER (DRAG & DROP) ---
    if (pdfFileInput && fileNameDisplay && fileUploadArea) {
        
        // Klik area untuk membuka dialog file
        fileUploadArea.addEventListener('click', () => pdfFileInput.click());
        // Cegah klik pada input file memicu event parent (infinite loop)
        pdfFileInput.addEventListener('click', (e) => e.stopPropagation());

        pdfFileInput.addEventListener('change', () => {
            handleFileSelection(pdfFileInput.files);
        });

        // Event Drag & Drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.add('drag-over'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, () => fileUploadArea.classList.remove('drag-over'), false);
        });

        fileUploadArea.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFileSelection(files);
        }, false);

        function handleFileSelection(files) {
            if (files.length > 0) {
                const file = files[0];
                
                // Validasi PDF di frontend
                if (file.type !== 'application/pdf') {
                    displayStatus('error', 'Hanya file PDF yang diizinkan.');
                    resetFileUI();
                    return;
                }

                // Masukkan file ke input (untuk drag & drop)
                if (files !== pdfFileInput.files) {
                     const dataTransfer = new DataTransfer();
                     dataTransfer.items.add(file);
                     pdfFileInput.files = dataTransfer.files;
                }

                // Update UI
                fileNameDisplay.textContent = file.name;
                fileNameDisplay.style.color = '#001a41';
                fileUploadArea.classList.add('file-selected');
                
                // Reset Progress UI
                resetProgressUI();
                if(uploadStatus) uploadStatus.style.display = 'none';

            } else {
                resetFileUI();
            }
        }

        function resetFileUI() {
            fileNameDisplay.textContent = 'Belum ada file dipilih';
            fileNameDisplay.style.color = 'var(--text-muted)';
            fileUploadArea.classList.remove('file-selected');
        }

        function resetProgressUI() {
            if(uploadProgressBar) uploadProgressBar.style.width = '0%';
            if(uploadProgressText) {
                uploadProgressText.textContent = '0%';
                uploadProgressText.style.display = 'none';
            }
            if(progressContainer) progressContainer.style.display = 'none';
        }
    }

    // --- 2. SUBMIT FORM (AJAX) ---
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(event) {
            event.preventDefault();

            if (!pdfFileInput || pdfFileInput.files.length === 0) {
                displayStatus('error', 'Mohon pilih file PDF terlebih dahulu.');
                return;
            }

            const file = pdfFileInput.files[0];
            if (file.size > MAX_FILE_SIZE) {
                displayStatus('error', `Ukuran file terlalu besar (Max: 10MB).`);
                return;
            }

            // Siapkan UI Upload
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengunggah...';
            if(progressContainer) progressContainer.style.display = 'block';
            if(uploadProgressText) uploadProgressText.style.display = 'block';

            const formData = new FormData(uploadForm);
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function(event) {
                if (event.lengthComputable) {
                    const percentComplete = Math.round((event.loaded / event.total) * 100);
                    if(uploadProgressBar) uploadProgressBar.style.width = percentComplete + '%';
                    if(uploadProgressText) uploadProgressText.textContent = percentComplete + '%';
                }
            });

            xhr.onload = function() {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-upload"></i> Unggah Sekarang';

                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        const result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            displayStatus('success', result.message || 'Berhasil diunggah!');
                            
                            // Reset Form Total
                            uploadForm.reset();
                            resetFileUI();
                            resetProgressUI();
                        } else {
                            displayStatus('error', result.error || 'Gagal mengunggah.');
                        }
                    } catch (e) {
                        displayStatus('error', 'Respon server tidak valid.');
                        console.error(e);
                    }
                } else {
                    displayStatus('error', `Error HTTP: ${xhr.status}`);
                }
            };

            xhr.onerror = function() {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-upload"></i> Unggah Sekarang';
                displayStatus('error', 'Gagal koneksi ke server.');
            };

            xhr.open('POST', UPLOAD_API_URL);
            xhr.send(formData);
        });

        // Tombol Reset
        if (resetButton) {
            resetButton.addEventListener('click', () => {
                uploadForm.reset();
                resetFileUI();
                resetProgressUI();
                if(uploadStatus) uploadStatus.style.display = 'none';
            });
        }
    }

    function displayStatus(type, message) {
        if (!uploadStatus) return;
        uploadStatus.textContent = message;
        uploadStatus.className = `status-message ${type}`;
        uploadStatus.style.display = 'block';
        
        // Auto hide pesan sukses setelah 5 detik
        if (type === 'success') {
            setTimeout(() => {
                uploadStatus.style.display = 'none';
            }, 5000);
        }
    }
});