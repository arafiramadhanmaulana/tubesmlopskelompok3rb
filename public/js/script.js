const API_URL = 'api.php?action=get_documents';
let allDocuments = [];
let filteredDocuments = [];
let currentSearchQuery = '';
let currentSortBy = 'recent';
let currentSubjectFilter = 'all'; 
let currentPage = 1;
const documentsPerPage = 9;

const SUBJECT_MAP = {
    "DM": "Data Mining", "PS": "Pemodelan Stokastik", "PD": "Pergudangan Data",
    "KP": "Komputasi Paralel", "ADS": "Analisis Data Statistik", "TBD": "Teknologi Basis Data",
    "BD": "Basis Data", "AP": "Algoritma Pemrograman", "DL": "Deep Learning", "ML": "Machine Learning"
};

function cleanCode(str) {
    if (!str) return '';
    return String(str).replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

function normalizeText(text) {
    return String(text).toLowerCase().replace(/[^a-z0-9\-\/\s]/g, '');
}

function getValidDateObj(doc) {
    let dateStr = doc.created_at;
    if (!dateStr || dateStr.startsWith('0000')) dateStr = doc.updated_at; 
    if (!dateStr || dateStr.startsWith('0000')) return new Date(); 
    return new Date(dateStr);
}

document.addEventListener('DOMContentLoaded', () => {
    fetch(API_URL)
        .then(res => res.json())
        .then(data => {
            if(data.error) console.error(data.error);
            else {
                allDocuments = data;
                applyFilters(); 
                initScrollReveal();
            }
        });

    const searchInput = document.getElementById('searchInput');
    if(searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(window.searchTimeout);
            window.searchTimeout = setTimeout(applyFilters, 300);
        });
        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault(); searchInput.focus();
            }
        });
    }

    const chips = document.querySelectorAll('.chip');
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            
            currentSubjectFilter = chip.dataset.value;
            currentPage = 1; 
            applyFilters();
        });
    });
});

function initScrollReveal() {
    const reveals = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('active');
        });
    }, { threshold: 0.1 });
    reveals.forEach(el => observer.observe(el));
}

function applyFilters() {
    currentSearchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
    let filtered = allDocuments;

    if (currentSearchQuery) {
        const searchTerms = currentSearchQuery.split(/\s+/).map(normalizeText);
        filtered = filtered.filter(doc => {
            const realSubject = SUBJECT_MAP[doc.subject_code] || '';
            const combined = normalizeText(`${doc.subject_code} ${realSubject} ${doc.topic} ${doc.description}`);
            return searchTerms.every(term => combined.includes(term));
        });
    }

    if (currentSubjectFilter !== 'all') {
        const targetCode = cleanCode(currentSubjectFilter); 
        
        const targetName = cleanCode(SUBJECT_MAP[currentSubjectFilter] || '');

        filtered = filtered.filter(doc => {
            const docCode = cleanCode(doc.subject_code);
            
            return docCode === targetCode || docCode === targetName;
        });

        console.log(`Filter: ${currentSubjectFilter}, Found: ${filtered.length}`);
    }
    
    filteredDocuments = filtered;
    applySorting();
}

function handleSortChange(val) {
    currentSortBy = val;
    applySorting();
}

function applySorting() {
    const sorted = [...filteredDocuments];
    const sortBy = currentSortBy;

    if (sortBy === 'oldest') sorted.sort((a, b) => getValidDateObj(a) - getValidDateObj(b));
    else if (sortBy === 'az') sorted.sort((a, b) => a.topic.localeCompare(b.topic));
    else sorted.sort((a, b) => getValidDateObj(b) - getValidDateObj(a));

    document.querySelector('.total-documents').textContent = `Menampilkan ${sorted.length} dokumen`;
    renderDocuments(sorted);
}

function renderDocuments(documents) {
    const container = document.getElementById('documentList');
    container.innerHTML = '';
    
    const totalPages = Math.ceil(documents.length / documentsPerPage);
    if (currentPage > totalPages) currentPage = totalPages > 0 ? totalPages : 1;
    
    const start = (currentPage - 1) * documentsPerPage;
    const end = Math.min(start + documentsPerPage, documents.length);
    const toRender = documents.slice(start, end);

    if (toRender.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align:center; padding:80px 20px; opacity:0.7;">
                <i class="fas fa-folder-open" style="font-size:4rem; color:var(--primary); margin-bottom:20px; opacity:0.5;"></i>
                <h3 style="color:var(--primary); margin-bottom:10px;">Tidak ada dokumen ditemukan</h3>
                <p style="color:var(--text-muted);">Coba ubah filter menjadi "Tampilkan Semua".</p>
            </div>`;
        renderPagination(0, 0);
        return;
    }

    toRender.forEach((doc, index) => {
        const card = document.createElement('div');
        card.className = 'doc-card';
        const delay = index * 0.05; 
        card.style.animation = `softEntranceUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) ${delay}s forwards`;
        
        card.onclick = (e) => {
            if(e.target.closest('.card-actions')) return;
            openDetailModal(doc);
        };
        
        const matkulName = SUBJECT_MAP[doc.subject_code] || doc.subject_code;
        
        const dateObj = getValidDateObj(doc);
        const dateStr = dateObj.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        
        let desc = doc.description || '';
        if(desc.length > 90) desc = desc.substring(0, 90) + '...';

        let adminActions = '';
        if (typeof userRole !== 'undefined' && userRole === 'admin') {
            adminActions = `
                <a href="update.php?id=${doc.id}" class="action-btn" title="Edit"><i class="fas fa-pen"></i></a>
                <button onclick="deleteDoc(event, '${doc.id}')" class="action-btn delete" title="Hapus"><i class="fas fa-trash-alt"></i></button>
            `;
        }

        card.innerHTML = `
            <div class="card-top">
                <span class="subject-badge">${matkulName}</span>
                <div class="card-icon"><i class="fas fa-file-pdf"></i></div>
            </div>
            <h3><a href="javascript:void(0)">${doc.topic}</a></h3>
            <p class="doc-desc">${desc}</p>
            <div class="card-footer">
                <span class="upload-date"><i class="far fa-calendar-alt" style="color:var(--accent)"></i> ${dateStr}</span>
                <div class="card-actions">
                    <a href="download.php?file=${doc.file_path}" target="_blank" class="action-btn" title="Download"><i class="fas fa-cloud-download-alt"></i></a>
                    ${adminActions}
                </div>
            </div>
        `;
        container.appendChild(card);
    });
    
    renderPagination(totalPages, documents.length);
    setTimeout(initScrollReveal, 100);
}

function renderPagination(totalPages, totalItems) {
    let container = document.querySelector('.pagination-controls');
    if (!container) return;
    if (totalItems === 0) { container.innerHTML = ''; return; }

    container.innerHTML = `
        <button class="page-btn" onclick="changePage(-1)" ${currentPage === 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>
        <span class="page-info">Halaman ${currentPage} / ${totalPages}</span>
        <button class="page-btn" onclick="changePage(1)" ${currentPage >= totalPages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>
    `;
}

function changePage(dir) {
    currentPage += dir;
    applySorting();
    const targetSection = document.querySelector('.filter-section');
    if(targetSection) {
        const topPos = targetSection.getBoundingClientRect().top + window.scrollY - 100;
        window.scrollTo({ top: topPos, behavior: 'smooth' });
    }
}

function openDetailModal(doc) {
    const modal = document.getElementById('detailModal');
    const iframe = document.getElementById('pdf-frame');
    document.getElementById('m-title').textContent = doc.topic;
    document.getElementById('m-badge').textContent = SUBJECT_MAP[doc.subject_code] || doc.subject_code;
    document.getElementById('m-desc').textContent = doc.description;
    
    const dateObj = getValidDateObj(doc);
    const dateStr = dateObj.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('m-date').textContent = dateStr;
    
    const uploaderName = doc.uploader_name || 'Administrator';
    document.getElementById('m-uploader').textContent = uploaderName;
    document.getElementById('m-download').href = `download.php?file=${doc.file_path}`;
    iframe.src = doc.file_path;
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('detailModal');
    const iframe = document.getElementById('pdf-frame');
    modal.style.display = 'none';
    iframe.src = '';
    document.body.style.overflow = 'auto';
}

function deleteDoc(event, id) {
    event.stopPropagation();
    if(!confirm('PERINGATAN: Dokumen ini akan dihapus permanen!\nLanjutkan?')) return;
    const formData = new FormData();
    formData.append('doc_id', id);
    const btn = event.currentTarget;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    fetch('delete_document.php', { method: 'POST', body: formData })
    .then(async response => {
        const text = await response.text();
        try {
            const data = JSON.parse(text);
            if(data.success) {
                allDocuments = allDocuments.filter(d => d.id != id);
                applyFilters();
            } else {
                alert('Gagal: ' + (data.error || 'Server Error'));
                btn.innerHTML = oldHtml;
                btn.disabled = false;
            }
        } catch(e) {
            console.error(text);
            alert('Error respon server');
            btn.innerHTML = oldHtml;
            btn.disabled = false;
        }
    })
    .catch(err => {
        alert('Koneksi terputus');
        btn.innerHTML = oldHtml;
        btn.disabled = false;
    });
}
