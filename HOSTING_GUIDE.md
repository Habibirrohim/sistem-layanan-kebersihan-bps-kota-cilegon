# PANDUAN DEPLOY KE FREE HOSTING

## 📋 CHECKLIST PERSIAPAN

### 1. ✅ File yang Sudah Siap

- [x] config.php (perlu edit database credentials)
- [x] database.sql (script instalasi database)
- [x] Semua file PHP sistem
- [x] Assets (CSS, JS, images)
- [x] Upload folder structure

### 2. 🔧 Yang Perlu Diedit untuk Hosting

#### A. config.php

```php
// Ganti database credentials sesuai hosting
$db_host = 'localhost'; // atau sesuai hosting
$db_name = 'nama_database_dari_hosting';
$db_user = 'username_dari_hosting';
$db_pass = 'password_dari_hosting';
```

#### B. Upload Directory

- Pastikan folder 'uploads/checklist_photos/' writable (777)
- Beberapa hosting mungkin perlu setting permission manual

### 3. 📁 Struktur Upload

```
public_html/
├── config.php
├── database.sql
├── login.php
├── dashboard.php
├── jadwal.php
├── form_checklist.php
├── laporan.php
├── detail_laporan.php
├── verifikasi.php
├── rekap_jadwal.php
├── rekap_laporan.php
├── export_jadwal.php
├── export_jadwal_pdf.php
├── export_laporan_excel.php
├── export_laporan_pdf.php
├── get_report_details.php
├── logout.php
├── includes/
│   ├── header.php
│   ├── sidebar.php
│   └── footer.php
├── assets/
│   ├── css/styles.css
│   ├── js/main.js
│   └── images/logo.jpg
└── uploads/
    └── checklist_photos/
```

## 🚀 LANGKAH DEPLOY - 000WEBHOST (RECOMMENDED)

### 🎯 **Cara Mudah Upload ke 000webhost:**

#### Step 1: Daftar 000webhost

1. **Buka https://www.000webhost.com/**
2. **Klik "Start Now" atau "Get Started"**
3. **Isi data pendaftaran:**
   - Email aktif
   - Password
   - Website name: `monitoringkebersihan` (contoh)
4. **Verifikasi email**
5. **Login ke dashboard**

#### Step 2: Setup Website

1. **Klik "Build Website"**
2. **Pilih "Upload Files"** (bukan website builder)
3. **Akan mendapat subdomain:** `monitoringkebersihan.000webhostapp.com`

#### Step 3: Upload Files (MUDAH!)

1. **Klik "File Manager"** di dashboard
2. **Masuk ke folder "public_html"**
3. **Klik tombol "Upload Files"**
4. **Drag & drop semua file sistem Anda**
   - Bisa upload multiple files sekaligus
   - Atau upload ZIP dan extract
5. **Upload selesai!**

#### Step 4: Setup Database

1. **Klik "Database" di dashboard**
2. **Klik "Create Database"**
3. **Catat credentials:**
   - Database name
   - Username
   - Password
4. **Import database.sql via phpMyAdmin**

### 🔄 **ALTERNATIF JIKA 000WEBHOST JUGA BERMASALAH:**

#### 🌟 **Option A: XAMPP + Ngrok (Instant Online)**

1. **Keep sistem di XAMPP localhost**
2. **Download Ngrok** (gratis) - https://ngrok.com/
3. **Jalankan command:**
   ```bash
   ngrok http 80
   ```
4. **Dapat URL online instant:** `https://abc123.ngrok.io`
5. **Share URL ini untuk akses dari mana saja**

#### 🌟 **Option B: GitHub Pages + JSON Database**

**📋 PANDUAN LENGKAP UPLOAD KE GITHUB:**

#### Step 1: Persiapan GitHub

1. **Buat akun GitHub** (jika belum punya):

   - Buka https://github.com/
   - Klik "Sign up" dan isi data

2. **Buat Repository Baru:**
   - Login ke GitHub
   - Klik tombol "+" → "New repository"
   - Nama repository: `monitoring-kebersihan-kantor`
   - Centang "Add a README file"
   - Klik "Create repository"

#### Step 2: Upload Files ke GitHub

**Metode 1: Via Web Browser (Mudah untuk Pemula)**

1. **Masuk ke repository yang baru dibuat**
2. **Klik "uploading an existing file"**
3. **Drag & drop semua file sistem Anda:**
   - Pilih semua file dari folder `c:\xampp\htdocs\Monitoring Kebersihan Kantor\`
   - Drag ke area upload GitHub
4. **Tulis commit message:** "Upload sistem monitoring kebersihan"
5. **Klik "Commit changes"**

**Metode 2: Via Git Command (Advanced)**

1. **Install Git** (jika belum ada):

   - Download dari https://git-scm.com/
   - Install dengan setting default

2. **Clone repository:**

   ```bash
   git clone https://github.com/username/monitoring-kebersihan-kantor.git
   cd monitoring-kebersihan-kantor
   ```

3. **Copy semua file sistem ke folder repository**

4. **Upload ke GitHub:**
   ```bash
   git add .
   git commit -m "Upload sistem monitoring kebersihan"
   git push origin main
   ```

#### Step 3: Enable GitHub Pages

1. **Di repository, klik tab "Settings"**
2. **Scroll ke bawah, cari "Pages"**
3. **Di Source, pilih "Deploy from a branch"**
4. **Pilih branch "main" dan folder "/ (root)"**
5. **Klik "Save"**
6. **GitHub akan generate URL:** `https://username.github.io/monitoring-kebersihan-kantor/`

#### Step 4: Akses Website

1. **Tunggu 5-10 menit untuk deployment**
2. **Akses URL:** `https://username.github.io/monitoring-kebersihan-kantor/login.php`
3. **Website Anda sudah online!**

**✅ KEUNTUNGAN GITHUB PAGES:**

- ✅ **100% Gratis** selamanya
- ✅ **SSL gratis** (HTTPS)
- ✅ **Unlimited bandwidth**
- ✅ **Version control** (backup otomatis)
- ✅ **Custom domain** support
- ✅ **Deploy otomatis** saat update code

**⚠️ CATATAN PENTING:**

- ❌ **Tidak support PHP/MySQL** (static hosting only)
- ❌ **Perlu konversi** ke HTML/JavaScript
- ✅ **Bisa pakai GitHub API** untuk database
- ✅ **Atau gunakan localStorage** untuk menyimpan data

**🔧 JIKA INGIN TETAP PAKAI PHP:**

Gunakan GitHub hanya untuk **backup code**, lalu deploy ke:

- 000webhost (support PHP)
- Heroku (support PHP)
- Railway (support PHP)

**🚀 WORKFLOW RECOMMENDED:**

1. **Upload code ke GitHub** (untuk backup & version control)
2. **Deploy dari GitHub ke hosting** yang support PHP
3. **Update code di GitHub** → Auto deploy ke hosting

#### Step 5: Deploy dari GitHub ke Hosting

**A. Deploy ke Heroku:**

1. **Connect GitHub repo ke Heroku**
2. **Enable auto-deploy**
3. **Free tier available**

**B. Deploy ke Railway:**

1. **Import GitHub repo ke Railway**
2. **Auto-deploy on git push**
3. **Support PHP & MySQL**

**C. Deploy ke Vercel:**

1. **Import GitHub repo**
2. **Support serverless PHP**
3. **Auto SSL & CDN**

#### 🌟 **Option C: Netlify (Drag & Drop) - PANDUAN LENGKAP**

**⚠️ CATATAN PENTING:** Netlify adalah static hosting, jadi perlu modifikasi sistem PHP ke HTML/JavaScript.

**📋 LANGKAH PERSIAPAN:**

1. **Konversi Database MySQL ke JSON:**

   - Export data dari database.sql ke format JSON
   - Simpan sebagai `data.json` di folder `assets/`

2. **Modifikasi File PHP:**
   - Ganti koneksi database dengan fetch ke JSON
   - Ubah server-side PHP menjadi client-side JavaScript

**🚀 CARA DEPLOY KE NETLIFY:**

#### Step 1: Persiapan File

1. **Buat folder baru:** `netlify-version`
2. **Copy semua file ke folder tersebut**
3. **Rename file PHP menjadi HTML:**
   ```
   login.php → login.html
   dashboard.php → dashboard.html
   jadwal.php → jadwal.html
   laporan.php → laporan.html
   ```

#### Step 2: Konversi Database

1. **Buat file `assets/data/users.json`:**

   ```json
   [
     {
       "id": 1,
       "username": "admin@pa.co.id",
       "password": "indsm01_",
       "role": "admin",
       "nama": "Administrator"
     },
     {
       "id": 2,
       "username": "petugas@pa.co.id",
       "password": "indsm02_",
       "role": "petugas",
       "nama": "Petugas Kebersihan"
     }
   ]
   ```

2. **Buat file `assets/data/jadwal.json`:**
   ```json
   [
     {
       "id": 1,
       "lantai": "Lantai 1",
       "ruangan": "Ruang Tunggu",
       "petugas": "Petugas A",
       "jam": "08:00",
       "status": "pending"
     }
   ]
   ```

#### Step 3: Modifikasi JavaScript

1. **Update `assets/js/main.js` dengan:**

   ```javascript
   // Function untuk load data JSON
   async function loadData(file) {
     try {
       const response = await fetch(`/assets/data/${file}.json`);
       return await response.json();
     } catch (error) {
       console.error("Error loading data:", error);
       return [];
     }
   }

   // Function untuk save data (localStorage)
   function saveData(key, data) {
     localStorage.setItem(key, JSON.stringify(data));
   }

   // Function untuk get data
   function getData(key) {
     const data = localStorage.getItem(key);
     return data ? JSON.parse(data) : [];
   }
   ```

#### Step 4: Upload ke Netlify

1. **Buka https://netlify.com/**
2. **Klik "Deploy to Netlify"**
3. **Drag & drop folder `netlify-version`** ke area drop
4. **Tunggu proses deploy selesai**
5. **Dapat domain gratis:** `random-name.netlify.app`

#### Step 5: Custom Domain (Optional)

1. **Di dashboard Netlify klik "Domain settings"**
2. **Klik "Add custom domain"**
3. **Masukkan domain:** `monitoring-kebersihan.netlify.app`
4. **Domain aktif dengan SSL otomatis**

**✅ KEUNTUNGAN NETLIFY:**

- ✅ Upload super mudah (drag & drop)
- ✅ Deploy otomatis
- ✅ SSL gratis
- ✅ CDN global (loading cepat)
- ✅ Custom domain gratis
- ✅ Git integration

**⚠️ KEKURANGAN:**

- ❌ Tidak support PHP/MySQL
- ❌ Perlu konversi ke static files
- ❌ Data disimpan di localStorage (tidak persistent)

**🎯 ALTERNATIF MUDAH - NETLIFY DENGAN GITHUB:**

#### Option 1: Upload via GitHub (Recommended)

1. **Buat repository GitHub baru**
2. **Upload semua file sistem ke GitHub**
3. **Di Netlify klik "New site from Git"**
4. **Connect dengan GitHub repository**
5. **Deploy otomatis setiap kali ada update**

#### Option 2: Netlify Functions (Advanced)

1. **Gunakan Netlify Functions untuk backend**
2. **Support serverless functions**
3. **Bisa handle form submissions**
4. **Database menggunakan FaunaDB (gratis)**

#### 🌟 **Option D: Vercel (Modern)**

1. **Upload ke https://vercel.com/**
2. **Connect GitHub repository**
3. **Deploy otomatis dengan SSL**
4. **Domain gratis + fast loading**

**🌐 Domain Extensions yang Tersedia:**

**InfinityFree (Recommended):**

- `yoursite.epizy.com` (paling populer)
- `yoursite.42web.io` (modern)
- `yoursite.rf.gd` (singkat)

**000webhost:**

- `yoursite.000webhostapp.com`

**Freehostia:**

- `yoursite.freevar.com`

**📝 Contoh Subdomain Lengkap:**

- `monitoringkebersihan.epizy.com`
- `kebersihan-kantor.42web.io`
- `sistemkebersihan.rf.gd`
- `cleaningmonitor.epizy.com`

**📝 Aturan Subdomain:**

- Hanya boleh menggunakan huruf (a-z), angka (0-9), dan tanda hubung (-)
- Tidak boleh dimulai atau diakhiri dengan tanda hubung
- Panjang maksimal biasanya 63 karakter
- Contoh BENAR: `monitoringkebersihan`, `kebersihan-kantor`, `sistem123`
- Contoh SALAH: `-monitoring`, `kebersihan-`, `monitoring..kebersihan`

### Step 2: Upload Files

#### Metode 1: File Manager (Recommended untuk Pemula)

1. **Login ke cPanel hosting**

   - Masuk ke dashboard hosting Anda
   - Cari dan klik "File Manager"

2. **Navigasi ke public_html**

   - Klik folder `public_html/`
   - Ini adalah folder root website Anda

3. **Upload dengan ZIP (Recommended)**

   - Compress seluruh folder sistem ke ZIP
   - Klik tombol "Upload" di File Manager
   - Pilih file ZIP sistem Anda
   - Tunggu upload selesai
   - Klik kanan file ZIP → Extract
   - Hapus file ZIP setelah extract berhasil

4. **Upload Manual (File per File)**
   - Klik tombol "Upload"
   - Pilih semua file PHP (login.php, dashboard.php, dll)
   - Upload folder assets/, includes/, uploads/ satu per satu

#### Metode 2: FTP Client (untuk yang Berpengalaman)

1. **Download FTP Client**

   - FileZilla (gratis)
   - WinSCP (Windows)
   - Cyberduck (Mac)

2. **Setting Koneksi FTP**

   ```
   Host: ftp.yourdomain.com atau IP hosting
   Username: username FTP dari hosting
   Password: password FTP dari hosting
   Port: 21 (biasanya)
   ```

3. **Upload Folder**
   - Drag & drop folder sistem ke public_html/
   - Tunggu transfer selesai
   - Pastikan struktur folder tetap sama

### Step 3: Setup Database

1. Buka cPanel → MySQL Databases
2. Buat database baru
3. Buat user database
4. Import file database.sql via phpMyAdmin

## 🔧 TROUBLESHOOTING UPLOAD

### ❌ Masalah Umum dan Solusi

1. **Upload Gagal/Terputus**

   - Cek koneksi internet stabil
   - Coba upload file ZIP lebih kecil (<10MB untuk free hosting)
   - Upload di jam sepi (malam hari)

2. **Error: "FTP quota exceeded or blocked by security"**

   - Cek quota storage hosting (biasanya 1-5GB limit)
   - Upload file satu per satu (jangan ZIP)
   - Rename .zip menjadi .txt, upload, lalu rename kembali
   - **SOLUSI TERBAIK: Ganti ke hosting alternatif (000webhost)**

3. **Upload Tetap Gagal - Solusi Alternatif:**

   **A. Gunakan FTP Client (Recommended):**

   - Download FileZilla (gratis)
   - Gunakan kredensial FTP dari hosting
   - Upload via FTP lebih stabil daripada web upload

   **B. Upload ke GitHub Pages:**

   - Upload sistem ke GitHub repository
   - Enable GitHub Pages (gratis)
   - Akses via username.github.io/repository

   **C. Gunakan Vercel (Modern):**

   - Upload ke https://vercel.com/
   - Deploy langsung dari GitHub
   - Domain gratis dengan SSL

   **D. Gunakan Netlify:**

   - Drag & drop folder sistem ke https://netlify.com/
   - Deploy otomatis dengan domain gratis

4. **File Permission Error**

   - Set permission folder uploads/ ke 755 atau 777
   - Via File Manager: klik kanan → Change Permissions

5. **Struktur Folder Salah**

   - Pastikan semua file PHP di root public_html/
   - Folder assets/, includes/, uploads/ harus dalam public_html/

6. **File Tidak Muncul**
   - Refresh File Manager
   - Cek apakah extract ZIP berhasil
   - Pastikan tidak ada subfolder tambahan

### 💡 TIPS UPLOAD

1. **Sebelum Upload**

   - Test sistem di localhost dulu
   - Backup semua file
   - Compress ke ZIP untuk upload cepat

2. **Saat Upload**

   - Upload di jam sepi (bandwidth lebih stabil)
   - Jangan tutup browser saat upload
   - Monitor progress upload

3. **Setelah Upload**
   - Cek semua file terupload dengan benar
   - Test akses website: `yourdomain.com/login.php`
   - Cek permission folder uploads/

### Step 4: Edit Konfigurasi

1. Edit config.php dengan credentials database baru
2. Test koneksi database

### Step 5: Set Permissions

1. Set folder uploads/ permission ke 755 atau 777
2. Set checklist_photos/ permission ke 755 atau 777

## 🔒 KEAMANAN

- Ganti password default di database
- Gunakan HTTPS jika tersedia
- Backup database secara berkala

## 📞 SUPPORT

- InfinityFree: Forum community support
- Documentation lengkap tersedia
- Response time biasanya 24-48 jam

## ⚠️ LIMITASI FREE HOSTING

- Storage terbatas (1-5GB)
- Bandwidth terbatas
- Mungkin ada downtime sesekali
- Support terbatas
- Tidak ada backup otomatis

## 💡 TIPS OPTIMASI

- Compress images sebelum upload
- Gunakan CDN untuk assets static
- Optimize database queries
- Enable caching jika memungkinkan
