# Panduan Pengembangan Infrastructure Inventory & Documentation Management System

## 1. Tujuan Aplikasi

Bangun aplikasi internal untuk mengelola inventaris infrastruktur IT dan dokumentasinya menggunakan Laravel.

Aplikasi digunakan untuk mencatat dan mengelola:
- Server room
- Rack
- Server fisik
- Proxmox
- Virtual machine (VM)
- Panel/server management
- Aplikasi yang berjalan di infrastruktur
- Dokumen infrastruktur
- Aktivitas pemeliharaan dan audit
- Gallery dokumentasi visual
- Relasi dan topologi infrastruktur

Aplikasi bersifat internal. Prioritaskan konsistensi data, kemudahan pemeliharaan, dokumentasi yang terpusat, dan tampilan yang mudah dipahami.

## 2. Aturan Pengembangan

1. Periksa struktur project, migration, model, relasi, route, controller, dan komponen yang sudah ada sebelum melakukan perubahan.
2. Gunakan struktur database dan relasi yang sudah tersedia. Jangan membuat ulang migration atau tabel yang sudah ada tanpa alasan kuat.
3. Jangan mengasumsikan kolom tersedia. Periksa migration dan model sebelum menggunakan field.
4. Jangan membuat data relasi palsu hanya untuk melengkapi tampilan.
5. Gunakan Laravel sesuai versi project dan dokumentasi package yang terpasang.
6. Utamakan Laravel, Livewire, Tailwind CSS, dan Alpine.js jika sesuai dengan stack project.
7. Hindari penggunaan Filament kecuali diminta secara eksplisit.
8. Gunakan komponen UI yang konsisten dan dapat digunakan kembali.
9. Terapkan validasi, authorization, penanganan error, dan transaksi database jika diperlukan.
10. Setiap fitur harus berfungsi secara nyata, bukan sekadar tampilan statis atau mockup.
11. Jangan menghapus atau mengubah data yang sudah ada secara sembarangan.
12. Implementasikan secara bertahap dan uji setiap bagian sebelum melanjutkan.

## 3. Struktur Relasi Infrastruktur

Gunakan relasi yang sesuai dengan database yang sudah tersedia.

Struktur utama:

```text
Server Room
├── Rack
│   └── Server
│       └── Proxmox
│           └── Virtual Machine
│               └── Panel
│                   └── Application
└── Server (tanpa Rack)
```

Relasi tersebut merupakan gambaran umum, bukan struktur wajib untuk setiap aset.

### Relasi alternatif yang harus didukung

- Server dapat terhubung langsung ke Server Room tanpa Rack.
- Server dapat berdiri sendiri tanpa Proxmox, VM, Panel, atau Application.
- Server dapat memiliki aplikasi secara langsung.
- Server dapat memiliki Panel secara langsung tanpa melalui Proxmox atau VM.
- Server dapat memiliki Proxmox.
- Proxmox dapat memiliki beberapa VM.
- VM dapat memiliki Panel.
- Panel dapat memiliki beberapa Application.

Jangan mewajibkan semua server memiliki Proxmox, VM, Panel, atau Application.

Contoh server database mandiri harus tetap valid meskipun tidak memiliki relasi virtualisasi atau aplikasi.

Setiap relasi yang ditampilkan harus berasal dari data sebenarnya.

## 4. Struktur Menu Sidebar

Gunakan struktur menu berikut.

```text
Dashboard

Infrastruktur
├── Server Room
├── Rack
├── Server
├── Virtualisasi
├── Panel
├── Aplikasi
└── Topology Explorer

Dokumentasi

Aktivitas
├── Semua Aktivitas
└── Laporan Bulanan

Gallery

Pengaturan
├── Pengguna
├── Role & Permission
└── Referensi Data
```

Sesuaikan implementasi dengan fitur autentikasi dan struktur project yang tersedia.

## 5. Pola Halaman Utama

Gunakan pendekatan **Table + Detail Workspace + Topology Explorer**.

Setiap modul inventaris memiliki tiga bagian utama.

### A. Halaman daftar

Berisi:
- Judul dan deskripsi singkat.
- Tombol tambah data.
- Pencarian.
- Filter yang relevan.
- Tabel data.
- Pagination.
- Aksi detail, edit, dan hapus sesuai permission.

### B. Halaman tambah dan edit

- Form khusus untuk setiap jenis entitas.
- Field mengikuti struktur database yang sebenarnya.
- Validasi input dilakukan di backend.
- Field relasi menggunakan pilihan data yang valid.
- Relasi opsional tidak boleh dipaksakan menjadi wajib.
- Field yang tidak relevan tidak perlu ditampilkan.

### C. Halaman detail

Gunakan satu halaman detail dengan navigasi tab yang konsisten:

```text
Overview
Relasi / Topologi
Dokumentasi
Aktivitas
Gallery
```

Tab ditampilkan sesuai relevansi dan data yang tersedia.

Tab Dokumentasi, Aktivitas, dan Gallery merupakan tampilan data terkait entitas, bukan modul CRUD terpisah.

Hindari menduplikasi fungsi pengelolaan yang sudah tersedia di menu global.

Tombol tambah pada tab terkait boleh diarahkan ke modul global dengan entitas terkait sudah dipilih sebelumnya.

## 6. Modul Infrastruktur

### 6.1 Server Room

Tabel menampilkan:
- Nama Server Room.
- Kode jika tersedia.
- Lokasi.
- Jumlah Rack.
- Jumlah Server.

Jumlah Server harus mencakup server yang terhubung melalui Rack maupun server yang terhubung langsung ke Server Room.

Detail Server Room menampilkan:
- Informasi umum.
- Daftar Rack.
- Daftar Server yang terhubung langsung tanpa Rack.
- Ringkasan relasi infrastruktur.
- Dokumentasi, aktivitas, dan gallery terkait.

### 6.2 Rack

Tabel menampilkan:
- Nama atau kode Rack.
- Server Room.
- Lokasi atau posisi jika tersedia.
- Kapasitas U jika tersedia.
- Jumlah Server.

Detail Rack menampilkan informasi Rack dan daftar server yang ditempatkan di dalamnya.

Validasi relasi Server Room harus konsisten. Server yang berada dalam Rack tidak boleh secara tidak sengaja dihitung sebagai server langsung di Server Room sekaligus dihitung dua kali.

### 6.3 Server

Tabel menampilkan field yang tersedia dan relevan, misalnya:
- Hostname atau nama server.
- IP utama.
- Jenis atau fungsi server.
- Server Room.
- Rack jika ada.
- Relasi Proxmox.
- Relasi Panel atau Application langsung jika ada.
- Status inventaris jika tersedia.

Server Room wajib diisi apabila memang diwajibkan oleh aturan database. Rack bersifat opsional.

Detail Server menampilkan:
- Informasi server.
- Lokasi fisik.
- Relasi Proxmox, Panel, dan Application yang benar-benar terhubung.
- Dokumentasi.
- Aktivitas.
- Gallery.

Jangan menampilkan informasi status operasional secara real-time jika belum ada integrasi monitoring.

### 6.4 Virtualisasi

Buat satu halaman Virtualisasi dengan dua tab terpisah:

```text
Virtualisasi
├── Proxmox
└── Virtual Machine
```

Jangan mencampur Proxmox dan VM dalam satu tabel yang tidak jelas.

**Tab Proxmox**

Kolom yang dapat ditampilkan jika field tersedia:
- Nama.
- Server host.
- Endpoint atau alamat manajemen.
- Versi.
- Jumlah VM.
- Status inventaris atau koneksi jika benar-benar tersedia.

**Tab Virtual Machine**

Kolom yang dapat ditampilkan jika field tersedia:
- Nama VM.
- VM ID.
- Proxmox induk.
- IP.
- Sistem operasi.
- Panel terkait.
- Application terkait.
- Status.

Form Proxmox harus menggunakan Server sebagai host sesuai relasi database.

Form VM harus menggunakan Proxmox sebagai induk sesuai relasi database.

Jangan mengklaim integrasi langsung dengan API Proxmox jika integrasi tersebut belum diimplementasikan.

### 6.5 Panel

Tabel menampilkan:
- Nama Panel.
- Jenis atau produk.
- Host berupa Server atau VM sesuai relasi sebenarnya.
- URL manajemen.
- Versi jika tersedia.
- Jumlah Application.
- Status jika tersedia.

Panel dapat terhubung langsung ke Server atau melalui VM, mengikuti skema database.

Jangan mewajibkan satu Panel memiliki dua host sekaligus jika model relasinya hanya mendukung satu host.

### 6.6 Aplikasi

Tabel menampilkan:
- Nama Application.
- Domain atau URL.
- Environment jika tersedia.
- Lokasi deployment.
- Teknologi jika tersedia.
- Status jika tersedia.

Lokasi deployment dapat berupa:
- Server secara langsung.
- Panel.
- Infrastruktur VM melalui relasi Panel yang sesuai.

Form harus menyesuaikan relasi yang didukung database. Jangan memaksakan Application selalu memiliki Panel apabila aplikasi dapat terhubung langsung ke Server.

## 7. Topology Explorer

Buat halaman untuk menjelajahi relasi infrastruktur secara visual.

Fitur:
- Tampilan hierarki yang dapat dibuka dan ditutup.
- Pencarian aset berdasarkan nama atau informasi yang relevan.
- Filter jenis aset.
- Klik node untuk membuka halaman detail aset.
- Tampilkan relasi aktual berdasarkan database.
- Berikan indikator jenis aset yang jelas.
- Tangani aset yang tidak memiliki anak atau relasi opsional.

Contoh:

```text
Server Room A
├── Rack A1
│   ├── Server 01
│   │   └── Proxmox
│   │       ├── VM 101
│   │       └── VM 102
│   └── Server 02
└── Server 03
    └── Application langsung
```

Contoh tersebut hanya ilustrasi. Data yang tampil harus mengikuti relasi nyata.

Jangan membuat node Proxmox, VM, Panel, atau Application secara otomatis jika relasinya tidak tersedia.

Jika memungkinkan, sediakan tampilan hierarki dan tampilan diagram grafis. Pastikan keduanya tetap menggunakan sumber data yang sama.

## 8. Modul Dokumentasi

Menu Dokumentasi merupakan pusat pengelolaan seluruh dokumen infrastruktur.

Fitur:
- Daftar dokumen.
- Pencarian.
- Filter kategori dan tanggal.
- Upload dokumen.
- Pengelolaan metadata.
- Preview apabila format didukung.
- Download.
- Pengaitan dokumen dengan satu atau beberapa entitas sesuai skema database.
- Hapus dokumen dengan validasi dan konfirmasi.

Kolom tabel:
- Judul dokumen.
- Kategori.
- Tanggal.
- Entitas terkait.
- Jenis file.
- Aksi.

Contoh dokumen:
- Dokumen pengadaan.
- Desain infrastruktur.
- Dokumentasi instalasi.
- Dokumentasi deployment.
- Konfigurasi yang aman untuk dibagikan secara internal.
- Dokumen pemeliharaan.

Jangan menyimpan password, API key, token, atau secret di dokumen biasa tanpa mekanisme pengamanan yang sesuai.

Tab Dokumentasi pada halaman detail entitas hanya menampilkan dokumen yang terkait dengan entitas tersebut.

Gunakan satu sumber pengelolaan dokumen. Jangan membuat CRUD dokumen terpisah di setiap modul infrastruktur.

## 9. Modul Aktivitas

Modul Aktivitas menjadi pusat pencatatan dan penelusuran aktivitas.

Bagi aktivitas menjadi dua jenis konseptual:

### A. Audit Log

Mencatat aktivitas aplikasi, misalnya:
- Pembuatan data.
- Perubahan data.
- Penghapusan data.
- Perubahan relasi.

Audit log harus mencatat informasi pelaku, waktu, jenis aksi, dan entitas terkait jika tersedia.

Lindungi audit log dari perubahan atau penghapusan yang tidak berwenang.

### B. Work Log

Mencatat pekerjaan teknis, misalnya:
- Pemeliharaan server.
- Update sistem operasi.
- Perubahan konfigurasi.
- Deployment.
- Penanganan gangguan.
- Pemeriksaan infrastruktur.

Work log dapat memuat:
- Judul aktivitas.
- Deskripsi.
- Tanggal.
- Pelaksana.
- Kategori.
- Entitas terkait.
- Bukti atau dokumentasi.

Jangan menganggap audit log otomatis sudah mencatat seluruh pekerjaan teknis. Buat mekanisme work log yang sesuai kebutuhan dan struktur database.

### Halaman Semua Aktivitas

Sediakan:
- Pencarian.
- Filter periode.
- Filter pelaksana.
- Filter kategori atau jenis aktivitas.
- Filter entitas.
- Detail aktivitas.

Kolom:
- Waktu.
- Ringkasan.
- Entitas.
- Pelaksana.
- Jenis aktivitas.
- Bukti.
- Aksi detail.

### Laporan Bulanan

Sediakan halaman laporan dengan:
- Periode laporan.
- Filter pelaksana.
- Filter kategori.
- Filter entitas.
- Ringkasan aktivitas.
- Rincian aktivitas.
- Export DOCX, PDF, atau XLSX jika implementasi dan package mendukung.

Laporan harus mengambil data dari sumber aktivitas yang sebenarnya.

Hindari membuat sistem laporan yang berbeda-beda untuk setiap jenis aset. Gunakan satu modul laporan dengan filter berdasarkan entitas.

## 10. Modul Gallery

Gallery merupakan pusat pengelolaan foto dan bukti visual.

Fitur:
- Tampilan grid thumbnail.
- Preview gambar.
- Pencarian dan filter.
- Metadata gambar.
- Entitas terkait.
- Kategori atau jenis bukti.
- Tanggal.
- Download dan penghapusan dengan konfirmasi.

Contoh penggunaan:
- Foto Server Room.
- Foto Rack.
- Foto label server.
- Foto instalasi.
- Bukti pemeliharaan.
- Dokumentasi kondisi fisik infrastruktur.

Tab Gallery pada detail entitas hanya menampilkan gambar yang berkaitan dengan entitas tersebut.

Gunakan satu pusat CRUD Gallery. Hindari implementasi pengelolaan gambar yang berbeda di setiap halaman detail.

## 11. Dashboard

Dashboard berfungsi sebagai ringkasan kondisi inventaris dan pintasan navigasi.

Tampilkan informasi sesuai ketersediaan data, misalnya:
- Total Server Room.
- Total Rack.
- Total Server.
- Total Proxmox.
- Total VM.
- Total Panel.
- Total Application.
- Aktivitas terbaru.
- Dokumen terbaru.
- Pintasan menuju modul utama.

Setiap kartu statistik sebaiknya dapat diklik menuju halaman daftar dengan filter yang sesuai.

Jangan membuat dashboard menjadi halaman CRUD kedua.

Jangan menampilkan status online, CPU, RAM, uptime, atau kesehatan server sebagai data real-time jika belum ada integrasi monitoring.

## 12. Pengaturan

Sediakan pengaturan yang relevan:
- Pengguna.
- Role dan permission.
- Referensi kategori dokumen.
- Referensi kategori aktivitas.
- Referensi jenis server atau status inventaris jika memang dikelola melalui database.

Jangan memindahkan CRUD inventaris ke menu Pengaturan.

Gunakan mekanisme authorization yang sesuai dengan package dan struktur project yang sudah tersedia.

## 13. Standar UI/UX

Gunakan antarmuka yang konsisten, rapi, dan mudah dipahami untuk aplikasi administrasi internal.

Ketentuan:
- Sidebar dengan hierarki menu yang jelas.
- Breadcrumb pada halaman dalam.
- Judul dan deskripsi singkat setiap halaman.
- Tabel dengan pencarian, filter, dan pagination.
- Form dengan label yang jelas.
- Validasi error di dekat field terkait.
- Loading state untuk proses asynchronous.
- Empty state saat data belum tersedia.
- Notifikasi sukses dan gagal.
- Dialog konfirmasi sebelum menghapus.
- Desain responsif.
- Navigasi detail yang konsisten.
- Komponen yang dapat digunakan kembali.

Hindari:
- Terlalu banyak kartu statistik.
- Informasi yang sama ditampilkan berulang tanpa tujuan.
- Tombol yang tidak berfungsi.
- Data dummy pada halaman produksi.
- Form yang menampilkan field tidak relevan.
- Duplikasi CRUD antarhalaman.

## 14. Konsistensi dan Keamanan Data

Pastikan:
- Validasi diterapkan pada backend.
- Authorization diperiksa pada setiap operasi yang dilindungi.
- Relasi foreign key dan aturan penghapusan dipatuhi.
- Penghapusan aset yang masih memiliki relasi ditangani dengan aman.
- Upload file memiliki validasi tipe, ukuran, dan penyimpanan.
- File privat tidak dapat diakses tanpa otorisasi yang sesuai.
- Operasi multi-tabel menggunakan transaksi apabila diperlukan.
- Data tidak dihitung ganda pada statistik dan laporan.
- Audit log tidak mudah dimanipulasi oleh pengguna biasa.
- Status inventaris dibedakan dari status operasional real-time.

Jangan hanya menyembunyikan tombol berdasarkan role. Semua otorisasi harus tetap ditegakkan di backend.

## 15. Strategi Implementasi

Kerjakan secara bertahap.

### Tahap 1 — Pemeriksaan Project dan Relasi

- Periksa migration, model, relasi, route, dan struktur UI.
- Identifikasi field yang benar-benar tersedia.
- Verifikasi relasi opsional dan relasi alternatif.
- Identifikasi fitur yang sudah ada.
- Hindari membuat ulang fitur yang sudah berfungsi.

### Tahap 2 — Modul Infrastruktur

Implementasikan:
- Server Room.
- Rack.
- Server.
- Virtualisasi.
- Panel.
- Application.
- Halaman detail setiap entitas.

Pastikan relasi dan validasi benar sebelum lanjut.

### Tahap 3 — Dokumentasi dan Gallery

Implementasikan pengelolaan terpusat, upload, metadata, relasi entitas, preview, dan akses file.

### Tahap 4 — Aktivitas dan Audit Log

Implementasikan pencatatan otomatis untuk operasi penting serta work log untuk aktivitas teknis.

### Tahap 5 — Laporan Bulanan

Implementasikan filter, rekap, dan export berdasarkan data aktivitas yang tersedia.

### Tahap 6 — Topology Explorer

Implementasikan visualisasi relasi infrastruktur yang dinamis berdasarkan data aktual.

### Tahap 7 — Dashboard dan Pengaturan

Implementasikan ringkasan data, navigasi, pengguna, permission, dan referensi data.

### Tahap 8 — Pengujian dan Penyempurnaan

Periksa:
- CRUD.
- Validasi.
- Relasi opsional.
- Hak akses.
- Penghapusan dan dependensi.
- Upload dan download dokumen.
- Pencatatan aktivitas.
- Perhitungan statistik.
- Laporan dan export.
- Tampilan responsif.
- Error log dan regresi fitur.

## 16. Kriteria Penerimaan

Implementasi dianggap sesuai jika:

1. Semua modul utama dapat digunakan dan terhubung ke database.
2. Setiap entitas memiliki halaman daftar, form, dan detail yang konsisten.
3. Relasi alternatif dan opsional berjalan dengan benar.
4. Server tanpa Rack atau tanpa virtualisasi tetap dapat dikelola.
5. Dokumentasi dan Gallery dikelola melalui modul global.
6. Aktivitas dapat ditelusuri dan difilter.
7. Laporan bulanan berasal dari data aktual.
8. Topology Explorer mencerminkan relasi database yang sebenarnya.
9. Tidak ada duplikasi fungsi CRUD yang tidak diperlukan.
10. Semua operasi penting memiliki validasi dan authorization backend.
11. Tidak ada tombol utama yang hanya berupa tampilan tanpa fungsi.
12. Fitur yang belum memiliki integrasi real-time tidak ditampilkan seolah-olah real-time.

## 17. Instruksi untuk AI Coding Agent

Sebelum mulai menulis kode:

1. Analisis project yang ada.
2. Buat daftar fitur yang sudah tersedia dan yang belum tersedia.
3. Cocokkan kebutuhan di dokumen ini dengan skema database.
4. Identifikasi potensi konflik relasi dan perubahan yang berisiko.
5. Usulkan implementasi bertahap yang paling sesuai dengan kondisi project.

Setelah analisis, implementasikan satu tahap pada satu waktu.

Jangan mengubah seluruh project sekaligus. Jangan mengarang struktur database, relasi, package, atau API. Jika terdapat ketidaksesuaian antara panduan dan implementasi aktual, jelaskan ketidaksesuaian tersebut dan gunakan solusi yang paling konsisten dengan project yang ada.

Setelah setiap tahap selesai, lakukan pengujian yang relevan dan laporkan:
- Fitur yang selesai.
- File utama yang diubah.
- Pengujian yang dilakukan.
- Hasil pengujian.
- Masalah atau pekerjaan yang masih tersisa.
