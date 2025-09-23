# Panduan Admin ViViaShop — Versi Lengkap (Bahasa Indonesia, mudah dimengerti)

Versi: 4.0
Tanggal: 23 September 2025

Ringkasan singkat:

-   Dokumen ini menjelaskan langkah demi langkah tugas-tugas yang biasa dilakukan admin toko ViViaShop. Ditulis dengan bahasa sederhana dan disusun per bagian agar operator/pemilik toko dapat mengikuti SOP harian, memproses order, mengelola produk, menangani print-service, dan menyelesaikan masalah teknis dasar.

Catatan penting sebelum mulai:

-   Contoh URL menggunakan host pengembangan: http://127.0.0.1:8000. Ganti dengan domain produksi (mis. https://toko-anda.com) jika Anda menggunakan server live.
-   Jika Anda ragu melakukan tindakan yang mengubah stok, pembayaran, atau menghapus file pelanggan, konfirmasi dulu dengan pemilik toko atau tim teknis.

Daftar isi singkat (gunakan pencarian di file ini untuk cepat lompat):

-   Bagian A — Masuk (Login) dan keamanan dasar
-   Bagian B — Dashboard & tugas harian singkat
-   Bagian C — Mengelola Orders (pesanan)
-   Bagian D — Pengiriman (Shipment) dan resi
-   Bagian E — Pembayaran (Midtrans & manual)
-   Bagian F — Produk, Variant, dan Barcode
-   Bagian G — Print Service (upload, proses, hapus file)
-   Bagian H — Manajemen Stok & Penyesuaian
-   Bagian I — Laporan, Export, dan PDF
-   Bagian J — Troubleshooting umum dan log
-   Lampiran: daftar URL penting dan catatan cepat untuk developer

---

## Bagian A — Masuk (Login) dan keamanan dasar

1. Akses halaman admin

-   Buka browser dan masuk ke: /admin (mis. https://toko-anda.com/admin)
-   Jika belum punya akun admin, minta pemilik untuk membuatkan akun atau minta teknisi membuat user dengan flag is_admin.

2. Tips keamanan singkat

-   Gunakan password yang unik dan kuat.
-   Jika memungkinkan aktifkan HTTPS dan jangan gunakan akun admin di wifi publik.
-   Batasi jumlah orang yang punya akses admin. Hanya beri akses kepada yang bertanggung jawab.
-   Backup data secara rutin (database dan folder storage).

3. Jika lupa password

-   Gunakan fitur reset password yang tersedia pada halaman login. Jika tidak tersedia, hubungi developer / sysadmin.

---

## Bagian B — Dashboard & SOP harian singkat (pekerjaan shift)

Halaman Dashboard: /admin

Apa yang dicek setiap shift (5 menit pemeriksaan):

-   Lihat jumlah pesanan baru (baru dibuat) dan pesanan yang menunggu pembayaran.
-   Lihat antrian print (jika toko menyediakan layanan cetak).
-   Periksa notifikasi atau error kecil di dashboard.
-   Cek daftar produk yang stoknya rendah.

Langkah SOP pagi (contoh):

1. Login → buka Dashboard.
2. Buka Orders → atur urutan terbaru (created_at desc) → proses 5 pesanan pertama.
3. Buka Print Service → buka Queue → serahkan pekerjaan ke operator print.
4. Jika ada stok kritis → catat untuk pembelian ulang.

Catatan administrasi:

-   Setiap pembatalan atau perubahan jumlah barang harus diberi catatan (notes) pada detail order, siapa yang mengubah, dan alasan.

---

## Bagian C — Mengelola Orders (pesanan)

Halaman utama: /admin/orders

Ringkasan alur pesanan (lifecycle) mudah dimengerti:

-   created: pelanggan membuat pesanan.
-   unpaid / waiting: pesanan ada tapi belum dibayar (bisa menunggu notifikasi dari gateway atau bukti transfer manual).
-   paid: pembayaran tercatat.
-   confirmed: pesanan siap diproses (biasanya setelah pembayaran terverifikasi).
-   shipped: barang dikirim.
-   delivered / completed: pesanan selesai.
-   cancelled: pesanan dibatalkan.

C.1 Melihat detail order

-   Klik pada order di daftar → buka detail.
-   Periksa: daftar produk, variasi (variant), alamat pengiriman, metode pembayaran, bukti transfer (jika manual), dan catatan customer.

C.2 Verifikasi pembayaran (pembayaran manual)
Langkah:

1. Buka detail order → lihat bukti transfer (gambar atau file).
2. Cocokkan nominal & rekening tujuan.
3. Jika cocok, klik tombol "Confirm Payment".
4. Sistem akan mengubah status pembayaran menjadi "paid" dan order siap diproses.

Catatan: jika Anda tidak yakin, hubungi customer via telepon/email untuk konfirmasi sebelum menandai sebagai paid.

C.3 Generate invoice (unduh PDF)

-   Di halaman detail order ada tombol "Download Invoice".
-   Gunakan ini untuk lampiran manual atau bukti jika pelanggan meminta invoice fisik.

C.4 Membatalkan order
Langkah:

1. Buka detail order → pilih action "Cancel".
2. Isi alasan pembatalan di kolom notes.
3. Sistem akan mengembalikan stok (jika konfigurasi mengizinkan rollback stok).

Catatan penting:

-   Jangan menggabungkan dua order pelanggan menjadi satu tanpa persetujuan pelanggan.
-   Jika menemukan order duplikat (created_at hampir sama dan isian sama), batalkan salah satu dan tulis catatan kenapa.

C.5 Resume checkout (kasus pelanggan menutup tab sebelum bayar)
Penjelasan sederhana:

-   Jika pelanggan menutup tab saat masih di halaman pembayaran, sistem dapat menyimpan order sebagai "waiting" atau "unpaid".
-   Sebagai admin, jangan otomatis menggabungkan atau memodifikasi order tersebut kecuali pelanggan meminta atau mengonfirmasi.
-   Untuk membantu pelanggan: Anda bisa mengirim ulang link pembayaran dengan fitur "Generate Payment Token" pada order (lihat Bagian E).

---

## Bagian D — Pengiriman (Shipment) dan resi

D.1 Menambahkan nomor resi
Langkah:

1. Buka detail order.
2. Pilih menu "Ship / Kirim" atau field untuk mengisi kurir & nomor resi.
3. Isi nama kurir dan nomor resi yang diberikan oleh jasa pengiriman.
4. Simpan. Status order akan berubah menjadi "shipped" dan biasanya pelanggan mendapat email notifikasi (jika fitur notifikasi aktif).

D.2 Mengubah status pengiriman

-   Beberapa toko menandai status: shipped -> in_transit -> delivered.
-   Update status sesuai dengan informasi dari kurir jika ada.

D.3 Jika perlu koreksi ongkos kirim

-   Jika kurir menginformasikan biaya berbeda, gunakan fitur adjust shipping pada order untuk memperbarui biaya pengiriman.

D.4 Mengecek resi secara manual

-   Salin nomor resi dan cek di website kurir terkait jika ingin memastikan posisi paket.

---

## Bagian E — Pembayaran (Midtrans & manual) — operasi admin sederhana

E.1 Overview singkat

-   ViViaShop menggunakan Midtrans sebagai salah satu pilihan gateway.
-   Sistem juga mendukung pembayaran manual (transfer bank) yang perlu diverifikasi oleh admin.

E.2 Memeriksa notifikasi pembayaran

-   Midtrans akan mengirim notifikasi (webhook) ke server. Jika ada masalah dengan notifikasi, periksa log aplikasi (storage/logs/laravel.log) dan dashboard Midtrans.

E.3 Mengirim ulang link pembayaran ke pelanggan
Langkah cepat:

1. Buka detail order.
2. Klik tombol "Generate Payment Token" atau sejenisnya (biasanya akan membuat link pembayaran baru atau token untuk midtrans).
3. Salin link/token dan kirim ke pelanggan via email/WA.

E.4 Menandai order sebagai sudah dibayar (manual)
Langkah:

1. Periksa bukti transfer pelanggan.
2. Jika benar, klik "Confirm Payment" di halaman order.
3. Sistem akan menandai payment_status = paid.

E.5 Troubleshooting pembayaran

-   Jika token tidak dapat dibuat, periksa pengaturan Midtrans (serverKey, clientKey) di file konfigurasi atau .env.
-   Jika notifikasi tidak diterima, periksa endpoint webhook di Midtrans Dashboard dan cek log retry di Midtrans.

---

## Bagian F — Produk, Variant, dan Barcode

Halaman utama produk admin: /admin/products

F.1 Menambah produk baru (ringkas)

1. Klik "Add Product" atau "Tambah Produk".
2. Isi Nama, SKU (unik), Harga, Harga Beli (opsional), Berat, Dimensi, Kategori, Deskripsi.
3. Upload gambar produk (thumbnail utama & galeri).
4. Centang "Is Print Service" jika produk adalah layanan cetak.
5. Simpan.

F.2 Mengelola variant (produk konfigurabel)

-   Jika produk memiliki pilihan (mis. ukuran, warna), buat atribut yang sesuai lalu tambahkan variant.
-   Variant disimpan sebagai entitas terpisah yang terkait ke produk induk.

F.3 Smart-Print auto-variant (khusus layanan cetak)

-   Jika centang "is_print_service" dan "is_smart_print_enabled":
    -   Sistem akan otomatis membuat dua variant default: BW (hitam putih) dan Color (berwarna).
    -   Nama SKU otomatis memakai akhiran -BW dan -CLR (contoh: SKU123-BW).

F.4 Barcode (mencetak/unduh)

-   Untuk membuat barcode PDF atau preview:
    -   Generate semua barcode: ada tombol atau route di admin.
    -   Generate single barcode dan download PDF juga tersedia.

F.5 Catatan stok pada produk

-   Periksa stok di halaman produk atau di Stock Card.
-   Saat order selesai (completed), stok akan berkurang setelah proses yang terkait (tergantung konfigurasi). Jika terjadi mismatch stok, laporkan ke tim gudang.

---

## Bagian G — Print Service (panduan langkah demi langkah untuk operator)

Print Service adalah fitur di mana pelanggan mengunggah file untuk dicetak. Bagian ini ditujukan untuk operator yang akan memproses file cetak.

G.1 Entitas sederhana yang perlu dipahami

-   Print Session: sesi unggah file pelanggan (ada token/ID session).
-   Print File: file yang diunggah pelanggan dalam satu session.
-   Print Order: hasil checkout dari sesi print (berisi informasi variant, jumlah halaman, estimasi harga).

G.2 Alur singkat dari sisi operator

1. Pelanggan mengunggah file dari frontend (mendapat token session).
2. Setelah checkout, PrintOrder dibuat dan masuk ke antrian (queue).
3. Buka /admin/print-service/queue untuk melihat order yang masuk.
4. Operator memeriksa file: klik View/Preview.
5. Jika file berada di folder public (public/storage) controller biasanya akan menyalin file ke storage internal sehingga operator mendapat path stabil.
6. Lakukan print test jika perlu, lalu print sesuai instruksi (BW/Color, paper type, jumlah halaman).
7. Setelah selesai cetak, klik "Complete Order" — sistem akan menghapus file dari storage untuk menjaga privasi pelanggan.

G.3 Konfirmasi pembayaran untuk print order manual

-   Jika pelanggan memilih pembayaran manual, admin/operator harus melakukan konfirmasi manual:
    1. Buka detail PrintOrder.
    2. Periksa bukti transfer.
    3. Klik "Confirm Payment". Sistem akan menandai order paid dan melakukan pengurangan stok (jika berlaku).

G.4 Memastikan file bisa dibuka/di-print

-   Jika file tidak dapat ditemukan: periksa di kedua lokasi:
    -   storage/app/print-service/...
    -   public/storage/print-service/...
-   Jika file ada di public/storage, controller biasanya akan menyalin ke storage internal. Pastikan storage:link sudah dibuat di server produksi (`php artisan storage:link`).

G.5 Setelah selesai cetak — penghapusan file

-   Untuk menjaga privasi, setelah tanda selesai (complete), sistem akan menghapus file upload pelanggan baik di folder storage maupun public.
-   Pastikan operator tidak sengaja menghapus file jika ingin menyimpan contoh cetak—gunakan fitur download sebelum menekan Complete.

---

## Bagian H — Manajemen Stok & Penyesuaian

H.1 Melihat kartu stok (Stock Card)

-   Di menu Stock Card, Anda dapat melihat riwayat masuk/keluar untuk tiap variant.

H.2 Menyesuaikan stok secara manual
Langkah:

1. Buka Stock Card atau halaman variant produk.
2. Pilih "Adjust Stock" atau sejenisnya.
3. Isi jumlah penyesuaian dan alasan (mis. koreksi, retur, rusak).
4. Simpan — sistem akan mencatat movement (in/out) di Stock Movement.

H.3 Laporan stok

-   Gunakan fitur report untuk melihat total in, total out, sales, print_orders per rentang tanggal.

---

## Bagian I — Laporan, Export, dan PDF

I.1 Laporan pendapatan & ekspor

-   Admin dapat mengekspor laporan revenue, produk, dan inventori ke XLSX/PDF.
-   Pada halaman laporan, pilih rentang tanggal (beberapa laporan dibatasi maksimal 31 hari untuk menghindari query besar).

I.2 Mengunduh invoice & PDF

-   Invoice pesanan dapat diunduh dari halaman detail order (PDF yang di-generate oleh library DomPDF).

---

## Bagian J — Troubleshooting umum dan log

J.1 Error pembayaran/midtrans

-   Jika midtrans tidak mengirim notifikasi: cek storage/logs/laravel.log.
-   Periksa pengaturan serverKey dan clientKey.

J.2 File print tidak ditemukan

-   Periksa kedua lokasi storage dan public/storage.
-   Pastikan folder storage dapat diakses dan storage:link aktif.

J.3 Stok tidak cocok/selisih

-   Periksa Stock Movement untuk melihat kapan stok masuk/keluar.
-   Lakukan stock adjustment jika perlu dan catat alasan.

J.4 Pesanan terjebak pada status waiting/unpaid

-   Jika pelanggan mengeluh tidak dapat menyelesaikan pembayaran, kirim ulang link pembayaran (Generate Payment Token) atau bantu langsung verifikasi manual jika pelanggan sudah transfer.

---

## Lampiran — Daftar URL dan tindakan singkat (quick cheatsheet)

Catatan: ganti domain sesuai server Anda.

-   Dashboard: /admin
-   Orders list: /admin/orders
-   Order detail: /admin/orders/{id}
-   Generate payment token: POST /admin/orders/{id}/generate-payment-token
-   Print Service index: /admin/print-service
-   Print Queue: /admin/print-service/queue
-   Print sessions: /admin/print-service/sessions
-   Print orders: /admin/print-service/orders
-   Products: /admin/products
-   Stock card: /admin/stock-cards (atau menu Stock Card di admin)

---

Terakhir: jika Anda memerlukan versi yang lebih teknis (termasuk peta controller, nama method, dan catatan developer), beri tahu saya dan saya akan menambahkan Lampiran teknis yang memetakan setiap URL ke controller dan method di kode.

Dokumen ini telah disusun untuk memudahkan operator dan admin toko. Jika Anda mau, saya bisa:

-   Menambahkan contoh screenshot dari halaman admin.
-   Menambahkan template pesan WA/email untuk menghubungi pelanggan terkait pembayaran atau pengiriman.
-   Menambahkan SOP lebih detail per shift untuk toko dengan layanan cetak.

Fitur employee performance melacak pegawai yang menangani order (opsional).

9.1 Halaman & actions

-   `/admin/employee-performance` untuk overview
-   `/admin/employee-performance/bonus` untuk pemberian bonus

    9.2 Data yang direkam

-   employee_name, order_id, transaction_value, completed_at, created_at

    9.3 SOP singkat

-   Pastikan order sudah `completed` sebelum merekam performance
-   Berikan bonus melalui fitur bonus dan catat bukti dokumentasi

---

## Bagian 10 — Stock management & adjustments

10.1 Pages & controllers

-   Print-service stock management: `/admin/print-service/stock`
-   Produk stock: `/admin/products` -> inventory tab

    10.2 Adjust stock

-   Admin dapat adjust stock via form (new_stock + reason). Selalu isi reason dan note.

    10.3 Audit trail

-   Semua stock adjustments sebaiknya direkam dalam movement log (cek StockService/StockManagementService)

---

## Bagian 11 — Import/Export, PDF invoice & barcode generation

11.1 Import Produk

-   Gunakan `Products -> Import` dengan template sample. Periksa preview sebelum commit.

    11.2 Export template

-   `admin/products/exportTemplate` untuk download template excel

    11.3 Invoice PDF

-   Tombol download invoice di order detail. PDF di-generate menggunakan `barryvdh/laravel-dompdf`.
-   Jika rendering gagal: cek view `admin.orders.invoice` atau `frontend.orders.invoice` untuk error

    11.4 Barcode

-   GenerateAll & GenerateSingle ada di ProductController
-   Download single barcode memakai PDF generator dengan ukuran kertas sesuai kebutuhan

---

## Bagian 12 — Troubleshooting & logs (operasional cepat)

12.1 Payment webhook not received
Steps:

1. Cek `storage/logs/laravel.log` untuk error exception
2. Cek Midtrans dashboard (sandbox/production) -> lihat webhook delivery history
3. Pastikan route `/payments/notification` ada dan accessible dari internet (production)

    12.2 Duplicate orders created (customer double-click)
    Steps:

4. Bandingkan `created_at` timestamp, cek cart & order items
5. Cancel duplicate order, beri note, hubungi customer jika perlu refund

    12.3 Print files missing
    Steps:

6. Periksa `storage/app` dan `public/storage` paths
7. Cek permission dan ownership folder
8. Jika files ada di public but not in storage: controller mencoba menyalin; cek logs untuk error salin

    12.4 Barcode/Print PDF rendering error

9. Pastikan `barryvdh/laravel-dompdf` terpasang dan environment PHP memiliki extension yang diperlukan (mbstring, gd, openssl)
10. Cek view blade yang digunakan untuk generation

    12.5 Common artisan commands untuk debugging

-   php artisan view:clear
-   php artisan cache:clear
-   php artisan route:list
-   tail -f storage/logs/laravel.log (atau buka file terakhir)

---

## Bagian 13 — SOP pra-go-live (checklist lengkap)

-   [ ] Setting Midtrans (serverKey + clientKey) diuji di sandbox
-   [ ] RajaOngkir key dikonfigurasi (jika dipakai)
-   [ ] Email konfigurasi (SMTP) teruji (order confirmation email)
-   [ ] Cek storage symlink: `php artisan storage:link` di production
-   [ ] Cron jobs / scheduler terpasang (jika ada recurring tasks)
-   [ ] Test end-to-end: add to cart -> checkout -> pay (sandbox) -> webhook -> status change -> invoice download -> shipment -> delivered

---

## Lampiran A — Route → controller quick map (teknis)

Catatan: ini ringkasan rute yang paling relevan untuk admin/operator. Jika butuh peta lengkap, jalankan `php artisan route:list`.

-   Admin Dashboard: GET `/admin` -> `App\Http\Controllers\Admin\DashboardController@index`
-   Admin Orders: GET `/admin/orders` -> `App\Http\Controllers\Admin\OrderController@index`
-   Admin Order Detail: GET `/admin/orders/{id}` -> OrderController@show
-   Admin Shipments: `/admin/shipments` -> ShipmentController
-   Admin Products: `/admin/products` -> `App\Http\Controllers\Admin\ProductController` (index/create/store/edit/update)
-   Product barcode generate single: `/products/generateSingleBarcode/{id}` -> ProductController@generateBarcodeSingle
-   Product generate all barcodes: `/products/generateAllBarcodes` -> ProductController@generateBarcodeAll
-   Barcode preview/print: `/barcode/preview*` & `/barcode/print/*` -> ProductController preview/print methods
-   Payments notification (webhook): POST `/payments/notification` -> `App\Http\Controllers\Frontend\OrderController::notificationHandler` (no CSRF)
-   Payments client key: GET `/payments/client-key` -> Frontend\OrderController@getMidtransClientKey
-   Print-service admin index: GET `/admin/print-service` -> `Admin\PrintServiceController@index`
-   Print-service queue/sessions/orders: `/admin/print-service/queue`, `/admin/print-service/sessions`, `/admin/print-service/orders`
-   Print-service confirm payment: POST `/admin/print-service/orders/{id}/confirm-payment` -> PrintServiceController@confirmPayment
-   Print-service print files: POST `/admin/print-service/orders/{id}/print-files` -> PrintServiceController@printFiles
-   Print-service complete: POST `/admin/print-service/orders/{id}/complete` -> PrintServiceController@completeOrder

---

## Lampiran B — Quick commands & files to check

-   Laravel logs: `storage/logs/laravel.log`
-   Clear view cache: `php artisan view:clear`
-   Route list: `php artisan route:list`
-   Env / midtrans config: `.env` keys `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION`

---

## Lampiran C — Template pesan untuk developer / ops

Subject: [Ops] Issue: <singkat masalah> - <env>

Body:

-   Environment: staging/production
-   URL: <link reproduksi>
-   Steps to reproduce:
    1.  ...
    2.  ...
-   Log excerpt: (paste dari `storage/logs/laravel.log`)
-   Screenshots/attachments: ...

