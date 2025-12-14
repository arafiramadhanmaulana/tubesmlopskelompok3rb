# Project MLOps: Repository Modul Praktikum Sains Data 🚀

**Sistem Repository Cerdas Berbasis Generative AI & Web Application**

Project ini bertujuan untuk membangun sistem manajemen untuk modul praktikum Sains Data. Sistem ini mengintegrasikan **Workflow MLOps** (Machine Learning Operations) mulai dari pengambilan data (Ingestion), pemrosesan menggunakan LLM (Large Language Model), hingga deployment ke aplikasi web interaktif dengan mekanisme monitoring *Human-in-the-Loop*.

-----

## 📋 Daftar Isi

1.  [Latar Belakang & Tujuan](https://www.google.com/search?q=%23-latar-belakang--tujuan)
2.  [Arsitektur Sistem (Workflow)](https://www.google.com/search?q=%23-arsitektur-sistem)
3.  [Tech Stack](https://www.google.com/search?q=%23%25EF%25B8%258F-tech-stack)
4.  [Implementasi MLOps (Menjawab Tugas)](https://www.google.com/search?q=%23-implementasi-mlops)
      - [Tahap 1: Build (Data & Modeling)](https://www.google.com/search?q=%23tahap-1-build-data--modeling)
      - [Tahap 2: Deploy (Serving)](https://www.google.com/search?q=%23tahap-2-deploy-serving)
      - [Tahap 3: Monitor (Governance)](https://www.google.com/search?q=%23tahap-3-monitor-governance)
5.  [Instalasi & Penggunaan](https://www.google.com/search?q=%23-instalasi--penggunaan)
6.  [Struktur Project](https://www.google.com/search?q=%23-struktur-project)
7.  [Tim Pengembang](https://www.google.com/search?q=%23-tim-pengembang)

-----

## 🎯 Latar Belakang & Tujuan

**Masalah:** Modul praktikum seringkali tercecer, tidak terstruktur, dan sulit dicari kembali oleh mahasiswa.
**Solusi:** Membangun repositori terpusat yang mampu membaca isi file PDF secara otomatis dan mengklasifikasikannya menggunakan AI.

**Objective (Tujuan Project):**

1.  **Mengenal Workflow MLOps:** Menerapkan siklus *Build, Deploy, Monitor*.
2.  **Otomatisasi Metadata:** Menggunakan GenAI untuk mengekstrak Judul, Topik, dan Deskripsi dari file PDF mentah.
3.  **Sentralisasi Data:** Menyajikan data dalam Web App yang *user-friendly*.

-----

## 🏗 Arsitektur Sistem

Sistem ini menggunakan pendekatan **Hybrid Environment**:

1.  **AI Processing Environment (Google Colab):** Digunakan untuk menjalankan model LLM yang berat (Qwen 1.5B) menggunakan GPU T4.
2.  **Production Environment (Localhost/XAMPP):** Digunakan untuk *User Interface* dan Database manajemen.

-----

## 🛠️ Tech Stack

| Komponen | Tools yang Digunakan | Fungsi Utama |
| :--- | :--- | :--- |
| **Data Ingestion** | Python, PyMuPDF (`fitz`), Google Drive | Membaca teks mentah dari file PDF. |
| **Preprocessing** | Python `re` (Regex), `json` | Membersihkan teks & parsing output AI. |
| **Model LLM** | **Qwen/Qwen2.5-1.5B-Instruct** | *In-Context Learning* untuk ekstraksi informasi. |
| **Optimization** | `bitsandbytes` (4-bit Quantization) | Optimasi memori agar berjalan di GPU gratisan. |
| **Framework** | Hugging Face `transformers`, PyTorch | Pipeline eksekusi model AI. |
| **Backend** | PHP Native (PDO) | Logika server dan API sederhana. |
| **Database** | MySQL | Menyimpan metadata terstruktur. |
| **Frontend** | HTML5, CSS3 (Modern UI), JS | Antarmuka pengguna (Dashboard/Search). |

-----

## 🚀 Implementasi MLOps

Bagian ini menjelaskan bagaimana project ini memenuhi komponen-komponen MLOps.

### Tahap 1: Build (Data & Modeling)

**1. Data Ingestion & Preparation**

  * **Code:** `mlops.ipynb`
  * **Proses:** Script secara otomatis memindai folder Google Drive (`FOLDER_PATH`). Menggunakan library `PyMuPDF` untuk mengekstrak teks hanya dari **2 halaman pertama** PDF (strategi efisiensi untuk mendapatkan konteks judul/topik).

**2. Data Preprocessing**

  * **Code:** `mlops.ipynb` (Fungsi `get_pdf_content` & `parse_json_response`)
  * **Proses:**
      * Membersihkan *whitespace* dan karakter non-standar menggunakan Regex.
      * *Truncating* teks maksimal 2500 karakter agar muat dalam *Context Window* LLM.

**3. Modeling (Generative AI Approach)**

  * **Code:** `mlops.ipynb`
  * **Model:** Menggunakan `Qwen2.5-1.5B-Instruct`.
  * **Teknik:**
      * **Quantization:** Menggunakan `load_in_4bit=True` untuk efisiensi komputasi.
      * **Prompt Engineering:** Menggunakan *System Prompt* ("Kamu adalah asisten akademik...") untuk memaksa model mengeluarkan output dalam format **JSON Valid**.
  * **Hyperparameter Tuning:**
      * `temperature = 0.1` (Sangat rendah agar output konsisten/deterministik).
      * `max_new_tokens = 450` (Agar deskripsi tidak terpotong).

**4. Packaging & Registering**

  * **Packaging:** Output prediksi dikemas dari JSON menjadi file CSV (`indexing_final_db.csv`).
  * **Registering:** File CSV diimpor ke Database MySQL (`import_data.php`) agar resmi terdaftar dalam sistem produksi.

-----

### Tahap 2: Deploy (Serving)

Sistem menggunakan strategi **Batch Inference** dengan **Web Serving**.

**1. Production Release**

  * Aplikasi web dideploy menggunakan server lokal (XAMPP/Apache).
  * File `index.php` bertindak sebagai *Serving Layer* dimana pengguna bisa mencari modul.

**2. Online Inference**

  * Meskipun model AI berjalan *offline* (batch), hasil inferensinya disajikan secara *online* melalui fitur pencarian (`script.js` & `api.php`).
  * Fitur **Upload Modul** (`upload.php`) memungkinkan penambahan data baru secara real-time ke dalam sistem.

-----

### Tahap 3: Monitor (Governance)

Menerapkan konsep **Human-in-the-Loop (HITL)** untuk mengatasi kelemahan AI (Halusinasi).

**1. Monitoring & Evaluation**

  * **Automated Evaluation:** Script Python memvalidasi format JSON sebelum disimpan. Jika format salah, dicatat sebagai `[Error AI Response]`.
  * **Human Evaluation:** Admin memantau hasil ekstraksi melalui **Admin Dashboard** (`admin.php`).

**2. Governance (Tata Kelola)**

  * Jika AI salah mendeteksi topik atau mata kuliah, Admin dapat melakukan **Intervensi Manual** melalui fitur `update.php`.
  * Sistem mencatat kapan data dibuat dan diperbarui (`created_at`, `updated_at`) untuk *audit trail*.

-----

## 💻 Instalasi & Penggunaan

### Prasyarat

1.  Akun Google (untuk Colab).
2.  XAMPP (PHP & MySQL) terinstall di komputer lokal.

### Langkah 1: Jalankan AI Pipeline (Cloud)

1.  Buka file `mlops.ipynb` di Google Colab.
2.  Upload file PDF modul praktikum ke folder Google Drive.
3.  Jalankan semua cell. Script akan menghasilkan file `indexing_final_db.csv`.
4.  Download file CSV tersebut.

### Langkah 2: Setup Web App (Local)

1.  Clone repository ini ke folder `htdocs` di XAMPP.
2.  Buat database MySQL baru bernama `project_mlops`.
3.  Import tabel user dan documents (bisa pakai query SQL standar).
4.  Jalankan `reset_admin.php` untuk membuat user admin default (User: `admin`, Pass: `123`).
5.  Letakkan file CSV hasil AI di folder `data/`.
6.  Buka browser: `http://localhost/folder_project/import_data.php` untuk memasukkan hasil AI ke Database.

### Langkah 3: Akses Aplikasi

  * **Dashboard Mahasiswa:** `http://localhost/folder_project/index.php`
  * **Login Admin:** `http://localhost/folder_project/login.php`

-----

## 📂 Struktur Project

```
project_mlops/
├── config/
│   └── config.php          # Koneksi Database
├── data/
│   └── indexing_final_db.csv # Output dari Model AI
├── uploads/                # Penyimpanan File PDF Fisik
├── js/
│   ├── script.js           # Logika Dashboard & Search
│   ├── script_upload.js    # Logika Upload (Drag & Drop)
│   └── script_update.js    # Logika Update Data
├── css/
│   ├── style.css           # UI Utama (Modern Gradient)
│   ├── style_admin.css     # UI Halaman Admin
│   └── ...
├── mlops.ipynb             # [CORE] Script Python AI/MLOps
├── index.php               # Halaman Utama (Serving Layer)
├── admin.php               # Dashboard Monitoring
├── import_data.php         # Script Registering Model Output
├── api.php                 # API Endpoint JSON
└── README.md               # Dokumentasi Project
```

-----

## 👥 Tim Pengembang

Project ini disusun untuk memenuhi Tugas Besar Mata Kuliah **Machine Learning Operations (MLOps)**, Program Studi Sains Data ITERA 2025.

  * **Platform:** GitHub
  * **Model ID:** `Qwen/Qwen2.5-1.5B-Instruct`

-----
