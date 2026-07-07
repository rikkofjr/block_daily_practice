# Panduan Instalasi & Konfigurasi Daily Practice Panel


## Proses Instalasi & Sinkronisasi Database

1. Download plugin berikut dalam bentuk file zip
2. Lakukan upload plugin di moodle
3. Instalasi sesuai dengan instruksi moodle

---

##  Konfigurasi Global Sistem (Settings)

Setelah menekan tombol *Continue*, Anda akan langsung diarahkan ke halaman pengaturan global. Jika ingin mengaksesnya kembali di kemudian hari, pergi ke:
**Site administration** ➡️ **Plugins** ➡️ **Blocks** ➡️ **Daily Practice Panel**.

1. **Aktifkan Pop-up Pengingat:** Centang opsi ini agar kuis yang belum dikerjakan otomatis muncul sebagai jendela *Popup/Modal* di tengah layar Dashboard karyawan.
2. **Shortname Custom Profile Field Jabatan:** Masukkan nama pendek (*shortname*) dari kolom profil Moodle yang menampung data jabatan hasil sinkronisasi HRIS Anda (Contoh bawaan: `jabatan`).
3. Klik **Save Changes**.

---

##  Pemetaan Jabatan pada Panel Operasional Manager

Akses halaman panel manajemen operasional melalui tautan yang tersedia di halaman pengaturan global di atas, atau akses langsung via URL browser:
`https://moodle-perusahaan.com/blocks/daily_practice/admin_manage.php`

1. **Daftarkan Jabatan dari HRIS:** Pada kotak teks "Daftarkan Jabatan Baru dari HRIS", ketik nama posisi karyawan **persis** seperti teks yang dikirimkan oleh sistem HRIS ke profil Moodle (Contoh: `STAFF`, `MANAGER`). Klik **Tambah Jabatan**.
2. **Petakan ke Course ID:** Ketik angka **Course ID** tempat kuis latihan harian untuk jabatan tersebut berada pada tabel yang tersedia.
3. Klik **💾 Simpan Perubahan Mapping**.
4. *Catatan:* Jabatan yang tidak memerlukan kuis harian (seperti Direksi/VP) **tidak perlu didaftarkan** agar Dashboard mereka tetap bersih.

---

## Memasang Blok di Dashboard Karyawan

Langkah terakhir adalah memunculkan blok ini di halaman utama agar sistem *popup* dan pelacakan kuis aktif berjalan bagi karyawan:

1. Buka halaman **Dashboard** atau halaman utama Moodle Anda.
2. Aktifkan **Edit mode** di pojok kanan atas layar.
3. Klik tombol **Add a block**.
4. Cari dan pilih **Daily Practice Popup**.
6. Pastikan block **Daily Practice Popup** sudah muncul.
7. Jika anda adalah seorang admin, anda bisa setting ini menjadi block default di setiap user. 